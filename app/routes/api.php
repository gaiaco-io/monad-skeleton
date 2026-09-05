<?php

/**
 * Machine-facing routes — loaded by public/index.php immediately after web.php. Anything
 * here answers a program rather than a browser: no views, no session, no CSRF token.
 */

declare(strict_types=1);

use App\Controllers\CheckoutCallbackController;
use Monad\Clarity\Services\Route;

/**
 * The payment gateway's callback endpoint (Clarity `Services\Checkout` §9.6.4). Give this
 * path to Stripe > Webhooks or Paddle > Notifications, and put the signing secret it issues
 * back into `.env` — Clarity refuses to verify a callback without one rather than accepting
 * it unverified, so the endpoint answers 400 until the secret is set.
 *
 * No middleware, deliberately. A gateway holds no CSRF token and no session, and the body
 * must reach parseCallback() as the exact bytes that were signed — so Csrf, Authentication
 * and Jsonify are each omitted for a reason the controller's docblock sets out in full.
 * Read it before adding one here.
 */
Route::post('/webhooks/checkout', [CheckoutCallbackController::class, 'receive']);
