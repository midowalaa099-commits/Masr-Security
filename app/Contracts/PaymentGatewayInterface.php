<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Exceptions\PaymentNotFoundException;
use App\Models\Payment;
use App\Models\PaymentCallbackResult;

/**
 * Thin abstraction over an online payment gateway.
 *
 * The only gateway shipped in the application is Paymob, which is bound to
 * this contract in the service container. Controllers and services depend on
 * the contract, not on the concrete gateway.
 */
interface PaymentGatewayInterface
{
    public function name(): string;

    /**
     * True when real gateway credentials are configured.
     */
    public function isConfigured(): bool;

    /**
     * True when the gateway falls back to the local sandbox simulator.
     */
    public function isSandboxMode(): bool;

    public function supportsMethod(PaymentMethod $method): bool;

    /**
     * Create the remote payment and return the URL the customer should be
     * redirected to.
     *
     * @return array{redirect_url: string}
     */
    public function createPayment(Payment $payment, PaymentMethod $method, string $returnUrl): array;

    /**
     * Validate + resolve a POST webhook payload posted by Paymob.
     *
     * @param  array<string, mixed>  $payload  JSON body of the callback
     * @param  array<string, mixed>  $query  Query string of the callback URL (contains hmac)
     *
     * @throws InvalidPaymentSignatureException
     * @throws PaymentNotFoundException
     */
    public function resolveWebhook(array $payload, array $query): PaymentCallbackResult;

    /**
     * Validate + resolve an authenticated redirect (GET) returning from Paymob.
     *
     * @param  array<string, mixed>  $query
     */
    public function resolveRedirect(array $query): ?PaymentCallbackResult;
}
