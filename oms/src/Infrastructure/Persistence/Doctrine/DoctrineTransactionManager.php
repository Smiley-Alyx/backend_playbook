<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Contract\TransactionManager;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionManager implements TransactionManager
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function transactional(callable $operation): mixed
    {
        return $this->em->wrapInTransaction(function () use ($operation): mixed {
            $result = $operation();
            $this->em->flush();

            return $result;
        });
    }
}
