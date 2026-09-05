# API_Contracts.md — API Source of Truth

## 1. Purpose

This document defines the API contract for the application.

Claude must not add, remove, rename, or reinterpret API routes, request fields, response fields, status codes, or error formats unless the user explicitly requests it and this document is updated.

## 2. Global API Rules

1. API implementation must follow Monad Framework project conventions.
2. Request validation must be server-side.
3. Permission checks must be server-side.
4. Response structure must match this document.
5. Error responses must be consistent across endpoints.
6. Do not expose secrets, internal stack traces, or sensitive infrastructure details.

## 3. Authentication

| Requirement | Behaviour |
|---|---|
| Auth mechanism | `[Describe session/API auth mechanism]` |
| Required headers | `[Headers]` |
| CSRF requirement | `[Yes/No/Rules]` |
| Unauthenticated response | `[Status + payload]` |

## 4. Standard Response Format

### 4.1 Success Response

```json
{
  "success": true,
  "data": {},
  "message": null
}
```

### 4.2 Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message",
    "details": {}
  }
}
```

## 5. Endpoint Catalogue

### 5.1 Payment gateway callback

| Field | Value |
|---|---|
| Method | `POST` |
| Path | `/webhooks/checkout` |
| Auth Required | No session or token — see below |
| Required Role/Permission | None |
| Controller | `App\Controllers\CheckoutCallbackController::receive` |
| Related Tables | `checkout_transactions`, `checkout_transaction_statuses`, `checkout_subscriptions` (created by `php mitosis checkout:install`, not by `DDL.sql`) |

Inbound only: the payment gateway calls this, no client of this application does. It is listed
here because §2 makes this document the record of every route, not because an application
developer writes against it.

**It does not use §4's response envelope, and that is deliberate.** The gateway defines this
contract, not this application: Stripe and Paddle read the status code and nothing else, retrying
any non-2xx with backoff. A `{"success": …}` body would be written for a reader that does not
exist, while the status code — the part that actually decides whether the gateway comes back —
did the work. Response bodies are short fixed strings for a human reading the gateway's
dashboard, and deliberately carry no internal detail (§10.6 of `CLAUDE.md`).

**Authentication is the signature, not a session.** A gateway holds no CSRF token and no session,
so the route carries no `Csrf`, `Authentication`, or `Jsonify` middleware. What stands in their
place is an HMAC over the exact request bytes, keyed by a secret only this application and the
gateway hold — which is why the body must reach the parser unparsed. The controller's docblock
sets out each omission in full.

#### Request Payload

The gateway's own event envelope, verbatim — `checkout.session.*` from Stripe, or
`transaction.*` and `subscription.*` from Paddle. This application defines no schema for it and
validates nothing by hand: the signature check and Clarity's parsers are the validation.

#### Responses

| Status | Condition |
|---:|---|
| 204 | Verified and applied, or verified and already applied (a redelivery, or an event older than the state stored). Both are acknowledged — anything else starts a retry storm. |
| 400 | The parser refused it: the signature was absent, malformed, stale or did not verify, **or** the bytes verified but were not an event this adapter can interpret. Retrying the same bytes fails identically. |
| 404 | Verified, but the transaction ledger holds no transaction for the reference named. Worth retrying — the usual cause is a race with the `open()` that records the checkout. |

### 5.2 `[Endpoint Name]`

| Field | Value |
|---|---|
| Method | `GET/POST/PUT/PATCH/DELETE` |
| Path | `/api/...` |
| Auth Required | Yes/No |
| Required Role/Permission | `[Permission]` |
| Controller | `[Controller class/file]` |
| Related Tables | `[Tables from resources/docs/DDL.sql]` |

#### Request Payload

```json
{
  "field": "value"
}
```

#### Validation Rules

| Field | Rule | Required | Error Code |
|---|---|---:|---|
| `field` | `[Rule]` | Yes | `VALIDATION_ERROR` |

#### Success Response

```json
{
  "success": true,
  "data": {}
}
```

#### Error Responses

| Status | Code | Condition |
|---:|---|---|
| 400 | `VALIDATION_ERROR` | Invalid request payload |
| 401 | `UNAUTHENTICATED` | User is not logged in |
| 403 | `FORBIDDEN` | User lacks permission |
| 404 | `NOT_FOUND` | Resource not found |
| 500 | `SERVER_ERROR` | Unexpected server error |

## 6. Contract Change Rules

Any API change must update:

1. This document.
2. Route/controller implementation.
3. Request validation.
4. Response tests.
5. Permission tests, if access-controlled.
6. Client-side usage, if applicable.
