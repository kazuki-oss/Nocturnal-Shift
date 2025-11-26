<?php

namespace App\Repository;

use App\Entity\Shift;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Shift>
 *
 * @method Shift|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shift|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shift[]    findAll()
 * @method Shift[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShiftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shift::class);
    }

    public function findByMonth(\App\Entity\Tenant $tenant, \DateTimeInterface $month): array
    {
        $firstDay = (clone $month)->modify('first day of this month')->setTime(0, 0, 0);
        $lastDay = (clone $month)->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('s.startTime >= :firstDay')
            ->andWhere('s.startTime <= :lastDay')
            ->setParameter('tenant', $tenant)
            ->setParameter('firstDay', $firstDay)
            ->setParameter('lastDay', $lastDay)
            ->orderBy('s.startTime', 'ASC')
            ->addOrderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByDate(\App\Entity\Tenant $tenant, \DateTimeInterface $date): int
    {
        $start = (clone $date)->setTime(0, 0, 0);
        $end = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->join('s.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('s.startTime >= :start')
            ->andWhere('s.startTime <= :end')
            ->setParameter('tenant', $tenant)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
