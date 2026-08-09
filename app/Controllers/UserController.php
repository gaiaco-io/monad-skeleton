<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\Registration;
use App\Services\RegistrationException;
use Monad\Clarity\Services\Request;
use Monad\Clarity\Services\Response;
use Monad\Clarity\Services\View;

/**
 * Example controller demonstrating the DB/View/Request wiring — deliberately not an
 * authentication flow (that's App\Middlewares\Authentication's job; see its docblock).
 * Signup validation and persistence both belong to App\Services\Registration; this class
 * only translates between HTTP and that call.
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

        try {
            Registration::register($email, $password, is_string($fullName) ? $fullName : null);
        } catch (RegistrationException $e) {
            return View::render('Users/create', ['errors' => $e->errors()], status: 422);
        }

        return Response::redirect('/users');
    }
}
