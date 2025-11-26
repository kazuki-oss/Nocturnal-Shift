<?php

namespace App\Repository;

use App\Entity\Attendance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attendance>
 *
 * @method Attendance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Attendance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Attendance[]    findAll()
 * @method Attendance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttendanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attendance::class);
    }

    public function findByPeriod(\App\Entity\Tenant $tenant, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.tenant = :tenant')
            ->andWhere('a.attendanceDate >= :from')
            ->andWhere('a.attendanceDate <= :to')
            ->setParameter('tenant', $tenant)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('a.attendanceDate', 'ASC')
            ->addOrderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyStats(\App\Entity\Tenant $tenant, int $year, int $month): array
    {
        $from = new \DateTime("$year-$month-01");
        $to = (clone $from)->modify('last day of this month');

        $records = $this->findByPeriod($tenant, $from, $to);

        $stats = [];
        foreach ($records as $attendance) {
            $userId = $attendance->getUser()->getId();
            
            if (!isset($stats[$userId])) {
                $stats[$userId] = [
                    'user' => $attendance->getUser(),
                    'days' => 0,
                    'totalMinutes' => 0,
                    'lateCount' => 0,
                    'earlyLeaveCount' => 0,
                ];
            }

            if ($attendance->getClockInAt() && $attendance->getClockOutAt()) {
                $stats[$userId]['days']++;
                $diff = $attendance->getClockOutAt()->getTimestamp() - $attendance->getClockInAt()->getTimestamp();
                $workingMinutes = floor($diff / 60) - ($attendance->getBreakMinutes() ?? 0);
                $stats[$userId]['totalMinutes'] += $workingMinutes;
            }
        }

        return array_values($stats);
    }

    public function getEmployeeMonthlyData(\App\Entity\User $user, int $year, int $month): array
    {
        $from = new \DateTime("$year-$month-01");
        $to = (clone $from)->modify('last day of this month');

        $records = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.attendanceDate >= :from')
            ->andWhere('a.attendanceDate <= :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('a.attendanceDate', 'ASC')
            ->getQuery()
            ->getResult();

        $totalDays = 0;
        $totalMinutes = 0;

        foreach ($records as $attendance) {
            if ($attendance->getClockInAt() && $attendance->getClockOutAt()) {
                $totalDays++;
                $diff = $attendance->getClockOutAt()->getTimestamp() - $attendance->getClockInAt()->getTimestamp();
                $workingMinutes = floor($diff / 60) - ($attendance->getBreakMinutes() ?? 0);
                $totalMinutes += $workingMinutes;
            }
        }

        return [
            'records' => $records,
            'totalDays' => $totalDays,
            'totalMinutes' => $totalMinutes,
            'totalHours' => floor($totalMinutes / 60),
            'avgMinutes' => $totalDays > 0 ? floor($totalMinutes / $totalDays) : 0,
        ];
    }
}
