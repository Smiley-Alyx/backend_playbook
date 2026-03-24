<?php

declare(strict_types=1);

namespace App\Interfaces\Http\EventSubscriber;

use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestIdSubscriber implements EventSubscriberInterface
{
    public const HEADER = 'X-Request-Id';
    public const ATTRIBUTE = 'request_id';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 100],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $incoming = $request->headers->get(self::HEADER);
        $id = is_string($incoming) && $incoming !== '' ? $incoming : Uuid::uuid7()->toString();

        $request->attributes->set(self::ATTRIBUTE, $id);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $id = $request->attributes->get(self::ATTRIBUTE);

        if (is_string($id) && $id !== '') {
            $response->headers->set(self::HEADER, $id);
        }
    }
}
