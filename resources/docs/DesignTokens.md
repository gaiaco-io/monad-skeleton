# DesignTokens.md — UI Design Token Source of Truth

## 1. Purpose

This document defines the visual design tokens for the application.

Claude must use these tokens instead of inventing colours, spacing, typography, shadows, or visual styles.

## 2. Design Direction

Editorial and restrained: an off-black/off-white ink-on-surface palette (not a brand-blue
SaaS look), a serif display face for headings against a plain sans body and a distinct
mono for code, generous body line-height with tight display line-height, and near-flat
corners. Matches `monad.gaiaco.io`'s own design system (the `www` repo's
`app/client/src/css/styles.css`) — see `CHANGELOG.md`'s `1.1.0` entry for why.

## 3. Colour Tokens

Defined in `app/client/src/css/styles.css` as CSS custom properties on `:root`, light by
default with a `@media (prefers-color-scheme: dark)` override — no manual toggle (that
needs a cookie-backed preference service and a pre-paint script; deliberately left out of
this starting point). Bridged to Tailwind `bg-*`/`text-*`/`border-*` utilities via an
`@theme` block, so `bg-surface`/`text-ink`/`border-border`/etc. are the classes to use in
views — never a raw hex value or an arbitrary Tailwind colour like `bg-slate-700`.

| Token | Light | Dark | Usage |
|---|---|---|---|
| `--surface` | `#FFFFFF` | `#0E0E0D` | App background |
| `--surface-raised` | `#FAFAF9` | `#181816` | Cards, panels, callouts |
| `--ink` | `#0E0E0D` | `#F5F5F3` | Primary text |
| `--ink-muted` | `#5C5C58` | `#9A9A94` | Secondary text |
| `--border` | `#E4E4E1` | `#2A2A27` | Borders and dividers |
| `--border-strong` | `#0E0E0D` | `#F5F5F3` | High-contrast borders, focus rings |
| `--signal-ok` | `#1A7F37` | `#3FB950` | Success states |
| `--signal-error` | `#C4302B` | `#F85149` | Error/destructive states |

No `--color-primary`/`--color-secondary`/warning token exists — there is no brand-blue
accent colour in this system; `--ink` (rendered via `bg-ink text-surface`) is what buttons
and other primary actions use. Add a warning token deliberately if a project needs one;
don't invent one ad hoc.

## 4. Typography Tokens

Three self-hosted typefaces (`public/assets/fonts/`, copied from `@fontsource/*` by
`scripts/copy-assets.js` — no font CDN request), bridged the same way as colour:

| Token | Value | Usage |
|---|---|---|
| `--font-display` | `"Fraunces", ui-serif, Georgia, "Times New Roman", serif` | Headings (`h1`–`h6`, applied automatically) |
| `--font-sans` | `"IBM Plex Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif` | Body text (`html`'s default) |
| `--font-mono` | `"IBM Plex Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace` | Code, status values (`code`/`pre`/`kbd`/`samp`, applied automatically) |

No `--text-*` size scale is defined as custom properties — use Tailwind's own `text-sm`/
`text-lg`/`text-2xl`/etc. utilities directly.

## 5. Spacing Tokens

No custom `--space-*` scale exists. Use Tailwind's default spacing utilities (`p-4`,
`gap-6`, `py-20`, etc.) directly — the default scale is a 4px/`0.25rem` base unit, already
fine-grained enough that a project-specific override hasn't been needed.

## 6. Radius Tokens

No custom `--radius-*` scale exists. Use Tailwind's default radius utilities (`rounded-sm`,
etc.) directly — the shipped views favour small, near-flat radii (`rounded-sm`), not the
rounded-pill SaaS default.

## 7. Shadow Tokens

None used anywhere in the shipped views — the flat, editorial look this system aims for
relies on colour and borders for separation, not elevation shadows. Don't add a shadow
without a specific reason; if one becomes genuinely necessary, define it here first.

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
