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
        private TenantResolver $tenantResolver
    ) {}

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'stats' => [
                'employees' => 12,
                'shifts_today' => 4,
                'pending_requests' => 2
            ]
        ]);
    }
}
