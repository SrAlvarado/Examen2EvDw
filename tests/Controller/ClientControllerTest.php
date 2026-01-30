<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClientControllerTest extends WebTestCase
{
    public function testShowClientBasicInfo(): void
    {
        $client = static::createClient();
        $client->request('GET', '/clients/1');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('type', $response);
    }

    public function testShowClientWithBookings(): void
    {
        $client = static::createClient();
        $client->request('GET', '/clients/1?with_bookings=true');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('activities_booked', $response);
        $this->assertIsArray($response['activities_booked']);
    }

    public function testShowClientWithStatistics(): void
    {
        $client = static::createClient();
        $client->request('GET', '/clients/1?with_statistics=true');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('activity_statistics', $response);
    }

    public function testShowClientNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/clients/99999');

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('code', $response);
        $this->assertEquals(44, $response['code']);
    }

    public function testClientTypeIsValid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/clients/1');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertContains($response['type'], ['standard', 'premium']);
    }
}
