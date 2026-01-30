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
        $qb = $this->createQueryBuilder('a');

        // Filter by type
        if ($type !== null) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        // Sorting
        $sortField = $sort === 'date' ? 'a.dateStart' : 'a.dateStart';
        $sortOrder = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy($sortField, $sortOrder);

        $activities = $qb->getQuery()->getResult();

        // Filter only activities with free places in PHP
        if ($onlyFree === true) {
            $activities = array_filter($activities, function (Activity $activity) {
                return $activity->hasFreePlaces();
            });
            $activities = array_values($activities);
        }

        // Pagination in PHP
        $offset = ($page - 1) * $pageSize;
        return array_slice($activities, $offset, $pageSize);
    }

    /**
     * Count total activities with filters (for pagination metadata)
     */
    public function countWithFilters(?bool $onlyFree = true, ?string $type = null): int
    {
        $qb = $this->createQueryBuilder('a');

        if ($type !== null) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        $activities = $qb->getQuery()->getResult();

        if ($onlyFree === true) {
            $activities = array_filter($activities, function (Activity $activity) {
                return $activity->hasFreePlaces();
            });
        }

        return count($activities);
    }
}

