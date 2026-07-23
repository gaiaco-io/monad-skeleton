# Monad Skeleton

Application skeleton for the Monad Framework — an MVC-based PHP framework for solo
developers and small teams. This is the project you clone/create to build an app; the
framework itself lives in [`gaia/monad-clarity`](https://github.com/gaiaco-io/monad-clarity),
installed as a dependency.

**Status:** `1.0.0`, tagged and matching `gaia/monad-clarity 1.0.0`. Not yet published to
Packagist — `composer create-project` below will work once it is.

## Requirements

- PHP `>=8.2`
- A database: MySQL (default), PostgreSQL, or SQLite
- Node.js (for the Tailwind/asset build only — not required at runtime)

## Installation

```bash
composer create-project gaia/monad-skeleton NewApp
cd NewApp
cp .env_example .env   # fill in APP_SECRET, DB_*, and anything else you need
php mitosis setup      # creates the sessions/caches tables
php mitosis migrate    # runs database/migrations/*
php mitosis serve      # http://127.0.0.1:8000
```

## Project layout

```
app/
├── Controllers/     # App\Controllers\* — PSR-4, capitalised to match the namespace
├── Models/          # App\Models\*
├── Services/        # App\Services\*
├── Middlewares/      # App\Middlewares\* — thin extensions of Gaia\Clarity\Middlewares\*
├── routes/           # web.php, api.php, cli.php — plain require'd registration files
└── views/            # resolved by Gaia\Clarity\Services\View
config/               # bootstrap.php is the single shared boot path for web/CLI/scripts
database/
├── migrations/
└── seeds/
public/                # web root; index.php is the front controller
mitosis                 # CLI entry point — php mitosis <command>
```

`Controllers`, `Models`, `Services`, and `Middlewares` are capitalised deliberately: PSR-4
resolves namespaces to paths case-sensitively, so the directory case must match the
namespace segment exactly (`App\Controllers\UserController` → `app/Controllers/
UserController.php`). `routes/` and `views/` stay lowercase since neither is
namespace-autoloaded.

## The `mitosis` CLI

```bash
php mitosis health          # config, DB connectivity, writable storage, migrations, extensions
php mitosis make:controller UserController
php mitosis make:model User
php mitosis make:service Billing
php mitosis make:migration add_index_to_users
php mitosis migrate
php mitosis migrate:status
php mitosis migrate:rollback
php mitosis db:seed --file=<name>.php       # relative to database/seeds/
php mitosis db:execute <path-to-sql-file>
php mitosis cache:clear
php mitosis logs:clear
php mitosis test                            # delegates to PHPUnit
php mitosis serve                           # PHP's built-in server, port 8000 by default
php mitosis setup                           # creates the sessions/caches tables
```

Custom commands are registered in `app/routes/cli.php`.

## Testing

```bash
composer run test    # or: php mitosis test
composer run lint    # php -l across app/
```

Tests live in `resources/tests/` and use an in-memory SQLite database — no real database
required to run the suite. `resources/tests/bootstrap.php` sets up the small amount of
ambient state (`PATH`, `APP_SECRET`) a few `App\Middlewares\*` stubs read at construction.

## Middleware stubs

`app/Middlewares/*` are thin, zero-argument-constructor subclasses of Clarity's engines
(`Csrf`, `RateLimiter`, `CORS`, `Jsonify`, `Logger`, `Authentication`, `RBAC`) — customise
by overriding the protected extension points documented on each parent class, or by
changing what the constructor passes through (env-driven config, resolver closures
against your own tables). `Authentication`/`RBAC` here are wired against the example
`users` table (`database/migrations/20260101000000_create_users_table.php`).

## License

MIT. See [LICENSE](LICENSE).
