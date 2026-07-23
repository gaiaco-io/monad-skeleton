# TestingStrategy.md — Testing Source of Truth

## 1. Purpose

This document defines required testing standards for Monad Framework development.

Claude must follow this document when adding, changing, fixing or validating implementation.

## 2. Test Commands

Replace placeholders with actual project commands.

| Purpose | Command |
|---|---|
| Full test suite | `[command]` |
| Unit tests | `[command]` |
| Integration tests | `[command]` |
| API tests | `[command]` |
| Frontend build | `[command]` |
| Lint/format check | `[command]` |
| Schema validation | `[command]` |

## 3. Required Tests by Change Type

| Change Type | Required Tests |
|---|---|
| Business logic | Unit + integration tests |
| Controller/route | Request/response tests |
| API contract | API contract tests |
| Database schema | Migration/schema validation tests |
| Permissions | Allowed/denied access tests |
| UI view | Rendering or browser smoke tests where available |
| JavaScript behaviour | JS/browser tests where available |
| Deployment script | Dry-run or staging verification |

## 4. Seed Data Rules

1. Seed data must be realistic enough to validate business logic.
2. Seed data must not contain real personal data, credentials, secrets, or production data.
3. Seed data must align with `docs/DDL.sql`.
4. Test data must include positive and negative cases.

## 5. Database Test Rules

1. Test tables and columns must match `docs/DDL.sql`.
2. Do not create tests relying on undocumented columns.
3. Migration tests must prove forward migration works.
4. Rollback tests should be included where practical.
5. Destructive database commands must never target production.

## 6. API Test Rules

API tests must verify:

1. Method and path.
2. Request validation.
3. Authentication requirement.
4. Permission requirement.
5. Success response structure.
6. Error response structure.
7. Status codes.
8. Edge cases.

## 7. Permission Test Rules

Permission tests must verify:

1. Unauthenticated access is rejected.
2. Authorised role is allowed.
3. Unauthorised role is rejected.
4. Ownership/tenant boundaries are enforced, if applicable.

## 8. UI Smoke Test Rules

Where browser testing is available, verify:

1. Page loads without server error.
2. Main UI elements render.
3. Forms submit expected data.
4. Validation errors appear correctly.
5. Permission-restricted UI is hidden or disabled where required.
6. Backend still enforces permissions regardless of UI visibility.

## 9. Definition of Passing

A task is not complete until:

1. Required tests pass.
2. Relevant manual or smoke verification is completed where automated tests are unavailable.
3. Claude reports exact commands run.
4. Claude reports known gaps honestly.
