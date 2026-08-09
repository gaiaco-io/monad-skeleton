<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function testAcceptsALongEnoughUncommonPassword(): void
    {
        self::assertSame([], PasswordPolicy::validate('correct horse battery staple'));
    }

    public function testRejectsAPasswordShorterThanTheMinimumLength(): void
    {
        $violations = PasswordPolicy::validate('short1');

        self::assertContains('Password must be at least 10 characters.', $violations);
    }

    public function testRejectsAPasswordLongerThanTheMaximumLength(): void
    {
        $violations = PasswordPolicy::validate(str_repeat('a', PasswordPolicy::MAX_LENGTH + 1));

        self::assertContains('Password must be no more than 256 characters.', $violations);
    }

    public function testRejectsACommonPasswordCaseInsensitively(): void
    {
        self::assertNotEmpty(PasswordPolicy::validate('Password1'));
        self::assertNotEmpty(PasswordPolicy::validate('TRUSTNO1'));
    }

    public function testRejectsAPasswordMatchingTheEmailLocalPart(): void
    {
        $violations = PasswordPolicy::validate('Marshal99', 'Marshal99@example.com');

        self::assertContains('Password must not match your email address.', $violations);
    }

    public function testDoesNotFlagTheLocalPartCheckWhenNoEmailIsGiven(): void
    {
        // A password long/uncommon enough to pass every other rule on its own.
        self::assertSame([], PasswordPolicy::validate('correct horse battery staple'));
    }

    public function testCanReportMultipleViolationsAtOnce(): void
    {
        $violations = PasswordPolicy::validate('123456');

        self::assertGreaterThan(1, count($violations));
    }
}
