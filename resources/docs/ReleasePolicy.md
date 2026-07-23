# ReleasePolicy.md — Git, Deployment and Rollback Rules

## 1. Purpose

This document defines the release process for Monad Framework applications.

Claude must follow this document when committing, pushing, deploying or preparing a release.

## 2. Branch Rules

| Branch Type | Rule |
|---|---|
| `main` / `master` | Protected. No direct commits unless explicitly approved. |
| Feature branches | Required for implementation work. |
| Release branches | Use only if project workflow requires them. |
| Hotfix branches | Use for urgent fixes with explicit scope. |

Feature branch naming convention:

```text
feature/[short-description]
fix/[short-description]
chore/[short-description]
```

## 3. Commit Rules

1. Commit only after required tests pass.
2. Commit messages must describe the change accurately.
3. Do not commit secrets, `.env`, logs, dumps, cache files, build junk or vendor folders.
4. Do not force-push.
5. Do not amend shared commits unless explicitly approved.

## 4. Pre-Commit Checks

Before committing, run:

1. Relevant tests for changed areas.
2. Full required tests where practical.
3. Schema validation if database files changed.
4. API contract tests if routes/controllers/API changed.
5. Permission tests if access control changed.
6. UI smoke/build checks if views/CSS/JS changed.

## 5. Staging Deployment Rules

Claude may deploy to staging only when:

1. The task scope is complete.
2. Required tests pass.
3. The deployment target is staging, not production.
4. The deployment command is documented in this file.
5. The deployment does not require production secrets.

Staging deployment command:

```bash
# Replace with actual command
./ops/deploy-staging.sh
```

## 6. Production Deployment Rules

Claude must not deploy to production automatically.

Production deployment requires explicit human approval.

Production deployment command:

```bash
# Replace with actual command
./ops/promote-to-production.sh <verified-commit-sha>
```

## 7. Database Migration Rules

1. Staging migrations may run automatically if tests pass and the project allows it.
2. Production migrations require explicit human approval.
3. Destructive migrations require backup and rollback plan.
4. Long-running migrations must be flagged before deployment.
5. Migrations must be compatible with MySQL 8.x.

## 8. Rollback Rules

Every production release must have:

1. Commit SHA.
2. Release timestamp.
3. Database backup or snapshot where schema/data changes are involved.
4. Previous release retained.
5. Rollback command documented.
6. Post-rollback health check.

Rollback command:

```bash
# Replace with actual command
./ops/rollback-production.sh <previous-release-id>
```

## 9. Release Report

Every release preparation must include:

1. Summary of changes.
2. Commit SHA.
3. Files changed.
4. Migrations included.
5. Tests run and results.
6. Staging deployment result.
7. Known risks.
8. Rollback method.
