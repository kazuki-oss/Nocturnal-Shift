<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\Shift;
use App\Entity\ShiftRequest;
use App\Form\ShiftType;
use App\Repository\EventRepository;
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
        $tenant = $this->tenantResolver->resolve();
        
        return $this->render('admin/schedule/index.html.twig', [
            'tenant' => $tenant,
            'businessHours' => $tenant->getBusinessHours() ?? [],
            'businessDays' => $tenant->getBusinessDays() ?? []
        ]);
    }

    #[Route('/events', name: 'admin_schedule_events', methods: ['GET'])]
    public function events(
        Request $request,
        ShiftRepository $shiftRepository,
        ShiftRequestRepository $shiftRequestRepository,
        EventRepository $eventRepository
    ): JsonResponse {
        $tenant = $this->tenantResolver->resolve();
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        $events = [];

        // 1. 確定シフトを取得
        $shifts = $shiftRepository->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('s.startTime >= :start')
            ->andWhere('s.startTime <= :end')
            ->setParameter('tenant', $tenant)
            ->setParameter('start', new \DateTime($start))
            ->setParameter('end', new \DateTime($end))
            ->getQuery()
            ->getResult();

        foreach ($shifts as $shift) {
            $events[] = [
                'id' => 'shift_' . $shift->getId(),
                'title' => $shift->getUser()->getName() . ' - シフト',
                'start' => $shift->getStartTime()->format('Y-m-d\TH:i:s'),
                'end' => $shift->getEndTime()->format('Y-m-d\TH:i:s'),
                'backgroundColor' => '#4ade80',
                'borderColor' => '#22c55e',
                'extendedProps' => [
                    'type' => 'shift',
                    'userId' => $shift->getUser()->getId(),
                    'userName' => $shift->getUser()->getName()
                ]
            ];
        }

        // 2. シフト希望を取得
        // Note: month field is YYYY-MM format, need different comparison logic
        $startMonth = (new \DateTime($start))->format('Y-m');
        $endMonth = (new \DateTime($end))->format('Y-m');
        
        $requests = $shiftRequestRepository->createQueryBuilder('sr')
            ->join('sr.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('sr.status != :status')
            ->andWhere('sr.month >= :startMonth')
            ->andWhere('sr.month <= :endMonth')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', 'draft')
            ->setParameter('startMonth', $startMonth)
            ->setParameter('endMonth', $endMonth)
            ->getQuery()
            ->getResult();

        foreach ($requests as $request) {
            $details = $request->getRequests();
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

        // 3. イベントデータの取得（繰り返し対応）
        $dbEvents = $eventRepository->findBy(['tenant' => $tenant]);
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);

        foreach ($dbEvents as $event) {
            if ($event->getEventType() === 'single') {
                // 単発イベント
                $eventStart = $event->getStartTime();
                $eventEnd = $event->getEndTime();
                
                // 範囲内かチェック（簡易）
                if ($eventStart >= $startDate && $eventStart < $endDate) {
                    $eventData = [
                        'title' => $event->getName(),
                        'start' => $eventStart->format('Y-m-d\TH:i:s'),
                        'backgroundColor' => $event->getColor() ?? '#3b82f6',
                        'borderColor' => $event->getColor() ?? '#3b82f6',
                        'extendedProps' => [
                            'type' => 'event',
                            'eventId' => $event->getId(),
                            'description' => $event->getDescription()
                        ]
                    ];

                    // 終日イベントの処理
                    if ($event->isAllDay()) {
                        $eventData['allDay'] = true;
                        $eventData['start'] = $eventStart->format('Y-m-d');
                        // 複数日イベントの場合
                        if ($event->getEndDate()) {
                            $evtEndDate = clone $event->getEndDate();
                            $evtEndDate->modify('+1 day'); // FullCalendarは終了日を含まないため+1日
                            $eventData['end'] = $evtEndDate->format('Y-m-d');
                        }
                    } else {
                        $eventData['end'] = $eventEnd->format('Y-m-d\TH:i:s');
                    }

                    $events[] = $eventData;
                }
            } elseif ($event->getEventType() === 'weekly') {
                // 毎週イベント
                $current = clone $startDate;
                // 開始日がイベントの曜日と異なる場合、次の該当曜日まで進める
                while ($current->format('w') != $event->getDayOfWeek()) {
                    $current->modify('+1 day');
                }
                
                while ($current < $endDate) {
                    // 繰り返し終了日のチェック
                    if ($event->getRecurrenceEndDate() && $current > $event->getRecurrenceEndDate()) {
                        break;
                    }

                    $eventStart = clone $current;
                    $eventStart->setTime(
                        (int)$event->getStartTime()->format('H'),
                        (int)$event->getStartTime()->format('i')
                    );
                    
                    $eventEnd = clone $current;
                    $eventEnd->setTime(
                        (int)$event->getEndTime()->format('H'),
                        (int)$event->getEndTime()->format('i')
                    );

                    $events[] = [
                        'id' => 'event_' . $event->getId() . '_' . $current->format('Ymd'),
                        'title' => $event->getName(),
                        'start' => $eventStart->format('Y-m-d\TH:i:s'),
                        'end' => $eventEnd->format('Y-m-d\TH:i:s'),
                        'backgroundColor' => $event->getColor() ?? '#10b981',
                        'borderColor' => $event->getColor() ?? '#10b981',
                        'extendedProps' => [
                            'type' => 'event',
                            'eventId' => $event->getId(),
                            'description' => $event->getDescription()
                        ]
                    ];

                    $current->modify('+1 week');
                }
            } elseif ($event->getEventType() === 'monthly') {
                // 毎月イベント
                $current = clone $startDate;
                $current->setDate($current->format('Y'), $current->format('m'), $event->getDayOfMonth());
                
                // 開始日より前なら翌月に移動
                if ($current < $startDate) {
                    $current->modify('+1 month');
                }

                while ($current < $endDate) {
                    // 繰り返し終了日のチェック
                    if ($event->getRecurrenceEndDate() && $current > $event->getRecurrenceEndDate()) {
                        break;
                    }

                    // 日付が存在するかチェック（例: 2月30日などはスキップ）
                    if ($current->format('d') == $event->getDayOfMonth()) {
                        $eventStart = clone $current;
                        $eventStart->setTime(
                            (int)$event->getStartTime()->format('H'),
                            (int)$event->getStartTime()->format('i')
                        );
                        
                        $eventEnd = clone $current;
                        $eventEnd->setTime(
                            (int)$event->getEndTime()->format('H'),
                            (int)$event->getEndTime()->format('i')
                        );

                        $events[] = [
                            'id' => 'event_' . $event->getId() . '_' . $current->format('Ymd'),
                            'title' => $event->getName(),
                            'start' => $eventStart->format('Y-m-d\TH:i:s'),
                            'end' => $eventEnd->format('Y-m-d\TH:i:s'),
                            'backgroundColor' => $event->getColor() ?? '#8b5cf6',
                            'borderColor' => $event->getColor() ?? '#8b5cf6',
                            'extendedProps' => [
                                'type' => 'event',
                                'eventId' => $event->getId(),
                                'description' => $event->getDescription()
                            ]
                        ];
                    }

                    $current->modify('+1 month');
                    // 日付を再設定
                    $current->setDate($current->format('Y'), $current->format('m'), $event->getDayOfMonth());
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
    public function approve(ShiftRequest $request, ShiftRepository $shiftRepository): Response
    {
        $request->setStatus('approved');
        
        // 承認時に自動でシフトを作成
        $details = $request->getRequests();
        if (is_array($details)) {
            foreach ($details as $date => $data) {
                if (isset($data['available']) && $data['available']) {
                    // Parse date and times
                    $shiftDate = new \DateTime($date);
                    $startTime = new \DateTime($date . ' ' . ($data['start'] ?? '09:00'));
                    $endTime = new \DateTime($date . ' ' . ($data['end'] ?? '17:00'));
                    
                    // 既存のシフトを確認（重複防止）
                    $existingShift = $shiftRepository->createQueryBuilder('s')
                        ->where('s.user = :user')
                        ->andWhere('s.startTime >= :dayStart')
                        ->andWhere('s.startTime < :dayEnd')
                        ->setParameter('user', $request->getUser())
                        ->setParameter('dayStart', $shiftDate->format('Y-m-d 00:00:00'))
                        ->setParameter('dayEnd', $shiftDate->format('Y-m-d 23:59:59'))
                        ->getQuery()
                        ->getOneOrNullResult();

                    if (!$existingShift) {
                        $shift = new Shift();
                        $shift->setTenant($request->getUser()->getTenant());
                        $shift->setUser($request->getUser());
                        $shift->setStartTime($startTime);
                        $shift->setEndTime($endTime);
                        $shift->setStatus('scheduled');

                        $this->entityManager->persist($shift);
                    }
                }
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'シフト希望を承認し、シフトを自動作成しました。');
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

    #[Route('/shift/create', name: 'admin_shift_create', methods: ['GET', 'POST'])]
    public function createShift(Request $request, UserRepository $userRepository): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $shift = new Shift();
        $shift->setTenant($tenant);
        $shift->setStatus('scheduled');

        $form = $this->createForm(ShiftType::class, $shift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($shift);
            $this->entityManager->flush();

            $this->addFlash('success', 'シフトを作成しました。');
            return $this->redirectToRoute('admin_schedule_index');
        }

        return $this->render('admin/schedule/shift_form.html.twig', [
            'tenant' => $tenant,
            'form' => $form->createView(),
            'shift' => null,
        ]);
    }

    #[Route('/shift/{id}/edit', name: 'admin_shift_edit', methods: ['GET', 'POST'])]
    public function editShift(Request $request, Shift $shift): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        if ($shift->getTenant() !== $tenant) {
            throw $this->createNotFoundException('シフトが見つかりません。');
        }

        $form = $this->createForm(ShiftType::class, $shift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'シフトを更新しました。');
            return $this->redirectToRoute('admin_schedule_index');
        }

        return $this->render('admin/schedule/shift_form.html.twig', [
            'tenant' => $tenant,
            'form' => $form->createView(),
            'shift' => $shift,
        ]);
    }

    #[Route('/shift/{id}/delete', name: 'admin_shift_delete', methods: ['POST'])]
    public function deleteShift(Request $request, Shift $shift): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        if ($shift->getTenant() !== $tenant) {
            throw $this->createNotFoundException('シフトが見つかりません。');
        }

        if ($this->isCsrfTokenValid('delete'.$shift->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($shift);
            $this->entityManager->flush();

            $this->addFlash('success', 'シフトを削除しました。');
        }

        return $this->redirectToRoute('admin_schedule_index');
    }
}
