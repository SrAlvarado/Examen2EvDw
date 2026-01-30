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
    private const SECONDS_PER_MINUTE = 60;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function countBookingsInWeek(Client $client, \DateTimeInterface $activityDate): int
    {
        $weekBounds = $this->calculateWeekBounds($activityDate);

        return $this->executeCountQuery($client, $weekBounds['monday'], $weekBounds['sunday']);
    }

    private function calculateWeekBounds(\DateTimeInterface $date): array
    {
        $monday = $this->getWeekStart($date);
        $sunday = $this->getWeekEnd($monday);

        return [
            'monday' => $monday,
            'sunday' => $sunday,
        ];
    }

    private function getWeekStart(\DateTimeInterface $date): \DateTime
    {
        $monday = \DateTime::createFromInterface($date);
        $dayOfWeek = (int) $monday->format('N');
        $daysToSubtract = $dayOfWeek - 1;

        $monday->modify("-{$daysToSubtract} days");
        $monday->setTime(0, 0, 0);

        return $monday;
    }

    private function getWeekEnd(\DateTime $monday): \DateTime
    {
        $sunday = clone $monday;
        $sunday->modify('+6 days');
        $sunday->setTime(23, 59, 59);

        return $sunday;
    }

    private function executeCountQuery(Client $client, \DateTime $monday, \DateTime $sunday): int
    {
        $queryBuilder = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->join('b.activity', 'a')
            ->where('b.client = :client')
            ->andWhere('a.dateStart >= :monday')
            ->andWhere('a.dateStart <= :sunday')
            ->setParameter('client', $client)
            ->setParameter('monday', $monday)
            ->setParameter('sunday', $sunday);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
