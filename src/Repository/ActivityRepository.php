<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    private const DEFAULT_SORT_FIELD = 'a.dateStart';
    private const SORT_ORDER_ASC = 'ASC';
    private const SORT_ORDER_DESC = 'DESC';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function findWithFilters(
        ?bool $onlyFree = true,
        ?string $type = null,
        ?int $page = 1,
        ?int $pageSize = 10,
        ?string $sort = 'date',
        ?string $order = 'desc'
    ): array {
        $queryBuilder = $this->createBaseQuery();

        $this->applyTypeFilter($queryBuilder, $type);
        $this->applySorting($queryBuilder, $order);

        $activities = $queryBuilder->getQuery()->getResult();
        $activities = $this->filterByAvailability($activities, $onlyFree);

        return $this->paginate($activities, $page, $pageSize);
    }

    public function countWithFilters(?bool $onlyFree = true, ?string $type = null): int
    {
        $queryBuilder = $this->createBaseQuery();

        $this->applyTypeFilter($queryBuilder, $type);

        $activities = $queryBuilder->getQuery()->getResult();
        $activities = $this->filterByAvailability($activities, $onlyFree);

        return count($activities);
    }

    private function createBaseQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('a');
    }

    private function applyTypeFilter(QueryBuilder $queryBuilder, ?string $type): void
    {
        if ($type === null) {
            return;
        }

        $queryBuilder->andWhere('a.type = :type')
                     ->setParameter('type', $type);
    }

    private function applySorting(QueryBuilder $queryBuilder, string $order): void
    {
        $sortOrder = $this->normalizeSortOrder($order);

        $queryBuilder->orderBy(self::DEFAULT_SORT_FIELD, $sortOrder);
    }

    private function normalizeSortOrder(string $order): string
    {
        return strtoupper($order) === self::SORT_ORDER_ASC
            ? self::SORT_ORDER_ASC
            : self::SORT_ORDER_DESC;
    }

    private function filterByAvailability(array $activities, bool $onlyFree): array
    {
        if (!$onlyFree) {
            return $activities;
        }

        $filtered = array_filter($activities, fn(Activity $activity) => $activity->hasFreePlaces());

        return array_values($filtered);
    }

    private function paginate(array $activities, int $page, int $pageSize): array
    {
        $offset = $this->calculateOffset($page, $pageSize);

        return array_slice($activities, $offset, $pageSize);
    }

    private function calculateOffset(int $page, int $pageSize): int
    {
        return ($page - 1) * $pageSize;
    }
}
