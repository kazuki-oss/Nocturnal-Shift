<?php

namespace App\Controller\Employee;

use App\Entity\DrinkRecord;
use App\Repository\DrinkRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employee/drinks')]
class DrinkRecordController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/record', name: 'employee_drink_record', methods: ['GET', 'POST'])]
    public function record(Request $request): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $count = $request->request->getInt('count');
            $date = new \DateTimeImmutable($request->request->get('date'));

            $record = new DrinkRecord();
            $record->setUser($user);
            $record->setCount($count);
            $record->setDate($date);

            $this->entityManager->persist($record);
            $this->entityManager->flush();

            $this->addFlash('success', 'ドリンク杯数を記録しました。');
            return $this->redirectToRoute('employee_drink_record');
        }

        return $this->render('employee/drink/record.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/history', name: 'employee_drink_history', methods: ['GET'])]
    public function history(Request $request, DrinkRecordRepository $repository): Response
    {
        $user = $this->getUser();
        
        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('m'));
        
        $start = new \DateTimeImmutable("$year-$month-01");
        $end = $start->modify('last day of this month');

        // 自分の記録のみ取得
        $records = $repository->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.date >= :start')
            ->andWhere('d.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('d.date', 'DESC')
            ->getQuery()
            ->getResult();

        // 月次合計
        $totalCount = $repository->createQueryBuilder('d')
            ->select('SUM(d.count)')
            ->where('d.user = :user')
            ->andWhere('d.date >= :start')
            ->andWhere('d.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('employee/drink/history.html.twig', [
            'user' => $user,
            'records' => $records,
            'totalCount' => $totalCount ?? 0,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
