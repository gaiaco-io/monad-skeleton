<?php

declare(strict_types=1);

namespace App\Services;

use Monad\Clarity\Services\DB;
use Monad\Clarity\Services\Migration;
use Throwable;

/**
 * Live, presentation-facing app diagnostics for the home page — the same checks
 * `mitosis health` runs (Monad\Clarity\Console\Health), reshaped for display rather than
 * a CLI exit code. A fresh install answers "did this actually wire up?" by looking at the
 * page instead of running a command.
 *
 * @package App\Services
 */
final class AppStatus
{
    /**
     * @return list<array{label: string, ok: bool, detail: string}>
     */
    public static function checks(): array
    {
        return [
            self::phpCheck(),
            self::environmentCheck(),
            self::databaseCheck(),
            self::migrationsCheck(),
        ];
    }

    /**
     * @return array{label: string, ok: bool, detail: string}
     */
    private static function phpCheck(): array
    {
        return ['label' => 'PHP', 'ok' => true, 'detail' => PHP_VERSION];
    }

    /**
     * @return array{label: string, ok: bool, detail: string}
     */
    private static function environmentCheck(): array
    {
        $mode = (string) (getenv('ENV_MODE') ?: 'production');

        return ['label' => 'Environment', 'ok' => true, 'detail' => ucfirst($mode)];
    }

    /**
     * @return array{label: string, ok: bool, detail: string}
     */
    private static function databaseCheck(): array
    {
        $driver = (string) (getenv('DB_DRIVER') ?: 'mysql');

        try {
            DB::connect()->query('SELECT 1');

            return ['label' => 'Database', 'ok' => true, 'detail' => "Connected ({$driver})"];
        } catch (Throwable) {
            return ['label' => 'Database', 'ok' => false, 'detail' => "Not connected ({$driver})"];
        }
    }

    /**
     * @return array{label: string, ok: bool, detail: string}
     */
    private static function migrationsCheck(): array
    {
        $path = getcwd() . '/database/migrations';

        if (!is_dir($path)) {
            return ['label' => 'Migrations', 'ok' => true, 'detail' => 'none'];
        }

        try {
            $status = Migration::status($path);
        } catch (Throwable) {
            return ['label' => 'Migrations', 'ok' => false, 'detail' => 'unable to check — is the database reachable?'];
        }

        $pending = array_keys(array_filter($status, static fn (bool $applied): bool => !$applied));

        return $pending === []
            ? ['label' => 'Migrations', 'ok' => true, 'detail' => 'up to date']
            : ['label' => 'Migrations', 'ok' => false, 'detail' => count($pending) . ' pending'];
    }
}
