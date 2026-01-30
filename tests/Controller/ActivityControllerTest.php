<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActivityControllerTest extends WebTestCase
{
    public function testListActivities(): void
    {
        $client = static::createClient();
        $client->request('GET', '/activities');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertIsArray($response['data']);
    }

    public function testListActivitiesWithTypeFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/activities?type=Spinning');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        foreach ($response['data'] as $activity) {
            $this->assertEquals('Spinning', $activity['type']);
        }
    }

    public function testListActivitiesWithInvalidType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/activities?type=InvalidType');

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('code', $response);
        $this->assertEquals(21, $response['code']);
    }

    public function testListActivitiesWithPagination(): void
    {
        $client = static::createClient();
        $client->request('GET', '/activities?page=1&page_size=2');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertEquals(1, $response['meta']['page']);
        $this->assertEquals(2, $response['meta']['limit']);
    }

    public function testListActivitiesOnlyFree(): void
    {
        $client = static::createClient();

        $client->request('GET', '/activities?onlyfree=true');
        $responseFree = json_decode($client->getResponse()->getContent(), true);

        $client->request('GET', '/activities?onlyfree=false');
        $responseAll = json_decode($client->getResponse()->getContent(), true);

        $this->assertLessThanOrEqual(
            $responseAll['meta']['total-items'],
            $responseFree['meta']['total-items']
        );
    }

    public function testActivityHasRequiredFields(): void
    {
        $client = static::createClient();
        $client->request('GET', '/activities?page_size=1');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        if (!empty($response['data'])) {
            $activity = $response['data'][0];

            $this->assertArrayHasKey('id', $activity);
            $this->assertArrayHasKey('max_participants', $activity);
            $this->assertArrayHasKey('clients_signed', $activity);
            $this->assertArrayHasKey('type', $activity);
            $this->assertArrayHasKey('date_start', $activity);
            $this->assertArrayHasKey('date_end', $activity);
            $this->assertArrayHasKey('play_list', $activity);
        }
    }
}
