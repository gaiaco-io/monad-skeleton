<?php

declare(strict_types=1);

namespace App\Tests\Middlewares;

use App\Middlewares\Jsonify;
use Gaia\Clarity\Services\Request;
use Gaia\Clarity\Services\Response;
use PHPUnit\Framework\TestCase;

final class JsonifyTest extends TestCase
{
    /**
     * A plain assertion on $r->json('name') wouldn't distinguish Jsonify actually running
     * from a no-op middleware — Request::json() lazy-parses the raw body itself with
     * identical results when no bag was set (CrossRepoContracts.md §6 makes the two paths
     * indistinguishable from the caller's side, by design). What Jsonify alone does is
     * pass $next a *new* Request instance (withJsonBag() returns a fresh immutable copy)
     * rather than the exact same object — that's the assertion that actually pins down
     * Jsonify ran, not just that json() happens to work.
     */
    public function testValidJsonBodyReachesNextThroughANewRequestInstanceCarryingTheParsedBag(): void
    {
        $jsonify = new Jsonify();

        $originalRequest = Request::fromArrays(
            server: ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
            rawBody: '{"name":"Marshal"}',
        );

        $receivedRequest = null;
        $jsonify($originalRequest, function (Request $r) use (&$receivedRequest): Response {
            $receivedRequest = $r;

            return Response::text('ok');
        });

        self::assertNotSame($originalRequest, $receivedRequest);
        self::assertSame('Marshal', $receivedRequest->json('name'));
    }

    public function testMalformedJsonReturns400(): void
    {
        $jsonify = new Jsonify();

        $request = Request::fromArrays(
            server: ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
            rawBody: '{not valid',
        );

        $response = $jsonify($request, static fn (Request $r): Response => Response::text('unreachable'));

        self::assertSame(400, $response->status());
    }
}
