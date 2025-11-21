<?php

namespace App\Controller\Employee;

use App\Entity\ShiftRequest;
use App\Repository\ShiftRequestRepository;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employee/shift-request')]
class ShiftRequestController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'employee_shift_request_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('employee/shift_request/index.html.twig', [
            'tenant' => $this->tenantResolver->resolve()
        ]);
    }

    #[Route('/submit', name: 'employee_shift_request_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $user = $this->getUser();
        $targetMonth = $request->request->get('target_month'); // 例: 2024-11
        $requestDetails = $request->request->all('days'); // 日付ごとのデータ

        // 既存のリクエストを検索
        $shiftRequest = $this->entityManager->getRepository(ShiftRequest::class)->findOneBy([
            'user' => $user,
            'targetMonth' => new \DateTimeImmutable($targetMonth . '-01')
        ]);

        if (!$shiftRequest) {
            $shiftRequest = new ShiftRequest();
            $shiftRequest->setUser($user);
            $shiftRequest->setTargetMonth(new \DateTimeImmutable($targetMonth . '-01'));
            $shiftRequest->setSubmittedAt(new \DateTimeImmutable());
        }

        // JSON形式で保存
        $shiftRequest->setRequestDetails($requestDetails);
        $shiftRequest->setStatus('submitted');

        $this->entityManager->persist($shiftRequest);
        $this->entityManager->flush();

        $this->addFlash('success', 'シフト希望を提出しました。');
        return $this->redirectToRoute('employee_shift_request_history');
    }

    #[Route('/history', name: 'employee_shift_request_history', methods: ['GET'])]
    public function history(ShiftRequestRepository $repository): Response
    {
        $user = $this->getUser();
        
        $requests = $repository->createQueryBuilder('sr')
            ->where('sr.user = :user')
            ->setParameter('user', $user)
            ->orderBy('sr.targetMonth', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('employee/shift_request/history.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'requests' => $requests
        ]);
    }

    #[Route('/data/{month}', name: 'employee_shift_request_data', methods: ['GET'])]
    public function getData(string $month): JsonResponse
    {
        $user = $this->getUser();
        
        $shiftRequest = $this->entityManager->getRepository(ShiftRequest::class)->findOneBy([
            'user' => $user,
            'targetMonth' => new \DateTimeImmutable($month . '-01')
        ]);

        if (!$shiftRequest) {
            return new JsonResponse(['days' => []]);
        }

        return new JsonResponse([
            'days' => $shiftRequest->getRequestDetails(),
            'status' => $shiftRequest->getStatus()
        ]);
    }
}
