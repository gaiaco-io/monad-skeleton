<?php

/**
 * The application's payment gateway — `Monad\Clarity\Services\Checkout`, Clarity 1.2.0+.
 *
 * Returns the adapter for the one gateway this application takes payments through, together
 * with the two facts about it that calling code cannot ask the adapter itself:
 *
 *     [
 *         'gateway' => 'stripe_checkout',
 *         'signature_header' => 'Stripe-Signature',
 *         'adapter' => $adapter,
 *         'credentials' => ['apiKey' => '...', 'webhookSecret' => '...'],
 *     ]
 *
 * `gateway` is there because `Checkout::gatewayName()` is `abstract protected` — deliberately,
 * since it exists to stamp value objects rather than to be interrogated — so a caller holding
 * a `Checkout` has no way to ask what it is. `signature_header` follows from it, and the
 * callback endpoint needs it before it can hand `parseCallback()` anything.
 *
 * This file follows `config/llm.php` rather than `config/mail.php`: it centralises credentials
 * and stops short of composing the application's whole payment flow. `config/mail.php`
 * constructs because a failover pool is a single application-wide instance and a composition
 * has to be composed somewhere; there is no such object here. What it does construct is the
 * one adapter the callback endpoint cannot work without.
 *
 *
 * ## Which gateway
 *
 * `CHECKOUT_GATEWAY` names one of `stripe_checkout`, `paddle_checkout`, or
 * `paddle_subscription`. The two Paddle values are a real distinction and not a convenience:
 * Paddle's `past_due` maps to `Pending` for a subscription and `Failed` for a one-time payment
 * (Clarity `ReleaseNotes_1.4.0.md` §2.6), so the adapter that parses a callback decides what
 * that callback is taken to mean. Naming the wrong one silently misreads a live payment state.
 *
 * Within `paddle_subscription` the callback endpoint already handles both families a Paddle
 * notification destination delivers: `transaction.*` events go to `TransactionLedger` and
 * `subscription.*` events to `SubscriptionLedger`, routed on the event type's prefix as Clarity
 * prescribes. What one adapter still cannot do is sell one-time *and* recurring through the
 * same account, because that is the `past_due` disagreement again — a `transaction.*` event
 * read by `PaddleSubscription` is read as a subscription's, which is wrong for an outright
 * sale. That application needs both adapters and a rule for which transaction belongs to
 * which, and the rule depends on what it sells. This file configures the gateway you name;
 * an application that outgrows one adapter extends the callback controller.
 *
 *
 * ## Subscriptions
 *
 * `paddle_subscription` here is catalogue mode, and so requires `PADDLE_CATALOG_PRICE_ID` —
 * the `pri_...` under Paddle > Catalog > Products. A subscription priced inline instead needs
 * a `BillingCycle`, which is to say how often you charge and how much: product behaviour this
 * file has no way to know and must not invent. Build that one where the plan is known:
 *
 *     $subscription = new PaddleSubscription(
 *         $checkout['credentials']['apiKey'],
 *         new HttpClient(),
 *         billingCycle: new BillingCycle(BillingInterval::Month, 1),
 *         webhookSecret: $checkout['credentials']['webhookSecret'],
 *     );
 *
 * `credentials` is exposed for exactly that: a second adapter built at a call site that knows
 * something this file does not, without re-reading the environment behind its back.
 *
 *
 * ## Before the first payment
 *
 * Run `php mitosis checkout:install` once per database context. It creates the four tables the
 * ledger reads and is re-runnable, which is also the upgrade path when a release adds one.
 * Nothing here creates them, and `TransactionLedger` fails on its first query without them.
 *
 * Point the gateway's webhook at `POST /webhooks/checkout` (app/routes/api.php) and put the
 * signing secret it issues in `.env`. A missing secret is not a soft failure: Clarity refuses
 * to verify at all without one rather than accepting unverified callbacks, so the endpoint
 * returns 400 for every delivery until it is set.
 */

declare(strict_types=1);

use Monad\Clarity\Services\CheckoutAdapters\PaddleCheckout;
use Monad\Clarity\Services\CheckoutAdapters\PaddleSubscription;
use Monad\Clarity\Services\CheckoutAdapters\StripeCheckout;
use Monad\Clarity\Services\HttpClient;

