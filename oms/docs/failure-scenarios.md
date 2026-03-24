# OMS Failure Scenarios

This document describes typical failure scenarios and the expected behavior of the OMS.

## General principles

- Prefer predictable failures over partial/undefined behavior.
- Return a unified error envelope for any HTTP error.
- Include `request_id` in errors and logs to enable correlation.
- Ensure write endpoints are safe to retry (idempotency).

## Scenario: Invalid JSON request body

- **Symptoms**:
  - Client sends malformed JSON.
- **Expected behavior**:
  - Return `400 Bad Request`.
  - Unified error envelope.
  - `request_id` present.
- **How to debug**:
  - Use `request_id` to find access log and error log line.

## Scenario: Validation failed

- **Symptoms**:
  - Client provides JSON with missing/invalid fields.
- **Expected behavior**:
  - Return `422 Unprocessable Entity`.
  - `error.code = VALIDATION_FAILED`.
  - `error.details` contains normalized `{field, message}` items.
- **How to debug**:
  - Compare fields in `error.details` with request payload.

## Scenario: PostgreSQL unavailable

- **Symptoms**:
  - Connection refused / timeouts.
  - Doctrine errors.
- **Expected behavior**:
  - Return `503 Service Unavailable` or `500 Internal Server Error` depending on how the exception is mapped.
  - Unified error envelope.
  - Log the exception with `request_id`.
- **How to debug**:
  - Confirm Postgres container is healthy.
  - Locate logs by `request_id`.

## Scenario: Redis unavailable

Redis is used for idempotency and may be used by async transports.

- **Symptoms**:
  - Timeouts / connection failures.
- **Expected behavior**:
  - The request fails fast with a unified error envelope.
  - The error is logged with `request_id`.
- **Operational notes**:
  - If Redis is required for idempotency, write endpoints should not silently bypass it.

## Scenario: Duplicate request / client retry

- **Symptoms**:
  - Client retries the same write request after a timeout.
- **Expected behavior**:
  - If client provided the same `Idempotency-Key`, the API returns the same outcome.
  - If no idempotency key is provided, the client may create duplicates.

## Scenario: Domain/application error

- **Symptoms**:
  - Invalid state transition (e.g. cancel after refund).
- **Expected behavior**:
  - Return a domain-specific HTTP status (often `409 Conflict` or `400`) depending on mapping.
  - Unified error envelope with stable `error.code`.

## Scenario: Async job retries (Messenger)

- **Symptoms**:
  - Handler throws, message is retried according to transport retry strategy.
- **Expected behavior**:
  - Retries happen automatically.
  - Logs show delivery attempts.
- **How to debug**:
  - Inspect worker logs.
  - Search by message id or `request_id` if propagated into the message (if implemented).

## Scenario: Health degradation

- **Symptoms**:
  - One of dependencies is degraded/unavailable.
- **Expected behavior**:
  - Health endpoint returns `503` when unhealthy.
  - Response uses the unified error envelope.
  - Details include dependency checks.

## Scenario: Unexpected exception

- **Symptoms**:
  - Bug or unhandled edge case.
- **Expected behavior**:
  - Return a unified error envelope.
  - Log stack trace with `request_id`.
  - Do not leak internal details to the client.
