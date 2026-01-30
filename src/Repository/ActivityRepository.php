<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * Find activities with optional filters, pagination and sorting
     */
    public function findWithFilters(
        ?bool $onlyFree = true,
        ?string $type = null,
        ?int $page = 1,
        ?int $pageSize = 10,
        ?string $sort = 'date',
        ?string $order = 'desc'
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.bookings', 'b')
            ->addSelect('COUNT(b.id) as HIDDEN bookingCount');

        // Filter by type
        if ($type !== null) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        $qb->groupBy('a.id');

        // Filter only activities with free places
        if ($onlyFree === true) {
            $qb->having('COUNT(b.id) < a.maxParticipants');
        }

        // Sorting
        $sortField = $sort === 'date' ? 'a.dateStart' : 'a.dateStart';
        $sortOrder = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy($sortField, $sortOrder);

        // Pagination
        $offset = ($page - 1) * $pageSize;
        $qb->setFirstResult($offset)
           ->setMaxResults($pageSize);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count total activities with filters (for pagination metadata)
     */
    public function countWithFilters(?bool $onlyFree = true, ?string $type = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.id)')
            ->leftJoin('a.bookings', 'b');

        if ($type !== null) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        if ($onlyFree === true) {
            $qb->groupBy('a.id')
               ->having('COUNT(b.id) < a.maxParticipants');
            
            return count($qb->getQuery()->getResult());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
