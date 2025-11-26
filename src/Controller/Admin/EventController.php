<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/events')]
class EventController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        return $this->render('admin/event/index.html.twig', [
            'tenant' => $tenant,
            'events' => $eventRepository->findBy(['tenant' => $tenant], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $event = new Event();
        $event->setTenant($tenant);
        $event->setColor('#3b82f6'); // Default blue

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleEventDates($event, $form);
            
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'イベントを作成しました。');
            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/new.html.twig', [
            'tenant' => $tenant,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        if ($event->getTenant() !== $tenant) {
            throw $this->createNotFoundException('イベントが見つかりません。');
        }

        $form = $this->createForm(EventType::class, $event);
        
        // Populate singleDate if it's a single event
        if ($event->getEventType() === 'single' && $event->getStartTime()) {
            $form->get('singleDate')->setData($event->getStartTime());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleEventDates($event, $form);
            
            $this->entityManager->flush();

            $this->addFlash('success', 'イベントを更新しました。');
            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/edit.html.twig', [
            'tenant' => $tenant,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        if ($event->getTenant() !== $tenant) {
            throw $this->createNotFoundException('イベントが見つかりません。');
        }

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
            $this->addFlash('success', 'イベントを削除しました。');
        }

        return $this->redirectToRoute('admin_event_index');
    }

    private function handleEventDates(Event $event, $form): void
    {
        // Combine date and time for single events
        if ($event->getEventType() === 'single') {
            $date = $form->get('singleDate')->getData();
            $timeStart = $form->get('startTime')->getData();
            $timeEnd = $form->get('endTime')->getData();

            if ($date && $timeStart) {
                $start = clone $date;
                $start->setTime($timeStart->format('H'), $timeStart->format('i'));
                $event->setStartTime($start);
            }

            if ($date && $timeEnd) {
                $end = clone $date;
                $end->setTime($timeEnd->format('H'), $timeEnd->format('i'));
                
                // 終了時刻が開始時刻より前の場合（日をまたぐ）、翌日に設定
                if ($timeStart && $timeEnd) {
                    $startHourMinute = (int)$timeStart->format('H') * 60 + (int)$timeStart->format('i');
                    $endHourMinute = (int)$timeEnd->format('H') * 60 + (int)$timeEnd->format('i');
                    
                    if ($endHourMinute < $startHourMinute) {
                        $end->modify('+1 day');
                    }
                }
                
                $event->setEndTime($end);
            }
        } else {
            // For recurring events, startTime/endTime hold the time part only (date part ignored/set to dummy)
            // But we need to ensure they are set. The form handles them as TimeType, so they come as DateTime objects (1970-01-01 H:i)
            // We can keep them as is, or normalize the date part.
            // Let's just ensure they are set from the form data which maps directly to the entity fields for TimeType?
            // Wait, TimeType maps to DateTime in entity but only time part is relevant.
            // The entity fields are DATETIME_MUTABLE.
            // If we use TimeType with mapped=true, it sets 1970-01-01. That's fine for recurring.
        }
    }
}
