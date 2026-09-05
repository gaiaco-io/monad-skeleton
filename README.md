# Monad Skeleton

Application skeleton for the Monad Framework — an MVC-based PHP framework for solo
developers and small teams. This is the project you clone/create to build an app; the
framework itself lives in [`monad/clarity`](https://github.com/gaiaco-io/monad-clarity),
installed as a dependency.

**Status:** `1.1.5`, published on Packagist. Depends on `monad/clarity ^1.0`, which
currently resolves to `1.7.1`. The two packages have independent version lines; each
repository's `CHANGELOG.md` is the authoritative record of its own.

## Requirements

- PHP `>=8.2`
- A database: MySQL (default), PostgreSQL, or SQLite
- Node.js — required once at install time to build the stylesheet and copy the vendored
  JavaScript into `public/assets/`. Not required at runtime.

## Installation

```bash
composer create-project monad/skeleton NewApp
cd NewApp
npm install            # builds public/assets/css + copies vendored JS — see below
                       # then edit .env: DB_*, and MAIL_MAILERS / CHECKOUT_GATEWAY if you need them
php mitosis setup      # creates the sessions/caches tables
php mitosis migrate    # runs database/migrations/*
php mitosis serve      # http://127.0.0.1:8000
```

`create-project` writes `.env` for you, from `.env_example` and with a freshly generated
`APP_SECRET` (`scripts/setup-env.php`, Composer's `post-create-project-cmd`). The secret is
generated rather than left blank because a blank one does not fail: `App\Middlewares\Csrf`
passes it to Clarity's `HMAC::sign()`, which signs happily with an empty key, so the
application would boot and issue CSRF and session tokens that anyone can forge. Every other
key arrives present and empty, documented in place.

It never overwrites an existing `.env`, and never fails the installation — if it cannot write
the file it says so and tells you to `cp .env_example .env` yourself.

`npm install` is not optional for a working page. Its `postinstall` hook runs
`npm run build:all`, which does two things nothing else does:

- **`build:css`** compiles `app/client/src/css/styles.css` to
  `public/assets/css/styles.css`. Tailwind emits only the utility classes it finds while
  scanning your templates, so a stylesheet built against different markup will silently
  omit whatever your views actually use — the page renders unstyled rather than erroring.
- **`build:assets`** copies jQuery, Preline, DataTables and Chart.js out of `node_modules`
  into `public/assets/js/` — available if you want them, but the default layout doesn't
  load any of them — plus the self-hosted Fraunces/IBM Plex Sans/IBM Plex Mono font files
  the built stylesheet references into `public/assets/fonts/`.

Re-run `npm run build:css` (or `npm run watch:css` while developing) whenever you add
Tailwind classes the previous build never saw.

## Project layout

```
app/
├── Controllers/     # App\Controllers\* — PSR-4, capitalised to match the namespace
├── Models/          # App\Models\*
├── Services/        # App\Services\*
├── Middlewares/      # App\Middlewares\* — thin extensions of Monad\Clarity\Middlewares\*
├── routes/           # web.php, api.php, cli.php — plain require'd registration files
└── views/            # resolved by Monad\Clarity\Services\View
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
php mitosis checkout:install                # creates the checkout tables — only if you take payments
php mitosis schedule:install                # creates the scheduled_runs table — Clarity 1.5.0+
php mitosis schedule:list                   # the registered schedule, and how each job last ran
php mitosis schedule:run                    # runs the jobs due this minute — the cron heartbeat
```

Custom commands are registered in `app/routes/cli.php`.

`checkout:install` is deliberately separate from `setup`: payments are opt-in, so an
application that takes none never creates the four checkout tables. Run it only if you
use `Monad\Clarity\Services\Checkout` (Clarity 1.2.0+). `schedule:install` is separate for
the same reason — see below.

## Scheduled jobs

`Monad\Clarity\Services\Scheduler` (Clarity 1.5.0+) keeps the application's schedule in
code rather than in a crontab, so jobs travel with a deploy and are visible to code review.
The system cron gets exactly one line, for the life of the application:

```cron
* * * * * cd /path/to/app && php mitosis schedule:run
```

Add it on **every** node that should be eligible to run jobs — three nodes give three
chances a due job runs, and no chance it runs three times: each due job is claimed
cluster-wide before it runs.

Note the deliberate absence of `> /dev/null 2>&1`. A tick where nothing was due, or where
another node already claimed everything, prints nothing and exits 0 — so silence is the
healthy signal, and the one line you do get is the alert. The reflexive redirect throws
the failures away along with the quiet.

To adopt it:

1. `php mitosis schedule:install` — once, per database context.
2. Register jobs in `app/routes/cli.php` with `Scheduler::job()`.
3. `php mitosis schedule:list` — confirms what is registered and, once the jobs start
   running, how each one last went. It is read-only and always exits 0, so it is the safe
   thing to run when you want to know what the cluster thinks it is doing.
4. Add the crontab line above, on every node that should run jobs.

`app/routes/cli.php` ships with a commented-out example. Jobs are registered with
`Scheduler::job($name, $cronExpression, $work, staleAfterMinutes: 60)`; the console kernel
loads that file before every dispatch, so a malformed expression breaks the next `mitosis`
invocation in plain sight rather than producing a job that quietly never fires.

Cron expressions are evaluated on the application's own clock — the `TIMEZONE` value in
`.env`, which `config/bootstrap.php` hands to `date_default_timezone_set()`. That is not
necessarily the timezone the system cron runs in, and every node in a cluster must agree
on it or they are running two different schedules.

Running the crontab line before `schedule:install` is safe: the tick exits 1 every minute
naming the install command, loudly, rather than silently doing nothing.

## Sending mail

`Monad\Clarity\Services\Mail` (Clarity 1.6.0+) sends through any of seven mailers — Postmark,
Resend, SendGrid, Mailgun, Mailtrap, Amazon SES, or plain SMTP — behind one contract, so
changing provider is a change to `.env` and nothing else. There is no `mitosis` command and no
table: Mail owns no state.

`config/mail.php` builds the mailer and hands it back. Application code requires it and sends:

```php
use Monad\Clarity\Services\Mail\{Address, Message};

$mail = require __DIR__ . '/../config/mail.php';

$mail->send(new Message(
    from: new Address($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']),
    to: [new Address($user->email)],
    subject: 'Reset your password',
    text: "Reset your password: {$url}",
    html: View::render('Emails/PasswordReset', ['url' => $url]),
));
```

**`MAIL_MAILERS` is the whole of the multi-mailer decision.** One name gives you that mailer;
a comma-separated list gives you a pool that tries them left to right, so a provider outage
does not take password resets down with it:

```dotenv
MAIL_MAILERS=postmark              # one mailer
MAIL_MAILERS=postmark,resend,smtp  # try each in turn
```

Both return a `Services\Mail`, so nothing downstream changes when you switch between them.
There is no separate "enable failover" flag — the list is it.

Three things worth knowing before you rely on a pool:

- **Failover keys on whose fault a failure is, not on the status code.** A rejected credential
  fails over, because the next mailer holds a different one; a malformed recipient does not,
  because every provider would refuse it the same way.
- **A pool can send twice.** If a provider accepts a message and then times out before
  acknowledging, the pool cannot tell that from never having sent it. Put invoices and
  one-time codes through a single mailer.
- **Send one message through each member on its own first.** A standby whose credentials and
  egress have never been exercised is a standby you have no evidence about — and SMTP wants
  outbound 587 or 465, the one Clarity component needing a port other than 443.

Check `$sent->failedOver()` and log `$sent->attempts` if you pool: Clarity keeps no delivery
table, so that return value is the only record a mailer is failing.

## Taking payments

`Monad\Clarity\Services\Checkout` (Clarity 1.2.0+) puts Stripe and Paddle behind one contract:
begin a checkout, re-query it, verify a callback, refund it. Changing gateway is a change to
`.env` and a `checkout:install` you have probably already run.

Two commands before the first payment — the tables are opt-in, so an application that takes no
payments never carries them:

```bash
php mitosis checkout:install    # once per database context; re-runnable, and that is the upgrade path
```

`config/checkout.php` reads the credentials and builds the adapter for the one gateway named in
`CHECKOUT_GATEWAY`:

```dotenv
CHECKOUT_GATEWAY=stripe_checkout      # or paddle_checkout, or paddle_subscription
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...       # from Stripe > Webhooks, issued per endpoint
```

**The two Paddle names are not interchangeable.** Paddle's `past_due` means Pending for a
subscription and Failed for a one-time payment (Clarity `ReleaseNotes_1.4.0.md` §2.6), so the
name here decides what a live callback is taken to mean, not merely which object gets built.

### The callback endpoint is shipped; the sale is not

`POST /webhooks/checkout` (`app/routes/api.php`) is built and tested. Point Stripe > Webhooks or
Paddle > Notifications at it and put the signing secret it issues into `.env`. It verifies the
signature, applies the event to `TransactionLedger`, and answers **204** when it worked or was a
redelivery, **400** when the callback did not verify or was not a checkout event, and **404**
when it named a transaction this ledger never opened — which asks the gateway to retry, and is
what resolves a race against the sale that records it.

With `CHECKOUT_GATEWAY=paddle_subscription` it handles both families a Paddle notification
destination delivers to that one URL, routed on the event type's prefix: `transaction.*` events
go to `TransactionLedger`, and the `subscription.*` events describing the plan's life afterwards
go to `SubscriptionLedger`. A subscription is born from a transaction (Clarity
`ReleaseNotes_1.4.0.md` §2.3), so both streams arrive, and the endpoint also joins the two
references when the paying transaction carries the subscription it created.

This half is shipped because it is identical in every application, and because without it a paid
transaction sits at `Pending` in the ledger for ever: the redirect back from a checkout page
tells you where the customer went, not what the bank did.

The other half — `createCheckout()` — is not shipped, and that is deliberate. What is being sold,
at what price, with which success and cancel URLs is your product, and a skeleton that guessed it
would be guessing. Build it where the sale happens:

```php
use Monad\Clarity\Services\Checkout\{CheckoutRequest, Money, TransactionLedger};

$checkout = require __DIR__ . '/../config/checkout.php';

$sale = new CheckoutRequest(
    reference: $order->id,
    amount: new Money(2500, 'GBP'),
    successUrl: 'https://example.com/paid',
    cancelUrl: 'https://example.com/cancelled',
);

$session = $checkout['adapter']->createCheckout($sale);

// Record it before redirecting: the callback can arrive before the customer comes back.
(new TransactionLedger())->open($sale, $session);

return Response::redirect($session->redirectUrl);
```

A signing secret is not optional. Clarity refuses to verify a callback without one rather than
accepting it unverified, so the endpoint answers 400 for every delivery until it is set.

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
