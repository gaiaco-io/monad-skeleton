<?php

/**
 * The application's mailer — `Monad\Clarity\Services\Mail`, Clarity 1.6.0+.
 *
 * Returns a `Services\Mail`: one adapter, or a `MailerPool` of several tried in priority
 * order. **Which of those it returns is the whole of the multi-mailer decision** — there is
 * no "enable failover" flag anywhere, because the object this file hands back already says
 * (Clarity's `ReleaseNotes_1.6.0.md` §2.6). Set `MAIL_MAILERS` to one name for a single
 * mailer, or to a comma-separated list to fail over through them left to right:
 *
 *     MAIL_MAILERS=postmark                 # one mailer
 *     MAIL_MAILERS=postmark,resend,smtp     # try each in turn
 *
 * Application code requires this file and calls `send()` on what it gets, without caring
 * which of the two it is:
 *
 *     $mail = require __DIR__ . '/../config/mail.php';
 *     $sent = $mail->send($message);
 *
 * Unlike `config/llm.php`, which centralises credentials and leaves construction to the call
 * site, this file constructs. That is not an inconsistency between the two: an LLM adapter
 * has no single application-wide instance — llm.php's own docblock says exactly that —
 * whereas a failover pool is precisely such an instance, and a composition has to be
 * composed somewhere.
 *
 * **Amazon SES is deliberately absent.** `MailAdapters\AmazonSes` takes an
 * `Aws\SesV2Client`-shaped object rather than a credential, so it belongs wherever your AWS
 * client is built; construct it there, and pass it into a `MailerPool` yourself if you want
 * it pooled.
 *
 * Before trusting a pool, send one message through each member on its own. A pool exists to
 * reach its later members when the earlier ones fail, so a standby whose credentials and
 * egress have never been exercised is a standby you have no evidence about — and SMTP wants
 * outbound 587 or 465, the one Clarity component needing a port other than 443.
 */

declare(strict_types=1);

use Monad\Clarity\Services\HttpClient;
use Monad\Clarity\Services\Mail;
use Monad\Clarity\Services\Mail\MailerPool;
use Monad\Clarity\Services\Mail\SmtpEncryption;
use Monad\Clarity\Services\MailAdapters\Mailgun;
use Monad\Clarity\Services\MailAdapters\Mailtrap;
use Monad\Clarity\Services\MailAdapters\Postmark;
use Monad\Clarity\Services\MailAdapters\Resend;
use Monad\Clarity\Services\MailAdapters\SendGrid;
use Monad\Clarity\Services\MailAdapters\Smtp;

return (static function (): Mail {
    $env = static function (string $key, string $default = ''): string {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return trim((string) $value);
    };

    $httpClient = new HttpClient();

    $build = static function (string $name) use ($env, $httpClient): Mail {
        return match ($name) {
            'postmark' => new Postmark(
                $env('POSTMARK_SERVER_TOKEN'),
                $httpClient,
                $env('POSTMARK_MESSAGE_STREAM', 'outbound'),
            ),
            'resend' => new Resend($env('RESEND_API_KEY'), $httpClient),
            'sendgrid' => new SendGrid($env('SENDGRID_API_KEY'), $httpClient),
            'mailgun' => new Mailgun(
                $env('MAILGUN_API_KEY'),
                $env('MAILGUN_DOMAIN'),
                $httpClient,
                $env('MAILGUN_REGION', 'us') === 'eu' ? Mailgun::REGION_EU : Mailgun::REGION_US,
            ),
            'mailtrap' => Mailtrap::sending($env('MAILTRAP_API_TOKEN'), $httpClient),
            // A distinct name rather than a flag on 'mailtrap': the difference is whether the
            // mail reaches a human, which is not something to leave to a boolean that reads
            // as nothing at all when it is wrong.
            'mailtrap_sandbox' => Mailtrap::sandbox(
                $env('MAILTRAP_API_TOKEN'),
                $env('MAILTRAP_INBOX_ID'),
                $httpClient,
            ),
            'smtp' => new Smtp(
                host: $env('SMTP_HOST', '127.0.0.1'),
                port: (int) $env('SMTP_PORT', '587'),
                username: $env('SMTP_USERNAME') !== '' ? $env('SMTP_USERNAME') : null,
                password: $env('SMTP_PASSWORD') !== '' ? $env('SMTP_PASSWORD') : null,
                // Named, never inferred from the port. A silent downgrade is indistinguishable
                // from an interception, so switching it off has to be written down.
                encryption: match ($env('SMTP_ENCRYPTION', 'starttls')) {
                    'implicit', 'tls', 'ssl' => SmtpEncryption::ImplicitTls,
                    'none' => SmtpEncryption::None,
                    default => SmtpEncryption::StartTls,
                },
            ),
            default => throw new InvalidArgumentException(sprintf(
                'MAIL_MAILERS names "%s", which is not a mailer this file can build. Use one or '
                . 'more of: postmark, resend, sendgrid, mailgun, mailtrap, mailtrap_sandbox, smtp. '
                . 'Amazon SES is built where your AWS client is, not here.',
                $name
            )),
        };
    };

    $names = array_values(array_filter(
        array_map('trim', explode(',', $env('MAIL_MAILERS', 'smtp'))),
        static fn (string $name): bool => $name !== ''
    ));

    if ($names === []) {
        $names = ['smtp'];
    }

    $mailers = array_map($build, $names);

    // One name is one mailer; several is a pool, in the order given — §2.6.
    return count($mailers) === 1 ? $mailers[0] : new MailerPool($mailers);
})();
