# RepoMap.md — Repository Map

## 1. Purpose

Use this document for multi-repository or large Monad Framework projects.

Claude must read this document before making cross-repo or integration changes.

## 2. Repositories

| Repository | Purpose | Deployment Target | Notes |
|---|---|---|---|
| `[repo-name]` | `[Purpose]` | `[Target]` | `[Notes]` |

## 3. Ownership Boundaries

| Concern | Owning Repo | Notes |
|---|---|---|
| `[Concern]` | `[Repo]` | `[Notes]` |

## 4. Shared Contracts

| Contract | Source File | Consuming Repos |
|---|---|---|
| API Contract | `resources/docs/API_Contracts.md` | `[Repos]` |
| Database Contract | `resources/docs/DDL.sql` | `[Repos]` |
| UI Rules | `resources/docs/UIUXRules.md` | `[Repos]` |

## 5. Cross-Repo Change Rules

1. Do not change one repo in a way that breaks another repo’s documented contract.
2. Update `resources/docs/CrossRepoContracts.md` when integration behaviour changes.
3. Run integration tests before marking cross-repo work complete.
4. Produce a cross-repo impact summary.
