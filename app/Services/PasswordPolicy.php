<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Password validation, deliberately not requiring forced complexity (mixed case, a
 * digit, a symbol) — NIST 800-63B recommends against those rules since they push users
 * toward predictable substitutions ("Password1!") without meaningfully raising guessing
 * resistance, while length is the strongest single signal available. What this checks
 * instead: long enough, not absurdly long, not one of the passwords attackers try first,
 * and not just the user's own email address.
 *
 * The common-password list below is a small, illustrative starter set, not a real breach
 * corpus — an application with real users should check against something like the
 * Have I Been Pwned Pwned Passwords range API (k-anonymity: only a 5-character hash
 * prefix leaves the server) rather than a hardcoded list this short.
 *
 * @package App\Services
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 10;

    /**
     * A password past this length is rejected outright rather than hashed — Argon2id's
     * cost is proportional to input size, so an attacker submitting megabyte-sized
     * "passwords" repeatedly is a cheap way to burn CPU on every request.
     */
    public const MAX_LENGTH = 256;

    /**
     * Not exhaustive — the most-reused passwords from published breach analyses
     * (rockyou.txt, NCSC's annual most-breached-passwords lists), lowercased for
     * case-insensitive comparison.
     */
    private const COMMON_PASSWORDS = [
        'password', 'password1', '123456', '1234567', '12345678', '123456789',
        '1234567890', '12345', '1234', 'qwerty', 'qwerty123', '111111', 'letmein',
        'iloveyou', 'admin', 'welcome', 'monkey', 'dragon', 'football', 'baseball',
        'abc123', 'trustno1', 'sunshine', 'master', 'superman',
    ];

    /**
     * @return list<string> Human-readable violations. Empty means the password passes.
     */
    public static function validate(string $password, ?string $email = null): array
    {
        $violations = [];

        if (mb_strlen($password) < self::MIN_LENGTH) {
            $violations[] = sprintf('Password must be at least %d characters.', self::MIN_LENGTH);
        }

        if (mb_strlen($password) > self::MAX_LENGTH) {
            $violations[] = sprintf('Password must be no more than %d characters.', self::MAX_LENGTH);
        }

        if (in_array(mb_strtolower($password), self::COMMON_PASSWORDS, true)) {
            $violations[] = 'That password is one of the most commonly breached — choose something less guessable.';
        }

        $localPart = $email !== null ? strstr($email, '@', true) : false;

        if ($localPart !== false && $localPart !== '' && mb_strtolower($password) === mb_strtolower($localPart)) {
            $violations[] = 'Password must not match your email address.';
        }

        return $violations;
    }
}
