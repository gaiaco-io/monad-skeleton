<?php

declare(strict_types=1);

namespace App\Tests\Middlewares;

use App\Middlewares\RateLimiter;
use Monad\Clarity\Services\Cache;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private const TEST_KEY = 'ratelimiter-test-key';

    #[After]
    public function clearTestKey(): void
    {
        (new Cache(Cache::DRIVER_FILE, path: PATH['cache']))->delete(self::keyHash());
    }

    private static function keyHash(): string
    {
        // Mirrors Clarity's own RateLimiter::CACHE_KEY_PREFIX ('ratelimit_') + sha256(key)
        // so the test can clean up after itself without a public accessor for it.
        return 'ratelimit_' . hash('sha256', self::TEST_KEY);
    }

    public function testConstructsWithDefaultsAndAllowsAttemptsWithinTheLimit(): void
    {
        $limiter = new RateLimiter(maxAttempts: 2, windowSeconds: 60);

        self::assertTrue($limiter->attempt(self::TEST_KEY));
        self::assertTrue($limiter->attempt(self::TEST_KEY));
    }

    public function testBlocksOnceTheLimitIsExceeded(): void
    {
        $limiter = new RateLimiter(maxAttempts: 2, windowSeconds: 60);

        $limiter->attempt(self::TEST_KEY);
        $limiter->attempt(self::TEST_KEY);

        self::assertFalse($limiter->attempt(self::TEST_KEY));
    }

    public function testClearResetsTheCounter(): void
    {
        $limiter = new RateLimiter(maxAttempts: 1, windowSeconds: 60);

        $limiter->attempt(self::TEST_KEY);
        self::assertFalse($limiter->attempt(self::TEST_KEY));

        $limiter->clear(self::TEST_KEY);

        self::assertTrue($limiter->attempt(self::TEST_KEY));
    }
}
