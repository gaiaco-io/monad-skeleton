<?php

declare(strict_types=1);

namespace App\Tests\Config;

use InvalidArgumentException;
use Monad\Clarity\Services\Mail;
use Monad\Clarity\Services\Mail\MailerPool;
use Monad\Clarity\Services\MailAdapters\Postmark;
use Monad\Clarity\Services\MailAdapters\Resend;
use Monad\Clarity\Services\MailAdapters\Smtp;
use PHPUnit\Framework\TestCase;

/**
 * `config/mail.php` carries the whole of the multi-mailer decision: one name in
 * `MAIL_MAILERS` returns a single adapter, several return a `MailerPool` in that order, and
 * there is no separate "enable failover" flag (Clarity `ReleaseNotes_1.6.0.md` §2.6).
 *
 * Pinned here because the file is configuration rather than code, and configuration is
 * exactly what nobody notices has quietly stopped doing what its comment says.
 */
final class MailConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $original = [];

    private const KEYS = [
        'MAIL_MAILERS',
        'POSTMARK_SERVER_TOKEN',
        'RESEND_API_KEY',
        'SMTP_HOST',
        'SMTP_USERNAME',
        'SMTP_PASSWORD',
        'SMTP_ENCRYPTION',
    ];

    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->original[$key] = $_ENV[$key] ?? false;
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->original as $key => $value) {
            if ($value === false) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $value;
            }
        }
    }

    private static function load(): Mail
    {
        // Required fresh each time: the file returns a newly built mailer per require, which
        // is what lets a test vary the environment between loads.
        return require __DIR__ . '/../../../config/mail.php';
    }

    public function testDefaultsToASingleSmtpMailerWithNoConfiguration(): void
    {
        $mail = self::load();

        self::assertInstanceOf(Smtp::class, $mail);
        self::assertSame('smtp', $mail->mailerName());
    }

    public function testOneNameReturnsThatAdapterAndNotAPool(): void
    {
        $_ENV['MAIL_MAILERS'] = 'postmark';
        $_ENV['POSTMARK_SERVER_TOKEN'] = 'token';

        $mail = self::load();

        self::assertInstanceOf(Postmark::class, $mail);
        self::assertNotInstanceOf(MailerPool::class, $mail);
    }

    /** The list is the failover setting — §2.6, and the point of the whole file. */
    public function testSeveralNamesReturnAPoolInTheOrderGiven(): void
    {
        $_ENV['MAIL_MAILERS'] = 'postmark,resend,smtp';
        $_ENV['POSTMARK_SERVER_TOKEN'] = 'token';
        $_ENV['RESEND_API_KEY'] = 'key';

        $mail = self::load();

        self::assertInstanceOf(MailerPool::class, $mail);

        $members = $mail->mailers();
        self::assertCount(3, $members);
        self::assertInstanceOf(Postmark::class, $members[0]);
        self::assertInstanceOf(Resend::class, $members[1]);
        self::assertInstanceOf(Smtp::class, $members[2]);

        self::assertSame('pool(postmark+resend+smtp)', $mail->mailerName());
    }

    public function testToleratesWhitespaceAndTrailingSeparatorsInTheList(): void
    {
        $_ENV['MAIL_MAILERS'] = ' resend , smtp , ';
        $_ENV['RESEND_API_KEY'] = 'key';

        $mail = self::load();

        self::assertInstanceOf(MailerPool::class, $mail);
        self::assertCount(2, $mail->mailers());
    }

    public function testAnUnknownNameFailsLoudlyAndNamesTheValidOnes(): void
    {
        $_ENV['MAIL_MAILERS'] = 'sparkpost';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a mailer this file can build');

        self::load();
    }

    /**
     * Switching encryption off has to be written down: a silent downgrade is
     * indistinguishable from an interception, so it is never inferred from the port.
     */
    public function testSmtpEncryptionIsNamedRatherThanInferred(): void
    {
        $_ENV['MAIL_MAILERS'] = 'smtp';
        $_ENV['SMTP_ENCRYPTION'] = 'none';

        self::assertInstanceOf(Smtp::class, self::load());
    }

    /** Clarity refuses a half-written credential; the config must not paper over it. */
    public function testAUsernameWithoutAPasswordIsRefused(): void
    {
        $_ENV['MAIL_MAILERS'] = 'smtp';
        $_ENV['SMTP_USERNAME'] = 'someone';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('both a username and a password, or neither');

        self::load();
    }
}
