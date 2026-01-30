<?php

namespace App\DTO\Response;

class BookingResponse
{
    public function __construct(
        public int $id,
        public ActivityResponse $activity,
        public int $client_id
    ) {}

    public static function fromEntity(\App\Entity\Booking $booking): self
    {
        return new self(
            id: $booking->getId(),
            activity: ActivityResponse::fromEntity($booking->getActivity()),
            client_id: $booking->getClient()->getId()
        );
    }
}
