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
    // --- OBTENER INFORMACIÓN DE CLIENTE (GET) ---
    #[Route('/{id}', methods: ['GET'])]
    public function show(
        int $id,
        Request $request,
        ClientRepository $clientRepo,
        SerializerInterface $serializer
    ): JsonResponse {
        // Get query parameters
        $withStatistics = filter_var($request->query->get('with_statistics', 'false'), FILTER_VALIDATE_BOOLEAN);
        $withBookings = filter_var($request->query->get('with_bookings', 'false'), FILTER_VALIDATE_BOOLEAN);

        // Find client
        $client = $clientRepo->find($id);
        
        if (!$client) {
            return $this->error(44, 'Client not found');
        }

        // Set flags for serialization
        $client->setIncludeBookings($withBookings);
        $client->setIncludeStatistics($withStatistics);

        try {
            $json = $serializer->serialize($client, 'json', ['groups' => 'client:read']);
            return new JsonResponse($json, 200, [], true);
        } catch (\Exception $e) {
            return $this->error(99, 'Server error: ' . $e->getMessage());
        }
    }

    private function error(int $code, string $description): JsonResponse
    {
        return new JsonResponse(['code' => $code, 'description' => $description], 400);
    }
}
