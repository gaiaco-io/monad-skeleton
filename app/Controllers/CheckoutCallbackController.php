<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middlewares\Logger;
use Monad\Clarity\Services\Checkout;
use Monad\Clarity\Services\Checkout\CheckoutException;
use Monad\Clarity\Services\Checkout\SubscriptionLedger;
use Monad\Clarity\Services\Checkout\TransactionLedger;
use Monad\Clarity\Services\CheckoutAdapters\PaddleSubscription;
use Monad\Clarity\Services\Request;
use Monad\Clarity\Services\Response;

/**
 * Receives the payment gateway's callbacks — `POST /webhooks/checkout`, the endpoint named in
 * Stripe > Webhooks or Paddle > Notifications.
 *
 * This is the half of a payment the customer's browser never touches. The redirect back from a
 * checkout page tells you where the customer went, not what the bank did; the callback is the
 * gateway's own word on whether money moved, and it arrives whether or not the customer ever
 * came back. Without an endpoint applying it, a paid transaction sits at `Pending` in the
 * ledger for ever — which is why this controller is the one piece of the payment flow the
 * skeleton ships built rather than left as an example.
 *
 * What it does not ship is the other half: `createCheckout()`. What is being sold, at what
 * price, with which success and cancel URLs is product behaviour and differs for every
 * application, so inventing it here would be guessing. This endpoint is identical everywhere.
 *
 *
 * ## Two event families, two ledgers
 *
 * A subscription is born from a transaction, so `createCheckout()` returns a `txn_` and never a
 * `sub_` (Clarity `ReleaseNotes_1.4.0.md` §2.3). What follows is two streams of callbacks
 * arriving at this one URL: `transaction.*` events belong to `TransactionLedger`, and the
 * `subscription.*` events that describe the plan's life afterwards belong to
 * `SubscriptionLedger`. They are separate doors on the adapter — `parseCallback()` and
 * `parseSubscriptionCallback()` — and neither payload can be read as the other. Clarity's
 * instruction is to route on the event type's prefix and call the matching one, so that is what
 * `receive()` does.
 *
 * Routing reads `event_type` out of the body *before* the signature has been checked, which
 * looks alarming and is not. It grants nothing: both parsers verify over the raw bytes as their
 * first act, so a forged body that steers itself to one door or the other has only chosen which
 * door refuses it. Nothing is read from the decoded copy except the prefix, and the bytes handed
 * on for verification are untouched.
 *
 * Only `paddle_subscription` has the second door. Stripe subscriptions are a separate Clarity
 * adapter that has not been built, and `paddle_checkout` sells one-time — for both, every
 * callback is a transaction event.
 *
 *
 * ## Why it carries no middleware
 *
 * Its route names none, and every omission is deliberate rather than forgotten:
 *
 * - **No `Csrf`.** A CSRF token defends a browser form against a cross-site POST by proving
 *   the request came from a page this application rendered. A gateway is not a browser and was
 *   never issued a token; requiring one would reject every genuine callback. What stands in its
 *   place is strictly stronger — an HMAC over the exact request bytes, keyed by a secret only
 *   this application and the gateway hold.
 * - **No `Authentication`.** Stripe and Paddle carry no session and no bearer token. The
 *   signature is the authentication.
 * - **No `Jsonify`.** It would parse the body into `$request->json()`, and parsing is precisely
 *   what must not happen first: a signature is computed over the bytes as sent, so a body
 *   decoded and re-encoded fails verification even when genuine — and a scheme that tolerated
 *   the round trip would be verifying something other than what arrived. `rawBody()` only.
 *
 *
 * ## What each status code means to the gateway
 *
 * Stripe and Paddle both retry a non-2xx delivery with backoff, so the status is not
 * decoration — it decides whether the gateway comes back:
 *
 * - **204** — verified and applied, or verified and already applied. Both ledgers report that
 *   nothing moved for a redelivery, and for an event older than the state already stored; both
 *   mean "handled, nothing further to do", so both acknowledge. Answering anything else to a
 *   redelivery is how a retry storm starts.
 * - **400** — the parser refused it. That covers two causes it does not distinguish between,
 *   and neither does this endpoint, because the remedy is the same: a retry of the same bytes
 *   fails identically. Either the signature was absent, malformed, stale or did not verify — in
 *   practice a missing or wrong `*_WEBHOOK_SECRET` far more often than an attack — or the bytes
 *   verified but were not an event this adapter can interpret. Endpoints receive every event
 *   type enabled on them, and the default is all of them, so a `product.created` or a
 *   `customer.updated` arriving here is an endpoint scoped too widely rather than anything
 *   sinister. The log line carries Clarity's own message, which says which of the two happened;
 *   the response body does not (§10.6).
 * - **404** — verified, but the transaction ledger holds no transaction for the reference it
 *   names. Worth retrying, because the usual cause is a race with the `open()` that records a
 *   checkout, and the retry finds the row. The other cause is a foreign transaction — another
 *   application on the same gateway account — which retries until the gateway gives up, as it
 *   should. Subscription events have no equivalent: `SubscriptionLedger::record()` creates the
 *   record on first sight rather than requiring one to exist.
 *
 * The response body is a short fixed string. The gateway's dashboard shows it verbatim, so it
 * says enough to tell the cases apart and nothing about this application's internals (§10.6).
 * The detail goes to the error log, where it is not on the public internet.
 *
 * @package App\Controllers
 */
