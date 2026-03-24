# OMS API Guidelines

## Overview

This document describes the conventions used by the OMS HTTP API.

## Content type

- Requests and responses use JSON.
- Clients should send:
  - `Content-Type: application/json`

## Correlation / Request ID

- The API supports request correlation via:
  - `X-Request-Id`
- If the client sends `X-Request-Id`, it will be propagated.
- If not provided, the server generates one.
- The server includes `X-Request-Id` in every response.

## Success envelope

Successful responses use the `data` envelope:

- `{"data": ...}`

Examples:

- `201 Created`:
  - `{"data": {"id": "...", ...}}`
- `200 OK` (list):
  - `{"data": [...], "meta": {...}}`

## Error envelope (unified)

All error responses use a unified envelope:

- `{"error": {"code": string, "message": string, "request_id": string|null, "details"?: any}}`

Notes:

- `request_id` is included to simplify log correlation.
- `details` is optional and may contain structured data.

### Validation errors

Validation failures return:

- HTTP status: `422 Unprocessable Entity`
- `error.code`: `VALIDATION_FAILED`
- `error.details`: list of items:
  - `{ "field": string, "message": string }`

Field paths are normalized for readability.

## Idempotency

Some endpoints support idempotency via the `Idempotency-Key` request header.

- Header:
  - `Idempotency-Key: <string>`
- The same key should be reused for retries of the same logical operation.
- If the same operation is repeated with the same key, the API should return the same outcome.

## Pagination

List endpoints use offset-style pagination with `page` and `per_page`.

- Query params:
  - `page` (default: `1`, min: `1`)
  - `per_page` (default: `20`, min: `1`, max: `100`)

Response:

- `meta.page`
- `meta.per_page`
- `meta.total`
- `meta.total_pages`

## Filtering

List endpoints may support filters via query parameters.

Example:

- `status`: order status string

## Status codes

The API uses standard HTTP status codes.

Common ones:

- `200 OK`
- `201 Created`
- `400 Bad Request` (invalid input, invalid JSON)
- `404 Not Found`
- `409 Conflict` (invalid transition / idempotency conflict)
- `422 Unprocessable Entity` (validation)
- `500 Internal Server Error`

## OpenAPI

- OpenAPI is generated using swagger-php attributes and NelmioApiDoc.
- Error responses reference reusable schemas:
  - `ErrorResponse`
  - `ValidationErrorResponse`
