<?php

declare(strict_types=1);

namespace App\Middlewares;

use Monad\Clarity\Middlewares\Authentication as BaseAuthentication;
use Monad\Clarity\Services\DB;

/**
 * Thin extension of Clarity's Authentication engine (CrossRepoContracts.md §5). Wires
 * the pluggable user resolver (§15.2.15) against the app-owned `users` table (see
 * database/migrations/20260101000000_create_users_table.php) — Clarity never queries
 * this table itself, only through these two closures.
 *
 * Google SSO is left unconfigured (constructor's httpClient/googleClientId/
 * googleClientSecret are omitted) until GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET are set —
 * verifyGoogleAuthorizationCode() throws until then, per the base class's own
 * misconfiguration check.
 *
 * @package App\Middlewares
 */
final class Authentication extends BaseAuthentication
{
    public function __construct()
    {
        parent::__construct(
            findByCredential: static function (string $email): ?array {
                $row = DB::run(
                    'SELECT id, password_hash, locked, email_verified_at FROM users WHERE email = ? LIMIT 1',
                    [$email]
                )->fetch();

                return $row === false ? null : self::toResolverShape($row);
            },
            findById: static function (string $id): ?array {
                $row = DB::run(
                    'SELECT id, password_hash, locked, email_verified_at FROM users WHERE id = ? LIMIT 1',
                    [$id]
                )->fetch();

                return $row === false ? null : self::toResolverShape($row);
            },
            hmacSecret: (string) getenv('APP_SECRET'),
            loginRateLimiter: new RateLimiter(maxAttempts: 5, windowSeconds: 300),
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: string, passwordHash: string, locked: bool, emailVerifiedAt: ?string}
     */
    private static function toResolverShape(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'passwordHash' => (string) $row['password_hash'],
            'locked' => (bool) $row['locked'],
            'emailVerifiedAt' => $row['email_verified_at'],
        ];
    }
}
