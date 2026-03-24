# OMS Observability

## Goals

- Provide request correlation via `X-Request-Id`.
- Emit structured logs (JSON) in development.
- Produce HTTP access logs for every request.

## Request ID

### Header

- Incoming header: `X-Request-Id`
- Outgoing header: `X-Request-Id`

### Behavior

- If the client sends `X-Request-Id`, the API propagates it.
- If the client does not send it, the API generates one.
- The request id is stored on the request object (attributes) and reused by:
  - error responses (`error.request_id`)
  - access logs
  - application logs (via a Monolog processor)

### Recommended client behavior

- Always send `X-Request-Id`.
- Generate a unique value per logical request.

## Logging

### Structured logs in dev

In the dev environment logs are formatted as JSON to simplify parsing and filtering in Docker logs.

### Request context fields

Application logs include additional context fields:

- `request_id`
- `route`
- `http_method`
- `path`

These fields are injected via a Monolog processor.

### Access logs

For every HTTP request the API emits an access log entry with:

- `request_id`
- `route`
- `status`
- `duration_ms`

## Error correlation

All HTTP errors use a unified error envelope and include the `request_id`:

- `{"error": {"code": ..., "message": ..., "request_id": ..., "details"?: ...}}`

This allows you to:

- copy `request_id` from a client error response
- find the corresponding access log line
- find the application error/stack trace (if any)

## Where to look

### Docker logs

When running via Docker Compose, the simplest approach is to use container logs:

- application logs (including access logs)
- filtered by `request_id`

## Notes

- The central place that maps exceptions to HTTP error envelopes is the HTTP exception subscriber.
- The request id is generated/propagated by a dedicated request subscriber.
