# Order Management Service (OMS) Architecture

## Goals

- Predictable and testable code by splitting responsibilities into layers.
- Clear boundaries between the domain model and application use-cases.
- Observability (request/correlation id, structured logs).
- Unified HTTP API error format.
- Reliable async processing via a queue.

## Context and external dependencies

- **HTTP API**: Symfony Controllers (Interfaces layer).
- **Database**: PostgreSQL (Doctrine ORM).
- **Cache / idempotency / counters**: Redis.
- **Queue**: Symfony Messenger + Redis transport.

## Layers and dependencies

Principle: dependencies point “inwards”, from outer layers to inner layers.

- **Domain (`src/Domain`)**
  - Business model: entities / value objects / invariants.
  - Domain logic has no dependency on Symfony/Doctrine/Redis.

- **Application (`src/Application`)**
  - Use-cases: `CreateOrder`, `ConfirmPayment`, `CancelOrder`, `RefundOrder`, `GetOrder`, `ListOrders`.
  - Ports (interfaces) for infrastructure concerns: repositories, transactions, etc.
  - DTOs used as use-case outputs (`OrderView`, `OrderListResult`).

- **Infrastructure (`src/Infrastructure`)**
  - Port implementations: Doctrine repositories, queue handlers (Messenger), logging.
  - Technical integrations with Redis/Postgres.

- **Interfaces (`src/Interfaces`)**
  - Inbound adapters: HTTP controllers, console commands, HTTP event subscribers.
  - HTTP response shaping, input validation, mapping exceptions to the unified error envelope.

## Main flows (data flow)

### 1) HTTP: create order

- `OrdersController::create()`
  - Decodes JSON.
  - Validates input fields.
  - Calls `CreateOrder::execute()`.
  - Returns `201` with `data`.

### 2) HTTP: change order state

- `confirm-payment`, `cancel`, `refund`:
  - Controller calls the corresponding use-case.
  - Domain/application errors are mapped to the unified error envelope.

### 3) HTTP: get and list

- `get` / `list`:
  - Controller calls a use-case.
  - Returns `data` (+ `meta` for lists).

### 4) Queue (async job example)

- `DispatchExampleJobCommand` sends an `ExampleJob` message to the async transport.
- `ExampleJobHandler` processes the message and demonstrates retry behavior.

## Validation and unified error format

The HTTP layer validates inputs and converts failures into a unified error envelope:

- Envelope:
  - `{"error": {"code": string, "message": string, "request_id": string|null, "details"?: any}}`
- Validation errors:
  - `error.code = VALIDATION_FAILED`
  - `error.details` is a list of `{field, message}` items.

The central place for mapping exceptions: `ApiExceptionSubscriber`.

## Observability

- **Request ID**
  - `RequestIdSubscriber` generates/propagates `X-Request-Id` and stores it in request attributes.
  - `ApiExceptionSubscriber` and access logging include `request_id`.

- **Logging**
  - JSON logs in dev (Monolog + request-context processor).
  - Dedicated channel for API/access logs.

## Transactions

Use-cases use `TransactionManager::transactional()` for atomic changes.

## Idempotency

HTTP endpoints support idempotency via `Idempotency-Key` (implementation is backed by Redis).

## API documentation (OpenAPI)

- OpenAPI is generated using swagger-php attributes and NelmioApiDoc.
- Errors are documented using reusable schemas:
  - `ErrorResponse`
  - `ValidationErrorResponse`

## Key conventions

- Domain types do not depend on infrastructure.
- Use-cases return typed response DTOs.
- Any HTTP error is returned in a unified format.
