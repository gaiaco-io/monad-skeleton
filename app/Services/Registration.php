<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserModel;
use Monad\Clarity\Services\Event;

/**
 * Orchestrates a new-user signup: validation with real consequences (a malformed email,
 * a taken one, a weak password) plus a side effect on success (Event::USER_REGISTERED).
 * This is the layering UserModel and UserController deliberately don't do themselves —
 * UserModel stays a thin, honest reflection of the `users` table (CLAUDE.md: no
 * abstraction for aesthetics), and a Controller's job is HTTP in/out, not business rules.
 * A Service is for the logic in between that has nowhere else to live.
 *
 * @package App\Services
 */
final class Registration
{
    /**
     * @return string The new user's id.
     * @throws RegistrationException If the email or password fails validation.
     */
    public static function register(string $email, string $password, ?string $fullName = null): string
    {
        $email = trim($email);
        $errors = [];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address.';
        } elseif (UserModel::emailExists($email)) {
            $errors[] = 'That email is already registered.';
        }

        array_push($errors, ...PasswordPolicy::validate($password, $email));

        if ($errors !== []) {
            throw new RegistrationException($errors);
        }

        $id = UserModel::create($email, $password, $fullName);

        Event::dispatch(Event::USER_REGISTERED, ['id' => $id, 'email' => $email]);

        return $id;
    }
}
