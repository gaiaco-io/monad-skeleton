# CLAUDE.md — Monad Framework Development Rules

## 1. Purpose

This file defines the mandatory development rules for projects built using Monad Framework.

Claude Code must treat this file as the project constitution. Detailed product, schema, API, UI, testing and release requirements are stored in the `resources/docs/` directory.

## 2. Source-of-Truth Hierarchy

When documents, code, or instructions conflict, follow this priority order:

1. The user's latest explicit instruction in the current task.
2. `resources/docs/DDL.sql` for database structure.
3. `resources/docs/API_Contracts.md` for API request and response behaviour.
4. `resources/docs/PermissionsMatrix.md` for access control.
5. `resources/docs/PRD.md` for product scope and business requirements.
6. `resources/docs/Architecture.md` for system structure and code placement.
7. `resources/docs/DesignTokens.md` for visual design tokens.
8. `resources/docs/UIUXRules.md` for UI/UX behaviour and interface rules.
9. `resources/docs/TestingStrategy.md` for testing requirements.
10. `resources/docs/ReleasePolicy.md` for Git, deployment and release rules.
11. Existing code conventions, only when they do not contradict the documents above.

If a requirement is missing, ambiguous, or contradictory, stop and report the ambiguity instead of inventing behaviour.

## 3. Monad Framework Rules

1. Do not modify Monad Framework core files unless the task explicitly requires framework-level changes.
2. Follow the existing Monad application structure.
3. Keep business logic inside application services, domain services, or models according to existing project conventions.
4. Keep controllers thin. Controllers should orchestrate request handling, validation, service calls and responses.
5. Use server-rendered PHP views unless the project documents explicitly require a different rendering approach.
6. Use TailwindCSS 4 according to `resources/docs/DesignTokens.md` and `resources/docs/UIUXRules.md`.
7. Use native JavaScript or jQuery only according to existing project conventions.
8. Use MySQL 8.x compatible SQL.
9. Use UUID primary keys unless `resources/docs/DDL.sql` states otherwise.
10. Do not introduce Laravel, Symfony, React, Vue, Next.js, Express, Prisma, Doctrine ORM, or other external framework assumptions unless explicitly approved.
11. Do not introduce new packages, services, queues, workers, cloud services, or infrastructure dependencies without explicit approval.
12. Do not use JWT, localStorage authentication, or browser token storage unless explicitly approved.
13. Follow the existing session handling convention. If the project uses database-backed sessions, do not replace it with another authentication mechanism.

## 4. Repository Inspection Rules

Before editing or referencing existing functionality, inspect the repository.

Required checks before implementation:

1. Search for existing routes related to the task.
2. Search for existing controllers related to the task.
3. Search for existing models and services related to the task.
4. Search for existing views/components related to the task.
5. Search for existing tests related to the task.
6. Search `resources/docs/DDL.sql` before referencing any table or column.
7. Search `resources/docs/API_Contracts.md` before changing any API behaviour.
8. Search `resources/docs/PermissionsMatrix.md` before changing access-controlled behaviour.

Do not claim a route, table, controller, model, helper, config key, component, or service exists unless it has been found in the repository or documented source of truth.

## 5. Anti-Hallucination Rules

1. Do not invent tables, columns, routes, config keys, roles, permissions, workflows, business rules, services, UI states, or API payloads.
2. Do not infer missing product behaviour unless the user explicitly asks for a recommendation.
3. If a reasonable default is needed, mark it as a recommendation and explain why.
4. Do not create fallback behaviour that changes business logic.
5. Do not silently change scope to make implementation easier.
6. Do not modify tests to pass an incorrect implementation.
7. Do not remove failing tests unless the user explicitly approves and the reason is documented.
8. Do not use mock logic, fake integrations, hardcoded responses, hardcoded credentials, TODO placeholders, or skeleton code in production paths.
9. Do not hide uncertainty. Report blockers clearly.

## 6. Development Workflow

