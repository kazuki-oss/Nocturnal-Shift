<?php

namespace App\EventSubscriber;

use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TenantSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $em
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $tenant = $this->tenantResolver->resolve();

        if ($tenant) {
            $filter = $this->em->getFilters()->enable('tenant_filter');
            $filter->setParameter('tenant_id', $tenant->getId());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
