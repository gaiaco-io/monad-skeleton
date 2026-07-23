<?php

declare(strict_types=1);

namespace App\Middlewares;

use Gaia\Clarity\Middlewares\Csrf as BaseCsrf;

/**
 * Thin extension of Clarity's Csrf engine (CrossRepoContracts.md §5). Registered as a
 * bare class-string in route middleware (`->middleware(Csrf::class)`), so this must keep
 * a zero-argument constructor — customise by overriding requiresValidation(),
 * isExcluded(), originIsTrusted(), or reject() from the parent class.
 *
 * @package App\Middlewares
 */
final class Csrf extends BaseCsrf
{
    public function __construct()
    {
        parent::__construct(
            hmacSecret: (string) getenv('APP_SECRET'),
            excludedPaths: [],
        );
    }
}
