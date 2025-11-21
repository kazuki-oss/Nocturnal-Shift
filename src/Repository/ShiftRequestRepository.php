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
}
