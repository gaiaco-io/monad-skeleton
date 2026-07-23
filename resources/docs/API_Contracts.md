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

### 5.1 `[Endpoint Name]`

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
