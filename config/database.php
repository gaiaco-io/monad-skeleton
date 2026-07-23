<?php

/**
 * Builds the $DATABASE array config/bootstrap.php hands to DB::configure('default', ...).
 * Supports mysql (production default), pgsql, and sqlite (handy for local development —
 * no server to install). DB_DRIVER selects the DSN shape; everything else is read from
 * .env with sensible defaults.
 */

declare(strict_types=1);

$env = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return is_string($value) ? trim($value) : $value;
};

$driver = $env('DB_DRIVER', 'mysql');

$dsn = match ($driver) {
    'sqlite' => 'sqlite:' . rtrim((string) $env('DB_DATABASE', dirname(__DIR__) . '/storage/database.sqlite'), '/'),
    'pgsql' => sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $env('DB_HOST', '127.0.0.1'),
        $env('DB_PORT', '5432'),
        $env('DB_DATABASE', '')
    ),
    default => sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $env('DB_HOST', '127.0.0.1'),
        $env('DB_PORT', '3306'),
        $env('DB_DATABASE', ''),
        $env('DB_CHARSET', 'utf8mb4')
    ),
};

$DATABASE = [
    'dsn' => $dsn,
    'username' => $driver === 'sqlite' ? null : $env('DB_USERNAME', 'root'),
    'password' => $driver === 'sqlite' ? null : $env('DB_PASSWORD'),
];
