<?php

declare(strict_types=1);

namespace App\Interfaces\Http\EventSubscriber;

use App\Application\Exception\OrderNotFound;
use App\Domain\Order\Exception\InvalidOrderTransition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }

    public function onException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        $request = $event->getRequest();

        $requestId = $request->attributes->get(RequestIdSubscriber::ATTRIBUTE);

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $code = 'INTERNAL_ERROR';
        $message = 'Internal error';

        if ($e instanceof OrderNotFound) {
            $status = Response::HTTP_NOT_FOUND;
            $code = 'ORDER_NOT_FOUND';
            $message = $e->getMessage();
        } elseif ($e instanceof InvalidOrderTransition) {
            $status = Response::HTTP_CONFLICT;
            $code = 'INVALID_ORDER_TRANSITION';
            $message = $e->getMessage();
        }

        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'request_id' => is_string($requestId) ? $requestId : null,
            ],
        ];

        $event->setResponse(new JsonResponse($payload, $status));
    }
}
