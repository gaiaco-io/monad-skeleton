<?php

declare(strict_types=1);

namespace App\Models;

use Monad\Clarity\Services\DB;
use Monad\Clarity\Utils\Hash;

/**
 * Example model demonstrating DB usage against the app-owned `users` table (see
 * database/migrations/20260101000000_create_users_table.php). Authentication itself
 * never touches this class — its user resolver (App\Middlewares\Authentication) queries
 * the table directly, since Clarity's Authentication engine is storage-agnostic
 * (CrossRepoContracts.md §5).
 *
 * @package App\Models
 */
final class UserModel
{
    /**
     * @return list<array{id: string, email: string, full_name: ?string, role: string}>
     */
    public static function all(): array
    {
        return DB::run('SELECT id, email, full_name, role FROM users ORDER BY email ASC')->fetchAll();
    }

    /**
     * @return array{id: string, email: string, full_name: ?string, role: string}|null
     */
    public static function findById(string $id): ?array
    {
        $row = DB::run('SELECT id, email, full_name, role FROM users WHERE id = ? LIMIT 1', [$id])->fetch();

        return $row === false ? null : $row;
    }

    public static function emailExists(string $email): bool
    {
        return (bool) DB::run('SELECT 1 FROM users WHERE email = ? LIMIT 1', [$email])->fetch();
    }

    /**
     * @return string The new user's id.
     */
    public static function create(string $email, string $password, ?string $fullName = null): string
    {
        return DB::insert('users', [
            'email' => $email,
            'password_hash' => Hash::make($password),
            'full_name' => $fullName,
        ], DB::ID_TYPE_UUID);
    }
}
