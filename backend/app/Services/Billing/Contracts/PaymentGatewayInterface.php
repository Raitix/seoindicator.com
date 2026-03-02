<?php

namespace App\Services\Billing\Contracts;

use App\Models\User;
use App\Services\Billing\DTOs\PaymentResult;
use Illuminate\Http\Request;

/**
 * Payment Gateway Interface
 *
 * Defines the contract for payment gateway implementations.
 * All payment providers (Stripe, PayPal) must implement this interface.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
interface PaymentGatewayInterface
{
    /**
     * Create a payment intent for processing a payment.
     *
     * @param User $user The user making the payment
     * @param float $amount Payment amount in dollars (e.g., 10.00)
     * @param string $currency Currency code (USD, EUR, etc.)
     * @param array $metadata Additional metadata (package_id, etc.)
     * @return array Payment intent data with client_secret
     * @throws \App\Services\Billing\Exceptions\StripePaymentException
     * @throws \App\Services\Billing\Exceptions\PaymentValidationException
     */
    public function createPaymentIntent(
        User $user,
        float $amount,
        string $currency = 'USD',
        array $metadata = []
    ): array;

    /**
     * Capture a previously authorized payment.
     *
     * @param string $paymentIntentId The payment intent ID to capture
     * @param array $options Additional capture options (partial capture, etc.)
     * @return bool True if capture succeeded
     */
    public function capturePayment(string $paymentIntentId, array $options = []): bool;

    /**
     * Refund a payment (full or partial).
     *
     * @param string $paymentIntentId The payment intent ID to refund
     * @param float|null $amount Amount to refund (null for full refund)
     * @return array Refund data with refund_id, amount, status
     * @throws \App\Services\Billing\Exceptions\StripePaymentException
     */
    public function refundPayment(string $paymentIntentId, ?float $amount = null): array;

    /**
     * Handle an incoming webhook from the payment provider.
     *
     * SECURITY CRITICAL: Must verify webhook signature before processing.
     *
     * @param Request $request The incoming webhook request
     * @return PaymentResult Structured result of webhook processing
     * @throws \App\Services\Billing\Exceptions\InvalidWebhookSignatureException
     */
    public function handleWebhook(Request $request): PaymentResult;

    /**
     * Get the Stripe customer ID for a user if exists.
     *
     * @param User $user The user to look up
     * @return string|null Customer ID or null if not exists
     */
    public function getCustomer(User $user): ?string;

    /**
     * Create a new Stripe customer for a user.
     *
     * @param User $user The user to create a customer for
     * @return string The new customer ID
     * @throws \App\Services\Billing\Exceptions\StripePaymentException
     */
    public function createCustomer(User $user): string;

    /**
     * Get saved payment methods for a user.
     *
     * @param User $user The user to get payment methods for
     * @return array List of payment method objects
     */
    public function getPaymentMethods(User $user): array;

    /**
     * Delete a payment method.
     *
     * @param string $paymentMethodId The payment method ID to delete
     * @return bool True if deletion succeeded
     */
    public function deletePaymentMethod(string $paymentMethodId): bool;

    /**
     * Get the owner user ID of a payment method.
     *
     * @param string $paymentMethodId The payment method ID
     * @return int|null User ID or null if not found
     */
    public function getPaymentMethodOwner(string $paymentMethodId): ?int;
}