return (static function (): array {
    $env = static function (string $key, string $default = ''): string {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return trim((string) $value);
    };

    $optional = static fn (string $key): ?string => $env($key) !== '' ? $env($key) : null;

    $gateway = $env('CHECKOUT_GATEWAY');
    $httpClient = new HttpClient();

    // Stripe and Paddle issue separate credentials, so which pair this file reads follows from
    // the gateway rather than from one shared name — an application that has configured both
    // while migrating between them keeps two intact sets in .env, and switching is one key.
    $credentials = str_starts_with($gateway, 'paddle_')
        ? ['apiKey' => $env('PADDLE_API_KEY'), 'webhookSecret' => $env('PADDLE_WEBHOOK_SECRET')]
        : ['apiKey' => $env('STRIPE_SECRET_KEY'), 'webhookSecret' => $env('STRIPE_WEBHOOK_SECRET')];

    // Sandbox is a wholly separate Paddle environment — its own keys, catalogue, notification
    // destinations, and refunds approved automatically rather than by review. Nothing crosses
    // between the two, so the base URI is the whole of the switch.
    $paddleBaseUri = $env('PADDLE_BASE_URI', 'https://api.paddle.com');

    $adapter = match ($gateway) {
        'stripe_checkout' => new StripeCheckout(
            $credentials['apiKey'],
            $httpClient,
            webhookSecret: $credentials['webhookSecret'],
        ),

        // Exactly one of hostedCheckoutUrl or paymentPageUrl is passed through per Paddle's own
        // rule; Clarity enforces that at createCheckout() rather than here, so a misconfigured
        // pair fails with its message and not a second, vaguer one from this file.
        'paddle_checkout' => new PaddleCheckout(
            $credentials['apiKey'],
            $httpClient,
            webhookSecret: $credentials['webhookSecret'],
            hostedCheckoutUrl: $optional('PADDLE_HOSTED_CHECKOUT_URL'),
            paymentPageUrl: $optional('PADDLE_PAYMENT_PAGE_URL'),
            taxCategory: $env('PADDLE_TAX_CATEGORY', 'standard'),
            baseUri: $paddleBaseUri,
            catalogPriceId: $optional('PADDLE_CATALOG_PRICE_ID'),
        ),

        'paddle_subscription' => PaddleSubscription::forCatalogPrice(
            $credentials['apiKey'],
            $httpClient,
            catalogPriceId: $env('PADDLE_CATALOG_PRICE_ID') !== ''
                ? $env('PADDLE_CATALOG_PRICE_ID')
                : throw new InvalidArgumentException(
                    'CHECKOUT_GATEWAY=paddle_subscription needs PADDLE_CATALOG_PRICE_ID — the pri_... under '
                    . 'Paddle > Catalog > Products. A subscription priced inline instead needs a billing cycle, '
                    . 'which is how often you charge and how much: build that adapter where the plan is known, '
                    . 'not here.'
                ),
            webhookSecret: $credentials['webhookSecret'],
            hostedCheckoutUrl: $optional('PADDLE_HOSTED_CHECKOUT_URL'),
            paymentPageUrl: $optional('PADDLE_PAYMENT_PAGE_URL'),
            baseUri: $paddleBaseUri,
            taxCategory: $env('PADDLE_TAX_CATEGORY', 'standard'),
        ),

        default => throw new InvalidArgumentException($gateway === ''
            ? 'CHECKOUT_GATEWAY is not set, so this application takes no payments. Set it to one of: '
                . 'stripe_checkout, paddle_checkout, paddle_subscription — or leave it unset and do not '
                . 'require this file.'
            : sprintf(
                'CHECKOUT_GATEWAY names "%s", which is not a gateway this file can build. Use one of: '
                . 'stripe_checkout, paddle_checkout, paddle_subscription.',
                $gateway
            )),
    };

    return [
        'gateway' => $gateway,
        'signature_header' => $adapter instanceof StripeCheckout ? 'Stripe-Signature' : 'Paddle-Signature',
        'adapter' => $adapter,
        'credentials' => $credentials,
    ];
})();
