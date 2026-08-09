# Changelog

All notable changes to `monad/skeleton` are documented in this file. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); this project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.1] - 2026-08-10

### Documentation
- Re-synced `resources/docs/CrossRepoContracts.md` and `RepoMap.md` from Clarity's
  canonical copies. `CrossRepoContracts.md` §4 corrected a stale `app/middlewares/`
  (lowercase) to the actual, PSR-4-correct `app/Middlewares/` — the rest of that document,
  `RepoMap.md`'s own tree, and this repository itself already agreed on the capitalised
  form. `RepoMap.md`'s tree gained `public/assets/fonts/`, added by `1.1.0`'s landing-page
  redesign but not previously documented, and corrected `app/api` to `Api` — capitalised
  and commented (`namespace App\Api;` — PSR-4) to match its `Controllers`/`Models`/
  `Middlewares`/`Services` siblings. Renamed the stray, empty, git-untracked local
  `app/api` directory to `app/Api` to match; no `App\Api` classes exist yet. `RepoMap.md`'s
  Clarity tree also had `MetaTag.php` listed under `src/Services/`; corrected to
  `src/Middlewares/` — where it has actually lived since being relocated and renamed from
  `Services\SeoService`, per Clarity's own `API_Contracts.md` and `CHANGELOG.md`. Both
  files are byte-identical to Clarity's again.
- Audited the rest of `resources/docs/` — `API_Contracts.md`, `Architecture.md`, `DDL.sql`,
  `DeploymentTopology.md`, `DesignTokens.md`, `PermissionsMatrix.md`, `PRD.md`,
  `ReleasePolicy.md`, `TestingStrategy.md`, `UIUXRules.md` — the same way as Clarity's, with
  one adjustment: unlike Clarity's docs, these are largely generic starter templates for
  whatever application gets built from this skeleton, not records of the skeleton's own
  build, so most bracketed `[placeholder]` content is correctly unfilled scaffolding, not a
  bug to fix. Four things were real drift, not placeholders:
  - `DesignTokens.md` presented itself as the definitive "Source of Truth ... Claude must
    use these tokens instead of inventing" — but its colour palette (`--color-primary`,
    `--color-background`, generic slate/blue values), font stack (`[Font stack]`), radius,
    and spacing tokens matched nothing in the actual shipped
    `app/client/src/css/styles.css`, which defines a completely different, real token
    system (`--surface`/`--ink`/`--signal-ok`/etc., Fraunces/IBM Plex Sans/IBM Plex Mono)
    from the `1.1.0` landing-page redesign — this doc was simply never touched when that
    redesign shipped. Rewrote the colour and typography sections to the real values, and
    was honest that no custom radius/spacing/shadow tokens exist at all — those views use
    Tailwind's defaults directly.
  - `DDL.sql` describes itself as "Database Source of Truth" and says "Claude must not
    invent tables or columns not defined in this file" — but didn't document `users`, the
    one real table this skeleton actually ships
    (`database/migrations/20260101000000_create_users_table.php`). Added it, sourced
    directly from the migration's `Blueprint` definition rather than re-typed by hand.
  - `Architecture.md` §3's directory-structure example didn't match reality in either
    content or convention — `app/server/controllers/` nesting that has never existed,
    routes as subdirectories rather than the three real `.php` files, lowercase dirs where
    the real ones are capitalised for PSR-4. Replaced with the actual current tree
    (matching `RepoMap.md`, the canonical source for the full version of the same
    information).
  - `TestingStrategy.md` referenced `docs/DDL.sql` twice — there is no top-level `docs/`
    directory in this repository; every other document in this same folder correctly says
    `resources/docs/DDL.sql`. Fixed both instances.

## [1.1.0] - 2026-08-09

