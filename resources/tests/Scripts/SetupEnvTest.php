<?php

declare(strict_types=1);

namespace App\Tests\Scripts;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

/**
 * `scripts/setup-env.php` — Composer's `post-create-project-cmd`.
 *
 * Worth pinning because it runs exactly once in a project's life, on a machine nobody is
 * watching, and its most important behaviour is the one that is invisible when it works: a
 * generated `APP_SECRET`. A blank secret does not raise anything — Clarity's `HMAC::sign()`
 * signs happily with an empty key — so a regression here ships applications whose CSRF and
 * session tokens are forgeable, and every one of them looks perfectly healthy.
 */
final class SetupEnvTest extends TestCase
{
    private string $root;

    #[Before]
    public function makeProject(): void
    {
        require_once dirname(__DIR__, 3) . '/scripts/setup-env.php';

        $this->root = sys_get_temp_dir() . '/monad-setup-env-' . bin2hex(random_bytes(6));
        mkdir($this->root);
    }

    #[After]
    public function removeProject(): void
    {
        foreach (['/.env', '/.env_example'] as $file) {
            if (is_file($this->root . $file)) {
                unlink($this->root . $file);
            }
        }

        if (is_dir($this->root)) {
            rmdir($this->root);
        }
    }

    private function writeTemplate(string $contents = "APP_SECRET=\nCHECKOUT_GATEWAY=\nDB_DRIVER=mysql\n"): void
    {
        file_put_contents($this->root . '/.env_example', $contents);
    }

    private function env(): string
    {
        return (string) file_get_contents($this->root . '/.env');
    }

    public function testCreatesEnvFromTheTemplate(): void
    {
        $this->writeTemplate();

        [$message, $usable] = monad_prepare_env_file($this->root);

        self::assertTrue($usable);
        self::assertStringContainsString('Created .env', $message);
        self::assertFileExists($this->root . '/.env');
        self::assertStringContainsString('CHECKOUT_GATEWAY=', $this->env());
    }

    /** The whole reason this is a script and not a one-line copy() in composer.json. */
    public function testGeneratesARealAppSecret(): void
    {
        $this->writeTemplate();

        monad_prepare_env_file($this->root);

        self::assertMatchesRegularExpression('/^APP_SECRET=[0-9a-f]{64}$/m', $this->env());
    }

    /** Two projects must not share a secret, which is what a hardcoded default would give them. */
    public function testEachProjectGetsADifferentSecret(): void
    {
        $this->writeTemplate();
        monad_prepare_env_file($this->root);
        $first = $this->env();

        $second = sys_get_temp_dir() . '/monad-setup-env-' . bin2hex(random_bytes(6));
        mkdir($second);
        file_put_contents($second . '/.env_example', "APP_SECRET=\n");
        monad_prepare_env_file($second);
        $secondEnv = (string) file_get_contents($second . '/.env');

        unlink($second . '/.env');
        unlink($second . '/.env_example');
        rmdir($second);

        self::assertNotSame($first, $secondEnv);
    }

    /** A developer's configured .env is the only one that matters. */
    public function testNeverOverwritesAnExistingEnv(): void
    {
        $this->writeTemplate();
        file_put_contents($this->root . '/.env', "APP_SECRET=mine\nDB_DRIVER=sqlite\n");

        [$message, $usable] = monad_prepare_env_file($this->root);

        self::assertTrue($usable);
        self::assertStringContainsString('nothing was overwritten', $message);
        self::assertStringContainsString('APP_SECRET=mine', $this->env());
    }

    /** .env holds credentials, so it is readable by its owner and nobody else. */
    public function testTheCreatedEnvIsNotWorldReadable(): void
    {
        $this->writeTemplate();

        monad_prepare_env_file($this->root);

        self::assertSame('0600', substr(sprintf('%o', fileperms($this->root . '/.env')), -4));
    }

    /**
     * A secret already written into the template is a deliberate choice by whoever wrote it,
     * so it is left alone — and the message says so rather than implying one was generated.
     */
    public function testATemplateThatAlreadyCarriesASecretIsNotClobbered(): void
    {
        $this->writeTemplate("APP_SECRET=deliberate-value\nDB_DRIVER=mysql\n");

        [$message] = monad_prepare_env_file($this->root);

        self::assertStringContainsString('APP_SECRET=deliberate-value', $this->env());
        self::assertStringContainsString('Set APP_SECRET yourself', $message);
    }

    /** Reports rather than throwing: a scaffold that aborts leaves a half-installed project. */
    public function testAMissingTemplateIsReportedWithoutFailing(): void
    {
        [$message, $usable] = monad_prepare_env_file($this->root);

        self::assertFalse($usable);
        self::assertStringContainsString('No .env_example', $message);
        self::assertFileDoesNotExist($this->root . '/.env');
    }
}
