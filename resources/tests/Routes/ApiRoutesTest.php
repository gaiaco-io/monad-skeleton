<?php

declare(strict_types=1);

namespace App\Tests\Routes;

use Monad\Clarity\Console\Arguments;
use Monad\Clarity\Console\CheckoutInstall;
use Monad\Clarity\Services\Checkout\CheckoutRequest;
use Monad\Clarity\Services\Checkout\CheckoutSession;
use Monad\Clarity\Services\Checkout\Money;
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
 * Exercises app/routes/api.php — the payment gateway's callback endpoint — through the real
 * Route::dispatch() pipeline, the same path public/index.php runs in production.
 *
 * Nothing here is mocked, and it does not need to be: `parseCallback()` is HMAC over the raw
 * body with no network call in it, so these tests sign a payload with a known secret exactly
 * as Stripe does and let the real adapter verify it. The ledger runs against in-memory SQLite
 * with the real `checkout:install` schema. The only thing not exercised is `createCheckout()`,
 * which does talk to Stripe — and which the skeleton deliberately does not ship a caller for.
 */
final class ApiRoutesTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';
    private const SESSION_ID = 'cs_test_a1b2c3';

    /** @var array<string, string|false> */
    private array $original = [];

    private const KEYS = ['CHECKOUT_GATEWAY', 'STRIPE_SECRET_KEY', 'STRIPE_WEBHOOK_SECRET'];

    #[Before]
    public function setUpApp(): void
    {
        foreach (self::KEYS as $key) {
            $this->original[$key] = $_ENV[$key] ?? false;
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        $_ENV['CHECKOUT_GATEWAY'] = 'stripe_checkout';
        $_ENV['STRIPE_SECRET_KEY'] = 'sk_test_key';
        $_ENV['STRIPE_WEBHOOK_SECRET'] = self::SECRET;

        DB::useConnection(new PDO('sqlite::memory:'));

        // The real command, not a hand-copied schema — a second definition of these four
        // tables is one that can drift from the one production actually runs.
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

    /** Records a pending transaction, as createCheckout() would have done at the sale. */
    private function openTransaction(string $gatewayReference = self::SESSION_ID): string
    {
        return (new TransactionLedger())->open(
            new CheckoutRequest(
                reference: 'order-1001',
                amount: new Money(2500, 'GBP'),
                successUrl: 'https://example.com/paid',
                cancelUrl: 'https://example.com/cancelled',
            ),
            new CheckoutSession(
                gateway: 'stripe_checkout',
                gatewayReference: $gatewayReference,
                redirectUrl: 'https://checkout.stripe.com/pay/' . $gatewayReference,
                status: TransactionStatus::Pending,
                amount: new Money(2500, 'GBP'),
            )
        );
    }

    /** @param array<string, mixed> $object */
    private static function payload(string $eventId, string $type, array $object): string
    {
        return json_encode(['id' => $eventId, 'type' => $type, 'data' => ['object' => $object]], JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> A paid checkout.session, the shape Stripe sends on completion. */
    private static function paidSession(string $id = self::SESSION_ID): array
    {
        return [
            'id' => $id,
            'object' => 'checkout.session',
            'status' => 'complete',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_9z8y',
        ];
    }

    private function post(string $body, ?string $signature): Response
    {
        $server = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/webhooks/checkout', 'HTTP_HOST' => '127.0.0.1'];

        if ($signature !== null) {
            $server['HTTP_STRIPE_SIGNATURE'] = $signature;
        }

        return Route::dispatch(Request::fromArrays(server: $server, rawBody: $body));
    }

    /** Signs exactly as Stripe does: HMAC over "<timestamp>.<raw body>". */
    private static function sign(string $body, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return sprintf('t=%d,v1=%s', $timestamp, HMAC::sign($timestamp . '.' . $body, self::SECRET));
    }

    public function testAVerifiedCallbackSettlesTheTransactionAndAcknowledges(): void
    {
        $transactionId = $this->openTransaction();
        $body = self::payload('evt_paid_1', 'checkout.session.completed', self::paidSession());

        $response = $this->post($body, self::sign($body));

        self::assertSame(204, $response->status());

        $transaction = (new TransactionLedger())->find($transactionId);
        self::assertSame('success', $transaction['status']);
        self::assertSame('pi_test_9z8y', $transaction['payment_reference']);
    }

    /**
     * Both gateways redeliver, and a redelivery is not an error. `recordCallback()` reports
     * that nothing moved; the endpoint still acknowledges, because answering anything else is
     * how a retry storm starts.
     */
    public function testARedeliveredCallbackIsAcknowledgedWithoutChangingAnything(): void
    {
        $transactionId = $this->openTransaction();
        $body = self::payload('evt_paid_1', 'checkout.session.completed', self::paidSession());
        $signature = self::sign($body);

        self::assertSame(204, $this->post($body, $signature)->status());
        self::assertSame(204, $this->post($body, $signature)->status());

        $ledger = new TransactionLedger();
        self::assertSame('success', $ledger->find($transactionId)['status']);

        // Two deliveries, but the history records the sale once — the second was recognised
        // as the same event rather than filed again.
        $history = $ledger->statusHistory($transactionId);
        self::assertCount(2, $history, 'Expected the opening Pending row and one Success row.');
    }

    public function testAForgedSignatureIsRejectedAndTheTransactionIsUntouched(): void
    {
        $transactionId = $this->openTransaction();
        $body = self::payload('evt_forged', 'checkout.session.completed', self::paidSession());

        $response = $this->post($body, sprintf('t=%d,v1=%s', time(), str_repeat('0', 64)));

        self::assertSame(400, $response->status());
        self::assertSame('pending', (new TransactionLedger())->find($transactionId)['status']);
    }

    public function testACallbackWithNoSignatureHeaderIsRejected(): void
    {
        $this->openTransaction();
        $body = self::payload('evt_bare', 'checkout.session.completed', self::paidSession());

        self::assertSame(400, $this->post($body, null)->status());
    }

    /**
     * A body altered after signing must fail, which is the whole reason the endpoint reads
     * rawBody() and carries no Jsonify.
     */
    public function testABodyTamperedWithAfterSigningIsRejected(): void
    {
        $transactionId = $this->openTransaction();
        $body = self::payload('evt_tampered', 'checkout.session.completed', self::paidSession());
        $signature = self::sign($body);

        $tampered = str_replace('"payment_status":"paid"', '"payment_status":"unpaid"', $body);
        self::assertNotSame($body, $tampered, 'The tamper must actually change the body.');

        self::assertSame(400, $this->post($tampered, $signature)->status());
        self::assertSame('pending', (new TransactionLedger())->find($transactionId)['status']);
    }

    /** Outside the replay tolerance, a genuine signature is still refused. */
    public function testAStaleCallbackIsRejectedAsAReplay(): void
    {
        $this->openTransaction();
        $body = self::payload('evt_stale', 'checkout.session.completed', self::paidSession());

        $staleTimestamp = time() - 3600;

        self::assertSame(400, $this->post($body, self::sign($body, $staleTimestamp))->status());
    }

    /**
     * Verified, but for a transaction this ledger never opened — another application on the
     * same Stripe account, or a race with the open() that records the sale. 404 asks the
     * gateway to try again, which is what resolves the race.
     */
    public function testAVerifiedCallbackForAnUnknownTransactionAsksForARetry(): void
    {
        $body = self::payload('evt_foreign', 'checkout.session.completed', self::paidSession('cs_test_never_seen'));

        self::assertSame(404, $this->post($body, self::sign($body))->status());
    }

    /**
     * A Stripe endpoint receives every event type enabled on it, and the default is all of
     * them. A `product.created` is not a checkout and must not be recorded as one.
     */
    public function testAnEventThatIsNotACheckoutSessionIsRejectedRatherThanMisrecorded(): void
    {
        $transactionId = $this->openTransaction();
        $body = self::payload('evt_product', 'product.created', ['id' => 'prod_123', 'object' => 'product']);

        self::assertSame(400, $this->post($body, self::sign($body))->status());
        self::assertSame('pending', (new TransactionLedger())->find($transactionId)['status']);
    }

    public function testAnExpiredCheckoutIsRecordedAsCancelled(): void
    {
        $transactionId = $this->openTransaction();
        $body = self::payload('evt_expired', 'checkout.session.expired', [
            'id' => self::SESSION_ID,
            'object' => 'checkout.session',
            'status' => 'expired',
            'payment_status' => 'unpaid',
        ]);

        self::assertSame(204, $this->post($body, self::sign($body))->status());
        self::assertSame('cancelled', (new TransactionLedger())->find($transactionId)['status']);
    }
}
