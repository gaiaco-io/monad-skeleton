<?php

declare(strict_types=1);

namespace App\Tests\Middlewares;

use App\Middlewares\Logger;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    private const LOG_PATH = PATH['error_log'] . 'app.log';

    #[After]
    public function removeTestLogFile(): void
    {
        if (is_file(self::LOG_PATH)) {
            unlink(self::LOG_PATH);
        }
    }

    public function testLogWritesToTheAppErrorChannel(): void
    {
        (new Logger())->log('error', 'Something went wrong in a test.');

        self::assertFileExists(self::LOG_PATH);
        self::assertStringContainsString('Something went wrong in a test.', (string) file_get_contents(self::LOG_PATH));
    }
}
