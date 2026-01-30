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
        $qb = $this->createBaseQuery();

        $this->applyTypeFilter($qb, $type);
        $this->applySorting($qb, $order);

        if ($onlyFree) {
            $this->applyOnlyFreeFilter($qb);
        }

        // Pagination
        $qb->setFirstResult(($page - 1) * $pageSize)
           ->setMaxResults($pageSize);

        return $qb->getQuery()->getResult();
    }

    public function countWithFilters(?bool $onlyFree = true, ?string $type = null): int
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('COUNT(a.id)');

        $this->applyTypeFilter($qb, $type);

        if ($onlyFree) {
             $this->applyOnlyFreeFilter($qb);
             // When filtering by aggregation (SIZE), we cannot use simple COUNT(a.id) easily
             // because it implies a HAVING clause or subquery logic that Doctrine 
             // might not handle in a single scalar validation without grouping.
             // However, SIZE() usually works in WHERE clauses in simpler contexts.
             // Let's safe-bet on counting results for this specific requirement to avoid DQL complexity issues.
             return count($qb->getQuery()->getResult());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createBaseQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('a');
    }

    private function applyTypeFilter(QueryBuilder $qb, ?string $type): void
    {
        if ($type === null) {
            return;
        }

        $qb->andWhere('a.type = :type')
           ->setParameter('type', $type);
    }

    private function applySorting(QueryBuilder $qb, string $order): void
    {
        $sortOrder = $this->normalizeSortOrder($order);
        $qb->orderBy('a.dateStart', $sortOrder);
    }

    private function applyOnlyFreeFilter(QueryBuilder $qb): void
    {
        // Use DQL SIZE() function to compare collection size with maxParticipants
        $qb->andWhere('SIZE(a.bookings) < a.maxParticipants');
    }

    private function normalizeSortOrder(string $order): string
    {
        return strtoupper($order) === self::SORT_ORDER_ASC
            ? self::SORT_ORDER_ASC
            : self::SORT_ORDER_DESC;
    }
}
