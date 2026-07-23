<?php

declare(strict_types=1);

namespace App\Tests\Middlewares;

use App\Middlewares\Csrf;
use Gaia\Clarity\Services\Request;
use Gaia\Clarity\Services\Response;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    private const HOST = '127.0.0.1:8000';

    private function next(): callable
    {
        return static fn (Request $request): Response => Response::text('ok');
    }

    private static function request(string $method, array $extraServer = [], array $input = []): Request
    {
        return Request::fromArrays(
            input: $input,
            server: ['REQUEST_METHOD' => $method, 'HTTP_HOST' => self::HOST, ...$extraServer],
        );
    }

    public function testSafeMethodsPassThroughWithoutAToken(): void
    {
        $csrf = new Csrf();
        $response = $csrf(self::request('GET'), $this->next());

        self::assertSame('ok', $response->content());
    }

    public function testTokenForIssuesAVerifiableStatelessToken(): void
    {
        $csrf = new Csrf();
        $token = $csrf->tokenFor(self::request('GET'));

        $response = $csrf(
            self::request('POST', ['HTTP_ORIGIN' => 'http://' . self::HOST], ['_csrf' => $token]),
            $this->next()
        );

        self::assertSame('ok', $response->content());
    }

    public function testPostWithoutATokenIsRejected(): void
    {
        $csrf = new Csrf();
        $response = $csrf(self::request('POST', ['HTTP_ORIGIN' => 'http://' . self::HOST]), $this->next());

        self::assertSame(403, $response->status());
    }

    public function testPostWithAMismatchedOriginIsRejectedEvenWithAValidToken(): void
    {
        $csrf = new Csrf();
        $token = $csrf->tokenFor(self::request('GET'));

        $response = $csrf(
            self::request('POST', ['HTTP_ORIGIN' => 'http://evil.example'], ['_csrf' => $token]),
            $this->next()
        );

        self::assertSame(403, $response->status());
    }
}
