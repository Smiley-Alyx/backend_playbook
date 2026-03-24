# OMS Testing Strategy

## Goals

- Keep the domain and use-cases predictable and refactor-friendly.
- Validate HTTP contracts (status codes, envelopes, validation errors).
- Catch integration issues with Postgres/Redis/Messenger configuration.

## Test levels

### Unit tests (Domain)

Scope:

- Entities and value objects.
- Invariants (e.g. allowed state transitions).

Characteristics:

- No Symfony kernel.
- No database.
- Fast and deterministic.

### Application tests (Use-cases)

Scope:

- Use-cases: create, confirm payment, cancel, refund, get, list.
- Transaction boundaries.
- Repository ports (use fakes/mocks when possible).

Characteristics:

- Prefer in-memory fakes for repositories.
- If a real database is required, treat as integration.

### HTTP API tests (Contract)

Scope:

- Request/response envelopes:
  - success: `{"data": ...}`
  - error: `{"error": {"code": ..., "message": ..., "request_id": ..., "details"?: ...}}`
- Validation errors:
  - status `422`
  - `error.code = VALIDATION_FAILED`
  - `error.details` contains normalized field paths
- Correlation id:
  - `X-Request-Id` response header exists
  - `error.request_id` is present

Characteristics:

- Boot Symfony kernel.
- Use the HTTP client provided by the framework test tooling.

### Integration tests

Scope:

- Doctrine mapping + persistence against Postgres.
- Redis-backed behavior:
  - idempotency
  - counters/locks (if used)
- Messenger transports configuration.

Characteristics:

- Run against real dependencies via Docker Compose.

## What we prioritize

- Correctness of business rules (domain invariants).
- Stable and well-documented API contracts.
- Unified errors and debuggability via `request_id`.

## Running tests

The project exposes composer scripts.

Typical commands:

- `composer test`
- `composer ci` (runs static analysis + tests)

## Notes

- When adding a new endpoint, add at least one HTTP API test covering:
  - happy path
  - validation failure
  - one representative domain/application error
