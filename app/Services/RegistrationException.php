<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Thrown by Registration::register() when a signup can't proceed. Carries every
 * violation found (email format, uniqueness, password policy) rather than just the
 * first — a form that rejects one problem at a time, resubmit by resubmit, is a worse
 * experience than being told everything wrong at once.
 *
 * @package App\Services
 */
final class RegistrationException extends RuntimeException
{
    /** @var list<string> */
    private array $errors;

    /**
     * @param list<string> $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct(implode(' ', $errors));

        $this->errors = $errors;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
