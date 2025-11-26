<?php

namespace App\Repository;

use App\Entity\DrinkRecord;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DrinkRecord>
 */
class DrinkRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DrinkRecord::class);
    }

    public function getRanking(Tenant $tenant, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('d')
            ->select('u.name as userName', 'SUM(d.count) as totalCount')
            ->join('d.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('d.date >= :start')
            ->andWhere('d.date <= :end')
            ->setParameter('tenant', $tenant)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('u.id')
            ->orderBy('totalCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
