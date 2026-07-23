<?php

namespace Gaia\Herodo\Controllers;

use Gaia\Herodo\Models\UserModel;
use Gaia\Clarity\Services\Session;
use Gaia\Clarity\Services\View;

/**
 * User controller class handles the following actions:
 * - List view
 * - Create new record view
 * - Save record
 * - Edit record view
 * - Soft delete record
 *
 * @package Gaia\Herodo\Controllers
 * @author Marshal Yung <marshal.yung@gaiaco.io>
 */

final class UserController
{
    /**
     * Get all users
     * 
     * @param ?array $options The options.
     * @return array
     */
    public static function index(?array $options = null): array|null
    {
        return (new UserModel())->all($options);
    }

    /**
     * Render sign up view
     * 
     * @return void
     */
    public static function create(): void
    {
        View::render('user/signup', []);
    }

    /**
     * Create new user
     * 
     * @param ?string $id The user ID.
     * @return void
     */
    public static function store(?string $id = null): void
    {
        if ($id === null) {
            (new UserModel())->signUp();
        } else {
            (new UserModel())->updateUser($id);
        }
    }

    /**
     * Render login view
     * 
     * @return void
     */
    public static function login(): void
    {
        View::render('user/login', []);
    }

    /**
     * Render profile view
     * 
     * @return void
     */
    public static function profile(): void
    {
        View::render('user/profile', ['user' => (new UserModel())->getUserById((new Session())->read('user_id'))]);
    }

    /**
     * Soft delete user
     * 
     * @param string $id The user ID.
     * @return void
     */
    public static function softDelete(string $id): void
    {
        (new UserModel())->softDeleteUser($id);
    }

    /**
     * Render export account view
     * 
     * @param string $id The user ID.
     * @return void
     */
    public static function exportAccount(string $id): void
    {
        View::render('user/export', ['user' => (new UserModel())->getUserById($id)]);
    }
}
