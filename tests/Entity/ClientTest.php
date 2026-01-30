<?php

namespace App\Tests\Entity;

use App\Entity\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClientConstants(): void
    {
        $this->assertEquals('standard', Client::TYPE_STANDARD);
        $this->assertEquals('premium', Client::TYPE_PREMIUM);
    }

    public function testDefaultClientType(): void
    {
        $client = new Client();

        $this->assertEquals('standard', $client->getType());
    }

    public function testIsStandardUser(): void
    {
        $client = new Client();
        $client->setType(Client::TYPE_STANDARD);

        $this->assertTrue($client->isStandardUser());
        $this->assertFalse($client->isPremiumUser());
    }

    public function testIsPremiumUser(): void
    {
        $client = new Client();
        $client->setType(Client::TYPE_PREMIUM);

        $this->assertTrue($client->isPremiumUser());
        $this->assertFalse($client->isStandardUser());
    }

    public function testClientSettersAndGetters(): void
    {
        $client = new Client();
        $client->setName('Test User');
        $client->setEmail('test@example.com');

        $this->assertEquals('Test User', $client->getName());
        $this->assertEquals('test@example.com', $client->getEmail());
    }
}
