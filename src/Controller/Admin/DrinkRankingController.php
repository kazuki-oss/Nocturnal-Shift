<?php

namespace App\Controller\Admin;

use App\Repository\DrinkRecordRepository;
use App\Service\TenantResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/drinks')]
class DrinkRankingController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver
    ) {}

    #[Route('', name: 'admin_drink_index', methods: ['GET'])]
    public function index(Request $request, DrinkRecordRepository $repository): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('m'));
        
        $start = new \DateTimeImmutable("$year-$month-01");
        $end = $start->modify('last day of this month');

        $ranking = $repository->getRanking($tenant, $start, $end);

        return $this->render('admin/drink/index.html.twig', [
            'tenant' => $tenant,
            'ranking' => $ranking,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