For every non-trivial task:

1. Read the relevant source-of-truth files.
2. Inspect the current repository state.
3. Produce a concise implementation plan.
4. Identify affected files, tables, routes, views, and tests.
5. Implement only the approved or requested scope.
6. Add or update tests.
7. Run relevant tests.
8. Run full tests before commit.
9. Summarise files changed, tests run, risks, and remaining work.

## 7. Database Rules

1. `resources/docs/DDL.sql` is the schema source of truth.
2. Do not add, remove, rename, or change tables/columns unless explicitly required by the task.
3. If schema changes are required, update both implementation and `resources/docs/DDL.sql`.
4. Migrations must be safe, repeatable where possible, and compatible with MySQL 8.x.
5. Do not perform destructive migrations on production without explicit human approval.
6. Do not assume soft-delete, audit columns, tenant IDs, status fields, or timestamps exist unless present in `resources/docs/DDL.sql`.
7. Validate all model properties, SQL queries, seeders and migrations against `resources/docs/DDL.sql`.

## 8. API Rules

1. `resources/docs/API_Contracts.md` is the API source of truth.
2. API routes, request payloads, response payloads, status codes and errors must match the documented contract.
3. Do not add undocumented fields to API responses unless explicitly requested.
4. Do not remove or rename documented fields unless explicitly requested.
5. Do not change authentication or authorisation behaviour without updating `resources/docs/API_Contracts.md` and `resources/docs/PermissionsMatrix.md`.

## 9. UI/UX Rules

1. `resources/docs/DesignTokens.md` defines colours, spacing, typography, radius, shadows and component tokens.
2. `resources/docs/UIUXRules.md` defines layout, behaviour, states, responsiveness and accessibility rules.
3. Do not introduce random colours, arbitrary shadows, inconsistent spacing, unrelated components, or unapproved design systems.
4. Use TailwindCSS 4 classes according to the project design tokens and conventions.
5. Do not redesign unrelated screens while implementing a feature.
6. UI changes must preserve usability on the target devices documented in `resources/docs/UIUXRules.md`.

## 10. Security Rules

1. Never commit secrets, credentials, API keys, tokens, private certificates, `.env` files, database dumps, or production logs.
2. Do not weaken authentication, session handling, CSRF protection, validation, permission checks, or audit logging.
3. Validate all user inputs server-side.
4. Escape output in server-rendered views unless existing safe rendering rules apply.
5. Permission checks must be enforced server-side, not only hidden in UI.
6. Do not expose internal IDs or sensitive operational details through API or UI unless documented.

## 11. Testing Rules

1. Follow `resources/docs/TestingStrategy.md`.
2. Add or update tests for every business logic change.
3. Add or update API tests for API changes.
4. Add or update permission tests for access-controlled changes.
5. Add or update schema validation tests for database changes.
6. Run relevant tests after implementation.
7. Run full required tests before commit.
8. If tests fail, fix the implementation unless the test is proven incorrect.

## 12. Git Rules

1. Work on feature branches only.
2. Do not commit directly to `main`, `master`, `production`, or release branches unless explicitly instructed.
3. Do not force-push.
4. Do not commit generated junk, temporary files, logs, caches, vendor folders, node_modules, database dumps, or secrets.
5. Commit only after required checks pass.
6. Commit messages must be concise and describe the actual change.

## 13. Deployment Rules

1. Claude may deploy to staging only when `resources/docs/ReleasePolicy.md` allows it and all required checks pass.
2. Claude must never deploy to production without explicit human approval.
3. Claude must never edit files directly inside live production directories.
4. Claude must never run destructive production database commands.
5. Every release must have a rollback path.

## 14. Task Completion Report

At task completion, provide:

1. Summary of changes.
2. Files changed.
3. Source-of-truth documents consulted.
4. Tests added or updated.
5. Tests run and result.
6. Schema/API/UI/permission changes, if any.
7. Known risks.
8. Recommended next step.
