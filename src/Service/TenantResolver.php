<?php

namespace App\Service;

use App\Entity\Tenant;
use App\Repository\TenantRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class TenantResolver
{
    private ?Tenant $currentTenant = null;

    public function __construct(
        private RequestStack $requestStack,
        private TenantRepository $tenantRepository
    ) {}

    public function resolve(): ?Tenant
    {
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $host = $request->getHost();
        // Simple domain matching. In production, you might want more robust logic.
        // e.g. tenant1.example.com -> tenant1
        
        // For local development, we might simulate with a header or just return the first one if not found
        // Or check if the host matches a tenant's domain
        
        $this->currentTenant = $this->tenantRepository->findOneBy(['domain' => $host]);

        return $this->currentTenant;
    }

    public function getCurrentTenant(): ?Tenant
    {
        return $this->resolve();
    }
}
