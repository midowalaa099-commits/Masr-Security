<?php

namespace App\Http\Controllers\Payments;

use App\Exceptions\InvalidPaymentSignatureException;
use App\Exceptions\PaymentNotFoundException;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives Paymob's HTTPS POST transaction callbacks.
 *
 * The route is exempt from CSRF (server-to-server) and always acknowledges
 * with 200 to avoid retries, but only *acts* when the HMAC signature is valid.
 */
class PaymobWebhookController
{
    public function __construct(private readonly PaymentService $payments) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $query = $request->query();

        try {
            $this->payments->handleWebhook($payload, $query);

            Log::info('paymob.webhook.ok', [
                'transaction_id' => data_get($payload, 'obj.id'),
                'order_id' => data_get($payload, 'obj.order.id'),
            ]);
        } catch (InvalidPaymentSignatureException) {
            Log::warning('paymob.webhook.invalid_signature');
        } catch (PaymentNotFoundException $e) {
            Log::warning('paymob.webhook.payment_not_found', [
                'transaction_id' => data_get($payload, 'obj.id'),
                'order_id' => data_get($payload, 'obj.order.id'),
                'merchant_order_id' => data_get($payload, 'obj.order.merchant_order_id'),
            ]);
        } catch (\Throwable $e) {
            Log::error('paymob.webhook.error', ['exception' => $e->getMessage()]);
        }

        // Always acknowledge so Paymob stops retrying.
        return response()->json(['received' => true]);
    }
}
