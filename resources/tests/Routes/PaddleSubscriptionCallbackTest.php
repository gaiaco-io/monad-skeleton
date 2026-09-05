<?php

declare(strict_types=1);

namespace App\Tests\Routes;

use Monad\Clarity\Console\Arguments;
use Monad\Clarity\Console\CheckoutInstall;
use Monad\Clarity\Services\Checkout\CheckoutRequest;
use Monad\Clarity\Services\Checkout\CheckoutSession;
use Monad\Clarity\Services\Checkout\Money;
use Monad\Clarity\Services\Checkout\SubscriptionLedger;
use Monad\Clarity\Services\Checkout\SubscriptionStatus;
use Monad\Clarity\Services\Checkout\TransactionLedger;
use Monad\Clarity\Services\Checkout\TransactionStatus;
use Monad\Clarity\Services\DB;
use Monad\Clarity\Services\Request;
use Monad\Clarity\Services\Response;
use Monad\Clarity\Services\Route;
use Monad\Clarity\Utils\HMAC;
use PDO;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

/**
 * The `paddle_subscription` half of `POST /webhooks/checkout`.
 *
 * A subscription is born from a transaction (Clarity `ReleaseNotes_1.4.0.md` §2.3), so one
 * notification destination delivers two families of event to this one URL: `transaction.*`,
 * which belongs to `TransactionLedger`, and `subscription.*`, which belongs to
 * `SubscriptionLedger`. What is pinned here is that the endpoint sends each to the right
 * ledger — the failure this guards against is quiet, because a subscription whose lifecycle
 * events are all refused still looks fine on the transaction side.
 *
 * Signed exactly as Paddle signs: HMAC over "<timestamp>:<raw body>", `ts=...;h1=...`.
 */
final class PaddleSubscriptionCallbackTest extends TestCase
{
    private const SECRET = 'pdl_ntfset_test_secret';
    private const SUBSCRIPTION_ID = 'sub_01hxyzactive';
    private const TRANSACTION_ID = 'txn_01hxyzpaid';

    /** @var array<string, string|false> */
    private array $original = [];

    private const KEYS = ['CHECKOUT_GATEWAY', 'PADDLE_API_KEY', 'PADDLE_WEBHOOK_SECRET', 'PADDLE_CATALOG_PRICE_ID'];

    #[Before]
    public function setUpApp(): void
    {
        foreach (self::KEYS as $key) {
            $this->original[$key] = $_ENV[$key] ?? false;
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        $_ENV['CHECKOUT_GATEWAY'] = 'paddle_subscription';
        $_ENV['PADDLE_API_KEY'] = 'pdl_test_key';
        $_ENV['PADDLE_WEBHOOK_SECRET'] = self::SECRET;
        $_ENV['PADDLE_CATALOG_PRICE_ID'] = 'pri_01hxyzplan';

        DB::useConnection(new PDO('sqlite::memory:'));

        ob_start();
        (new CheckoutInstall())(Arguments::parse([]));
        ob_end_clean();

        Route::reset();
        require dirname(__DIR__, 3) . '/app/routes/api.php';
    }

    #[After]
    public function tearDownApp(): void
    {
        DB::reset();
        Route::reset();

        foreach ($this->original as $key => $value) {
            if ($value === false) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $value;
            }
        }
    }

    private function openTransaction(): string
    {
        return (new TransactionLedger())->open(
            new CheckoutRequest(
                reference: 'order-2002',
                amount: new Money(0, 'GBP'),
                successUrl: 'https://example.com/paid',
                cancelUrl: 'https://example.com/cancelled',
            ),
            new CheckoutSession(
                gateway: 'paddle_subscription',
                gatewayReference: self::TRANSACTION_ID,
                redirectUrl: null,
                status: TransactionStatus::Pending,
                amount: new Money(900, 'GBP'),
            )
        );
    }

    /**
     * `occurred_at` is not decoration. A subscription is a single mutable record rather than an
     * insert-only history, so Clarity refuses an event it cannot order against what is already
     * stored — which is why every payload here carries one, and why the cancellation below is
     * stamped after the activation it supersedes.
     *
     * @param array<string, mixed> $data
     */
    private static function payload(string $eventId, string $type, array $data, ?string $occurredAt = null): string
    {
        return json_encode([
            'event_id' => $eventId,
            'event_type' => $type,
            'occurred_at' => $occurredAt ?? gmdate('Y-m-d\\TH:i:s.000\\Z'),
            'data' => $data,
        ], JSON_THROW_ON_ERROR);
    }

    private const ACTIVATED_AT = '2026-09-01T10:00:00.000Z';
    private const CANCELLED_AT = '2026-09-02T10:00:00.000Z';

    /** @return array<string, mixed> */
    private static function subscriptionData(string $status = 'active'): array
    {
        return [
            'id' => self::SUBSCRIPTION_ID,
            'status' => $status,
            'transaction_id' => self::TRANSACTION_ID,
            'customer_id' => 'ctm_01hxyz',
            'custom_data' => ['reference' => 'order-2002'],
        ];
    }

    private function post(string $body, ?string $signature): Response
    {
        $server = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/webhooks/checkout', 'HTTP_HOST' => '127.0.0.1'];

        if ($signature !== null) {
            $server['HTTP_PADDLE_SIGNATURE'] = $signature;
        }

        return Route::dispatch(Request::fromArrays(server: $server, rawBody: $body));
    }

