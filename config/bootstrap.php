<?php

/**
 * The single shared boot path for the web front controller, the mitosis CLI, and any
 * ad-hoc script (CrossRepoContracts.md §2.2). Required exactly once, before anything else
 * runs: Composer autoloader, .env, then hand config to each Clarity service that needs it.
 * Nothing here is business logic — that belongs in app/.
 */

declare(strict_types=1);

use Dotenv\Dotenv;
use Gaia\Clarity\Services\Console;
use Gaia\Clarity\Services\DB;
use Gaia\Clarity\Services\Mediator;
use Gaia\Clarity\Services\Session;
use Gaia\Clarity\Services\View;

require __DIR__ . '/../vendor/autoload.php';

$rootPath = dirname(__DIR__);

if (is_file($rootPath . '/.env')) {
    Dotenv::createImmutable($rootPath)->load();
}

$env = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return is_string($value) ? trim($value) : $value;
};

date_default_timezone_set($env('TIMEZONE', 'UTC'));

// Middlewares\MetaTag reads this ambient constant directly (name/base_url) rather than
// an explicit configure() call — a pre-existing convention owned by the consuming app,
// not something Clarity defines on its own (see Clarity's MetaTagTest.php).
if (!defined('APP')) {
    define('APP', [
        'name' => $env('APP_NAME', 'Monad'),
        'base_url' => $env('BASE_URL', ''),
    ]);
}

require __DIR__ . '/dir.php';
require __DIR__ . '/database.php';

DB::configure('default', $DATABASE);

Session::configure((int) $env('SESSION_TIMEOUT', 1800));

View::configure($PATH['view']);

Mediator::configure(debug: $env('ENV_MODE', 'production') !== 'production');
Mediator::register();

Console::configure(dirname(__DIR__) . '/app/routes/cli.php');
