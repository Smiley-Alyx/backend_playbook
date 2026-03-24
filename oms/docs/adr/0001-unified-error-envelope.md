# ADR 0001: Unified HTTP Error Envelope

## Status

Accepted

## Context

The OMS HTTP API exposes multiple endpoints and uses validation at the boundary.
Without a single, consistent error format, clients must implement endpoint-specific parsing and correlation becomes harder.

We also want to:

- return stable machine-readable error codes
- include a request correlation id for debugging
- be able to attach structured details (e.g. validation field errors)

## Decision

All non-2xx responses produced by the HTTP API MUST use a unified JSON envelope:

- `{"error": {"code": string, "message": string, "request_id": string|null, "details"?: any}}`

Validation failures:

- HTTP status: `422 Unprocessable Entity`
- `error.code = VALIDATION_FAILED`
- `error.details` contains a list of `{field, message}` items with normalized field paths

The central place for mapping exceptions to this envelope is a single HTTP exception subscriber.

## Consequences

- Clients can parse errors consistently across endpoints.
- Logs and support requests can reference `request_id`.
- Adding a new error type requires:
  - defining an error code
  - mapping it in the central subscriber
  - documenting it in OpenAPI
