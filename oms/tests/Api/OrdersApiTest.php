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
        self::assertResponseHasHeader('X-Request-Id');

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
        self::assertResponseHasHeader('X-Request-Id');

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

    public function testIdempotencyKeyReuseWithDifferentPayloadReturnsConflict(): void
    {
        $client = static::createClient();

        $payload1 = json_encode(['amount_minor' => 1111, 'currency' => 'usd'], JSON_THROW_ON_ERROR);
        $client->request('POST', '/orders', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_IDEMPOTENCY_KEY' => 'phpunit-key-mismatch'], content: $payload1);
        self::assertResponseStatusCodeSame(201);

        $payload2 = json_encode(['amount_minor' => 2222, 'currency' => 'usd'], JSON_THROW_ON_ERROR);
        $client->request('POST', '/orders', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_IDEMPOTENCY_KEY' => 'phpunit-key-mismatch'], content: $payload2);
        self::assertResponseStatusCodeSame(409);

        $error = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($error);
        self::assertArrayHasKey('error', $error);
        self::assertIsArray($error['error']);
        self::assertSame('IDEMPOTENCY_KEY_CONFLICT', $error['error']['code'] ?? null);
    }

    public function testIdempotencyRequestInProgressReturnsConflict(): void
    {
        $client = static::createClient();

        $key = 'phpunit-key-in-progress';
        $lockKey = 'idempotency:lock:' . hash('sha256', 'orders.create' . '|' . $key);

        $container = static::getContainer();
        $redis = $container->get(\App\Infrastructure\Redis\RedisClient::class);
        self::assertInstanceOf(\App\Infrastructure\Redis\RedisClient::class, $redis);

        $redis->set($lockKey, 'token', 30);

        $payload = json_encode(['amount_minor' => 3333, 'currency' => 'usd'], JSON_THROW_ON_ERROR);
        $client->request('POST', '/orders', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_IDEMPOTENCY_KEY' => $key], content: $payload);
        self::assertResponseStatusCodeSame(409);

        $error = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($error);
        self::assertArrayHasKey('error', $error);
        self::assertIsArray($error['error']);
        self::assertSame('IDEMPOTENCY_REQUEST_IN_PROGRESS', $error['error']['code'] ?? null);
    }

    public function testListOrdersPaginationAndMeta(): void
    {
        $client = static::createClient();

        for ($i = 0; $i < 3; $i++) {
            $content = json_encode(['amount_minor' => 1000 + $i, 'currency' => 'usd'], JSON_THROW_ON_ERROR);
            $client->request('POST', '/orders', server: ['CONTENT_TYPE' => 'application/json'], content: $content);
            self::assertResponseStatusCodeSame(201);
        }

        $client->request('GET', '/orders?page=1&per_page=2');
        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($payload);

        self::assertArrayHasKey('data', $payload);
        self::assertIsArray($payload['data']);
        self::assertCount(2, $payload['data']);

        self::assertArrayHasKey('meta', $payload);
        self::assertIsArray($payload['meta']);
        self::assertSame(1, $payload['meta']['page'] ?? null);
        self::assertSame(2, $payload['meta']['per_page'] ?? null);

        $total = $payload['meta']['total'] ?? null;
        self::assertIsInt($total);
        self::assertGreaterThanOrEqual(3, $total);

        $totalPages = $payload['meta']['total_pages'] ?? null;
        self::assertIsInt($totalPages);
        self::assertGreaterThanOrEqual(1, $totalPages);
    }

    public function testListOrdersStatusFilterAndValidationErrors(): void
    {
        $client = static::createClient();

        $content = json_encode(['amount_minor' => 1234, 'currency' => 'usd'], JSON_THROW_ON_ERROR);
        $client->request('POST', '/orders', server: ['CONTENT_TYPE' => 'application/json'], content: $content);
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/orders?status=created');
        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('data', $payload);
        self::assertIsArray($payload['data']);
        self::assertNotEmpty($payload['data']);

        $client->request('GET', '/orders?status=not-a-status');
        self::assertResponseStatusCodeSame(422);

        $error = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($error);
        self::assertArrayHasKey('error', $error);
        self::assertIsArray($error['error']);
        self::assertSame('VALIDATION_FAILED', $error['error']['code'] ?? null);

        $client->request('GET', '/orders?per_page=1000');
        self::assertResponseStatusCodeSame(422);
    }
}
