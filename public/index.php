<?php

/**
 * Web entry point (CrossRepoContracts.md §2.1) — thin by design. Boots via
 * config/bootstrap.php, registers the app's routes, then hands the captured request to
 * Clarity's Route kernel and emits whatever Response comes back.
 */

declare(strict_types=1);

use App\Middlewares\Csrf;
use Monad\Clarity\Services\Request;
use Monad\Clarity\Services\Route;
use Monad\Clarity\Services\View;

require __DIR__ . '/../config/bootstrap.php';

require __DIR__ . '/../app/routes/web.php';
require __DIR__ . '/../app/routes/api.php';

$request = Request::capture();

View::share('csrf_token', (new Csrf())->tokenFor($request));

Route::dispatch($request)->send();
