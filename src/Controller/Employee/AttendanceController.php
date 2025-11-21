<?php

namespace App\Controller\Employee;

use App\Entity\Attendance;
use App\Repository\AttendanceRepository;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employee/attendance')]
class AttendanceController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'employee_attendance_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('employee/attendance/index.html.twig', [
            'tenant' => $this->tenantResolver->resolve()
        ]);
    }

    #[Route('/today', name: 'employee_attendance_today', methods: ['GET'])]
    public function today(AttendanceRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        $attendance = $repository->findOneBy([
            'user' => $user,
            'attendanceDate' => $today
        ]);

        if (!$attendance) {
            return new JsonResponse([
                'status' => 'not_started',
                'clockIn' => null,
                'clockOut' => null,
                'breakMinutes' => 0,
                'workingMinutes' => 0
            ]);
        }

        $workingMinutes = 0;
        if ($attendance->getClockInAt() && $attendance->getClockOutAt()) {
            $diff = $attendance->getClockOutAt()->getTimestamp() - $attendance->getClockInAt()->getTimestamp();
            $workingMinutes = floor($diff / 60) - ($attendance->getBreakMinutes() ?? 0);
        } elseif ($attendance->getClockInAt()) {
            $diff = time() - $attendance->getClockInAt()->getTimestamp();
            $workingMinutes = floor($diff / 60) - ($attendance->getBreakMinutes() ?? 0);
        }

        return new JsonResponse([
            'status' => $this->determineStatus($attendance),
            'clockIn' => $attendance->getClockInAt()?->format('H:i'),
            'clockOut' => $attendance->getClockOutAt()?->format('H:i'),
            'breakMinutes' => $attendance->getBreakMinutes() ?? 0,
            'workingMinutes' => max(0, $workingMinutes)
        ]);
    }

    #[Route('/clock-in', name: 'employee_attendance_clock_in', methods: ['POST'])]
    public function clockIn(Request $request, AttendanceRepository $repository): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        // 既に出勤済みかチェック
        $existing = $repository->findOneBy([
            'user' => $user,
            'attendanceDate' => $today
        ]);

        if ($existing && $existing->getClockInAt()) {
            $this->addFlash('error', '既に出勤打刻済みです。');
            return $this->redirectToRoute('employee_attendance_index');
        }

        if (!$existing) {
            $attendance = new Attendance();
            $attendance->setUser($user);
            $attendance->setAttendanceDate($today);
        } else {
            $attendance = $existing;
        }

        $attendance->setClockInAt(new \DateTimeImmutable());
        
        // TODO: GPS位置情報の記録
        // $locationData = [
        //     'clock_in' => [
        //         'latitude' => $request->request->get('latitude'),
        //         'longitude' => $request->request->get('longitude'),
        //         'accuracy' => $request->request->get('accuracy')
        //     ]
        // ];
        // $attendance->setLocationData($locationData);

        $this->entityManager->persist($attendance);
        $this->entityManager->flush();

        $this->addFlash('success', '出勤打刻しました。');
        return $this->redirectToRoute('employee_attendance_index');
    }

    #[Route('/clock-out', name: 'employee_attendance_clock_out', methods: ['POST'])]
    public function clockOut(Request $request, AttendanceRepository $repository): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        $attendance = $repository->findOneBy([
            'user' => $user,
            'attendanceDate' => $today
        ]);

        if (!$attendance || !$attendance->getClockInAt()) {
            $this->addFlash('error', '出勤打刻が見つかりません。');
            return $this->redirectToRoute('employee_attendance_index');
        }

        if ($attendance->getClockOutAt()) {
            $this->addFlash('error', '既に退勤打刻済みです。');
            return $this->redirectToRoute('employee_attendance_index');
        }

        $attendance->setClockOutAt(new \DateTimeImmutable());
        
        // TODO: GPS位置情報の記録
        // $locationData = $attendance->getLocationData() ?? [];
        // $locationData['clock_out'] = [
        //     'latitude' => $request->request->get('latitude'),
        //     'longitude' => $request->request->get('longitude'),
        //     'accuracy' => $request->request->get('accuracy')
        // ];
        // $attendance->setLocationData($locationData);

        $this->entityManager->flush();

        $this->addFlash('success', '退勤打刻しました。');
        return $this->redirectToRoute('employee_attendance_index');
    }

    #[Route('/break-start', name: 'employee_attendance_break_start', methods: ['POST'])]
    public function breakStart(AttendanceRepository $repository): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        $attendance = $repository->findOneBy([
            'user' => $user,
            'attendanceDate' => $today
        ]);

        if (!$attendance || !$attendance->getClockInAt()) {
            $this->addFlash('error', '出勤打刻が見つかりません。');
            return $this->redirectToRoute('employee_attendance_index');
        }

        // セッションに休憩開始時刻を記録
        $this->get('session')->set('break_start_time', time());

        $this->addFlash('success', '休憩を開始しました。');
        return $this->redirectToRoute('employee_attendance_index');
    }

    #[Route('/break-end', name: 'employee_attendance_break_end', methods: ['POST'])]
    public function breakEnd(AttendanceRepository $repository): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        $attendance = $repository->findOneBy([
            'user' => $user,
            'attendanceDate' => $today
        ]);

        if (!$attendance) {
            $this->addFlash('error', '勤怠データが見つかりません。');
            return $this->redirectToRoute('employee_attendance_index');
        }

        $breakStartTime = $this->get('session')->get('break_start_time');
        if (!$breakStartTime) {
            $this->addFlash('error', '休憩開始時刻が記録されていません。');
            return $this->redirectToRoute('employee_attendance_index');
        }

        $breakMinutes = floor((time() - $breakStartTime) / 60);
        $currentBreak = $attendance->getBreakMinutes() ?? 0;
        $attendance->setBreakMinutes($currentBreak + $breakMinutes);

        $this->get('session')->remove('break_start_time');
        $this->entityManager->flush();

        $this->addFlash('success', '休憩を終了しました。（' . $breakMinutes . '分）');
        return $this->redirectToRoute('employee_attendance_index');
    }

    #[Route('/history', name: 'employee_attendance_history', methods: ['GET'])]
    public function history(AttendanceRepository $repository): Response
    {
        $user = $this->getUser();
        
        $attendances = $repository->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.attendanceDate', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();

        return $this->render('employee/attendance/history.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'attendances' => $attendances
        ]);
    }

    private function determineStatus(Attendance $attendance): string
    {
        if ($attendance->getClockOutAt()) {
            return 'finished';
        }

        // 休憩中かチェック
        if ($this->get('session')->get('break_start_time')) {
            return 'on_break';
        }

        if ($attendance->getClockInAt()) {
            return 'working';
        }

        return 'not_started';
    }
}
