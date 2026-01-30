<?php

namespace App\Tests\DTO;

use App\DTO\Request\BookingRequest;
use App\DTO\Response\ErrorResponse;
use PHPUnit\Framework\TestCase;

class DTOTest extends TestCase
{
    public function testBookingRequestFromArray(): void
    {
        $data = [
            'activity_id' => 1,
            'client_id' => 2
        ];

        $dto = BookingRequest::fromArray($data);

        $this->assertEquals(1, $dto->activity_id);
        $this->assertEquals(2, $dto->client_id);
    }

    public function testBookingRequestFromEmptyArray(): void
    {
        $dto = BookingRequest::fromArray([]);

        $this->assertNull($dto->activity_id);
        $this->assertNull($dto->client_id);
    }

    public function testErrorResponseToArray(): void
    {
        $error = new ErrorResponse(21, 'activity_id is mandatory');

        $array = $error->toArray();

        $this->assertEquals(21, $array['code']);
        $this->assertEquals('activity_id is mandatory', $array['description']);
    }
}
