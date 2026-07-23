<?php

declare(strict_types=1);

namespace App\Tests\Models;

use App\Models\UserModel;
use Gaia\Clarity\Services\DB;
use Gaia\Clarity\Services\Migration;
use PDO;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

final class UserModelTest extends TestCase
{
    #[Before]
    public function setUpDatabase(): void
    {
        DB::useConnection(new PDO('sqlite::memory:'));
        Migration::migrate(dirname(__DIR__, 3) . '/database/migrations');
    }

    #[After]
    public function resetDatabase(): void
    {
        DB::reset();
    }

    public function testCreateThenFindByIdRoundTrips(): void
    {
        $id = UserModel::create('marshal@example.com', 'correct horse battery staple', 'Marshal');

        $user = UserModel::findById($id);

        self::assertNotNull($user);
        self::assertSame('marshal@example.com', $user['email']);
        self::assertSame('Marshal', $user['full_name']);
        self::assertSame('member', $user['role']);
    }

    public function testEmailExistsReflectsWhetherAUserWasCreated(): void
    {
        self::assertFalse(UserModel::emailExists('marshal@example.com'));

        UserModel::create('marshal@example.com', 'correct horse battery staple');

        self::assertTrue(UserModel::emailExists('marshal@example.com'));
    }

    public function testAllReturnsEveryUserOrderedByEmail(): void
    {
        UserModel::create('zoe@example.com', 'password');
        UserModel::create('amir@example.com', 'password');

        $emails = array_column(UserModel::all(), 'email');

        self::assertSame(['amir@example.com', 'zoe@example.com'], $emails);
    }
}
