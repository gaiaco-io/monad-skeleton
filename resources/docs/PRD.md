# PRD.md — Product Requirements Document

## 1. Product Overview

### 1.1 Product Name

`[Project name]`

### 1.2 Product Purpose

`[Describe the core business purpose of the application.]`

### 1.3 Target Users

| User Type | Description | Primary Goals |
|---|---|---|
| `[Role]` | `[Description]` | `[Goals]` |

### 1.4 Business Context

`[Describe the business problem, operating context, and why the system is needed.]`

## 2. Scope

### 2.1 In Scope

1. `[Feature / module]`
2. `[Feature / module]`
3. `[Feature / module]`

### 2.2 Out of Scope

1. `[Explicit exclusion]`
2. `[Explicit exclusion]`
3. `[Explicit exclusion]`

### 2.3 Assumptions

1. `[Assumption]`
2. `[Assumption]`

### 2.4 Constraints

1. Built using Monad Framework.
2. Database schema must align with `resources/docs/DDL.sql`.
3. API behaviour must align with `resources/docs/API_Contracts.md`.
4. UI/UX must align with `resources/docs/DesignTokens.md` and `resources/docs/UIUXRules.md`.
5. Access control must align with `resources/docs/PermissionsMatrix.md`.

## 3. User Roles

| Role | Description | Notes |
|---|---|---|
| `[Role]` | `[Description]` | `[Notes]` |

## 4. Core User Journeys

### 4.1 Journey: `[Journey Name]`

**Actor:** `[Role]`

**Goal:** `[Goal]`

**Steps:**

1. `[Step]`
2. `[Step]`
3. `[Step]`

**Expected Result:**

`[Expected result]`

## 5. Functional Requirements

### 5.1 `[Module Name]`

| ID | Requirement | Priority | Acceptance Criteria |
|---|---|---:|---|
| FR-001 | `[Requirement]` | Must | `[Acceptance criteria]` |

## 6. Non-Functional Requirements

| ID | Requirement | Acceptance Criteria |
|---|---|---|
| NFR-001 | Performance | `[Requirement]` |
| NFR-002 | Security | `[Requirement]` |
| NFR-003 | Usability | `[Requirement]` |
| NFR-004 | Maintainability | `[Requirement]` |

## 7. Business Rules

| ID | Rule | Applies To |
|---|---|---|
| BR-001 | `[Business rule]` | `[Module / role]` |

## 8. Validation Rules

| Field / Action | Rule | Error Message |
|---|---|---|
| `[Field]` | `[Validation rule]` | `[Message]` |

## 9. Reporting / Dashboard Requirements

| Report / Widget | Users | Data Source | Behaviour |
|---|---|---|---|
| `[Name]` | `[Role]` | `[Table/API]` | `[Behaviour]` |

## 10. Notifications

| Trigger | Recipient | Channel | Content Rules |
|---|---|---|---|
| `[Trigger]` | `[Role]` | `[Email/In-app/etc.]` | `[Rules]` |

## 11. Audit Logging Requirements

| Action | Actor | Data to Log |
|---|---|---|
| `[Action]` | `[Role]` | `[Fields]` |

## 12. Acceptance Criteria Summary

The feature/application is considered complete only when:

1. All in-scope functional requirements are implemented.
2. All out-of-scope items remain excluded.
3. Database usage matches `resources/docs/DDL.sql`.
4. API behaviour matches `resources/docs/API_Contracts.md`.
5. Access control matches `resources/docs/PermissionsMatrix.md`.
6. UI/UX matches `resources/docs/DesignTokens.md` and `resources/docs/UIUXRules.md`.
7. Required tests pass.
8. No production secrets or unsafe shortcuts are introduced.

## 13. Open Questions

| ID | Question | Impact | Status |
|---|---|---|---|
| Q-001 | `[Question]` | `[Impact]` | Open |
