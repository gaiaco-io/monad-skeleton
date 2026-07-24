<?php

declare(strict_types=1);

namespace App\Middlewares;

use Monad\Clarity\Middlewares\RBAC as BaseRBAC;
use Monad\Clarity\Services\DB;

/**
 * Thin extension of Clarity's RBAC engine (CrossRepoContracts.md §5). A minimal
 * role -> permission map is enough for a fresh skeleton (users.role is a plain string
 * column, see database/migrations/20260101000000_create_users_table.php) — grow this
 * into a real roles/permissions table if the app's access model outgrows two roles.
 *
 * @package App\Middlewares
 */
final class RBAC extends BaseRBAC
{
    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        'admin' => ['users.view', 'users.create', 'users.delete'],
        'member' => ['users.view'],
    ];

    public function __construct()
    {
        parent::__construct(
            permissionsForUser: static function (string $userId): array {
                $role = DB::run('SELECT role FROM users WHERE id = ? LIMIT 1', [$userId])->fetch()['role'] ?? null;

                return self::ROLE_PERMISSIONS[$role] ?? [];
            },
            permissionsForRole: static fn (string $role): array => self::ROLE_PERMISSIONS[$role] ?? [],
        );
    }
}
