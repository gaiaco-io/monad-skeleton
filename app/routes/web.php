<?php

use Gaia\Clarity\Services\Route;
use Gaia\Clarity\Services\CsrfService;
use Gaia\Clarity\Services\Response;
use Gaia\Herodo\Controllers\UserController;
use Gaia\Herodo\Models\UserModel;

Route::get('/login', [UserController::class, 'login']);

Route::post('/login', function () {
    (new CsrfService())->requireValid();

    if ((new UserModel())->signIn()) {
        Response::redirect('/profile');
        return;
    }

    Response::redirect('/login');
});

Route::get('/signup', [UserController::class, 'create']);

Route::post('/signup', function () {
    (new CsrfService())->requireValid();
    UserController::store();
});

Route::post('/user/{id}', function (string $id) {
    (new CsrfService())->requireValid();
    UserController::store($id);
});

Route::post('/logout', function () {
    (new CsrfService())->requireValid();
    UserModel::signout();
    Response::redirect('/login');
});
