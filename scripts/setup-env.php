<?php

/**
 * Prepares `.env` for a freshly created project — Composer's `post-create-project-cmd`, the
 * counterpart to `scripts/copy-assets.js` on the npm side.
 *
 * Two steps, and the second is the reason this is a script rather than a one-line `copy()`
 * in composer.json. Copying the template alone would leave `APP_SECRET` blank, and a blank
 * secret does not fail: `App\Middlewares\Csrf` hands it to Clarity's `HMAC::sign()`, which
 * signs perfectly happily with an empty key. The application would boot, serve pages, and
 * issue CSRF and session tokens that anyone can forge — a failure that looks exactly like
 * success, which is the worst kind to ship in a scaffold. So the secret is generated here,
 * at the one moment we know the project is new.
 *
 * Never overwrites an existing `.env`. A developer who has already configured one is the
 * only person whose file matters, and `create-project` is not the only thing that runs this.
 *
 * Never fails the installation. A scaffold that aborts because it could not write a file
 * leaves a half-installed project and a stack trace; saying plainly what did not happen, and
 * what to run by hand, is more useful than a non-zero exit.
 */

declare(strict_types=1);

/**
 * @return array{0: string, 1: bool} The message to print, and whether `.env` is now usable.
 */
function monad_prepare_env_file(string $root): array
{
    $env = $root . '/.env';
    $template = $root . '/.env_example';

    if (is_file($env)) {
        return ['Kept the .env already here — nothing was overwritten.', true];
    }

    if (!is_file($template)) {
        return ['No .env_example to copy, so .env was not created. Write one before running php mitosis setup.', false];
    }

    $contents = file_get_contents($template);

    if ($contents === false) {
        return ['Could not read .env_example, so .env was not created. Copy it across by hand.', false];
    }

    // 32 bytes, hex-encoded. random_bytes() is the cryptographically secure source and throws
    // rather than returning weak output if the system cannot provide one — which is a failure
    // worth stopping for, unlike the file-level ones above.
    $contents = preg_replace(
        '/^APP_SECRET=$/m',
        'APP_SECRET=' . bin2hex(random_bytes(32)),
        $contents,
        1,
        $replaced
    );

    if ($contents === null || file_put_contents($env, $contents) === false) {
        return ['Could not write .env. Run: cp .env_example .env — then set APP_SECRET yourself.', false];
    }

    // .env holds credentials, so it is readable by its owner and nobody else. Filesystems that
    // cannot express that (a Windows share, some containers) are not worth failing over.
    @chmod($env, 0600);

    return [$replaced === 1
        ? 'Created .env from .env_example, with a freshly generated APP_SECRET.'
        : 'Created .env from .env_example. Set APP_SECRET yourself — the template no longer carried an empty one to fill.',
        true];
}

// Guarded so a test can require this file for its function without the side effects.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    [$message, $usable] = monad_prepare_env_file(dirname(__DIR__));

    fwrite(STDOUT, PHP_EOL . '  ' . $message . PHP_EOL);

    if ($usable) {
        fwrite(STDOUT, <<<'NEXT'

  Next, in .env:
    DB_*               your database, or DB_DRIVER=sqlite for a file you do not have to install
    CHECKOUT_GATEWAY   only if you take payments — then php mitosis checkout:install
    MAIL_MAILERS       defaults to smtp on 127.0.0.1

  Then: php mitosis setup && php mitosis migrate && php mitosis serve

NEXT);
    }

    // Always 0: see the docblock — a scaffold that aborts is worse than one that explains.
    exit(0);
}
