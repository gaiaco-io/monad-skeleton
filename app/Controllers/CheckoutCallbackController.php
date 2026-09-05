<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middlewares\Logger;
use Monad\Clarity\Services\Checkout\CheckoutException;
use Monad\Clarity\Services\Checkout\TransactionLedger;
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
 * - **204** — verified and applied, or verified and already applied. `recordCallback()` returns
 *   false for a redelivery and for an event that arrived after the transaction had already
 *   settled; both mean "handled, nothing further to do", so both acknowledge. Answering
 *   anything else to a redelivery is how a retry storm starts.
 * - **400** — `parseCallback()` refused it. That covers two causes it does not distinguish
 *   between, and neither does this endpoint, because the remedy is the same: a retry of the
 *   same bytes fails identically. Either the signature was absent, malformed, stale or did not
 *   verify — in practice a missing or wrong `*_WEBHOOK_SECRET` far more often than an attack —
 *   or the bytes verified but were not a checkout event at all. A Stripe endpoint receives
 *   every event type enabled on it, and the default is all of them, so a `product.created`
 *   arriving here is an endpoint scoped too widely rather than anything sinister. The log line
 *   carries Clarity's own message, which says which of the two happened; the response body
 *   does not (§10.6).
 * - **404** — verified, but the ledger holds no transaction for the reference it names. Worth
 *   retrying, because the usual cause is a race with the `open()` that records a checkout, and
 *   the retry finds the row. The other cause is a foreign transaction — another application on
 *   the same gateway account — which retries until the gateway gives up, as it should.
 *
 * The response body is a short fixed string. The gateway's dashboard shows it verbatim, so it
 * says enough to tell the three cases apart and nothing about this application's internals
 * (§10.6). The detail goes to the error log, where it is not on the public internet.
 *
 * @package App\Controllers
 */
final class CheckoutCallbackController
{
    public static function receive(Request $request): Response
    {
        $checkout = require dirname(__DIR__, 2) . '/config/checkout.php';

        $headers = [$checkout['signature_header'] => $request->header($checkout['signature_header']) ?? ''];

        try {
            $event = $checkout['adapter']->parseCallback($request->rawBody(), $headers);
        } catch (CheckoutException $e) {
            // Deliberately not phrased as "the signature failed": this also catches a verified
            // body that was not a checkout event, and a log line that named the wrong cause
            // would send whoever reads it during an incident to the wrong place entirely.
            // Clarity's own message says which it was, so it is carried through verbatim.
            (new Logger())->warning('Refused a checkout callback.', [
                'gateway' => $checkout['gateway'],
                'reason' => $e->getMessage(),
            ]);

            return Response::text('Callback rejected: not a verified checkout event.', 400);
        }

        try {
            $applied = (new TransactionLedger())->recordCallback($event);
        } catch (CheckoutException $e) {
            (new Logger())->warning('A verified checkout callback named an unknown transaction.', [
                'gateway' => $checkout['gateway'],
                'event_id' => $event->eventId,
                'gateway_reference' => $event->gatewayReference,
                'reason' => $e->getMessage(),
            ]);

            return Response::text('No transaction for that reference.', 404);
        }

        // Logged either way: a redelivery is not a problem, but a payment that only ever
        // arrives as one is, and that is invisible unless the quiet case is written down too.
        (new Logger())->info($applied ? 'Applied a checkout callback.' : 'Acknowledged a checkout callback that changed nothing.', [
            'gateway' => $checkout['gateway'],
            'event_id' => $event->eventId,
            'event_type' => $event->eventType,
            'status' => $event->status->value,
        ]);

        return Response::noContent();
    }
}
