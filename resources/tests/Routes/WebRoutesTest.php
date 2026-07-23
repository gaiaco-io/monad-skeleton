<?php

declare(strict_types=1);

namespace App\Tests\Routes;

use App\Middlewares\Csrf;
use Gaia\Clarity\Services\DB;
use Gaia\Clarity\Services\Migration;
use Gaia\Clarity\Services\Request;
use Gaia\Clarity\Services\Response;
use Gaia\Clarity\Services\Route;
use Gaia\Clarity\Services\View;
use PDO;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

/**
 * Exercises app/routes/web.php through the real Route::dispatch() pipeline — the same
 * path public/index.php runs in production, including View::share('csrf_token', ...)
 * happening before dispatch, exactly as index.php does it. Fills the gap flagged after
 * Phase 7: manual curl verification covered this once, but nothing kept it covered.
 */
final class WebRoutesTest extends TestCase
{
    private const HOST = '127.0.0.1:8000';

    #[Before]
    public function setUpApp(): void
    {
        DB::useConnection(new PDO('sqlite::memory:'));
        Migration::migrate(dirname(__DIR__, 3) . '/database/migrations');

        View::configure(dirname(__DIR__, 3) . '/app/views');

        Route::reset();
        require dirname(__DIR__, 3) . '/app/routes/web.php';
    }

    #[After]
    public function tearDownApp(): void
    {
        DB::reset();
        View::reset();
        Route::reset();
    }

    private function dispatch(string $method, string $path, array $extraServer = [], array $input = []): Response
    {
        $request = Request::fromArrays(
            input: $input,
            server: ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $path, 'HTTP_HOST' => self::HOST, ...$extraServer],
        );

        View::share('csrf_token', (new Csrf())->tokenFor($request));

        return Route::dispatch($request);
    }

    public function testHomeRendersWelcomeContent(): void
    {
        $response = $this->dispatch('GET', '/');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Welcome to Monad', $response->content());
    }

    public function testUsersIndexListsCreatedUsers(): void
    {
        \App\Models\UserModel::create('marshal@example.com', 'password', 'Marshal');

        $response = $this->dispatch('GET', '/users');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('marshal@example.com', $response->content());
    }

    public function testUsersCreateRendersTheForm(): void
    {
        $response = $this->dispatch('GET', '/users/create');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('<form', $response->content());
    }

    public function testCreatingAUserWithAValidTokenRedirectsToTheListing(): void
    {
        $csrfToken = (new Csrf())->tokenFor(Request::fromArrays(server: ['HTTP_HOST' => self::HOST]));

        $response = $this->dispatch(
            'POST',
            '/users',
            ['HTTP_ORIGIN' => 'http://' . self::HOST],
            ['email' => 'new@example.com', 'password' => 'password', 'full_name' => 'New User', '_csrf' => $csrfToken]
        );

        self::assertSame(302, $response->status());
        self::assertSame('/users', $response->header('Location'));
        self::assertTrue(\App\Models\UserModel::emailExists('new@example.com'));
    }

    public function testCreatingAUserWithoutACsrfTokenIsRejected(): void
    {
        $response = $this->dispatch(
            'POST',
            '/users',
            ['HTTP_ORIGIN' => 'http://' . self::HOST],
            ['email' => 'new@example.com', 'password' => 'password']
        );

        self::assertSame(403, $response->status());
        self::assertFalse(\App\Models\UserModel::emailExists('new@example.com'));
    }

    public function testCreatingAUserWithADuplicateEmailReturnsAValidationError(): void
    {
        \App\Models\UserModel::create('marshal@example.com', 'password');
        $csrfToken = (new Csrf())->tokenFor(Request::fromArrays(server: ['HTTP_HOST' => self::HOST]));

        $response = $this->dispatch(
            'POST',
            '/users',
            ['HTTP_ORIGIN' => 'http://' . self::HOST],
            ['email' => 'marshal@example.com', 'password' => 'password', '_csrf' => $csrfToken]
        );

        self::assertSame(422, $response->status());
        self::assertStringContainsString('already registered', $response->content());
    }

    public function testUnknownRouteFallsBackToTheStyled404(): void
    {
        $response = $this->dispatch('GET', '/does-not-exist');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('404', $response->content());
    }
}
