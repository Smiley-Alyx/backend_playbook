<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Handler;

use App\Infrastructure\Redis\RedisClient;
use App\Infrastructure\Queue\Message\ExampleJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ExampleJobHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private RedisClient $redis,
    )
    {
    }

    public function __invoke(ExampleJob $job): void
    {
        $attemptKey = 'example_job:attempt:' . \sha1($job->message);
        $deliveryAttempt = (int) $this->redis->incr($attemptKey);
        $this->redis->expire($attemptKey, 3600);

        $this->logger->warning('ExampleJob received', [
            'message' => $job->message,
            'deliveryAttempt' => $deliveryAttempt,
        ]);

        if ($deliveryAttempt <= 2) {
            throw new \RuntimeException('ExampleJob forced failure for retry');
        }

        $this->logger->warning('ExampleJob done', [
            'message' => $job->message,
            'deliveryAttempt' => $deliveryAttempt,
        ]);

        $this->redis->del($attemptKey);
    }
}
