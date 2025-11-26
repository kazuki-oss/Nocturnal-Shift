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

        $session = $request->hasSession() ? $request->getSession() : null;

        // 優先順位1: URLパラメータでテナントIDを指定（開発・テスト用）
        $tenantId = $request->query->get('tenant_id');
        if ($tenantId) {
            $tenant = $this->tenantRepository->find($tenantId);
            if ($tenant) {
                $this->currentTenant = $tenant;
                // セッションに保存
                if ($session) {
                    $session->set('tenant_id', $tenantId);
                }
                return $this->currentTenant;
            }
        }

        // 優先順位2: セッションに保存されたテナントID
        if ($session && $session->has('tenant_id')) {
            $sessionTenantId = $session->get('tenant_id');
            $tenant = $this->tenantRepository->find($sessionTenantId);
            if ($tenant) {
                $this->currentTenant = $tenant;
                return $this->currentTenant;
            }
        }

        // 優先順位3: ドメインベースの識別（本番環境用）
        $host = $request->getHost();
        $this->currentTenant = $this->tenantRepository->findOneBy(['domain' => $host]);

        // セッションに保存
        if ($this->currentTenant && $session) {
            $session->set('tenant_id', $this->currentTenant->getId());
        }

        return $this->currentTenant;
    }

    public function getCurrentTenant(): ?Tenant
    {
        return $this->resolve();
    }

    /**
     * テナントをクリア（テスト用）
     */
    public function clearTenant(): void
    {
        $this->currentTenant = null;
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->hasSession()) {
            $request->getSession()->remove('tenant_id');
        }
    }
}
