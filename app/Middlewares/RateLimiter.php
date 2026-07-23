<?php

declare(strict_types=1);

namespace App\Middlewares;

use Gaia\Clarity\Middlewares\RateLimiter as BaseRateLimiter;
use Gaia\Clarity\Services\Cache;

/**
 * Thin extension of Clarity's RateLimiter engine (CrossRepoContracts.md §5). Backed by
 * the file cache driver at storage/cache/ — swap for the database or Redis driver in
 * production by changing the Cache constructor below. Required call sites per §28.2:
 * login, password reset, public API, LLM operations, webhook abuse protection.
 *
 * @package App\Middlewares
 */
final class RateLimiter extends BaseRateLimiter
{
    public function __construct(int $maxAttempts = 10, int $windowSeconds = 60)
    {
        parent::__construct(
            cache: new Cache(Cache::DRIVER_FILE, path: PATH['cache']),
            maxAttempts: $maxAttempts,
            windowSeconds: $windowSeconds,
        );
    }
}
