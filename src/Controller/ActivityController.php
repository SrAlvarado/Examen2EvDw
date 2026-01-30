<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/activities')]
class ActivityController extends AbstractController
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_PAGE_SIZE = 10;
    private const DEFAULT_SORT = 'date';
    private const DEFAULT_ORDER = 'desc';
    private const VALID_SORT_OPTIONS = ['date'];
    private const VALID_ORDER_OPTIONS = ['asc', 'desc'];

    #[Route('', methods: ['GET'])]
    public function list(
        Request $request,
        ActivityRepository $activityRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $queryParams = $this->extractQueryParameters($request);

        $validationError = $this->validateQueryParameters($queryParams);
        if ($validationError !== null) {
            return $validationError;
        }

        return $this->fetchAndReturnActivities($activityRepository, $serializer, $queryParams);
    }

    private function extractQueryParameters(Request $request): array
    {
        $onlyFreeParam = $request->query->get('onlyfree');

        return [
            'onlyFree' => $onlyFreeParam === null ? true : filter_var($onlyFreeParam, FILTER_VALIDATE_BOOLEAN),
            'type' => $request->query->get('type'),
            'page' => (int) ($request->query->get('page') ?? self::DEFAULT_PAGE),
            'pageSize' => (int) ($request->query->get('page_size') ?? self::DEFAULT_PAGE_SIZE),
            'sort' => $request->query->get('sort') ?? self::DEFAULT_SORT,
            'order' => $request->query->get('order') ?? self::DEFAULT_ORDER,
        ];
    }

    private function validateQueryParameters(array $params): ?JsonResponse
    {
        if ($params['type'] !== null && !$this->isValidActivityType($params['type'])) {
            return $this->createErrorResponse(21, $this->buildInvalidTypeMessage());
        }

        if (!$this->isValidSortOption($params['sort'])) {
            return $this->createErrorResponse(22, 'Invalid sort parameter. Must be: date');
        }

        if (!$this->isValidOrderOption($params['order'])) {
            return $this->createErrorResponse(23, 'Invalid order parameter. Must be: asc or desc');
        }

        return null;
    }

    private function isValidActivityType(string $type): bool
    {
        return in_array($type, Activity::VALID_TYPES, true);
    }

    private function isValidSortOption(string $sort): bool
    {
        return in_array($sort, self::VALID_SORT_OPTIONS, true);
    }

    private function isValidOrderOption(string $order): bool
    {
        return in_array(strtolower($order), self::VALID_ORDER_OPTIONS, true);
    }

    private function buildInvalidTypeMessage(): string
    {
        $validTypes = implode(', ', Activity::VALID_TYPES);
        return "Invalid activity type. Must be one of: {$validTypes}";
    }

    private function fetchAndReturnActivities(
        ActivityRepository $repository,
        SerializerInterface $serializer,
        array $params
    ): JsonResponse {
        try {
            $activities = $repository->findWithFilters(
                $params['onlyFree'],
                $params['type'],
                $params['page'],
                $params['pageSize'],
                $params['sort'],
                $params['order']
            );

            $totalItems = $repository->countWithFilters($params['onlyFree'], $params['type']);

            return $this->createSuccessResponse($serializer, $activities, $params, $totalItems);
        } catch (\Exception $exception) {
            return $this->createErrorResponse(99, 'Server error: ' . $exception->getMessage());
        }
    }

    private function createSuccessResponse(
        SerializerInterface $serializer,
        array $activities,
        array $params,
        int $totalItems
    ): JsonResponse {
        $responseDTO = \App\DTO\Response\ActivityListResponse::fromEntities(
            $activities,
            $params['page'],
            $params['pageSize'],
            $totalItems
        );

        return new JsonResponse($responseDTO->toArray(), 200);
    }

    private function createErrorResponse(int $code, string $description): JsonResponse
    {
        $errorDTO = new \App\DTO\Response\ErrorResponse($code, $description);
        return new JsonResponse($errorDTO->toArray(), 400);
    }
}
