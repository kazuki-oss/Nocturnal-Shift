<?php

namespace App\Controller\Admin;

use App\Repository\AttendanceRepository;
use App\Repository\UserRepository;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/attendance')]
class AttendanceManagementController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_attendance_index', methods: ['GET'])]
    public function index(Request $request, AttendanceRepository $repository, UserRepository $userRepository): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $date = $request->query->get('date', date('Y-m-d'));
        $targetDate = new \DateTimeImmutable($date);

        // 全従業員を取得
        $employees = $userRepository->createQueryBuilder('u')
            ->where('u.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();

        // 各従業員の勤怠データを取得
        $attendanceData = [];
        foreach ($employees as $employee) {
            $attendance = $repository->findOneBy([
                'user' => $employee,
                'attendanceDate' => $targetDate
            ]);

            $workingMinutes = 0;
            $status = 'absent';

            if ($attendance) {
                if ($attendance->getClockInAt() && $attendance->getClockOutAt()) {
                    $diff = $attendance->getClockOutAt()->getTimestamp() - $attendance->getClockInAt()->getTimestamp();
                    $workingMinutes = floor($diff / 60) - ($attendance->getBreakMinutes() ?? 0);
                    $status = 'finished';
                } elseif ($attendance->getClockInAt()) {
                    $status = 'working';
                }
            }

            $attendanceData[] = [
                'employee' => $employee,
                'attendance' => $attendance,
                'workingMinutes' => $workingMinutes,
                'status' => $status
            ];
        }

        return $this->render('admin/attendance/index.html.twig', [
            'tenant' => $tenant,
            'date' => $targetDate,
            'attendanceData' => $attendanceData
        ]);
    }

    #[Route('/employee/{id}', name: 'admin_attendance_employee', methods: ['GET'])]
    public function employeeHistory(int $id, AttendanceRepository $repository, UserRepository $userRepository): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $employee = $userRepository->find($id);

        if (!$employee || $employee->getTenant() !== $tenant) {
            throw $this->createNotFoundException('従業員が見つかりません。');
        }

        $attendances = $repository->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $employee)
            ->orderBy('a.attendanceDate', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();

        // 集計データ
        $totalWorkingMinutes = 0;
        $totalDays = 0;
        foreach ($attendances as $attendance) {
            if ($attendance->getClockInAt() && $attendance->getClockOutAt()) {
                $diff = $attendance->getClockOutAt()->getTimestamp() - $attendance->getClockInAt()->getTimestamp();
                $workingMinutes = floor($diff / 60) - ($attendance->getBreakMinutes() ?? 0);
                $totalWorkingMinutes += $workingMinutes;
                $totalDays++;
            }
        }

        return $this->render('admin/attendance/employee.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'employee' => $employee,
            'attendances' => $attendances,
            'totalWorkingHours' => floor($totalWorkingMinutes / 60),
            'totalDays' => $totalDays,
            'avgWorkingHours' => $totalDays > 0 ? floor($totalWorkingMinutes / $totalDays / 60) : 0
        ]);
    }
}
