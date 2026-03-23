<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    public function __construct(private Connection $db)
    {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    #[OA\Get(
        path: '/health',
        tags: ['System'],
        summary: 'Health check',
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 503, description: 'Degraded'),
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

        return new JsonResponse(
            ['status' => $status, 'checks' => ['db' => $dbOk]],
            $dbOk ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
