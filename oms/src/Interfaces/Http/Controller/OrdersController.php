<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\UseCase\CancelOrder\CancelOrder;
use App\Application\UseCase\CancelOrder\CancelOrderRequest;
use App\Application\UseCase\ConfirmPayment\ConfirmPayment;
use App\Application\UseCase\ConfirmPayment\ConfirmPaymentRequest;
use App\Application\UseCase\CreateOrder\CreateOrder;
use App\Application\UseCase\CreateOrder\CreateOrderRequest;
use App\Application\UseCase\GetOrder\GetOrder;
use App\Application\UseCase\GetOrder\GetOrderRequest;
use App\Application\UseCase\ListOrders\ListOrders;
use App\Application\UseCase\ListOrders\ListOrdersRequest;
use App\Application\UseCase\RefundOrder\RefundOrder;
use App\Application\UseCase\RefundOrder\RefundOrderRequest;
use App\Interfaces\Http\Exception\ValidationFailedHttpException;
use DateTimeImmutable;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class OrdersController
{
    public function __construct(
        private ValidatorInterface $validator,
        private CreateOrder $createOrder,
        private ConfirmPayment $confirmPayment,
        private CancelOrder $cancelOrder,
        private RefundOrder $refundOrder,
        private GetOrder $getOrder,
        private ListOrders $listOrders,
    ) {
    }

    #[Route('/orders', name: 'orders_create', methods: ['POST'])]
    #[OA\Post(
        path: '/orders',
        tags: ['Orders'],
        summary: 'Create order',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount_minor', 'currency'],
                properties: [
                    new OA\Property(property: 'amount_minor', type: 'integer', example: 1234),
                    new OA\Property(property: 'currency', type: 'string', example: 'USD'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '019d1a26-3008-7090-a91a-1ae43363f2dd'),
                                new OA\Property(property: 'status', type: 'string', example: 'created'),
                                new OA\Property(property: 'amountMinor', type: 'integer', example: 1234),
                                new OA\Property(property: 'currency', type: 'string', example: 'USD'),
                                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Invalid JSON'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);

        $violations = $this->validator->validate($payload, new Assert\Collection([
            'amount_minor' => [new Assert\Required(), new Assert\Type('integer'), new Assert\Positive()],
            'currency' => [new Assert\Required(), new Assert\Type('string'), new Assert\Length(['min' => 3, 'max' => 3])],
        ], allowExtraFields: true));

        if ($violations->count() > 0) {
            $this->validationError($violations);
        }

        $result = $this->createOrder->execute(new CreateOrderRequest(
            amountMinor: (int) $payload['amount_minor'],
            currency: (string) $payload['currency'],
            now: new DateTimeImmutable(),
        ));

        return new JsonResponse(['data' => $result->order], Response::HTTP_CREATED);
    }

    #[Route('/orders/{id}', name: 'orders_get', methods: ['GET'])]
    #[OA\Get(
        path: '/orders/{id}',
        tags: ['Orders'],
        summary: 'Get order',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'amountMinor', type: 'integer'),
                                new OA\Property(property: 'currency', type: 'string'),
                                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Invalid id'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function get(string $id): JsonResponse
    {
        $result = $this->getOrder->execute(new GetOrderRequest(orderId: $id));

        return new JsonResponse(['data' => $result->order], Response::HTTP_OK);
    }

    #[Route('/orders', name: 'orders_list', methods: ['GET'])]
    #[OA\Get(
        path: '/orders',
        tags: ['Orders'],
        summary: 'List orders',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'object'),
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'total_pages', type: 'integer'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function list(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per_page', 20);
        $status = $request->query->get('status');

        $violations = $this->validator->validate([
            'page' => $page,
            'per_page' => $perPage,
        ], new Assert\Collection([
            'page' => [new Assert\Required(), new Assert\Type('integer'), new Assert\Positive()],
            'per_page' => [new Assert\Required(), new Assert\Type('integer'), new Assert\Range(['min' => 1, 'max' => 100])],
        ], allowExtraFields: true));

        if ($violations->count() > 0) {
            $this->validationError($violations);
        }

        $result = $this->listOrders->execute(new ListOrdersRequest(
            page: $page,
            perPage: $perPage,
            status: is_string($status) ? $status : null,
        ));

        $totalPages = (int) ceil($result->result->total / $result->result->perPage);

        return new JsonResponse([
            'data' => $result->result->items,
            'meta' => [
                'page' => $result->result->page,
                'per_page' => $result->result->perPage,
                'total' => $result->result->total,
                'total_pages' => max(1, $totalPages),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/orders/{id}/confirm-payment', name: 'orders_confirm_payment', methods: ['POST'])]
    #[OA\Post(
        path: '/orders/{id}/confirm-payment',
        tags: ['Orders'],
        summary: 'Confirm payment',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'Idempotency-Key', in: 'header', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 409, description: 'Invalid transition or idempotency conflict'),
        ],
    )]
    public function confirmPayment(string $id): JsonResponse
    {
        $result = $this->confirmPayment->execute(new ConfirmPaymentRequest(
            orderId: $id,
            now: new DateTimeImmutable(),
        ));

        return new JsonResponse(['data' => $result->order], Response::HTTP_OK);
    }

    #[Route('/orders/{id}/cancel', name: 'orders_cancel', methods: ['POST'])]
    #[OA\Post(
        path: '/orders/{id}/cancel',
        tags: ['Orders'],
        summary: 'Cancel order',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'Idempotency-Key', in: 'header', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 409, description: 'Invalid transition or idempotency conflict'),
        ],
    )]
    public function cancel(string $id): JsonResponse
    {
        $result = $this->cancelOrder->execute(new CancelOrderRequest(
            orderId: $id,
            now: new DateTimeImmutable(),
        ));

        return new JsonResponse(['data' => $result->order], Response::HTTP_OK);
    }

    #[Route('/orders/{id}/refund', name: 'orders_refund', methods: ['POST'])]
    #[OA\Post(
        path: '/orders/{id}/refund',
        tags: ['Orders'],
        summary: 'Refund order',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'Idempotency-Key', in: 'header', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 409, description: 'Invalid transition or idempotency conflict'),
        ],
    )]
    public function refund(string $id): JsonResponse
    {
        $result = $this->refundOrder->execute(new RefundOrderRequest(
            orderId: $id,
            now: new DateTimeImmutable(),
        ));

        return new JsonResponse(['data' => $result->order], Response::HTTP_OK);
    }

    private function decodeJson(Request $request): array
    {
        $raw = (string) $request->getContent();

        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        if (!is_array($decoded)) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        return $decoded;
    }

    private function validationError($violations): void
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = [
                'field' => (string) $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        throw new ValidationFailedHttpException($errors);
    }
}
