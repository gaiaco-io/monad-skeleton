<?php

declare(strict_types=1);

namespace App\Middlewares;

use Gaia\Clarity\Middlewares\Jsonify as BaseJsonify;

/**
 * Thin extension of Clarity's Jsonify engine (CrossRepoContracts.md §5), with defaults
 * as-is from the parent — override the constructor args below once the API's actual
 * content-type/body-size requirements are known.
 *
 * @package App\Middlewares
 */
final class Jsonify extends BaseJsonify
{
    public function __construct()
    {
        parent::__construct();
    }
}
