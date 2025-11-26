<?php

namespace App\Controller\Admin;

use App\Entity\Attendance;
use App\Form\AttendanceType;
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
        
        // フィルター
        $date = $request->query->get('date');
        $userId = $request->query->get('user');
        $status = $request->query->get('status');
        
        // デフォルトは当日
        if (!$date) {
            $date = (new \DateTime())->format('Y-m-d');
        }
        
        $dateObj = new \DateTime($date);
        
        // クエリビルダー
        $qb = $repository->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('a.attendanceDate = :date')
            ->setParameter('tenant', $tenant)
            ->setParameter('date', $dateObj)
            ->orderBy('u.name', 'ASC');
        
        // ユーザーフィルター
        if ($userId) {
            $qb->andWhere('u.id = :userId')
               ->setParameter('userId', $userId);
        }
        
        // ステータスフィルター
        if ($status === 'incomplete') {
            $qb->andWhere('a.clockOutAt IS NULL');
        } elseif ($status === 'complete') {
            $qb->andWhere('a.clockOutAt IS NOT NULL');
        }
        
        $attendances = $qb->getQuery()->getResult();
        
        // 全従業員リスト
        $users = $userRepository->findBy(['tenant' => $tenant], ['name' => 'ASC']);

        return $this->render('admin/attendance/index.html.twig', [
            'tenant' => $tenant,
            'attendances' => $attendances,
            'users' => $users,
            'selectedDate' => $date,
            'selectedUser' => $userId,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_attendance_edit', methods: ['GET', 'POST'])]
    public function editAttendance(Request $request, Attendance $attendance): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        if ($attendance->getUser()->getTenant() !== $tenant) {
            throw $this->createNotFoundException('勤怠データが見つかりません。');
        }

        $form = $this->createForm(AttendanceType::class, $attendance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 修正履歴を記録
            $attendance->setLastModifiedAt(new \DateTimeImmutable());
            $attendance->setLastModifiedBy($this->getUser());
            
            $this->entityManager->flush();

            $this->addFlash('success', '勤怠データを修正しました。');
            return $this->redirectToRoute('admin_attendance_index', ['date' => $attendance->getAttendanceDate()->format('Y-m-d')]);
        }

        return $this->render('admin/attendance/edit.html.twig', [
            'tenant' => $tenant,
            'attendance' => $attendance,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/monthly-report', name: 'admin_attendance_monthly_report', methods: ['GET'])]
    public function monthlyReport(Request $request, AttendanceRepository $repository): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('m'));
        
        $stats = $repository->getMonthlyStats($tenant, $year, $month);
        $monthDate = new \DateTime("$year-$month-01");

        return $this->render('admin/attendance/monthly_report.html.twig', [
            'tenant' => $tenant,
            'stats' => $stats,
            'year' => $year,
            'month' => $month,
            'monthDate' => $monthDate,
        ]);
    }
}
