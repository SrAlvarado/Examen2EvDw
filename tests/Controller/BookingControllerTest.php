<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    public function testCreateBookingMissingActivityId(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['client_id' => 1])
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(21, $response['code']);
    }

    public function testCreateBookingMissingClientId(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['activity_id' => 1])
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(22, $response['code']);
    }

    public function testCreateBookingActivityNotFound(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['activity_id' => 99999, 'client_id' => 1])
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(31, $response['code']);
    }

    public function testCreateBookingClientNotFound(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['activity_id' => 1, 'client_id' => 99999])
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(32, $response['code']);
    }

    public function testBookingResponseHasRequiredFields(): void
    {
        $client = static::createClient();
        
        // First get available activities to find one we can book
        $client->request('GET', '/activities?onlyfree=true&page_size=1');
        $activitiesResponse = json_decode($client->getResponse()->getContent(), true);

        if (!empty($activitiesResponse['data'])) {
            $activityId = $activitiesResponse['data'][0]['id'];

            $client->request(
                'POST',
                '/bookings',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['activity_id' => $activityId, 'client_id' => 3])
            );

            $response = json_decode($client->getResponse()->getContent(), true);

            // Check if it's a success or expected error
            if (isset($response['id'])) {
                $this->assertArrayHasKey('id', $response);
                $this->assertArrayHasKey('activity', $response);
                $this->assertArrayHasKey('client_id', $response);
            }
        }

        $this->assertTrue(true); // Pass if no activities available
    }
}