    private static function sign(string $body, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return sprintf('ts=%d;h1=%s', $timestamp, HMAC::sign($timestamp . ':' . $body, self::SECRET));
    }

    /**
     * The event family the transaction ledger cannot hold. Before the endpoint routed on the
     * prefix, this returned 400 and `checkout_subscriptions` stayed empty for ever.
     */
    public function testASubscriptionEventIsRecordedInTheSubscriptionLedger(): void
    {
        $body = self::payload('ntf_activated', 'subscription.activated', self::subscriptionData());

        self::assertSame(204, $this->post($body, self::sign($body))->status());

        $subscription = (new SubscriptionLedger())->findByGatewayReference(self::SUBSCRIPTION_ID);

        self::assertNotNull($subscription, 'The subscription callback should have created a record.');
        self::assertSame(SubscriptionStatus::Active, (new SubscriptionLedger())->statusOf(self::SUBSCRIPTION_ID));
    }

    /** The other family, through the same URL, into the other ledger. */
    public function testATransactionEventStillReachesTheTransactionLedger(): void
    {
        $transactionId = $this->openTransaction();
        $body = self::payload('ntf_paid', 'transaction.completed', [
            'id' => self::TRANSACTION_ID,
            'status' => 'completed',
            'subscription_id' => self::SUBSCRIPTION_ID,
        ]);

        self::assertSame(204, $this->post($body, self::sign($body))->status());
        self::assertSame('success', (new TransactionLedger())->find($transactionId)['status']);
    }

    /**
     * The two references are only ever seen together on the transaction that paid for the
     * subscription, so that is where they are joined.
     */
    public function testThePayingTransactionIsLinkedToTheSubscriptionItCreated(): void
    {
        $activation = self::payload('ntf_activated', 'subscription.activated', self::subscriptionData());
        $this->post($activation, self::sign($activation));

        $this->openTransaction();
        $paid = self::payload('ntf_paid', 'transaction.completed', [
            'id' => self::TRANSACTION_ID,
            'status' => 'completed',
            'subscription_id' => self::SUBSCRIPTION_ID,
        ]);

        self::assertSame(204, $this->post($paid, self::sign($paid))->status());

        $subscription = (new SubscriptionLedger())->findByTransactionReference(self::TRANSACTION_ID);
        self::assertNotNull($subscription, 'The subscription should be reachable from the transaction that paid for it.');
        self::assertSame(self::SUBSCRIPTION_ID, $subscription['gateway_reference']);
    }

    public function testARedeliveredSubscriptionEventIsAcknowledgedWithoutChangingAnything(): void
    {
        $body = self::payload('ntf_activated', 'subscription.activated', self::subscriptionData());
        $signature = self::sign($body);

        self::assertSame(204, $this->post($body, $signature)->status());
        self::assertSame(204, $this->post($body, $signature)->status());

        self::assertSame(SubscriptionStatus::Active, (new SubscriptionLedger())->statusOf(self::SUBSCRIPTION_ID));
    }

    public function testACancellationMovesTheStoredSubscription(): void
    {
        $activated = self::payload('ntf_activated', 'subscription.activated', self::subscriptionData(), self::ACTIVATED_AT);
        $this->post($activated, self::sign($activated));

        $canceled = self::payload('ntf_canceled', 'subscription.canceled', self::subscriptionData('canceled'), self::CANCELLED_AT);

        self::assertSame(204, $this->post($canceled, self::sign($canceled))->status());
        self::assertSame(SubscriptionStatus::Cancelled, (new SubscriptionLedger())->statusOf(self::SUBSCRIPTION_ID));
    }

    public function testAForgedSubscriptionCallbackIsRejectedAndNothingIsRecorded(): void
    {
        $body = self::payload('ntf_forged', 'subscription.activated', self::subscriptionData());

        $response = $this->post($body, sprintf('ts=%d;h1=%s', time(), str_repeat('0', 64)));

        self::assertSame(400, $response->status());
        self::assertNull((new SubscriptionLedger())->findByGatewayReference(self::SUBSCRIPTION_ID));
    }

    /**
     * A notification destination delivers every event type subscribed on it. A `customer.*`
     * is neither family, and must be refused rather than recorded as either.
     */
    public function testAnEventInNeitherFamilyIsRefused(): void
    {
        $body = self::payload('ntf_customer', 'customer.updated', ['id' => 'ctm_01hxyz']);

        self::assertSame(400, $this->post($body, self::sign($body))->status());
        self::assertNull((new SubscriptionLedger())->findByGatewayReference(self::SUBSCRIPTION_ID));
    }

    /**
     * Routing reads the event type from an unverified body, so a forged one can choose which
     * parser sees it. It gains nothing: the parser verifies first, and refuses either way.
     */
    public function testChoosingTheOtherDoorWithAForgedBodyGainsNothing(): void
    {
        $body = self::payload('ntf_forged', 'transaction.completed', [
            'id' => self::TRANSACTION_ID,
            'status' => 'completed',
        ]);

        self::assertSame(400, $this->post($body, sprintf('ts=%d;h1=%s', time(), str_repeat('0', 64)))->status());
    }
}
