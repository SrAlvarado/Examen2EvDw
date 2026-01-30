<?php

namespace App\Controller;

use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/activities')]
class ActivityController extends AbstractController
{
    // --- LISTAR ACTIVIDADES (GET) ---
    #[Route('', methods: ['GET'])]
    public function list(
        Request $request, 
        ActivityRepository $activityRepo, 
        SerializerInterface $serializer
    ): JsonResponse {
        // Get query parameters
        $onlyFreeParam = $request->query->get('onlyfree');
        $onlyFree = $onlyFreeParam === null ? true : filter_var($onlyFreeParam, FILTER_VALIDATE_BOOLEAN);
        
        $type = $request->query->get('type');
        $page = (int) ($request->query->get('page') ?? 1);
        $pageSize = (int) ($request->query->get('page_size') ?? 10);
        $sort = $request->query->get('sort') ?? 'date';
        $order = $request->query->get('order') ?? 'desc';

        // Validate type if provided
        $validTypes = ['BodyPump', 'Spinning', 'Core'];
        if ($type !== null && !in_array($type, $validTypes)) {
            return $this->error(21, 'Invalid activity type. Must be one of: BodyPump, Spinning, Core');
        }

        // Validate sort
        if ($sort !== 'date') {
            return $this->error(22, 'Invalid sort parameter. Must be: date');
        }

        // Validate order
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            return $this->error(23, 'Invalid order parameter. Must be: asc or desc');
        }

        try {
            $activities = $activityRepo->findWithFilters($onlyFree, $type, $page, $pageSize, $sort, $order);
            $totalItems = $activityRepo->countWithFilters($onlyFree, $type);

            $response = [
                'data' => json_decode($serializer->serialize($activities, 'json', ['groups' => 'activity:read']), true),
                'meta' => [
                    'page' => $page,
                    'limit' => $pageSize,
                    'total-items' => $totalItems,
                ]
            ];

            return new JsonResponse($response, 200);
        } catch (\Exception $e) {
            return $this->error(99, 'Server error: ' . $e->getMessage());
        }
    }

    private function error(int $code, string $description): JsonResponse
    {
        return new JsonResponse(['code' => $code, 'description' => $description], 400);
    }
}
