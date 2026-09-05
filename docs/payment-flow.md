# Payment Flow

This document describes how orders and payments move through the system:
checkout, gateway redirection, sandbox mode, callbacks (webhook + redirect),
HMAC security, and idempotent state transitions.

```
Browser                 Laravel                         Paymob
  │  POST /checkout        │                              │
  │───────────────────────>│ placeOrder() (DB txn, locks) │
  │                        │  startPayment() -> Pending   │
  │                        │  createPayment()             │
  │                        │─────────────────────────────>│ Intention API
  │                        │<─────────────────────────────│ client_secret
  │<─ 302 (unified checkout│                              │
  │     or sandbox)        │                              │
  │  pay on gateway        │                              │
  │──────────────────────────────────────────────────────>│ auth/capture
  │                        │                              │
  │  webhook POST ─────────>  PaymobWebhookController      │
  │  redirect GET ────────>  CheckoutController::return    │
  │                        │ handleWebhook/handleRedirect  │
  │                        │ applyResult() (idempotent)    │
  │<──────────────────────── success/awaiting pages        │
```

## 1. Placing an order

`POST /checkout` → `CheckoutController::store()`:

1. `CheckoutService::placeOrder()` validates the cart under locked rows,
   recomputes totals server-side, snapshots inventory to each order item, and
   decrements stock. See `docs/architecture.md`.
2. `PaymentService::startPayment()` creates (or reuses) a **pending** `payment`
   record and moves the order from `pending` → `awaiting_payment` so it no longer
   shows as an unpaid open order.
3. `PaymentService::redirect()` asks the gateway to create the remote payment
   and returns a redirect URL.

## 2. Gateway integration (Paymob)

Implemented in `app/Services/Payments/PaymobGateway.php` against the current
official Paymob API:

- **Create**: `POST {base_url}/v1/intention/` with `Authorization: Token {secret_key}`.
  The payload includes amount (cents), currency `EGP`, `payment_methods`
  (integration id), items, billing/customer data, `special_reference` (the order
  number), `notification_url` (the webhook), and `redirection_url` (the return
  route).
- **Checkout**: redirect to
  `{base_url}/unifiedcheckout/?publicKey=...&clientSecret=...`.
- The returned `client_secret` is stored as `transaction_reference` and the
  intention id as `paymob_order_id` on the payment row.

Only `PaymentMethod::Card` and `PaymentMethod::Wallet` are exposed. A method is
offered only when `supportsMethod()` is true — i.e. the matching
`card_integration_id` / `wallet_integration_id` is configured (or sandbox mode
is active, where everything is "supported" locally).

## 3. Callbacks & signature validation

Callbacks are authenticated with **HMAC-SHA512** over the 20 documented
transaction fields, concatenated in the exact fixed order for webhooks (nested
keys like `order.id`, `source_data.pan`) and the matching flat key set
(`order_id`, `source_data_pan`, …) for the GET redirect. See
`PaymobGateway::WEBHOOK_HMAC_FIELDS` / `REDIRECT_HMAC_FIELDS`.

- `POST /api/paymob/webhook` → `PaymobWebhookController::handle()`. This route
  is **CSRF-exempt** (`bootstrap/app.php` whitelist) because it is
  server-to-server; its authenticity comes from the HMAC only. The controller
  always answers `200 {"received": true}` so Paymob stops retrying, but only
  *acts* on a valid signature.
- `GET /checkout/return/{payment}` → `CheckoutController::return()`. When the
  query string carries the HMAC fields, it runs the same resolution path.

Resolution (`resolveWebhook` / `resolveRedirect`):

1. Recompute the HMAC from the callback fields and compare with `hash_equals`.
   Invalid signatures throw `InvalidPaymentSignatureException` and are logged,
   never applied.
2. Match the local payment by `paymob_transaction_id` → `paymob_order_id` →
   the merchant order number (fallback for unlinked callbacks), with an amount
   cross-check (`amount_matches`).
3. Build a normalized `PaymentCallbackResult` (status, provider ids, raw
   payload with the card `pan` scrubbed).

## 4. State transitions (idempotent)

`PaymentService::applyResult()` runs in a transaction:

1. Re-fetch the payment with `lockForUpdate`.
2. **Duplicate protection**: if the payment is already final with the same
   status, do nothing.
3. Persist provider ids, status, `raw_response`, and `paid_at` on success.
4. Synchronize the order:
   - `success` → order `pending/awaiting_payment` → `paid`, audit logged.
   - `failed` → an order still `pending` → `awaiting_payment`, audit logged.

This guarantees at-least-once delivery from Paymob is safe: replaying a
webhook never double-charges or rewrites a settled payment.

## 5. Sandbox mode (no real credentials)

When `PAYMOB_SECRET_KEY`/`PAYMOB_PUBLIC_KEY` are not configured (or
`PAYMOB_SANDBOX_MODE=true`), `isSandboxMode()` is true and:

- `createPayment()` returns a **local** redirect URL: `/payments/sandbox/{payment}`
  (`SandboxPaymentController::show`), instead of calling Paymob.
- The sandbox page displays the order and two buttons:
  `Simulate Successful Payment` / `Simulate Failed Payment`.
- `POST /payments/sandbox/{payment}/complete` feeds the chosen outcome through
  the **exact same** `applyResult` path used for real webhooks — it never
  fabricates a payment by itself. It is only reachable when
  `config('paymob.sandbox_mode') === true`.

This keeps the whole checkout loop testable before real keys are configured.

## 6. The success page & guest access

After payment the customer lands on `/checkout/success/{order}`. Guests cannot
be matched by auth, so `CheckoutController::store()` records up to the 5 most
recent order numbers in the session (`recent_order_numbers`). The success page
renders if the order belongs to the authenticated user **or** its number is in
that session list; otherwise it 404s.

## Configuration keys

All in `config/paymob.php`, env-driven (see `.env.example`):

| Env | Purpose |
| --- | --- |
| `PAYMOB_API_URL` | API base URL (default `https://accept.paymob.com`) |
| `PAYMOB_SECRET_KEY` | Secret key (also gates the sandbox fallback) |
| `PAYMOB_PUBLIC_KEY` | Public key for the unified checkout URL |
| `PAYMOB_API_KEY` | Reserved master key (not used by the intention flow) |
| `PAYMOB_CARD_INTEGRATION_ID` | Integration id for card payments |
| `PAYMOB_WALLET_INTEGRATION_ID` | Integration id for wallet payments |
| `PAYMOB_HMAC_SECRET` | Secret used to verify callbacks |
| `PAYMOB_EXPIRATION` | Intention TTL in seconds (default 3600) |
| `PAYMOB_SANDBOX_MODE` | Force sandbox even if keys exist (`true`/`false`) |

## Verification points

- `tests/Feature/PaymobGatewayTest.php` — HMAC recomputation against the 20
  documented fields, rejection of tampered/webhook-with-invalid signature,
  mapping to a successful callback, and sandbox `supportsMethod` behaviour.
- `tests/Feature/CheckoutTest.php` — full guest checkout through the sandbox
  (success and failure paths), stock decrement, cart clearing, order-number
  format, and success-page access rules.