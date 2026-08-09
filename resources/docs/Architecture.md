# Architecture.md — Monad Framework Application Architecture

## 1. Purpose

This document defines the system architecture, module boundaries and code placement rules for a Monad Framework application.

Claude must use this document to decide where implementation belongs and must not invent architecture from other frameworks.

## 2. Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x using Monad Framework |
| Views | Server-rendered PHP views |
| Frontend styling | TailwindCSS 4 |
| Frontend scripting | Native JavaScript and/or jQuery according to project convention |
| Database | MySQL 8.x |
| Primary keys | UUID unless specified in `resources/docs/DDL.sql` |
| Bundling | Vite, if present in the project |
| Hosting | `[Server / cloud / deployment target]` |

## 3. Directory Structure

The real, current structure (kept in sync with `resources/docs/RepoMap.md`, the canonical
full tree — this is the abridged, application-focused view of the same thing):

```text
app/
├── Api                 <!-- capitalised to match `namespace App\Api;` — PSR-4; empty, no routes registered yet -->
├── client/
│   └── src/
│       ├── css/
│       └── js/
├── Controllers          <!-- capitalised — PSR-4 -->
├── Models                <!-- capitalised — PSR-4 -->
├── routes/               <!-- lowercase: files `require`d directly, not PSR-4-autoloaded -->
│   ├── api.php
│   ├── cli.php
│   └── web.php
├── Middlewares            <!-- capitalised — PSR-4; thin extensions of Monad\Clarity\Middlewares\* -->
├── Services                <!-- capitalised — PSR-4 -->
└── views/                   <!-- lowercase: resolved by View's own path logic, not PSR-4 -->
database/
public/
```

Unlike most of this document, this section is not a generic template — it names real,
current directories. If the structure changes, update this section (and `RepoMap.md`) in
the same commit, not "eventually."

## 4. Request Lifecycle

1. Request enters through public entry point.
2. Router resolves route.
3. Middleware/session/auth checks run.
4. Controller validates request.
5. Controller delegates business logic to service/model.
6. Service/model performs database operations.
7. Controller returns view or API response.

Update this section if the actual project lifecycle differs.

## 5. Controller Rules

1. Controllers should remain thin.
2. Controllers may handle request parsing, validation orchestration, permission checks, service calls and response selection.
3. Controllers should not contain large business workflows.
4. Controllers should not directly contain complex SQL unless existing project convention requires it.

## 6. Model Rules

1. Models must align with `resources/docs/DDL.sql`.
2. Models must not reference non-existent tables or columns.
3. Model relationships must match actual foreign keys or documented relationships.
4. Model methods should be focused and testable.

## 7. Service Rules

1. Business workflows should live in service classes where the project uses services.
2. Services should not render views.
3. Services should not read directly from `$_POST`, `$_GET`, or request globals unless existing framework convention requires it.
4. Services should return structured results or domain objects according to project convention.

## 8. Route Rules

1. Web routes must return views or redirects.
2. API routes must return responses matching `resources/docs/API_Contracts.md`.
3. Route naming must follow existing project convention.
4. Protected routes must enforce authentication and permissions server-side.

## 9. View Rules

1. Views must use server-rendered PHP according to project convention.
2. Views must follow `resources/docs/DesignTokens.md` and `resources/docs/UIUXRules.md`.
3. Views must not contain sensitive business logic.
4. Views must not bypass server-side permissions.

## 10. Authentication and Sessions

Describe the project’s actual session/auth mechanism here.

Rules:

1. Do not replace the auth mechanism unless explicitly instructed.
2. Do not use JWT/localStorage unless explicitly approved.
3. Permission enforcement must be server-side.

## 11. Error Handling

1. User-facing errors must be clear and safe.
2. Do not expose stack traces or sensitive internals.
3. API errors must match `resources/docs/API_Contracts.md`.
4. Log operational errors according to project convention.

## 12. Audit Logging

Document audit logging requirements and implementation location.

Any audit-related behaviour must match `resources/docs/PRD.md` and `resources/docs/DDL.sql`.

## 13. Prohibited Architecture Changes

Claude must not introduce the following unless explicitly approved:

1. New backend framework.
2. New frontend framework.
3. New database engine.
4. New ORM.
5. New auth architecture.
6. New queue/worker infrastructure.
7. New cloud dependency.
8. New package manager workflow.
9. New deployment architecture.
