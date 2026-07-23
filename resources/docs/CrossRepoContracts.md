# CrossRepoContracts.md — Cross-Repository Contracts

## 1. Purpose

This document defines contracts between repositories in a multi-repo Monad project.

Claude must use this document before modifying APIs, events, shared database assumptions, authentication flows or deployment dependencies across repositories.

## 2. Contract Catalogue

| Contract ID | Provider Repo | Consumer Repo | Contract Type | Source of Truth |
|---|---|---|---|---|
| CRC-001 | `[Repo]` | `[Repo]` | API / DB / Event / File | `[Doc/path]` |

## 3. API Integration Contracts

### 3.1 `[Contract Name]`

| Field | Value |
|---|---|
| Provider | `[Repo]` |
| Consumer | `[Repo]` |
| Endpoint | `[Method + path]` |
| Auth | `[Auth requirement]` |
| Payload | `[Reference resources/docs/API_Contracts.md]` |

## 4. Shared Data Assumptions

| Data | Source Repo | Consumer Repo | Rule |
|---|---|---|---|
| `[Data]` | `[Repo]` | `[Repo]` | `[Rule]` |

## 5. Change Rules

1. Provider changes must not break consumers without explicit migration plan.
2. Consumer changes must not rely on undocumented provider behaviour.
3. Contract changes require tests on provider and consumer sides.
4. Breaking changes require versioning or coordinated deployment.
