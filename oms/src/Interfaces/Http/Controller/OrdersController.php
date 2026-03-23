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
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function create(Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);

        $violations = $this->validator->validate($payload, new Assert\Collection([
            'amount_minor' => [new Assert\Required(), new Assert\Type('integer'), new Assert\Positive()],
            'currency' => [new Assert\Required(), new Assert\Type('string'), new Assert\Length(['min' => 3, 'max' => 3])],
        ], allowExtraFields: true));

        if ($violations->count() > 0) {
            return $this->validationError($violations);
        }

        $result = $this->createOrder->execute(new CreateOrderRequest(
            amountMinor: (int) $payload['amount_minor'],
            currency: (string) $payload['currency'],
            now: new DateTimeImmutable(),
        ));

        return new JsonResponse(['data' => $result->order], Response::HTTP_CREATED);
    }

    #[Route('/orders/{id}', name: 'orders_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $result = $this->getOrder->execute(new GetOrderRequest(orderId: $id));

        return new JsonResponse(['data' => $result->order], Response::HTTP_OK);
    }

    #[Route('/orders', name: 'orders_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per_page', 20);
        $status = $request->query->get('status');

        $result = $this->listOrders->execute(new ListOrdersRequest(
            page: $page,
            perPage: $perPage,
            status: is_string($status) ? $status : null,
        ));

        return new JsonResponse(['data' => $result->result], Response::HTTP_OK);
    }

    #[Route('/orders/{id}/confirm-payment', name: 'orders_confirm_payment', methods: ['POST'])]
    public function confirmPayment(string $id): JsonResponse
    {
        $result = $this->confirmPayment->execute(new ConfirmPaymentRequest(
            orderId: $id,
            now: new DateTimeImmutable(),
        ));

        return new JsonResponse(['data' => $result->order], Response::HTTP_OK);
    }

    #[Route('/orders/{id}/cancel', name: 'orders_cancel', methods: ['POST'])]
    public function cancel(string $id): JsonResponse
    {
        $result = $this->cancelOrder->execute(new CancelOrderRequest(
            orderId: $id,
            now: new DateTimeImmutable(),
        ));

        return new JsonResponse(['data' => $result->order], Response::HTTP_OK);
    }

    #[Route('/orders/{id}/refund', name: 'orders_refund', methods: ['POST'])]
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

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function validationError($violations): JsonResponse
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = [
                'field' => (string) $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return new JsonResponse([
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'Validation failed',
                'details' => $errors,
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
