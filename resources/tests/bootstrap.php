<?php

/**
 * Test-only bootstrap (phpunit.xml.dist's `bootstrap` attribute) — sets up the small
 * amount of ambient state several App\Middlewares\* stubs read at construction time
 * (PATH, APP, APP_SECRET) without requiring a real .env file or database connection.
 * Deliberately NOT config/bootstrap.php itself: that also wires DB/Session/View/Console
 * against real app config, which individual tests set up themselves (in-memory SQLite,
 * View::configure() against fixtures, etc.) so each test controls its own environment.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

putenv('APP_SECRET=test-suite-secret-do-not-use-in-production');
putenv('APP_NAME=Test App');
putenv('BASE_URL=http://127.0.0.1');

require __DIR__ . '/../../config/dir.php';

if (!defined('APP')) {
    define('APP', ['name' => 'Test App', 'base_url' => 'http://127.0.0.1']);
}
