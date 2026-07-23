<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use Gaia\Clarity\Services\Request;
use Gaia\Clarity\Services\Response;
use Gaia\Clarity\Services\View;

/**
 * Example controller demonstrating the DB/View/Request wiring — deliberately not an
 * authentication flow (that's App\Middlewares\Authentication's job; see its docblock).
 *
 * @package App\Controllers
 */
final class UserController
{
    public static function index(Request $request): Response
    {
        return View::render('Users/index', ['users' => UserModel::all()]);
    }

    public static function create(Request $request): Response
    {
        return View::render('Users/create');
    }

    public static function store(Request $request): Response
    {
        $email = (string) $request->input('email');
        $password = (string) $request->input('password');
        $fullName = $request->input('full_name');

        if ($email === '' || $password === '') {
            return View::render('Users/create', ['error' => 'Email and password are required.'], status: 422);
        }

        if (UserModel::emailExists($email)) {
            return View::render('Users/create', ['error' => 'That email is already registered.'], status: 422);
        }

        UserModel::create($email, $password, is_string($fullName) ? $fullName : null);

        return Response::redirect('/users');
    }
}
