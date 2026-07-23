<?php

declare(strict_types=1);

namespace App\Tests\Middlewares;

use App\Middlewares\RBAC;
use App\Models\UserModel;
use Gaia\Clarity\Services\DB;
use Gaia\Clarity\Services\Migration;
use PDO;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

final class RBACTest extends TestCase
{
    #[Before]
    public function setUpDatabase(): void
    {
        DB::useConnection(new PDO('sqlite::memory:'));
        Migration::migrate(dirname(__DIR__, 3) . '/database/migrations');
    }

    #[After]
    public function tearDownDatabase(): void
    {
        DB::reset();
    }

    public function testDefaultMemberRoleCanOnlyViewUsers(): void
    {
        $id = UserModel::create('member@example.com', 'password');

        $rbac = new RBAC();

        self::assertTrue($rbac->can($id, 'users.view'));
        self::assertFalse($rbac->can($id, 'users.delete'));
    }

    public function testAdminRoleHasFullUserPermissions(): void
    {
        $id = UserModel::create('admin@example.com', 'password');
        DB::run('UPDATE users SET role = ? WHERE id = ?', ['admin', $id]);

        $rbac = new RBAC();

        self::assertTrue($rbac->can($id, 'users.view'));
        self::assertTrue($rbac->can($id, 'users.create'));
        self::assertTrue($rbac->can($id, 'users.delete'));
    }

    public function testRoleHasPermissionChecksARoleIndependentOfAnyUser(): void
    {
        $rbac = new RBAC();

        self::assertTrue($rbac->roleHasPermission('admin', 'users.delete'));
        self::assertFalse($rbac->roleHasPermission('member', 'users.delete'));
    }
}
