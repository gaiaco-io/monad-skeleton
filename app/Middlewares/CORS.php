<?php

declare(strict_types=1);

namespace App\Middlewares;

use Gaia\Clarity\Middlewares\CORS as BaseCORS;

/**
 * Thin extension of Clarity's CORS engine (CrossRepoContracts.md §5). Defaults to
 * allow-all with no credentials — the safest default for a fresh skeleton, since
 * enabling credentialed cross-origin requests without an explicit allowlist would be a
 * live security hole. Set CORS_ALLOWED_ORIGINS (comma-separated) in .env for a real API.
 *
 * @package App\Middlewares
 */
final class CORS extends BaseCORS
{
    public function __construct()
    {
        $origins = (string) getenv('CORS_ALLOWED_ORIGINS');

        parent::__construct(
            allowedOrigins: $origins !== '' ? array_map('trim', explode(',', $origins)) : ['*'],
        );
    }
}
