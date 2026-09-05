<?php

declare(strict_types=1);

namespace App\Tests\Config;

use InvalidArgumentException;
use Monad\Clarity\Services\Checkout\CheckoutException;
use Monad\Clarity\Services\CheckoutAdapters\PaddleCheckout;
use Monad\Clarity\Services\CheckoutAdapters\PaddleSubscription;
use Monad\Clarity\Services\CheckoutAdapters\StripeCheckout;
use PHPUnit\Framework\TestCase;

/**
 * `config/checkout.php` decides which gateway the application talks to and, with it, which
 * signature header the callback endpoint reads and how a Paddle `past_due` is interpreted.
 *
 * Pinned here for the same reason `MailConfigTest` pins its sibling: this is configuration,
 * and configuration is what nobody notices has quietly stopped doing what its comment says.
 * The stakes are a little higher — the failure mode is a payment recorded as the wrong thing.
 */
final class CheckoutConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $original = [];

    private const KEYS = [
        'CHECKOUT_GATEWAY',
        'STRIPE_SECRET_KEY',
        'STRIPE_WEBHOOK_SECRET',
        'PADDLE_API_KEY',
        'PADDLE_WEBHOOK_SECRET',
        'PADDLE_BASE_URI',
        'PADDLE_HOSTED_CHECKOUT_URL',
        'PADDLE_PAYMENT_PAGE_URL',
        'PADDLE_TAX_CATEGORY',
        'PADDLE_CATALOG_PRICE_ID',
    ];

    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->original[$key] = $_ENV[$key] ?? false;
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->original as $key => $value) {
            if ($value === false) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $value;
            }
        }
    }

    /**
     * @return array{gateway: string, signature_header: string, adapter: object, credentials: array{apiKey: string, webhookSecret: string}}
     */
    private static function load(): array
    {
        // Required fresh each time: the file builds a new adapter per require, which is what
        // lets a test vary the environment between loads.
        return require __DIR__ . '/../../../config/checkout.php';
    }

    public function testAnApplicationThatTakesNoPaymentsIsToldSoRatherThanGivenAnAdapter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('takes no payments');

        self::load();
    }

    public function testAnUnknownGatewayFailsLoudlyAndNamesTheValidOnes(): void
    {
        $_ENV['CHECKOUT_GATEWAY'] = 'braintree';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('stripe_checkout, paddle_checkout, paddle_subscription');

        self::load();
    }

    public function testStripeBuildsItsAdapterAndNamesItsSignatureHeader(): void
    {
        $_ENV['CHECKOUT_GATEWAY'] = 'stripe_checkout';
        $_ENV['STRIPE_SECRET_KEY'] = 'sk_test_key';
        $_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_secret';

        $checkout = self::load();

        self::assertInstanceOf(StripeCheckout::class, $checkout['adapter']);
        self::assertSame('stripe_checkout', $checkout['gateway']);
        self::assertSame('Stripe-Signature', $checkout['signature_header']);
    }

    public function testPaddleBuildsItsAdapterAndNamesItsSignatureHeader(): void
    {
        $_ENV['CHECKOUT_GATEWAY'] = 'paddle_checkout';
        $_ENV['PADDLE_API_KEY'] = 'pdl_live_key';
        $_ENV['PADDLE_WEBHOOK_SECRET'] = 'pdl_ntfset_secret';

        $checkout = self::load();

        self::assertInstanceOf(PaddleCheckout::class, $checkout['adapter']);
        self::assertSame('paddle_checkout', $checkout['gateway']);
        self::assertSame('Paddle-Signature', $checkout['signature_header']);
    }

    /**
     * Which credential pair is read follows from the gateway, so a .env carrying both sets
     * mid-migration hands the adapter the right one rather than the first one found.
     */
    public function testCredentialsFollowTheGatewayRatherThanBeingShared(): void
    {
        $_ENV['CHECKOUT_GATEWAY'] = 'paddle_checkout';
        $_ENV['STRIPE_SECRET_KEY'] = 'sk_test_wrong';
        $_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_wrong';
        $_ENV['PADDLE_API_KEY'] = 'pdl_right';
        $_ENV['PADDLE_WEBHOOK_SECRET'] = 'pdl_ntfset_right';

        $checkout = self::load();

        self::assertSame('pdl_right', $checkout['credentials']['apiKey']);
        self::assertSame('pdl_ntfset_right', $checkout['credentials']['webhookSecret']);
    }

    /**
     * The two Paddle gateways are a real distinction: `past_due` is Pending for a subscription
     * and Failed for a one-time payment (Clarity ReleaseNotes_1.4.0.md §2.6), so these must not
     * resolve to the same object.
     */
    public function testTheTwoPaddleGatewaysBuildDifferentAdapters(): void
    {
        $_ENV['CHECKOUT_GATEWAY'] = 'paddle_subscription';
        $_ENV['PADDLE_API_KEY'] = 'pdl_live_key';
        $_ENV['PADDLE_WEBHOOK_SECRET'] = 'pdl_ntfset_secret';
        $_ENV['PADDLE_CATALOG_PRICE_ID'] = 'pri_01hxyz';

        $checkout = self::load();

        self::assertInstanceOf(PaddleSubscription::class, $checkout['adapter']);
        self::assertNotInstanceOf(PaddleCheckout::class, $checkout['adapter']);
        self::assertSame('Paddle-Signature', $checkout['signature_header']);
    }

    /**
     * A subscription's terms come from exactly one place. Without a catalogue price this file
     * would have to invent a billing cycle — how often you charge and how much — which is
     * product behaviour, so it refuses and says where to build that adapter instead.
     */
    public function testASubscriptionWithoutACataloguePriceIsRefused(): void
    {
        $_ENV['CHECKOUT_GATEWAY'] = 'paddle_subscription';
        $_ENV['PADDLE_API_KEY'] = 'pdl_live_key';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PADDLE_CATALOG_PRICE_ID');

        self::load();
    }

    /**
     * A product id where a price id belongs is the easy mistake — they sit next to each other
     * in the Paddle dashboard. Clarity catches it at construction with a message that explains
     * the difference; this file must pass the value through rather than paper over it.
     */
    public function testAProductIdWhereAPriceIdBelongsIsRefusedByClarity(): void
    {
        $_ENV['CHECKOUT_GATEWAY'] = 'paddle_subscription';
        $_ENV['PADDLE_API_KEY'] = 'pdl_live_key';
        $_ENV['PADDLE_CATALOG_PRICE_ID'] = 'pro_01hxyz';

        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage('is a Paddle product id, not a price id');

        self::load();
    }
}