### Changed
- Redesigned the default application shell to match `monad.gaiaco.io`'s own design
  system (ported from the `www` repository's `app/client/src/css/styles.css`) instead of
  a generic Tailwind starter look. `app/client/src/css/styles.css` now defines the same
  color tokens (light by default, dark via `prefers-color-scheme` — see Simplified below),
  the same three self-hosted typefaces (Fraunces for headings, IBM Plex Sans for body, IBM
  Plex Mono for code — no font CDN request), and a Tailwind `@theme` bridge exposing them
  as `bg-surface`/`text-ink`/`border-border`/etc. utility classes. `package.json` gained
  `@fontsource/{fraunces,ibm-plex-mono,ibm-plex-sans}`; `scripts/copy-assets.js` copies
  the specific woff2 files used into `public/assets/fonts/`, mirroring the `www` repo's
  already-established pattern for this.

  Simplified from the marketing site's version in one respect: light-by-default with a
  `prefers-color-scheme` dark override, no manual light/dark/auto toggle. A toggle needs a
  cookie-backed preference service and a pre-paint script to avoid a flash of the wrong
  theme (see `www`'s `App\Services\Theme` and `Layouts/main.php` for that pattern); wiring
  it up is real, app-specific work this starting point deliberately leaves out
  ("necessitate only the necessary" — `CLAUDE.md`).

  `app/views/Layouts/main.php`'s header was rebuilt around this: a `monad` wordmark
  (previously `<monad />` in red, unrelated to the real site's branding), links to the
  `/users` example and to `DOCS_URL` (new `.env` key, defaults to this repo's GitHub page
  — the marketing site's real domain is not yet live; see Fixed), and a GitHub icon link.
  Also removed: a `<script src="/assets/js/jquery.min.js">` tag with nothing on the page
  that used it, and a mobile hamburger toggle wired to Preline JS that was never loaded on
  the page at all — dead weight and a non-functional control, not scaffolding. The new
  header has no JavaScript dependency; it's a plain responsive flex layout.

  `app/views/Home/index.php` no longer restates static "necessitate only the necessary"
  copy as the entire page. It now renders a live status panel — PHP version, environment
  mode, database connectivity and driver, migration status — sourced from a new
  `App\Services\AppStatus` (the same checks `Monad\Clarity\Console\Health` runs, reshaped
  for display) via a new `App\Controllers\HomeController`, replacing the inline closure
  route. A fresh install now answers "did this actually wire up?" by looking at the page.
  Below that, a "what to touch next" list points at `app/routes/web.php`, `CLAUDE.md`, and
  the example Users flow. `app/views/Users/index.php` and `Users/create.php` were restyled
  to the same tokens — both are one click from the redesigned home page, so leaving them
  on the old hardcoded slate/blue palette would have looked like two different, unfinished
  apps stitched together.

### Added
- `App\Services\PasswordPolicy` — a real, tested password validation service, demonstrating
  the case a Service class is for: logic with actual branches, not a pass-through
  `UserModel::create()` didn't need wrapping. Deliberately does not require forced
  complexity (a digit, a symbol, mixed case) — NIST 800-63B recommends against those rules,
  since they push users toward predictable substitutions without meaningfully raising
  guessing resistance. Instead: a minimum length (10), a maximum length (256, so a
  megabyte-sized "password" is rejected before it reaches Argon2id rather than burning CPU
  hashing it), a small illustrative common-password blocklist, and a check that the
  password isn't just the user's own email address. The blocklist is explicitly
  documented as a starting point, not a real breach corpus — a production app should
  check against something like the Have I Been Pwned Pwned Passwords range API instead.
- `App\Services\Registration` and `App\Services\RegistrationException` — orchestrates
  email format/uniqueness validation, `PasswordPolicy`, `UserModel::create()`, and
  dispatches `Monad\Clarity\Services\Event::USER_REGISTERED` (a constant that already
  existed in Clarity, reserved for exactly this) on success. Every violation is collected
  and thrown together in one `RegistrationException`, not the first one found — a form
  that reports one problem per resubmit is a worse experience than being told everything
  at once. `App\Controllers\UserController::store()` now delegates to this instead of
  inlining the checks; `app/views/Users/create.php` renders the resulting `list<string>`
  of errors instead of a single `?string`.

### Fixed
- `app/views/Errors/404.php` never set `$layout`, so `Route::fallback()`'s response — the
  actual 404 a visitor sees — rendered as a bare HTML fragment with no `<head>`, no
  stylesheet link, and therefore no styling whatsoever, regardless of what `.php`
  contained. `WebRoutesTest`'s `testUnknownRouteFallsBackToTheStyled404` only asserted the
  string `'404'` appeared in the body, which a plain-text fragment satisfies just as well
  as a styled page — so this was never caught. It now opts into `Layouts/main` like every
  other view.
- Investigated pointing "Documentation" at the real `monad.gaiaco.io` production domain
  (found in the `www` repository's own deployment docs). Not done: that repo's own deploy
  checklist (`DeployRunbook.md`) shows the site is not live yet, so hardcoding an unverified
  domain into a public starter kit risked a broken/parked-domain link for every
  `create-project` user. `DOCS_URL` is a new `.env` key instead, defaulting to this
  repository's GitHub page, so it can be pointed at the real site with no code change once
  one exists.

## [1.0.4] - 2026-08-09

### Fixed
- A new application rendered completely unstyled, and its every page requested a
  JavaScript file that returned 404. Found by actually opening the home page in a browser
  rather than asserting on the response body — the page returned `200 OK` with correct,
  complete markup the whole time, which is why the test suite and every `curl` check
  passed. Two separate causes, both in how built assets were handled:
  - `public/assets/css/styles.css` was committed to the repository and had last been
    written by `fd697cd`, the baseline commit of the pre-existing working tree. The layout
    that consumes it was rewritten afterwards, in `48934b8`, and the stylesheet was never
    rebuilt. Tailwind emits only the utility classes it finds while scanning templates, so
    the committed build contained none of the classes the rewritten views introduced —
    `bg-slate-700`, `max-w-340`, `hover:text-slate-300` and `bg-white/10` were all absent.
    The result was a valid, non-empty 45 KB stylesheet that simply did not style the
    markup it shipped alongside. Rebuilding produces a *smaller* file (32 KB): the old one
    was carrying utilities for markup that no longer exists while missing the ones in use.
  - `public/assets/js/` was never committed at all, while
    `app/views/Layouts/main.php` loads `/assets/js/jquery.min.js` on every page. That file
    is vendored from `node_modules` by `scripts/copy-assets.js`, so it existed only after
    an `npm install` that the README never asked anyone to run.

### Changed
- Built assets are no longer tracked in git. `public/assets/css/` and `public/assets/js/`
  are now ignored, and `public/assets/css/styles.css` has been removed from the index.
  Tracking one built file but not the other is what allowed the two failures above to
  differ in kind while sharing a root cause, and a committed build has no mechanism to
  notice that the templates it was generated from have changed. Sources remain tracked in
  `app/client/src/`; the artifacts are reproduced by `npm install`. `public/` now ships
  only `index.php`, `router.php`, `llms.txt` and `sitemap.xml`.
- `package-lock.json` is now committed, so the asset build is reproducible rather than
  resolving fresh dependency versions on every install.
- `/storage/database.sqlite` is now ignored. `config/database.php` defaults `DB_DATABASE`
  to that path when `DB_DRIVER=sqlite`, so anyone taking the documented SQLite option was
  left with an untracked database file in `git status`.

### Documentation
- `README.md`'s Installation section now includes `npm install`, without which no
  application renders correctly. Requirements previously described Node.js as being "for
  the Tailwind/asset build only — not required at runtime", which reads as optional; it is
  required once at install time, and the entry now says so. Added a short explanation of
  what `build:css` and `build:assets` each produce and why a stale stylesheet fails
  silently — the failure mode is an unstyled page, never an error — plus a pointer to
  `npm run build:css` / `watch:css` when adding classes a previous build never saw.

## [1.0.3] - 2026-08-09

### Documentation
- Followed `monad/clarity`'s retirement of the CalVer milestone naming convention in favour
  of semver. Releases were previously identified two ways at once — a semver package
  version and a parallel CalVer milestone name (`26.07`) used for release identity and
  document filenames — and the milestone name is now retired, anchored on the equivalence
  **`26.07` shipped as `monad/clarity 1.0.0`**. This repository's own version line is
  independent of Clarity's and is unaffected. The canonical policy lives in Clarity's
  `resources/docs/ReleasePolicy.md` § Release naming; this repository's `ReleasePolicy.md`
  is a different document (git, deployment and rollback rules) and carries no versioning
  content, so it needed no change.
- Updated the three references to Clarity's two renamed specification documents:
  `resources/docs/RepoMap.md`'s tree of the Clarity repository now lists
  `GapAnalysis_BuildPlan_1.0.0.md` and `ReleaseNotes_1.0.0.md`, and
  `resources/docs/CrossRepoContracts.md` §9 now cites `ReleaseNotes_1.0.0.md`.
- Re-synced `resources/docs/CrossRepoContracts.md` from Clarity's canonical copy, per that
  document's §10 and `ReleasePolicy.md` § Repository authority. The mirror had drifted by
  exactly one line (the specification filename above) and is now byte-identical, satisfying
  item 7 of Clarity's Packagist publication checklist.
- Historical prose is deliberately preserved rather than rewritten, matching the approach
  taken in Clarity: only references that would otherwise dangle were updated. The 1.0.0
  entry below still reads "Initial 26.07 release" and still describes Clarity's "finalized
  26.07 API", as written at the time.

## [1.0.2] - 2026-07-26

### Removed
- `public/.htaccess` — Apache-only config, unused with this project's documented Nginx
  topology. Inherited from the baseline pre-existing working tree; also carried a
  hardcoded `qsrbrands.com` CSP `img-src` entry left over from a prior project.

## [1.0.1] - 2026-07-24

### Fixed
- `composer.json` still shipped the local-dev-only `"repositories": [{"type": "path",
  "url": "../Clarity"}]` entry in the tagged v1.0.0 release — harmless for local
  development (where `../Clarity` exists as a sibling checkout) but fatal for a real
  `composer create-project monad/skeleton` from Packagist: Composer evaluates every
  listed repository regardless of whether it's actually needed to satisfy a constraint,
  and `../Clarity` doesn't exist relative to a freshly created project directory
  anywhere else. Caught by the first real `composer create-project` from Packagist
  (`PathRepository.php:163 — The 'url' supplied for the path (../Clarity) repository
  does not exist`). Removed entirely — now that both packages are on Packagist,
  `monad/clarity: ^1.0` resolves directly with no repository override needed.
- CI simplified to match: no longer checks out `gaia/monad-clarity` as a sibling
  directory or loosens `minimum-stability` for it — both were only ever a bridge for
  local/CI development before Packagist submission, and are obsolete now that a plain
  `composer install` resolves the real published package, exactly like any real
  consumer's install.

Verified against the actual Packagist packages, not local paths: a clean
`composer install` in a fresh checkout installs `monad/clarity (v1.0.0)` directly from
Packagist, and `php mitosis setup && php mitosis migrate && php mitosis health` all pass
against it (29 tests, 53 assertions, still green).

## [1.0.0] - 2026-07-24

Initial 26.07 release, following `monad/clarity 1.0.0`. `composer.json` pins
`monad/clarity: ^1.0`. CI's sibling Clarity checkout still resolves this via a CI-only,
uncommitted `minimum-stability: dev` override until Clarity is on Packagist with a real
stable release — see the comment in `.github/workflows/ci.yml`.

**Package renamed from `gaia/monad-skeleton` to `monad/skeleton`** (and its dependency
from `gaia/monad-clarity` to `monad/clarity`) before this tag was pushed — the `gaia`
vendor name was already claimed on Packagist. No functional change.

### Added
- GitHub Actions CI: checks out `monad/clarity` as a sibling directory (matching the
  local path repository, since Clarity isn't on Packagist yet), matrix over PHP 8.2/8.3,
  validate/install/lint/test.
- Automated test coverage: each of the 7 `App\Middlewares\*` stubs tested directly
  (`Csrf`'s adversarial cases — missing token, mismatched Origin — included, not just
  happy-path), plus a full route-level integration suite exercising the real
  `app/routes/web.php` through `Route::dispatch()` (Home, users listing/create, a
  CSRF-protected create-then-redirect round trip, rejected-without-token, duplicate-email
  validation, the 404 fallback).
- README, LICENSE (MIT, matching Clarity's).

### Changed
- Rewrote `app/`, `config/`, `public/`, `mitosis`, `composer.json` to match Clarity's
  finalized 26.07 API — the pre-existing code (`Gaia\Herodo\*` namespace, `CsrfService`,
  instance-based `Session`/`Request`, a hand-rolled autoloader) predated Clarity being an
  installable Composer package entirely. Thin entry points per `CrossRepoContracts.md` §2;
  `app/Middlewares/{Csrf,RateLimiter,CORS,Jsonify,Logger,Authentication,RBAC}` per §5;
  example `UserController`/`UserModel`/views rewritten against the current DB/View/
  Request/Response API, with a real `users` table migration backing
  `Authentication`/`RBAC`'s resolver closures.

### Fixed
- A latent PSR-4 case-sensitivity bug: `app/middlewares`, `app/controllers`, `app/models`
  were lowercase while their namespaces (`App\Middlewares`, `App\Controllers`,
  `App\Models`) are capitalised — worked by coincidence on this filesystem's
  case-insensitivity, would have broken autoloading on Linux/CI. Corrected to match.
- `Mediator::configure()` was only ever passed `debug:`, never a `Logger` instance —
  error handling worked but nothing was actually being logged.
- Scrubbed a real-looking `DB_PASSWORD` default from `.env_example`.
- The `mitosis` CLI stub itself still referenced `Gaia\Clarity\Services\Console` after
  the rename below — a critical miss, since that class no longer exists, and the
  mechanical rename script's file-extension globs never matched an extensionless file.
  Caught by a deliberate extension-agnostic sweep of the whole tree rather than trusting
  the `.php`/`.md`/`.json`/`.yml` glob alone.
