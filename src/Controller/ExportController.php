<?php

namespace App\Controller;

use App\Entity\Attendance;
use App\Repository\AttendanceRepository;
use App\Repository\ShiftRepository;
use App\Repository\TenantRepository;
use App\Service\TenantResolver;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportController extends AbstractController
{
    private TenantResolver $tenantResolver;
    private AttendanceRepository $attendanceRepository;
    private ShiftRepository $shiftRepository;

    public function __construct(TenantResolver $tenantResolver, AttendanceRepository $attendanceRepository, ShiftRepository $shiftRepository)
    {
        $this->tenantResolver = $tenantResolver;
        $this->attendanceRepository = $attendanceRepository;
        $this->shiftRepository = $shiftRepository;
    }

    /**
     * Export attendance data as CSV.
     */
    #[Route('/admin/export/attendance/csv', name: 'admin_export_attendance_csv', methods: ['GET'])]
    public function exportAttendanceCsv(Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $from = $request->query->get('from');
        $to = $request->query->get('to');
        $fromDate = $from ? new \DateTime($from) : new \DateTime('first day of this month');
        $toDate = $to ? new \DateTime($to) : new \DateTime('last day of this month');

        $records = $this->attendanceRepository->findByPeriod($tenant, $fromDate, $toDate);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Employee');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'Clock In');
        $sheet->setCellValue('D1', 'Clock Out');
        $row = 2;
        foreach ($records as $attendance) {
            $sheet->setCellValue('A' . $row, $attendance->getUser()->getName());
            $sheet->setCellValue('B' . $row, $attendance->getDate()->format('Y-m-d'));
            $sheet->setCellValue('C' . $row, $attendance->getClockIn()?->format('H:i') ?? '');
            $sheet->setCellValue('D' . $row, $attendance->getClockOut()?->format('H:i') ?? '');
            $row++;
        }

        $writer = new Csv($spreadsheet);
        $filename = 'attendance_' . $fromDate->format('Ymd') . '_' . $toDate->format('Ymd') . '.csv';
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    /**
     * Export attendance data as PDF.
     */
    #[Route('/admin/export/attendance/pdf', name: 'admin_export_attendance_pdf', methods: ['GET'])]
    public function exportAttendancePdf(Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $from = $request->query->get('from');
        $to = $request->query->get('to');
        $fromDate = $from ? new \DateTime($from) : new \DateTime('first day of this month');
        $toDate = $to ? new \DateTime($to) : new \DateTime('last day of this month');

        $records = $this->attendanceRepository->findByPeriod($tenant, $fromDate, $toDate);

        $html = $this->renderView('admin/export/attendance_pdf.html.twig', [
            'tenant' => $tenant,
            'records' => $records,
            'from' => $fromDate,
            'to' => $toDate,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();
        
        $filename = 'attendance_' . $fromDate->format('Ymd') . '_' . $toDate->format('Ymd') . '.pdf';
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    /**
     * Export shift schedule as PDF.
     */
    #[Route('/admin/export/shift/pdf', name: 'admin_export_shift_pdf', methods: ['GET'])]
    public function exportShiftPdf(Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $month = $request->query->get('month');
        $monthDate = $month ? new \DateTime($month . '-01') : new \DateTime('first day of this month');

        $shifts = $this->shiftRepository->findByMonth($tenant, $monthDate);

        $html = $this->renderView('admin/export/shift_pdf.html.twig', [
            'tenant' => $tenant,
            'shifts' => $shifts,
            'month' => $monthDate,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $pdfContent = $dompdf->output();
        
        $filename = 'shift_' . $monthDate->format('Ym') . '.pdf';
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    /**
     * Export payroll data as CSV.
     */
    #[Route('/admin/export/payroll/csv', name: 'admin_export_payroll_csv', methods: ['GET'])]
    public function exportPayrollCsv(Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $month = $request->query->get('month');
        $monthDate = $month ? new \DateTime($month . '-01') : new \DateTime('first day of this month');
        $fromDate = (clone $monthDate)->modify('first day of this month');
        $toDate = (clone $monthDate)->modify('last day of this month');

        $records = $this->attendanceRepository->findByPeriod($tenant, $fromDate, $toDate);

        // Group by employee
        $employeeData = [];
        foreach ($records as $attendance) {
            $employeeName = $attendance->getUser()->getName();
            if (!isset($employeeData[$employeeName])) {
                $employeeData[$employeeName] = [
                    'name' => $employeeName,
                    'days' => 0,
                    'hours' => 0,
                    'hourlyRate' => $attendance->getUser()->getEmployeeProfile()?->getHourlyRate() ?? 0,
                ];
            }
            if ($attendance->getClockIn() && $attendance->getClockOut()) {
                $employeeData[$employeeName]['days']++;
                $diff = $attendance->getClockOut()->diff($attendance->getClockIn());
                $hours = $diff->h + ($diff->i / 60);
                $employeeData[$employeeName]['hours'] += $hours;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '従業員名');
        $sheet->setCellValue('B1', '勤務日数');
        $sheet->setCellValue('C1', '勤務時間');
        $sheet->setCellValue('D1', '時給');
        $sheet->setCellValue('E1', '給与');
        $row = 2;
        foreach ($employeeData as $data) {
            $sheet->setCellValue('A' . $row, $data['name']);
            $sheet->setCellValue('B' . $row, $data['days']);
            $sheet->setCellValue('C' . $row, number_format($data['hours'], 2));
            $sheet->setCellValue('D' . $row, $data['hourlyRate']);
            $sheet->setCellValue('E' . $row, number_format($data['hours'] * $data['hourlyRate'], 0));
            $row++;
        }

        $writer = new Csv($spreadsheet);
        $filename = 'payroll_' . $monthDate->format('Ym') . '.csv';
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }
}
?>
