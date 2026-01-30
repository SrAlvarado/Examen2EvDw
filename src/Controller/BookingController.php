<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Booking;
use App\Entity\Client;
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
    private const STANDARD_USER_WEEKLY_LIMIT = 2;

    private const ERROR_ACTIVITY_ID_REQUIRED = 21;
    private const ERROR_CLIENT_ID_REQUIRED = 22;
    private const ERROR_ACTIVITY_NOT_FOUND = 31;
    private const ERROR_CLIENT_NOT_FOUND = 32;
    private const ERROR_NO_FREE_PLACES = 41;
    private const ERROR_ALREADY_BOOKED = 42;
    private const ERROR_WEEKLY_LIMIT_EXCEEDED = 43;
    private const ERROR_SERVER = 99;

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        ActivityRepository $activityRepository,
        ClientRepository $clientRepository,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $bookingRequest = \App\DTO\Request\BookingRequest::fromArray(json_decode($request->getContent(), true) ?? []);

        $validationError = $this->validateRequiredFields($bookingRequest);
        if ($validationError !== null) {
            return $validationError;
        }

        $activity = $activityRepository->find($bookingRequest->activity_id);
        if ($activity === null) {
            return $this->createErrorResponse(self::ERROR_ACTIVITY_NOT_FOUND, 'Activity not found');
        }

        $client = $clientRepository->find($bookingRequest->client_id);
        if ($client === null) {
            return $this->createErrorResponse(self::ERROR_CLIENT_NOT_FOUND, 'Client not found');
        }

        $businessValidationError = $this->validateBusinessRules($activity, $client, $bookingRepository);
        if ($businessValidationError !== null) {
            return $businessValidationError;
        }

        return $this->persistAndReturnBooking($activity, $client, $entityManager);
    }

    private function validateRequiredFields(\App\DTO\Request\BookingRequest $bookingRequest): ?JsonResponse
    {
        if (empty($bookingRequest->activity_id)) {
            return $this->createErrorResponse(self::ERROR_ACTIVITY_ID_REQUIRED, 'activity_id is mandatory');
        }

        if (empty($bookingRequest->client_id)) {
            return $this->createErrorResponse(self::ERROR_CLIENT_ID_REQUIRED, 'client_id is mandatory');
        }

        return null;
    }

    private function validateBusinessRules(
        Activity $activity,
        Client $client,
        BookingRepository $bookingRepository
    ): ?JsonResponse {
        if ($activity->isFull()) {
            return $this->createErrorResponse(self::ERROR_NO_FREE_PLACES, 'Activity is full, no free places available');
        }

        if ($activity->isClientAlreadyBooked($client)) {
            return $this->createErrorResponse(self::ERROR_ALREADY_BOOKED, 'Client already booked this activity');
        }

        if ($this->exceedsStandardUserWeeklyLimit($client, $activity, $bookingRepository)) {
            return $this->createErrorResponse(
                self::ERROR_WEEKLY_LIMIT_EXCEEDED,
                'Standard users cannot book more than 2 activities per week'
            );
        }

        return null;
    }

    private function exceedsStandardUserWeeklyLimit(
        Client $client,
        Activity $activity,
        BookingRepository $bookingRepository
    ): bool {
        if (!$client->isStandardUser()) {
            return false;
        }

        $bookingsThisWeek = $bookingRepository->countBookingsInWeek($client, $activity->getDateStart());

        return $bookingsThisWeek >= self::STANDARD_USER_WEEKLY_LIMIT;
    }

    private function persistAndReturnBooking(
        Activity $activity,
        Client $client,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $booking = $this->createBooking($activity, $client);

            $entityManager->persist($booking);
            $entityManager->flush();

            return $this->createSuccessResponse($booking);
        } catch (\Exception $exception) {
            return $this->createErrorResponse(self::ERROR_SERVER, 'Server error: ' . $exception->getMessage());
        }
    }

    private function createBooking(Activity $activity, Client $client): Booking
    {
        $booking = new Booking();
        $booking->setActivity($activity);
        $booking->setClient($client);

        return $booking;
    }

    private function createSuccessResponse(Booking $booking): JsonResponse
    {
        $responseDTO = \App\DTO\Response\BookingResponse::fromEntity($booking);
        return new JsonResponse($responseDTO, 200);
    }

    private function createErrorResponse(int $code, string $description): JsonResponse
    {
        $errorDTO = new \App\DTO\Response\ErrorResponse($code, $description);
        return new JsonResponse($errorDTO->toArray(), 400);
    }
}
