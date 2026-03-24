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
        $content = json_encode($payload, JSON_THROW_ON_ERROR);

        $client->request(
            method: 'POST',
            uri: '/orders',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_IDEMPOTENCY_KEY' => 'phpunit-key-1',
            ],
            content: $content,
        );

        self::assertResponseStatusCodeSame(201);

        $first = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($first);
        self::assertArrayHasKey('data', $first);
        self::assertIsArray($first['data']);
        self::assertArrayHasKey('id', $first['data']);

        $id1Raw = $first['data']['id'];
        self::assertIsString($id1Raw);
        $id1 = $id1Raw;
        self::assertNotSame('', $id1);

        $client->request(
            method: 'POST',
            uri: '/orders',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_IDEMPOTENCY_KEY' => 'phpunit-key-1',
            ],
            content: $content,
        );

        self::assertResponseStatusCodeSame(201);

        $second = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($second);
        self::assertArrayHasKey('data', $second);
        self::assertIsArray($second['data']);
        self::assertArrayHasKey('id', $second['data']);

        $id2Raw = $second['data']['id'];
        self::assertIsString($id2Raw);
        $id2 = $id2Raw;
        self::assertSame($id1, $id2);
    }
}
