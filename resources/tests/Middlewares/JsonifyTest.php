<?php

declare(strict_types=1);

namespace App\Tests\Middlewares;

use App\Middlewares\Jsonify;
use Gaia\Clarity\Services\Request;
use Gaia\Clarity\Services\Response;
use PHPUnit\Framework\TestCase;

final class JsonifyTest extends TestCase
{
    public function testValidJsonBodyPopulatesTheRequestJsonBag(): void
    {
        $jsonify = new Jsonify();

        $request = Request::fromArrays(
            server: ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
            rawBody: '{"name":"Marshal"}',
        );

        $response = $jsonify($request, static fn (Request $r): Response => Response::json(['echo' => $r->json('name')]));

        self::assertSame(['echo' => 'Marshal'], json_decode($response->content(), true));
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
