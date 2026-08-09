# Changelog

All notable changes to `monad/skeleton` are documented in this file. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); this project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

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
