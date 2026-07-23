<?php

namespace Gaia\Herodo\Models;

use Gaia\Clarity\Services\DB;
use Gaia\Clarity\Services\Request;
use Gaia\Clarity\Services\Mediator;
use Gaia\Clarity\Services\Session;
use Gaia\Clarity\Services\CsrfService;
use Carbon\Carbon;

/**
 * User model class.
 *
 * @package Gaia\Herodo\Models
 * @author Marshal Yung <marshal.yung@gaiaco.io>
 */

final class UserModel
{
    private Request $request;
    const SIGNUP_TYPE_EMAIL = 0;
    const SIGNUP_TYPE_GOOGLE = 1;
    const SIGNUP_TYPE_PASSKEY = 2;

    public function __construct()
    {
        $this->request = new Request();
        $this->request->assign([
            'timezone' => $this->request->getPostData('timezone'),
            'email' => $this->request->getPostData('email'),
            'full_name' => $this->request->getPostData('full_name'),
            'industry' => $this->request->getPostData('industry') ?? 0,
            'job_role' => $this->request->getPostData('job_role') ?? 0
        ]);
    }

    public function signUp(): string
    {
        (new CsrfService())->requireValid();

        if (!$this->isUniqueEmail($this->request->getAssignedData('email'))) {
            Mediator::handleUserMessage('Email already exists');
        }

        $this->request->assign([
            'id' => bin2hex(random_bytes(16)),
            'secret' => password_hash($this->request->getPostData('secret'), PASSWORD_DEFAULT),
        ]);

        DB::insert('users', $this->request->getAssignedData(), DB::ID_TYPE_UUID);
        return $this->request->getAssignedData('id');
    }

    /**
     * Sign in user and select the default organization belonging to the user.
     * 
     * @return bool
     */
    public function signIn(): bool
    {
        (new CsrfService())->requireValid();

        $this->request->assign(['secret' => password_hash($this->request->getPostData('secret'), PASSWORD_DEFAULT)]);
        $user = DB::run('SELECT id, timezone, full_name, email FROM users WHERE email = :email AND secret = :secret LIMIT 1;', [
            'email' => $this->request->getAssignedData('email'),
            'secret' => $this->request->getAssignedData('secret')
        ])->fetch() ?? [];

        if ($user) {
            $payload = [
                'user_id' => $user['id'],
                'timezone' => $user['timezone'],
                'email' => $user['email'],
                'full_name' => $user['full_name']
            ];

            (new Session())->start($user['id'], $payload);
            (new CsrfService())->rotate();
            return true;
        }

        return false;
    }

    /**
     * Get all users
     * 
     * @param array $options The options.
     * @return array
     */
    public function all(?array $options = null): array
    {
        $sql = 'SELECT id, is_active, timezone, full_name, email FROM users WHERE deleted IS NULL;';
        $sql .= 'ORDER BY full_name ASC;';
        return DB::run($sql, [])->fetchAll() ?? [];
    }

    /**
     * Update user profile and avatar photo
     * 
     * TODO: Upload avatar photo to S3 bucket.
     * 
     * @param string $id The user ID.
     * @return void
     */
    public function updateUser(string $id): void
    {
        (new CsrfService())->requireValid();

        DB::update('users', $this->request->getAssignedData(), ['id' => $id]);
    }

    /**
     * Soft delete user
     * 
     * @param string $id The user ID.
     * @return void
     */
    public function softDeleteUser(string $id): void
    {
        DB::update('users', ['deleted' => Carbon::now('UTC')], ['id' => $id]);
    }

    /**
     * Purge user account and all associated data
     * 
     * TODO: Needs to be more granular when deleting associated data.
     * TODO: Replace `user_id` with `name` where applicable for more intuitive UX
     * 
     * @param string $id The user ID.
     * @return void
     */
    public function purgeAccount(string $id): void
    {
        // TODO: Purge user account and all associated data
        // $entities = [
        //     'activities',
        //     'addresses',
        //     'contacts',
        //     'deals',
        //     'opportunity_actions',
        //     'projects',
        //     'project_subscribers',
        //     'reminders',
        //     'sessions',
        //     'users',
        //     'org_members',
        // ];

        DB::delete('users', ['id' => $id]);
        self::signout();
    }

    /**
     * Get user by ID
     * 
     * @param string $id The user ID.
     * @return array
     */
    public function getUserById(string $id): array
    {
        $sql = 'SELECT id, is_active, timezone, full_name, email, industry, job_role FROM users WHERE id = :id LIMIT 1;';
        return DB::run($sql, ['id' => $id])->fetch() ?? [];
    }

    /**
     * Check if email is unique
     * 
     * @param string $email The email address.
     * @return bool
     */
    private function isUniqueEmail(string $email): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email LIMIT 1;';
        return !DB::run($sql, ['email' => $email])->fetch();
    }

    /**
     * Sign up with Google
     * 
     * TODO: Rewrite Google sign up.
     * 
     * @return bool
     */
    private function signUpWithGoogle(): bool
    {
        $this->request->assign(['secret' => password_hash($this->request->getPostData('secret'), PASSWORD_DEFAULT)]);
        DB::insert('users', $this->request->getAssignedData(), DB::ID_TYPE_UUID);
        return true;
    }

    /**
     * Sign up with Passkey
     * 
     * TODO: Rewrite Passkey sign up.
     * 
     * @return bool
     */
    private function signUpWithPasskey(): bool
    {
        $this->request->assign(['secret' => password_hash($this->request->getPostData('secret'), PASSWORD_DEFAULT)]);
        DB::insert('users', $this->request->getAssignedData(), DB::ID_TYPE_UUID);
        return true;
    }

    /**
     * Export account data
     * 
     * @param string $id The user ID.
     * @return void
     */
    private function exportAccount(string $id): void
    {
        // TODO: Export account data
    }

    /**
     * Sign out
     * 
     * @return void
     */
    public static function signout(): void
    {
        (new Session())->destroy();
    }
}
