<?php

namespace App\Controller\Employee;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\TenantResolver;

#[Route('/employee')]
class DashboardController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver
    ) {}

    #[Route('/dashboard', name: 'employee_dashboard')]
    public function index(): Response
    {
        return $this->render('employee/dashboard.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'next_shift' => [
                'date' => '11月22日 (明日)',
                'time' => '22:00 - 05:00',
                'role' => 'バーテンダー'
            ],
            'hours_this_month' => 85,
            'estimated_earnings' => 127500
        ]);
    }
}