final class CheckoutCallbackController
{
    public static function receive(Request $request): Response
    {
        /** @var array{gateway: string, signature_header: string, adapter: Checkout} $checkout */
        $checkout = require dirname(__DIR__, 2) . '/config/checkout.php';

        $adapter = $checkout['adapter'];
        $rawBody = $request->rawBody();
        $headers = [$checkout['signature_header'] => $request->header($checkout['signature_header']) ?? ''];

        if ($adapter instanceof PaddleSubscription && self::looksLikeASubscriptionEvent($rawBody)) {
            return self::applySubscriptionEvent($adapter, $rawBody, $headers, $checkout['gateway']);
        }

        return self::applyTransactionEvent($adapter, $rawBody, $headers, $checkout['gateway']);
    }

    /**
     * The prefix Clarity routes on, read from an unverified body — see the class docblock for
     * why that is safe. A body that will not decode is not a subscription event, and saying so
     * here sends it to the transaction parser, which refuses it with a verified error message
     * rather than a guess made in this method.
     */
    private static function looksLikeASubscriptionEvent(string $rawBody): bool
    {
        $decoded = json_decode($rawBody, associative: true);

        return is_array($decoded)
            && is_string($decoded['event_type'] ?? null)
            && str_starts_with($decoded['event_type'], 'subscription.');
    }

    /** @param array<string, string> $headers */
    private static function applyTransactionEvent(Checkout $adapter, string $rawBody, array $headers, string $gateway): Response
    {
        try {
            $event = $adapter->parseCallback($rawBody, $headers);
        } catch (CheckoutException $e) {
            return self::refuse($gateway, $e);
        }

        try {
            $applied = (new TransactionLedger())->recordCallback($event);
        } catch (CheckoutException $e) {
            (new Logger())->warning('A verified checkout callback named an unknown transaction.', [
                'gateway' => $gateway,
                'event_id' => $event->eventId,
                'gateway_reference' => $event->gatewayReference,
                'reason' => $e->getMessage(),
            ]);

            return Response::text('No transaction for that reference.', 404);
        }

        // The transaction that paid for a subscription is the only place the two references are
        // seen together, so this is where they are joined. Paddle creates the subscription
        // asynchronously, so the id is often absent here and arrives with subscription.created
        // instead — the ledger takes the link from whichever side turns up first, and a null is
        // "not yet" rather than "never".
        if ($adapter instanceof PaddleSubscription) {
            $subscriptionReference = $adapter->subscriptionReferenceOf($event);

            if ($subscriptionReference !== null) {
                (new SubscriptionLedger())->linkTransaction($subscriptionReference, $event->gatewayReference);
            }
        }

        // Logged either way: a redelivery is not a problem, but a payment that only ever
        // arrives as one is, and that is invisible unless the quiet case is written down too.
        (new Logger())->info($applied ? 'Applied a checkout callback.' : 'Acknowledged a checkout callback that changed nothing.', [
            'gateway' => $gateway,
            'event_id' => $event->eventId,
            'event_type' => $event->eventType,
            'status' => $event->status->value,
        ]);

        return Response::noContent();
    }

    /** @param array<string, string> $headers */
    private static function applySubscriptionEvent(PaddleSubscription $adapter, string $rawBody, array $headers, string $gateway): Response
    {
        try {
            $event = $adapter->parseSubscriptionCallback($rawBody, $headers);
        } catch (CheckoutException $e) {
            return self::refuse($gateway, $e);
        }

        $applied = (new SubscriptionLedger())->record($event);

        (new Logger())->info($applied ? 'Applied a subscription callback.' : 'Acknowledged a subscription callback that changed nothing.', [
            'gateway' => $gateway,
            'event_id' => $event->eventId,
            'event_type' => $event->eventType,
            'subscription_reference' => $event->subscription->gatewayReference,
            'status' => $event->subscription->status->value,
        ]);

        return Response::noContent();
    }

    /**
     * Deliberately not phrased as "the signature failed": this also covers a verified body that
     * was not an event the adapter can interpret, and a log line naming the wrong cause would
     * send whoever reads it during an incident to the wrong place entirely. Clarity's own
     * message says which it was, so it is carried through verbatim.
     */
    private static function refuse(string $gateway, CheckoutException $e): Response
    {
        (new Logger())->warning('Refused a checkout callback.', [
            'gateway' => $gateway,
            'reason' => $e->getMessage(),
        ]);

        return Response::text('Callback rejected: not a verified checkout event.', 400);
    }
}
