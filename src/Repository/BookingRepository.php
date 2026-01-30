<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Count bookings for a client within the same week (Monday to Sunday)
     * of the given activity date
     */
    public function countBookingsInWeek(Client $client, \DateTimeInterface $activityDate): int
    {
        // Get Monday of the week
        $monday = clone $activityDate;
        if ($monday instanceof \DateTime) {
            $dayOfWeek = (int) $monday->format('N'); // 1 = Monday, 7 = Sunday
            $monday->modify('-' . ($dayOfWeek - 1) . ' days');
            $monday->setTime(0, 0, 0);
        }

        // Get Sunday of the week
        $sunday = clone $monday;
        if ($sunday instanceof \DateTime) {
            $sunday->modify('+6 days');
            $sunday->setTime(23, 59, 59);
        }

        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->join('b.activity', 'a')
            ->where('b.client = :client')
            ->andWhere('a.dateStart >= :monday')
            ->andWhere('a.dateStart <= :sunday')
            ->setParameter('client', $client)
            ->setParameter('monday', $monday)
            ->setParameter('sunday', $sunday);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
