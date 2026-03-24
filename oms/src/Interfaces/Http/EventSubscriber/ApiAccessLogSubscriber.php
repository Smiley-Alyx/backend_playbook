<?php

declare(strict_types=1);

namespace App\Interfaces\Http\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiAccessLogSubscriber implements EventSubscriberInterface
{
    private const STARTED_AT_ATTRIBUTE = '_access_log_started_at';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 0],
            KernelEvents::RESPONSE => ['onResponse', -100],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->attributes->set(self::STARTED_AT_ATTRIBUTE, microtime(true));
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $startedAt = $request->attributes->get(self::STARTED_AT_ATTRIBUTE);
        $durationMs = null;
        if (is_float($startedAt)) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        }

        $requestId = $request->attributes->get(RequestIdSubscriber::ATTRIBUTE);

        $this->logger->info('HTTP request', [
            'request_id' => is_string($requestId) ? $requestId : null,
            'http_method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'route' => is_string($request->attributes->get('_route')) ? $request->attributes->get('_route') : null,
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
        ]);
    }
}
