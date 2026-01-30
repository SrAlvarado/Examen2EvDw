<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\ActivityRepository;
use App\Repository\BookingRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/bookings')]
class BookingController extends AbstractController
{
    // --- CREAR RESERVA (POST) ---
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        ActivityRepository $activityRepo,
        ClientRepository $clientRepo,
        BookingRepository $bookingRepo,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validations
        if (empty($data['activity_id'])) {
            return $this->error(21, 'activity_id is mandatory');
        }

        if (empty($data['client_id'])) {
            return $this->error(22, 'client_id is mandatory');
        }

        // Find activity
        $activity = $activityRepo->find($data['activity_id']);
        if (!$activity) {
            return $this->error(31, 'Activity not found');
        }

        // Find client
        $client = $clientRepo->find($data['client_id']);
        if (!$client) {
            return $this->error(32, 'Client not found');
        }

        // Check if activity has free places
        if (!$activity->hasFreePlaces()) {
            return $this->error(41, 'Activity is full, no free places available');
        }

        // Check if client already booked this activity
        foreach ($activity->getBookings() as $existingBooking) {
            if ($existingBooking->getClient()->getId() === $client->getId()) {
                return $this->error(42, 'Client already booked this activity');
            }
        }

        // Check standard user weekly limit (max 2 bookings per week Monday-Sunday)
        if ($client->getType() === 'standard') {
            $bookingsThisWeek = $bookingRepo->countBookingsInWeek($client, $activity->getDateStart());
            if ($bookingsThisWeek >= 2) {
                return $this->error(43, 'Standard users cannot book more than 2 activities per week');
            }
        }

        // Create booking
        try {
            $booking = new Booking();
            $booking->setActivity($activity);
            $booking->setClient($client);

            $em->persist($booking);
            $em->flush();

            $json = $serializer->serialize($booking, 'json', ['groups' => 'booking:read']);
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
