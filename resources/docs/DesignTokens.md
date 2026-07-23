# DesignTokens.md — UI Design Token Source of Truth

## 1. Purpose

This document defines the visual design tokens for the application.

Claude must use these tokens instead of inventing colours, spacing, typography, shadows, or visual styles.

## 2. Design Direction

`[Describe the intended visual direction: e.g. clean, enterprise, calm, modern, industrial, warm, premium, minimal.]`

## 3. Colour Tokens

| Token | Value | Usage |
|---|---|---|
| `--color-primary` | `#000000` | Primary actions, key highlights |
| `--color-primary-hover` | `#000000` | Primary hover state |
| `--color-secondary` | `#000000` | Secondary accents |
| `--color-background` | `#ffffff` | App background |
| `--color-surface` | `#ffffff` | Cards, panels, sheets |
| `--color-border` | `#e5e7eb` | Borders and dividers |
| `--color-text` | `#111827` | Primary text |
| `--color-text-muted` | `#6b7280` | Secondary text |
| `--color-success` | `#16a34a` | Success states |
| `--color-warning` | `#d97706` | Warning states |
| `--color-danger` | `#dc2626` | Error/destructive states |

## 4. Typography Tokens

| Token | Value | Usage |
|---|---|---|
| `--font-sans` | `[Font stack]` | Main UI font |
| `--text-xs` | `0.75rem` | Captions, helper text |
| `--text-sm` | `0.875rem` | Secondary UI text |
| `--text-base` | `1rem` | Body text |
| `--text-lg` | `1.125rem` | Section headings |
| `--text-xl` | `1.25rem` | Page headings |
| `--text-2xl` | `1.5rem` | Major headings |

## 5. Spacing Tokens

| Token | Value | Usage |
|---|---|---|
| `--space-1` | `0.25rem` | Tight spacing |
| `--space-2` | `0.5rem` | Small spacing |
| `--space-3` | `0.75rem` | Compact groups |
| `--space-4` | `1rem` | Default spacing |
| `--space-6` | `1.5rem` | Section spacing |
| `--space-8` | `2rem` | Large spacing |

## 6. Radius Tokens

| Token | Value | Usage |
|---|---|---|
| `--radius-sm` | `0.25rem` | Small controls |
| `--radius-md` | `0.5rem` | Inputs/buttons |
| `--radius-lg` | `0.75rem` | Cards/panels |
| `--radius-xl` | `1rem` | Large surfaces |

## 7. Shadow Tokens

| Token | Value | Usage |
|---|---|---|
| `--shadow-sm` | `[value]` | Small elevation |
| `--shadow-md` | `[value]` | Cards and popovers |
| `--shadow-lg` | `[value]` | Modals and overlays |

## 8. Component Tokens

### Buttons

| State | Rule |
|---|---|
| Default | `[Style]` |
| Hover | `[Style]` |
| Disabled | `[Style]` |
| Destructive | `[Style]` |

### Inputs

| State | Rule |
|---|---|
| Default | `[Style]` |
| Focus | `[Style]` |
| Error | `[Style]` |
| Disabled | `[Style]` |

## 9. TailwindCSS 4 Usage Rules

1. Use Tailwind utility classes consistent with this document.
2. Do not use arbitrary colours unless they map to tokens defined here.
3. Do not introduce third-party component styles unless approved.
4. Preserve consistent spacing and typography across screens.
