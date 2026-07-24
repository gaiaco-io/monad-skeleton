<?php

declare(strict_types=1);

namespace App\Tests\Middlewares;

use App\Middlewares\Authentication;
use App\Models\UserModel;
use Monad\Clarity\Console\Setup;
use Monad\Clarity\Services\Cache;
use Monad\Clarity\Services\DB;
use Monad\Clarity\Services\Event;
use Monad\Clarity\Services\Migration;
use Monad\Clarity\Services\Schema;
use PDO;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

final class AuthenticationTest extends TestCase
{
    // Every identifier attempt()ed across this file — App\Middlewares\Authentication
    // constructs its own login-throttling RateLimiter against the real file cache, so
    // each of these leaves a rate-limit entry there unless explicitly cleared.
    private const IDENTIFIERS = ['marshal@example.com', 'nobody@example.com'];

    #[Before]
    public function setUpDatabase(): void
    {
        DB::useConnection(new PDO('sqlite::memory:'));
        Schema::createTable('sessions', Setup::sessionsBlueprint('sqlite'));
        Schema::createTable('caches', Setup::cachesBlueprint());
        Migration::migrate(dirname(__DIR__, 3) . '/database/migrations');
    }

    #[After]
    public function tearDownDatabase(): void
    {
        DB::reset();
        Event::forget();

        $cache = new Cache(Cache::DRIVER_FILE, path: PATH['cache']);

        foreach (self::IDENTIFIERS as $identifier) {
            $cache->delete('ratelimit_' . hash('sha256', $identifier));
        }
    }

    public function testAttemptSucceedsWithCorrectCredentials(): void
    {
        UserModel::create('marshal@example.com', 'correct horse battery staple');

        $result = (new Authentication())->attempt('marshal@example.com', 'correct horse battery staple', '127.0.0.1', 'phpunit');

        self::assertTrue($result->success);
    }

    public function testAttemptFailsWithWrongPassword(): void
    {
        UserModel::create('marshal@example.com', 'correct horse battery staple');

        $result = (new Authentication())->attempt('marshal@example.com', 'wrong password', '127.0.0.1', 'phpunit');

        self::assertFalse($result->success);
        self::assertSame(Authentication::FAILURE_INVALID_CREDENTIALS, $result->failureReason);
    }

    public function testAttemptFailsForAnUnknownIdentifier(): void
    {
        $result = (new Authentication())->attempt('nobody@example.com', 'whatever', '127.0.0.1', 'phpunit');

        self::assertFalse($result->success);
        self::assertSame(Authentication::FAILURE_INVALID_CREDENTIALS, $result->failureReason);
    }

    public function testAttemptFailsForALockedAccount(): void
    {
        $id = UserModel::create('marshal@example.com', 'correct horse battery staple');
        DB::run('UPDATE users SET locked = 1 WHERE id = ?', [$id]);

        $result = (new Authentication())->attempt('marshal@example.com', 'correct horse battery staple', '127.0.0.1', 'phpunit');

        self::assertFalse($result->success);
        self::assertSame(Authentication::FAILURE_ACCOUNT_LOCKED, $result->failureReason);
    }
}
