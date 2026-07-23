# UIUXRules.md — UI/UX Behaviour Rules

## 1. Purpose

This document defines UI/UX behaviour, layout rules, responsive behaviour, accessibility expectations and interaction patterns.

Claude must comply with this file when modifying views, CSS, JavaScript, layout components, forms, navigation, tables, modals or dashboards.

## 2. Target Platforms

| Platform | Requirement |
|---|---|
| Desktop browser | `[Required / Not required]` |
| Tablet browser | `[Required / Not required]` |
| Mobile browser | `[Required / Not required]` |
| PWA | `[Required / Not required]` |

## 3. Layout Principles

1. Prioritise clarity over visual decoration.
2. Use consistent spacing and alignment.
3. Keep primary actions visible and predictable.
4. Avoid dense screens for non-technical users.
5. Do not redesign unrelated screens when implementing a feature.
6. Preserve existing navigation patterns unless the task explicitly changes them.

## 4. Navigation Rules

| Area | Rule |
|---|---|
| Main navigation | `[Rule]` |
| Breadcrumbs | `[Rule]` |
| Mobile navigation | `[Rule]` |
| Active states | `[Rule]` |

## 5. Forms

1. Labels must be clear and visible.
2. Required fields must be indicated.
3. Validation errors must appear near the affected field.
4. Error messages must explain what the user should fix.
5. Destructive actions must require confirmation.
6. Long forms should be grouped into logical sections.

## 6. Tables and Lists

1. Tables must remain readable on target screen sizes.
2. Important row actions must be easy to discover.
3. Empty states must explain what to do next.
4. Loading states must avoid layout jumps where practical.
5. Pagination, filtering and sorting behaviour must be consistent.

## 7. Modals and Dialogs

1. Use modals only for focused actions or confirmations.
2. Modals must have clear titles.
3. Primary and secondary actions must be visually distinct.
4. Escape/cancel behaviour must be safe.
5. Destructive confirmations must state the consequence clearly.

## 8. Feedback States

| State | Requirement |
|---|---|
| Loading | Show visible loading state for async operations |
| Success | Confirm successful completion clearly |
| Error | Explain the failure and next action |
| Empty | Explain why no data exists and what to do next |
| Disabled | Explain unavailable actions where practical |

## 9. Accessibility

1. Use semantic HTML where possible.
2. Buttons must be buttons, not clickable divs.
3. Inputs must have labels.
4. Interactive elements must be keyboard reachable.
5. Colour must not be the only indicator of meaning.
6. Text contrast must be readable.

## 10. Responsive Behaviour

| Breakpoint | Behaviour |
|---|---|
| Mobile | `[Behaviour]` |
| Tablet | `[Behaviour]` |
| Desktop | `[Behaviour]` |

## 11. Copywriting Rules

1. Use plain English.
2. Avoid flamboyant marketing language inside operational UI.
3. Use concise button labels.
4. Use action-oriented empty states.
5. Avoid technical jargon for business users unless necessary.
