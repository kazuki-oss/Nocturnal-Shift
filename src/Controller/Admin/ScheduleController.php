<?php

namespace App\Controller\Admin;

use App\Entity\Shift;
use App\Entity\ShiftRequest;
use App\Repository\ShiftRepository;
use App\Repository\ShiftRequestRepository;
use App\Repository\UserRepository;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/schedule')]
class ScheduleController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_schedule_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/schedule/index.html.twig', [
            'tenant' => $this->tenantResolver->resolve()
        ]);
    }

    #[Route('/events', name: 'admin_schedule_events', methods: ['GET'])]
    public function events(
        Request $request,
        ShiftRepository $shiftRepository,
        ShiftRequestRepository $shiftRequestRepository
    ): JsonResponse {
        $tenant = $this->tenantResolver->resolve();
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        $events = [];

        // 確定シフトを取得
        $shifts = $shiftRepository->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('s.shiftDate >= :start')
            ->andWhere('s.shiftDate <= :end')
            ->setParameter('tenant', $tenant)
            ->setParameter('start', new \DateTime($start))
            ->setParameter('end', new \DateTime($end))
            ->getQuery()
            ->getResult();

        foreach ($shifts as $shift) {
            $events[] = [
                'id' => 'shift_' . $shift->getId(),
                'title' => $shift->getUser()->getName() . ' - シフト',
                'start' => $shift->getShiftDate()->format('Y-m-d') . 'T' . $shift->getStartTime()->format('H:i:s'),
                'end' => $shift->getShiftDate()->format('Y-m-d') . 'T' . $shift->getEndTime()->format('H:i:s'),
                'backgroundColor' => '#4ade80',
                'borderColor' => '#22c55e',
                'extendedProps' => [
                    'type' => 'shift',
                    'userId' => $shift->getUser()->getId(),
                    'userName' => $shift->getUser()->getName()
                ]
            ];
        }

        // シフト希望を取得
        $requests = $shiftRequestRepository->createQueryBuilder('sr')
            ->join('sr.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('sr.status != :status')
            ->andWhere('sr.targetMonth >= :start')
            ->andWhere('sr.targetMonth <= :end')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', 'draft')
            ->setParameter('start', new \DateTime($start))
            ->setParameter('end', new \DateTime($end))
            ->getQuery()
            ->getResult();

        foreach ($requests as $request) {
            $details = $request->getRequestDetails();
            if (!is_array($details)) {
                continue;
            }

            foreach ($details as $date => $data) {
                if (isset($data['available']) && $data['available']) {
                    $color = match($request->getStatus()) {
                        'submitted' => '#60a5fa',
                        'approved' => '#34d399',
                        'rejected' => '#ef4444',
                        default => '#9ca3af'
                    };

                    $events[] = [
                        'id' => 'request_' . $request->getId() . '_' . $date,
                        'title' => $request->getUser()->getName() . ' - 希望',
                        'start' => $date . 'T' . ($data['start'] ?? '09:00') . ':00',
                        'end' => $date . 'T' . ($data['end'] ?? '17:00') . ':00',
                        'backgroundColor' => $color,
                        'borderColor' => $color,
                        'extendedProps' => [
                            'type' => 'request',
                            'requestId' => $request->getId(),
                            'userId' => $request->getUser()->getId(),
                            'userName' => $request->getUser()->getName(),
                            'status' => $request->getStatus()
                        ]
                    ];
                }
            }
        }

        return new JsonResponse($events);
    }

    #[Route('/requests', name: 'admin_schedule_requests', methods: ['GET'])]
    public function requests(ShiftRequestRepository $repository): Response
    {
        $tenant = $this->tenantResolver->resolve();

        $requests = $repository->createQueryBuilder('sr')
            ->join('sr.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('sr.status != :draft')
            ->setParameter('tenant', $tenant)
            ->setParameter('draft', 'draft')
            ->orderBy('sr.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/schedule/requests.html.twig', [
            'tenant' => $tenant,
            'requests' => $requests
        ]);
    }

    #[Route('/approve/{id}', name: 'admin_schedule_approve', methods: ['POST'])]
    public function approve(ShiftRequest $request): Response
    {
        $request->setStatus('approved');
        $this->entityManager->flush();

        $this->addFlash('success', 'シフト希望を承認しました。');
        return $this->redirectToRoute('admin_schedule_requests');
    }

    #[Route('/reject/{id}', name: 'admin_schedule_reject', methods: ['POST'])]
    public function reject(ShiftRequest $request): Response
    {
        $request->setStatus('rejected');
        $this->entityManager->flush();

        $this->addFlash('success', 'シフト希望を却下しました。');
        return $this->redirectToRoute('admin_schedule_requests');
    }
}
