# Order Management Service (Symfony)

Order Management Service built with **PHP 8.3+** and **Symfony 7**.

The service is designed around production constraints:

- Layered architecture (DDD-lite)
- Explicit domain invariants and state transitions
- Idempotent command handling for critical operations
- Async processing with retries
- Structured logging + correlation IDs
- Health checks
- Strong quality gates (tests + static analysis)

## Domain

`Order` is modeled as an aggregate with strict invariants.

- **Statuses**
  - `created`
  - `paid`
  - `cancelled`
  - `refunded`
  - `fulfilled`
- **State machine**
  - Transitions are validated; illegal transitions fail fast.

## HTTP API

- `POST /orders`
- `GET /orders/{id}`
- `GET /orders` (pagination + filtering)
- `POST /orders/{id}/confirm-payment` (idempotent)
- `POST /orders/{id}/cancel`
- `POST /orders/{id}/refund`
- `GET /health`

### OpenAPI / Swagger

- Swagger UI: `http://localhost:8080/api/docs/`
- OpenAPI JSON: `http://localhost:8080/api/docs.json`

![Swagger UI](docs/swagger.png)

## Local run (Docker)

```bash
docker compose up --build
```

- `nginx`: `http://localhost:8080`
- `postgres`: `localhost:5432`
- `redis`: `localhost:6379`

## Code organization

- `src/Domain` — aggregates, value objects, domain exceptions
- `src/Application` — use cases + DTOs, framework-agnostic
- `src/Infrastructure` — DB/Redis/queue implementations
- `src/Interfaces` — HTTP controllers, validation, response mapping

## Implemented features

- Layered architecture (Domain/Application/Infrastructure/Interfaces)
- Order aggregate with explicit invariants and state transitions
- Use-cases (Create/ConfirmPayment/Cancel/Refund/Get/List)
- REST API endpoints:
  - `POST /orders`
  - `GET /orders/{id}`
  - `GET /orders`
  - `POST /orders/{id}/confirm-payment`
  - `POST /orders/{id}/cancel`
  - `POST /orders/{id}/refund`
  - `GET /health`
- Unified error envelope for all API errors (including validation)
- OpenAPI documentation (Swagger UI + OpenAPI JSON)
- Correlation via `X-Request-Id`
- Structured JSON logs (dev) + HTTP access logs
- Idempotency via `Idempotency-Key`
- Async processing example with retries (Symfony Messenger + Redis)
- Quality gates:
  - PHPUnit tests
  - PHPStan (max level)

## Documentation

- Architecture: `oms/docs/architecture.md`
- API guidelines: `oms/docs/api-guidelines.md`
- Observability: `oms/docs/observability.md`
- Testing strategy: `oms/docs/testing-strategy.md`
- Security: `oms/docs/security.md`
- Failure scenarios: `oms/docs/failure-scenarios.md`
- ADRs: `oms/docs/adr/`
  - `oms/docs/adr/0001-unified-error-envelope.md`
  - `oms/docs/adr/0002-request-id-and-structured-logging.md`
  - `oms/docs/adr/0003-redis-for-idempotency-and-messenger-transport.md`

## License

All rights reserved.
