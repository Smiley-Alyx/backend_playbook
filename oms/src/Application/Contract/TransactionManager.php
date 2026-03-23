<?php

declare(strict_types=1);

namespace App\Application\Contract;

interface TransactionManager
{
    public function transactional(callable $operation): mixed;
}
