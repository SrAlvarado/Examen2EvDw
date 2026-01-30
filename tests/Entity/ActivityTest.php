<?php

namespace App\Tests\Entity;

use App\Entity\Activity;
use App\Entity\Booking;
use App\Entity\Client;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    public function testActivityConstants(): void
    {
        $this->assertEquals('BodyPump', Activity::TYPE_BODYPUMP);
        $this->assertEquals('Spinning', Activity::TYPE_SPINNING);
        $this->assertEquals('Core', Activity::TYPE_CORE);

        $this->assertContains('BodyPump', Activity::VALID_TYPES);
        $this->assertContains('Spinning', Activity::VALID_TYPES);
        $this->assertContains('Core', Activity::VALID_TYPES);
    }

    public function testActivityHasFreePlaces(): void
    {
        $activity = new Activity();
        $activity->setMaxParticipants(10);

        $this->assertTrue($activity->hasFreePlaces());
        $this->assertEquals(10, $activity->getAvailablePlaces());
        $this->assertFalse($activity->isFull());
    }

    public function testActivityIsFull(): void
    {
        $activity = new Activity();
        $activity->setMaxParticipants(1);

        $client = new Client();
        $booking = new Booking();
        $booking->setClient($client);
        $activity->addBooking($booking);

        $this->assertTrue($activity->isFull());
        $this->assertFalse($activity->hasFreePlaces());
        $this->assertEquals(0, $activity->getAvailablePlaces());
    }

    public function testClientsSigned(): void
    {
        $activity = new Activity();
        $activity->setMaxParticipants(10);

        $this->assertEquals(0, $activity->getClientsSigned());

        $client = new Client();
        $booking = new Booking();
        $booking->setClient($client);
        $activity->addBooking($booking);

        $this->assertEquals(1, $activity->getClientsSigned());
    }

    public function testIsClientAlreadyBooked(): void
    {
        $activity = new Activity();
        $activity->setMaxParticipants(10);

        $client = new Client();
        
        // Use reflection to set client ID
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($client, 1);

        $this->assertFalse($activity->isClientAlreadyBooked($client));

        $booking = new Booking();
        $booking->setClient($client);
        $activity->addBooking($booking);

        $this->assertTrue($activity->isClientAlreadyBooked($client));
    }
}
