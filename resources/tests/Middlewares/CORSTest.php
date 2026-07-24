<?php

declare(strict_types=1);

namespace App\Tests\Middlewares;

use App\Middlewares\CORS;
use Monad\Clarity\Services\Request;
use Monad\Clarity\Services\Response;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class CORSTest extends TestCase
{
    #[After]
    public function clearCorsAllowedOriginsEnv(): void
    {
        putenv('CORS_ALLOWED_ORIGINS');
    }

    private function next(): callable
    {
        return static fn (Request $request): Response => Response::text('ok');
    }

    public function testDefaultsToAllowAllWhenNoEnvVarIsSet(): void
    {
        $cors = new CORS();
        $response = $cors(
            Request::fromArrays(server: ['REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'https://app.example.com']),
            $this->next()
        );

        self::assertSame('*', $response->header('Access-Control-Allow-Origin'));
    }

    public function testParsesACommaSeparatedAllowlistFromTheEnvVar(): void
    {
        putenv('CORS_ALLOWED_ORIGINS=https://app.example.com, https://admin.example.com');

        $cors = new CORS();

        $allowed = $cors(
            Request::fromArrays(server: ['REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'https://admin.example.com']),
            $this->next()
        );
        $disallowed = $cors(
            Request::fromArrays(server: ['REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'https://evil.example']),
            $this->next()
        );

        self::assertSame('https://admin.example.com', $allowed->header('Access-Control-Allow-Origin'));
        self::assertNull($disallowed->header('Access-Control-Allow-Origin'));
    }
}
