<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class ValidationFailedHttpException extends UnprocessableEntityHttpException
{
    /**
     * @param list<array{field: string, message: string}> $details
     */
    public function __construct(private readonly array $details)
    {
        parent::__construct('Validation failed');
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    public function details(): array
    {
        return $this->details;
    }
}
