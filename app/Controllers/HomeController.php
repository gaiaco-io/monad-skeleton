<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AppStatus;
use Monad\Clarity\Services\Request;
use Monad\Clarity\Services\Response;
use Monad\Clarity\Services\View;

final class HomeController
{
    public static function index(Request $request): Response
    {
        return View::render('Home/index', ['checks' => AppStatus::checks()]);
    }
}
