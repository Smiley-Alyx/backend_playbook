<?php

declare(strict_types=1);

namespace App\Interfaces\Http\EventSubscriber;

use App\Interfaces\Http\Exception\IdempotencyKeyConflictHttpException;
use App\Interfaces\Http\Exception\IdempotencyRequestInProgressHttpException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class IdempotencySubscriber implements EventSubscriberInterface
{
    private const HEADER = 'Idempotency-Key';
    private const ATTR_KEY = 'idempotency_key';
    private const ATTR_HASH = 'idempotency_body_hash';
    private const ATTR_LOCK_KEY = 'idempotency_lock_key';
    private const ATTR_LOCK_TOKEN = 'idempotency_lock_token';

    public function __construct(
        private readonly CacheItemPoolInterface $idempotencyCache,
        private readonly \Redis $redis,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 20],
            KernelEvents::RESPONSE => ['onResponse', -50],
            KernelEvents::EXCEPTION => ['onException', -50],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $scope = $this->scope($request);

        if ($scope === null) {
            return;
        }

        $key = $request->headers->get(self::HEADER);

        if (!is_string($key) || $key === '') {
            return;
        }

        $rawBody = (string) $request->getContent();
        $bodyHash = hash('sha256', $rawBody);

        $cacheKey = $this->responseCacheKey($scope, $key);
        $item = $this->idempotencyCache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();

            if (!is_array($cached)) {
                $item->expiresAfter(0);
                $this->idempotencyCache->save($item);
                throw new BadRequestHttpException('Idempotency cache corrupted');
            }

            $cachedHash = $cached['body_hash'] ?? null;

            if (!is_string($cachedHash) || $cachedHash === '') {
                throw new BadRequestHttpException('Idempotency cache corrupted');
            }

            if (!hash_equals($cachedHash, $bodyHash)) {
                throw new IdempotencyKeyConflictHttpException('Idempotency key reuse with different payload');
            }

            $status = (int) ($cached['status'] ?? 200);
            $body = $cached['body'] ?? null;

            if (!is_string($body)) {
                throw new BadRequestHttpException('Idempotency cache corrupted');
            }

            $response = new JsonResponse(json_decode($body, true), $status);

            $requestId = $request->attributes->get(RequestIdSubscriber::ATTRIBUTE);
            if (is_string($requestId) && $requestId !== '') {
                $response->headers->set(RequestIdSubscriber::HEADER, $requestId);
            }

            $event->setResponse($response);
            return;
        }

        $lockKey = $this->lockKey($scope, $key);
        $token = bin2hex(random_bytes(16));
        $acquired = $this->redis->set($lockKey, $token, ['nx', 'ex' => 30]);

        if ($acquired !== true) {
            throw new IdempotencyRequestInProgressHttpException('Idempotency request in progress');
        }

        $request->attributes->set(self::ATTR_KEY, $key);
        $request->attributes->set(self::ATTR_HASH, $bodyHash);
        $request->attributes->set(self::ATTR_LOCK_KEY, $lockKey);
        $request->attributes->set(self::ATTR_LOCK_TOKEN, $token);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $scope = $this->scope($request);

        if ($scope === null) {
            return;
        }

        $key = $request->attributes->get(self::ATTR_KEY);
        $hash = $request->attributes->get(self::ATTR_HASH);

        if (!is_string($key) || $key === '' || !is_string($hash) || $hash === '') {
            return;
        }

        $response = $event->getResponse();

        $payload = [
            'status' => $response->getStatusCode(),
            'body' => $response->getContent() === false ? '' : (string) $response->getContent(),
            'body_hash' => $hash,
        ];

        $item = $this->idempotencyCache->getItem($this->responseCacheKey($scope, $key));
        $item->set($payload);
        $item->expiresAfter(86400);
        $this->idempotencyCache->save($item);

        $this->releaseLock($request);
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->scope($request) === null) {
            return;
        }

        $this->releaseLock($request);
    }

    private function scope(Request $request): ?string
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $route = $request->attributes->get('_route');

        if (!is_string($route) || $route === '') {
            return null;
        }

        return match ($route) {
            'orders_create' => 'orders.create',
            'orders_confirm_payment' => $this->scopedOrderRoute('orders.confirm_payment', $request),
            'orders_cancel' => $this->scopedOrderRoute('orders.cancel', $request),
            'orders_refund' => $this->scopedOrderRoute('orders.refund', $request),
            default => null,
        };
    }

    private function scopedOrderRoute(string $prefix, Request $request): ?string
    {
        $id = $request->attributes->get('id');

        if (!is_string($id) || $id === '') {
            return null;
        }

        return $prefix . ':' . $id;
    }

    private function responseCacheKey(string $scope, string $key): string
    {
        return 'idem_' . hash('sha256', $scope . '|' . $key);
    }

    private function lockKey(string $scope, string $key): string
    {
        return 'idempotency:lock:' . hash('sha256', $scope . '|' . $key);
    }

    private function releaseLock(Request $request): void
    {
        $lockKey = $request->attributes->get(self::ATTR_LOCK_KEY);
        $token = $request->attributes->get(self::ATTR_LOCK_TOKEN);

        if (!is_string($lockKey) || $lockKey === '' || !is_string($token) || $token === '') {
            return;
        }

        $existing = $this->redis->get($lockKey);

        if (is_string($existing) && hash_equals($existing, $token)) {
            $this->redis->del($lockKey);
        }
    }
}
