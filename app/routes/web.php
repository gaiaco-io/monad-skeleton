<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Middlewares\Csrf;
use Monad\Clarity\Services\Request;
use Monad\Clarity\Services\Route;
use Monad\Clarity\Services\View;

Route::get('/', fn () => View::render('Home/index'));

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/create', [UserController::class, 'create']);
Route::post('/users', [UserController::class, 'store'])->middleware(Csrf::class);

Route::fallback(fn (Request $request) => View::render('Errors/404', status: 404));
