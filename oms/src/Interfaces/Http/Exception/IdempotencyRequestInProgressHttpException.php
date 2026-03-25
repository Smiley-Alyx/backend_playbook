<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class IdempotencyRequestInProgressHttpException extends ConflictHttpException
{
}
