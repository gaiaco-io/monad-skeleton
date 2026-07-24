# Changelog

All notable changes to `monad/skeleton` are documented in this file. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); this project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

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
