# ADR 0003: Redis for Idempotency and Messenger Transport

## Status

Accepted

## Context

The OMS needs:

- idempotency for write endpoints to make retries safe
- asynchronous background processing examples with retry

Redis is already used by the service (cache/counters) and is well-suited for:

- storing idempotency keys with TTL
- implementing simple locking
- serving as a transport backend for Symfony Messenger

## Decision

1) Implement idempotency at the HTTP boundary using the `Idempotency-Key` header and a Redis-backed store.

2) Use Symfony Messenger with Redis transport for async message handling.

3) Demonstrate retry behavior in an example job handler.

## Consequences

- The system can safely handle client retries for write operations.
- Redis becomes a critical dependency for:
  - idempotency
  - async queue transport
- Operationally, Redis availability and persistence settings must match the required reliability.
