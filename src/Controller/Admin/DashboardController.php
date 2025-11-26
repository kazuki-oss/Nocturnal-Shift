<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\TenantResolver;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private \App\Repository\UserRepository $userRepository,
        private \App\Repository\ShiftRepository $shiftRepository,
        private \App\Repository\ShiftRequestRepository $shiftRequestRepository,
        private \App\Repository\AttendanceRepository $attendanceRepository
    ) {}

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        // Get real stats
        $employeeCount = $this->userRepository->count(['tenant' => $tenant]);
        $shiftsToday = $this->shiftRepository->countByDate($tenant, new \DateTime());
        $pendingRequests = $this->shiftRequestRepository->countPendingByTenant($tenant);

        // Get recent activity
        $recentRequests = $this->shiftRequestRepository->findRecentByTenant($tenant, 5);
        $recentAttendance = $this->attendanceRepository->findRecentByTenant($tenant, 5);

        $activities = [];

        foreach ($recentRequests as $request) {
            $activities[] = [
                'type' => 'shift_request',
                'user' => $request->getUser(),
                'time' => $request->getSubmittedAt(),
                'message' => $request->getUser()->getName() . ' がシフト希望を提出しました',
                'initials' => $this->getInitials($request->getUser()->getName())
            ];
        }

        foreach ($recentAttendance as $attendance) {
            $activities[] = [
                'type' => 'attendance',
                'user' => $attendance->getUser(),
                'time' => $attendance->getClockInAt(),
                'message' => $attendance->getUser()->getName() . ' が出勤しました',
                'initials' => $this->getInitials($attendance->getUser()->getName())
            ];
        }

        // Sort by time DESC
        usort($activities, function ($a, $b) {
            return $b['time'] <=> $a['time'];
        });

        // Limit to 10 items
        $activities = array_slice($activities, 0, 10);

        return $this->render('admin/dashboard.html.twig', [
            'tenant' => $tenant,
            'stats' => [
                'employees' => $employeeCount,
                'shifts_today' => $shiftsToday,
                'pending_requests' => $pendingRequests
            ],
            'activities' => $activities
        ]);
    }

    private function getInitials(string $name): string
    {
        $parts = explode(' ', $name);
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(mb_substr($name, 0, 2));
    }
}
