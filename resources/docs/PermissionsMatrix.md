# PermissionsMatrix.md — Access Control Source of Truth

## 1. Purpose

This document defines roles, permissions and access-control behaviour.

Claude must use this document whenever implementing controllers, routes, API endpoints, views, navigation, buttons, forms or business logic that depends on user role or permission.

## 2. Global Permission Rules

1. Permission checks must be enforced server-side.
2. UI hiding is not a substitute for backend permission checks.
3. Do not invent roles or permissions.
4. Do not grant broader access than documented.
5. If a role’s permission is not documented, treat it as not allowed.
6. Changes to permissions must update this document and relevant tests.

## 3. Roles

| Role | Description | Notes |
|---|---|---|
| `[Role]` | `[Description]` | `[Notes]` |

## 4. Module Permission Matrix

| Role | Module | View | Create | Update | Delete | Export | Approve | Admin |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| `[Role]` | `[Module]` | No | No | No | No | No | No | No |

## 5. Route-Level Permissions

| Method | Path | Required Role / Permission | Notes |
|---|---|---|---|
| `GET` | `/example` | `[Permission]` | `[Notes]` |

## 6. API Permission Rules

| Endpoint | Required Role / Permission | Forbidden Behaviour |
|---|---|---|
| `[Endpoint]` | `[Permission]` | `[Behaviour]` |

## 7. UI Permission Rules

| UI Element | Visibility Rule | Server Enforcement Required |
|---|---|---:|
| `[Button / Link / Section]` | `[Rule]` | Yes |

## 8. Tenant / Ownership Rules

Use this section if the application is multi-tenant or ownership-scoped.

1. `[Rule]`
2. `[Rule]`

## 9. Permission Test Requirements

Any access-controlled feature must include tests for:

1. Allowed role can perform action.
2. Disallowed role cannot perform action.
3. Unauthenticated user is rejected.
4. Cross-tenant or cross-owner access is rejected, if applicable.
