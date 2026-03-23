<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrdersApiTest extends WebTestCase
{
    public function testCreateOrderIsIdempotent(): void
    {
        $client = static::createClient();

        $payload = ['amount_minor' => 1234, 'currency' => 'usd'];

        $client->request(
            method: 'POST',
            uri: '/orders',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_IDEMPOTENCY_KEY' => 'phpunit-key-1',
            ],
            content: json_encode($payload),
        );

        self::assertResponseStatusCodeSame(201);

        $first = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($first);
        self::assertArrayHasKey('data', $first);
        self::assertIsArray($first['data']);
        self::assertArrayHasKey('id', $first['data']);

        $id1 = (string) $first['data']['id'];
        self::assertNotSame('', $id1);

        $client->request(
            method: 'POST',
            uri: '/orders',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_IDEMPOTENCY_KEY' => 'phpunit-key-1',
            ],
            content: json_encode($payload),
        );

        self::assertResponseStatusCodeSame(201);

        $second = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($second);
        self::assertArrayHasKey('data', $second);
        self::assertIsArray($second['data']);
        self::assertArrayHasKey('id', $second['data']);

        $id2 = (string) $second['data']['id'];
        self::assertSame($id1, $id2);
    }
}
