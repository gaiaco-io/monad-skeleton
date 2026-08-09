<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Models\UserModel;
use App\Services\Registration;
use App\Services\RegistrationException;
use Monad\Clarity\Services\DB;
use Monad\Clarity\Services\Event;
use Monad\Clarity\Services\Migration;
use PDO;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

final class RegistrationTest extends TestCase
{
    #[Before]
    public function setUpDatabase(): void
    {
        DB::useConnection(new PDO('sqlite::memory:'));
        Migration::migrate(dirname(__DIR__, 3) . '/database/migrations');
    }

    #[After]
    public function tearDown(): void
    {
        DB::reset();
        Event::forget();
    }

    public function testRegisterCreatesTheUserAndDispatchesUserRegistered(): void
    {
        $payload = null;
        Event::listen(Event::USER_REGISTERED, function (mixed $p) use (&$payload): void {
            $payload = $p;
        });

        $id = Registration::register('ada@example.com', 'correct horse battery staple', 'Ada Lovelace');

        self::assertTrue(UserModel::emailExists('ada@example.com'));
        self::assertSame(['id' => $id, 'email' => 'ada@example.com'], $payload);
    }

    public function testRegisterRejectsADuplicateEmail(): void
    {
        Registration::register('ada@example.com', 'correct horse battery staple');

        try {
            Registration::register('ada@example.com', 'another correct horse staple');
            self::fail('Expected a RegistrationException.');
        } catch (RegistrationException $e) {
            self::assertContains('That email is already registered.', $e->errors());
        }
    }

    public function testRegisterRejectsAnInvalidEmailFormat(): void
    {
        try {
            Registration::register('not-an-email', 'correct horse battery staple');
            self::fail('Expected a RegistrationException.');
        } catch (RegistrationException $e) {
            self::assertContains('Enter a valid email address.', $e->errors());
        }
    }

    public function testRegisterRejectsAWeakPassword(): void
    {
        try {
            Registration::register('ada@example.com', 'short1');
            self::fail('Expected a RegistrationException.');
        } catch (RegistrationException $e) {
            self::assertContains('Password must be at least 10 characters.', $e->errors());
        }
    }

    public function testFailedValidationCreatesNoUserAndDispatchesNoEvent(): void
    {
        $called = false;
        Event::listen(Event::USER_REGISTERED, function () use (&$called): void {
            $called = true;
        });

        try {
            Registration::register('ada@example.com', 'short1');
        } catch (RegistrationException) {
        }

        self::assertFalse(UserModel::emailExists('ada@example.com'));
        self::assertFalse($called);
    }
}
