# ADR 0002: Request ID Propagation and Structured Logging

## Status

Accepted

## Context

Debugging distributed systems (or even a single service behind a reverse proxy) is significantly easier when every request can be traced end-to-end.

We want:

- request/response correlation
- easy filtering in container logs
- consistent fields attached to application and access logs

## Decision

1) The API uses `X-Request-Id` as the correlation header.

- If the client provides it, it is propagated.
- Otherwise the service generates a request id.
- The service includes `X-Request-Id` in responses.
- The request id is stored in request attributes so it is accessible throughout the HTTP pipeline.

2) In development, logs are formatted as JSON.

3) A logging processor injects request-scoped context fields into log records:

- `request_id`
- `route`
- `http_method`
- `path`

4) An access-log subscriber logs every HTTP request with duration and status.

## Consequences

- Support/debug workflows can start from a client-visible `request_id`.
- JSON logs are easier to parse and filter.
- The service must ensure `request_id` is included even for error responses.
