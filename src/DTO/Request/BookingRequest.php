<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class BookingRequest
{
    #[Assert\NotBlank(message: 'activity_id is mandatory')]
    #[Assert\Type(type: 'integer', message: 'activity_id must be an integer')]
    public ?int $activity_id = null;

    #[Assert\NotBlank(message: 'client_id is mandatory')]
    #[Assert\Type(type: 'integer', message: 'client_id must be an integer')]
    public ?int $client_id = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->activity_id = $data['activity_id'] ?? null;
        $dto->client_id = $data['client_id'] ?? null;
        return $dto;
    }
}
