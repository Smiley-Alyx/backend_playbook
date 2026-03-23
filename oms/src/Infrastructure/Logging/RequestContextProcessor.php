<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Interfaces\Http\EventSubscriber\RequestIdSubscriber;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RequestContextProcessor
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return $record;
        }

        $extra = $record->extra;

        $requestId = $request->attributes->get(RequestIdSubscriber::ATTRIBUTE);
        if (is_string($requestId) && $requestId !== '') {
            $extra['request_id'] = $requestId;
        }

        $route = $request->attributes->get('_route');
        if (is_string($route) && $route !== '') {
            $extra['route'] = $route;
        }

        $extra['http_method'] = $request->getMethod();
        $extra['path'] = $request->getPathInfo();

        return $record->with(extra: $extra);
    }
}
