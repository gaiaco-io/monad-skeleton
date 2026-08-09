<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\AppStatus;
use Monad\Clarity\Services\DB;
use Monad\Clarity\Services\Migration;
use PDO;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

final class AppStatusTest extends TestCase
{
    #[Before]
    public function setUpDatabase(): void
    {
        DB::useConnection(new PDO('sqlite::memory:'));
    }

    #[After]
    public function resetDatabase(): void
    {
        DB::reset();
    }

    public function testChecksReturnsOneEntryPerCheckWithTheExpectedLabels(): void
    {
        Migration::migrate(dirname(__DIR__, 3) . '/database/migrations');

        $labels = array_column(AppStatus::checks(), 'label');

        self::assertSame(['PHP', 'Environment', 'Database', 'Migrations'], $labels);
    }

    public function testPhpCheckAlwaysPassesAndReportsTheRunningVersion(): void
    {
        $php = array_values(array_filter(AppStatus::checks(), static fn (array $c): bool => $c['label'] === 'PHP'))[0];

        self::assertTrue($php['ok']);
        self::assertSame(PHP_VERSION, $php['detail']);
    }

    public function testDatabaseCheckPassesWhenConnected(): void
    {
        $db = array_values(array_filter(AppStatus::checks(), static fn (array $c): bool => $c['label'] === 'Database'))[0];

        self::assertTrue($db['ok']);
    }

    public function testDatabaseCheckFailsWhenNotConfigured(): void
    {
        DB::reset();

        $db = array_values(array_filter(AppStatus::checks(), static fn (array $c): bool => $c['label'] === 'Database'))[0];

        self::assertFalse($db['ok']);
    }

    public function testMigrationsCheckPassesOnceEveryMigrationIsApplied(): void
    {
        Migration::migrate(dirname(__DIR__, 3) . '/database/migrations');

        $migrations = array_values(array_filter(AppStatus::checks(), static fn (array $c): bool => $c['label'] === 'Migrations'))[0];

        self::assertTrue($migrations['ok']);
        self::assertSame('up to date', $migrations['detail']);
    }

    public function testMigrationsCheckFailsWhenMigrationsArePending(): void
    {
        $migrations = array_values(array_filter(AppStatus::checks(), static fn (array $c): bool => $c['label'] === 'Migrations'))[0];

        self::assertFalse($migrations['ok']);
        self::assertStringContainsString('pending', $migrations['detail']);
    }
}
