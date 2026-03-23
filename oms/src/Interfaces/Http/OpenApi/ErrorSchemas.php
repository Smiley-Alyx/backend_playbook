<?php

declare(strict_types=1);

namespace App\Interfaces\Http\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorObject',
    type: 'object',
    required: ['code', 'message', 'request_id'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'VALIDATION_FAILED'),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(property: 'request_id', type: 'string', example: '019d1b78-ac5c-7159-b07f-8815b196a79d'),
    ],
)]
final class ErrorObject
{
}

#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    required: ['error'],
    properties: [
        new OA\Property(property: 'error', ref: '#/components/schemas/ErrorObject'),
    ],
)]
final class ErrorResponse
{
}

#[OA\Schema(
    schema: 'ValidationErrorDetail',
    type: 'object',
    required: ['field', 'message'],
    properties: [
        new OA\Property(property: 'field', type: 'string', example: 'amount_minor'),
        new OA\Property(property: 'message', type: 'string', example: 'This field is missing.'),
    ],
)]
final class ValidationErrorDetail
{
}

#[OA\Schema(
    schema: 'ValidationErrorObject',
    type: 'object',
    required: ['code', 'message', 'request_id', 'details'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'VALIDATION_FAILED'),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(property: 'request_id', type: 'string', example: '019d1b78-ac5c-7159-b07f-8815b196a79d'),
        new OA\Property(property: 'details', type: 'array', items: new OA\Items(ref: '#/components/schemas/ValidationErrorDetail')),
    ],
)]
final class ValidationErrorObject
{
}

#[OA\Schema(
    schema: 'ValidationErrorResponse',
    type: 'object',
    required: ['error'],
    properties: [
        new OA\Property(property: 'error', ref: '#/components/schemas/ValidationErrorObject'),
    ],
)]
final class ValidationErrorResponse
{
}
