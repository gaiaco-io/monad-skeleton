# DeploymentTopology.md — Deployment Architecture

## 1. Purpose

This document defines environments, deployment locations, runtime services and release boundaries.

Claude must read this file before modifying deployment scripts, environment configuration, scheduled tasks, background workers or server paths.

## 2. Environments

| Environment | Purpose | URL / Host | Deployment Path | Database |
|---|---|---|---|---|
| Development | Local or isolated worktree testing | `[URL]` | `[Path]` | `[DB]` |
| Staging | Pre-production verification | `[URL]` | `[Path]` | `[DB]` |
| Production | Live system | `[URL]` | `[Path]` | `[DB]` |

## 3. Runtime Services

| Service | Environment | Purpose | Notes |
|---|---|---|---|
| PHP-FPM | `[Env]` | PHP runtime | `[Notes]` |
| Nginx | `[Env]` | Web server | `[Notes]` |
| MySQL | `[Env]` | Database | `[Notes]` |
| Vite build | `[Env]` | Asset build | `[Notes]` |

## 4. Server Paths

| Path | Purpose | Editable by Claude |
|---|---|---:|
| `/srv/projects/[project]/worktrees/` | Isolated development worktrees | Yes |
| `/srv/projects/[project]/staging/current` | Staging live checkout/release symlink | Only through deploy script |
| `/srv/projects/[project]/production/current` | Production live release symlink | No |
| `/srv/projects/[project]/shared/` | Shared env/uploads/storage | Restricted |

## 5. Deployment Rules

1. Development work must happen in isolated worktrees or containers.
2. Claude must not edit production live files directly.
3. Claude may deploy to staging only through documented scripts.
4. Production promotion requires explicit human approval.
5. Rollback procedure must be documented in `resources/docs/ReleasePolicy.md`.
