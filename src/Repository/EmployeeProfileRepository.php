<?php

namespace App\Repository;

use App\Entity\EmployeeProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmployeeProfile>
 *
 * @method EmployeeProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmployeeProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmployeeProfile[]    findAll()
 * @method EmployeeProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeProfile::class);
    }
}
