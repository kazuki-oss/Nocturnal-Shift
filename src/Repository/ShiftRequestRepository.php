<?php

namespace App\Repository;

use App\Entity\ShiftRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShiftRequest>
 *
 * @method ShiftRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShiftRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShiftRequest[]    findAll()
 * @method ShiftRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShiftRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShiftRequest::class);
    }

    public function countPendingByTenant(\App\Entity\Tenant $tenant): int
    {
        return $this->createQueryBuilder('sr')
            ->select('count(sr.id)')
            ->join('sr.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('sr.status = :status')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentByTenant(\App\Entity\Tenant $tenant, int $limit = 5): array
    {
        return $this->createQueryBuilder('sr')
            ->join('sr.user', 'u')
            ->where('u.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('sr.submittedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
