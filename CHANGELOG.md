# Changelog

All notable changes to `gaia/monad-skeleton` are documented in this file. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); this project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- GitHub Actions CI: checks out `gaia/monad-clarity` as a sibling directory (matching the
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
- `composer.json` pins `gaia/monad-clarity: dev-main` for now (Clarity has no version tag
  yet) — becomes `^1.0` once Clarity ships `1.0.0`, per `ReleasePolicy.md`'s tagging order.

### Fixed
- A latent PSR-4 case-sensitivity bug: `app/middlewares`, `app/controllers`, `app/models`
  were lowercase while their namespaces (`App\Middlewares`, `App\Controllers`,
  `App\Models`) are capitalised — worked by coincidence on this filesystem's
  case-insensitivity, would have broken autoloading on Linux/CI. Corrected to match.
- `Mediator::configure()` was only ever passed `debug:`, never a `Logger` instance —
  error handling worked but nothing was actually being logged.
- Scrubbed a real-looking `DB_PASSWORD` default from `.env_example`.
