<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/clients')]
class ClientController extends AbstractController
{
    private const ERROR_CLIENT_NOT_FOUND = 44;
    private const ERROR_SERVER = 99;

    #[Route('/{id}', methods: ['GET'])]
    public function show(
        int $id,
        Request $request,
        ClientRepository $clientRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $client = $clientRepository->find($id);

        if ($client === null) {
            return $this->createErrorResponse(self::ERROR_CLIENT_NOT_FOUND, 'Client not found');
        }

        $this->configureSerializationOptions($client, $request);

        return $this->serializeAndRespond($client, $serializer);
    }

    private function configureSerializationOptions($client, Request $request): void
    {
        $withStatistics = $this->getBooleanQueryParam($request, 'with_statistics');
        $withBookings = $this->getBooleanQueryParam($request, 'with_bookings');

        $client->setIncludeBookings($withBookings);
        $client->setIncludeStatistics($withStatistics);
    }

    private function getBooleanQueryParam(Request $request, string $paramName): bool
    {
        return filter_var(
            $request->query->get($paramName, 'false'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function serializeAndRespond($client, SerializerInterface $serializer): JsonResponse
    {
        try {
            $json = $serializer->serialize($client, 'json', ['groups' => 'client:read']);
            return new JsonResponse($json, 200, [], true);
        } catch (\Exception $exception) {
            return $this->createErrorResponse(self::ERROR_SERVER, 'Server error: ' . $exception->getMessage());
        }
    }

    private function createErrorResponse(int $code, string $description): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'description' => $description,
        ], 400);
    }
}
