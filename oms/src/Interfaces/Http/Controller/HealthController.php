<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use App\Interfaces\Http\EventSubscriber\RequestIdSubscriber;
use App\Interfaces\Http\OpenApi\ErrorResponse;

final readonly class HealthController
{
    public function __construct(private Connection $db, private RequestStack $requestStack)
    {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    #[OA\Get(
        path: '/health',
        tags: ['System'],
        summary: 'Health check',
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(
                response: 503,
                description: 'Degraded',
                content: new OA\JsonContent(ref: ErrorResponse::class),
            ),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $dbOk = false;

        try {
            $this->db->executeQuery('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        $status = $dbOk ? 'ok' : 'degraded';

        if ($dbOk) {
            return new JsonResponse(
                ['status' => $status, 'checks' => ['db' => $dbOk]],
                Response::HTTP_OK,
            );
        }

        $request = $this->requestStack->getCurrentRequest();
        $requestId = $request?->attributes->get(RequestIdSubscriber::ATTRIBUTE);

        return new JsonResponse(
            [
                'error' => [
                    'code' => 'SERVICE_DEGRADED',
                    'message' => 'Service degraded',
                    'request_id' => is_string($requestId) && $requestId !== '' ? $requestId : null,
                    'details' => ['checks' => ['db' => $dbOk]],
                ],
            ],
            Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
