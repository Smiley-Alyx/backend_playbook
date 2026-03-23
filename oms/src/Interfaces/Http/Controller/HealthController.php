<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    public function __construct(private Connection $db)
    {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
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
