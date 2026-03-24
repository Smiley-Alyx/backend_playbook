# OMS Security

## Goals

- Keep the HTTP surface predictable and safe.
- Avoid leaking secrets/PII in logs and error responses.
- Ensure input validation and consistent error handling.

## Input validation

- All external input must be validated in the HTTP layer.
- Prefer explicit type checks for JSON fields (string/int/bool/array) before using values.
- Validation failures must return the unified error envelope with `422`.

## Unified errors

- Do not expose stack traces or internal exception messages to clients.
- Use stable error codes in `error.code`.
- Include `request_id` for correlation, not internal details.

## Authentication and authorization

- This sample service does not implement auth.
- In a real deployment, protect the API with authentication (e.g. JWT/OAuth2/mTLS) and enforce authorization per endpoint.

## Secrets management

- Do not commit secrets to the repository.
- Use environment variables for secrets and connection strings.
- Rotate credentials periodically.

## Logging and sensitive data

- Never log:
  - passwords, tokens, API keys
  - full payment card data
  - personal data that is not required for debugging
- When logging request/response data:
  - log only minimal fields
  - redact known sensitive keys

## Request correlation

- Use `X-Request-Id` for correlation.
- `request_id` is safe to expose and should be included in error responses.

## Idempotency

- For write operations, support idempotency via `Idempotency-Key`.
- Treat idempotency keys as untrusted input.
- Ensure idempotency keys do not allow data leaks across users once authentication is added.

## Rate limiting / abuse protection

- Consider adding rate limiting per client identity.
- Consider basic protections:
  - request size limits
  - timeouts
  - circuit breakers on external dependencies

## Dependency security

- Keep dependencies up to date.
- Run CI checks (static analysis, tests).
- Consider enabling vulnerability scanning (Composer audit) in CI.
