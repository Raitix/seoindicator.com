<?php

namespace App\Services\Billing;

use App\Models\User;
use App\Models\CreditTransaction;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\DTOs\PaymentResult;
use App\Services\Billing\Exceptions\PayPalPaymentException;
use App\Services\Billing\Exceptions\PaymentTimeoutException;
use App\Services\Billing\Exceptions\PaymentValidationException;
use App\Services\Billing\Exceptions\InvalidWebhookSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PayPal Gateway Service
 *
 * Handles all PayPal payment operations using Orders API v2 including:
 * - Order creation with return/cancel URLs
 * - Order capture
 * - Refund processing
 * - Webhook handling with signature verification
 * - Customer management (PayPal uses payer_id, no customer storage)
 *
 * SECURITY CRITICAL: All webhook operations verify signatures via PayPal headers.
 *
 * @see TASK-26-12
 * @see FEAT-026
 */
class PayPalGatewayService implements PaymentGatewayInterface
{
    /**
     * Minimum purchase amount in dollars.
     */
    public const MINIMUM_AMOUNT = 5.00;

    /**
     * Maximum purchase amount in dollars.
     */
    public const MAXIMUM_AMOUNT = 999999.99;

    /**
     * Zero-decimal currencies (no cents).
     */
    public const ZERO_DECIMAL_CURRENCIES = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    /**
     * PayPal API base URL.
     */
    protected string $apiBaseUrl;

    /**
     * PayPal client ID.
     */
    protected string $clientId;

    /**
     * PayPal client secret.
     */
    protected string $clientSecret;

    /**
     * PayPal webhook ID.
     */
    protected string $webhookId;

    /**
     * Return URL for PayPal checkout.
     */
    protected string $returnUrl;

    /**
     * Cancel URL for PayPal checkout.
     */
    protected string $cancelUrl;

    /**
     * Access token cache.
     */
    protected ?string $accessToken = null;

    /**
     * Create a new PayPalGatewayService instance.
     *
     * @throws \RuntimeException If PayPal configuration is missing
     */
    public function __construct()
    {
        $this->clientId = Config::get('services.paypal.client_id', '');
        $this->clientSecret = Config::get('services.paypal.client_secret', '');
        $this->webhookId = Config::get('services.paypal.webhook_id', '');
        
        $mode = Config::get('services.paypal.mode', 'sandbox');
        $this->apiBaseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->returnUrl = Config::get('services.paypal.return_url', config('app.url') . '/payments/paypal/return');
        $this->cancelUrl = Config::get('services.paypal.cancel_url', config('app.url') . '/payments/paypal/cancel');

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }
    }

    /**
     * {@inheritdoc}
     *
     * PayPal creates an Order instead of a PaymentIntent.
     * Returns order_id and approval_url for frontend redirect.
     */
    public function createPaymentIntent(
        User $user,
        float $amount,
        string $currency = 'USD',
        array $metadata = []
    ): array {
        // Validate minimum amount
        if ($amount < self::MINIMUM_AMOUNT) {
            throw PaymentValidationException::minimumAmount(self::MINIMUM_AMOUNT, $amount);
        }

        // Validate maximum amount
        if ($amount > self::MAXIMUM_AMOUNT) {
            throw PaymentValidationException::maximumAmount(self::MAXIMUM_AMOUNT, $amount);
        }

        try {
            $accessToken = $this->getAccessToken();
            
            // Generate idempotency key
            $idempotencyKey = 'order_' . Str::uuid()->toString();

            // Build custom_id from user_id and package_id
            $customId = "user_{$user->id}";
            if (isset($metadata['package_id'])) {
                $customId .= "_package_{$metadata['package_id']}";
            }

            // Build order request
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => $this->formatAmount($amount, $currency),
                        ],
                        'custom_id' => $customId,
                    ],
                ],
                'application_context' => [
                    'brand_name' => Config::get('app.name', 'SEOIndicator'),
                    'return_url' => $this->returnUrl,
                    'cancel_url' => $this->cancelUrl,
                    'user_action' => 'PAY_NOW',
                ],
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
                'PayPal-Request-Id' => $idempotencyKey,
            ])->post("{$this->apiBaseUrl}/v2/checkout/orders", $orderData);

            if (!$response->successful()) {
                $this->handleApiError($response, 'Order creation failed');
            }

            $order = $response->json();

            // Extract approval URL
            $approvalUrl = null;
            foreach ($order['links'] ?? [] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            Log::info('PayPal order created', [
                'order_id' => $order['id'],
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return [
                'order_id' => $order['id'],
                'approval_url' => $approvalUrl,
                'amount' => $this->formatAmount($amount, $currency),
                'currency' => strtoupper($currency),
                'status' => $order['status'],
            ];
        } catch (PayPalPaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayPal order creation error', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'connection')) {
                throw new PaymentTimeoutException('PayPal service unavailable. Please try again.');
            }

            throw new PayPalPaymentException(
                $e->getMessage(),
                'ORDER_CREATION_FAILED',
                ['user_id' => $user->id]
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * Captures an approved PayPal order.
     */
    public function capturePayment(string $orderId, array $options = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            // Generate idempotency key
            $idempotencyKey = 'capture_' . Str::uuid()->toString();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
                'PayPal-Request-Id' => $idempotencyKey,
            ])->post("{$this->apiBaseUrl}/v2/checkout/orders/{$orderId}/capture");

            if (!$response->successful()) {
                Log::error('PayPal capture failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $capture = $response->json();

            // Check if capture was successful
            $status = $capture['status'] ?? 'UNKNOWN';
            
            Log::info('PayPal order captured', [
                'order_id' => $orderId,
                'status' => $status,
            ]);

            return $status === 'COMPLETED';
        } catch (\Exception $e) {
            Log::error('PayPal capture error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * Refunds a captured PayPal payment.
     */
    public function refundPayment(string $captureId, ?float $amount = null): array
    {
        try {
            $accessToken = $this->getAccessToken();

            // Generate idempotency key
            $idempotencyKey = 'refund_' . Str::uuid()->toString();

            $refundData = [];
            if ($amount !== null) {
                // Get currency from the capture (assume USD if not known)
                $refundData['amount'] = [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => 'USD',
                ];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
                'PayPal-Request-Id' => $idempotencyKey,
            ])->post("{$this->apiBaseUrl}/v2/payments/captures/{$captureId}/refund", $refundData);

            if (!$response->successful()) {
                $errorData = $response->json();
                throw new PayPalPaymentException(
                    $errorData['message'] ?? 'Refund failed',
                    $errorData['name'] ?? 'REFUND_FAILED',
                    ['capture_id' => $captureId]
                );
            }

            $refund = $response->json();

            Log::info('PayPal refund processed', [
                'refund_id' => $refund['id'],
                'capture_id' => $captureId,
                'amount' => $refund['amount']['value'] ?? $amount,
            ]);

            return [
                'refund_id' => $refund['id'],
                'amount' => $refund['amount']['value'] ?? number_format($amount ?? 0, 2, '.', ''),
                'status' => strtoupper($refund['status'] ?? 'COMPLETED'),
            ];
        } catch (PayPalPaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayPal refund error', [
                'capture_id' => $captureId,
                'error' => $e->getMessage(),
            ]);

            throw new PayPalPaymentException(
                'Failed to process refund.',
                'REFUND_ERROR',
                ['capture_id' => $captureId]
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * SECURITY CRITICAL: Verifies webhook signature using PayPal headers.
     */
    public function handleWebhook(Request $request): PaymentResult
    {
        $payload = $request->getContent();
        $signature = $request->header('PAYPAL-TRANSMISSION-SIG');
        $certUrl = $request->header('PAYPAL-CERT-URL');
        $transmissionId = $request->header('PAYPAL-TRANSMISSION-ID');
        $transmissionTime = $request->header('PAYPAL-TRANSMISSION-TIME');

        // Verify required headers exist
        if (!$signature || !$certUrl || !$transmissionId || !$transmissionTime) {
            Log::warning('PayPal webhook missing required headers');
            throw new InvalidWebhookSignatureException('Missing PayPal webhook verification headers.');
        }

        // Verify webhook signature
        if (!$this->verifyWebhookSignature($payload, $signature, $certUrl, $transmissionId, $transmissionTime)) {
            Log::warning('PayPal webhook signature verification failed');
            throw new InvalidWebhookSignatureException('Invalid webhook signature.');
        }

        $event = json_decode($payload, true);
        $eventType = $event['event_type'] ?? 'UNKNOWN';
        $eventId = $event['id'] ?? 'unknown';

        Log::info('PayPal webhook received', [
            'event_id' => $eventId,
            'event_type' => $eventType,
        ]);

        // Handle different event types
        return match ($eventType) {
            'CHECKOUT.ORDER.APPROVED' => $this->handleOrderApproved($event),
            'PAYMENT.CAPTURE.COMPLETED' => $this->handlePaymentCaptureCompleted($event),
            'PAYMENT.CAPTURE.DENIED' => $this->handlePaymentCaptureDenied($event),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handlePaymentCaptureRefunded($event),
            default => $this->handleUnknownEvent($event),
        };
    }

    /**
     * {@inheritdoc}
     *
     * PayPal doesn't store customers like Stripe.
     * Returns null as customer management is not applicable.
     */
    public function getCustomer(User $user): ?string
    {
        // PayPal doesn't have customer objects like Stripe
        // Uses payer_id from approved orders instead
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * PayPal doesn't support customer creation.
     * Throws exception explaining to use payer_id from approved orders.
     */
    public function createCustomer(User $user): string
    {
        throw PayPalPaymentException::customerNotSupported();
    }

    /**
     * {@inheritdoc}
     *
     * PayPal doesn't store payment methods like Stripe.
     * Returns empty array as payment method management is not applicable.
     */
    public function getPaymentMethods(User $user): array
    {
        // PayPal doesn't store payment methods like Stripe
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * PayPal doesn't support payment method deletion.
     */
    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        // PayPal doesn't support payment method deletion
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * PayPal doesn't track payment method ownership.
     */
    public function getPaymentMethodOwner(string $paymentMethodId): ?int
    {
        // PayPal doesn't track payment method ownership
        return null;
    }

    /**
     * Get PayPal access token using OAuth2.
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->apiBaseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new PayPalPaymentException(
                'Failed to authenticate with PayPal.',
                'AUTH_FAILED',
                [],
                401
            );
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    /**
     * Verify PayPal webhook signature.
     *
     * @param string $payload Raw request body
     * @param string $signature PAYPAL-TRANSMISSION-SIG header
     * @param string $certUrl PAYPAL-CERT-URL header
     * @param string $transmissionId PAYPAL-TRANSMISSION-ID header
     * @param string $transmissionTime PAYPAL-TRANSMISSION-TIME header
     * @return bool True if signature is valid
     */
    protected function verifyWebhookSignature(
        string $payload,
        string $signature,
        string $certUrl,
        string $transmissionId,
        string $transmissionTime
    ): bool {
        try {
            // Build the expected signature string
            $expectedSignature = "{$transmissionId}|{$transmissionTime}|{$this->webhookId}|" . crc32($payload);

            // Fetch the certificate
            $certResponse = Http::get($certUrl);
            if (!$certResponse->successful()) {
                Log::error('Failed to fetch PayPal certificate', ['cert_url' => $certUrl]);
                return false;
            }

            $certContent = $certResponse->body();
            
            // Extract public key from certificate
            $certInfo = openssl_x509_parse($certContent);
            if (!$certInfo) {
                Log::error('Failed to parse PayPal certificate');
                return false;
            }

            $publicKey = openssl_pkey_get_public($certContent);
            if (!$publicKey) {
                Log::error('Failed to extract public key from PayPal certificate');
                return false;
            }

            // Verify the signature
            $result = openssl_verify(
                $expectedSignature,
                base64_decode($signature),
                $publicKey,
                OPENSSL_ALGO_SHA256
            );

            return $result === 1;
        } catch (\Exception $e) {
            Log::error('PayPal webhook signature verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle CHECKOUT.ORDER.APPROVED event.
     */
    protected function handleOrderApproved(array $event): PaymentResult
    {
        $resource = $event['resource'] ?? [];
        $orderId = $resource['id'] ?? null;
        $customId = $resource['purchase_units'][0]['custom_id'] ?? null;

        // Parse user_id from custom_id
        $userId = null;
        if ($customId && preg_match('/user_(\d+)/', $customId, $matches)) {
            $userId = (int) $matches[1];
        }

        Log::info('PayPal order approved', [
            'order_id' => $orderId,
            'user_id' => $userId,
        ]);

        return new PaymentResult(
            success: true,
            paymentId: $orderId,
            userId: $userId,
            metadata: ['custom_id' => $customId],
            type: 'order_approved',
            requiresCapture: true,
        );
    }

    /**
     * Handle PAYMENT.CAPTURE.COMPLETED event.
     */
    protected function handlePaymentCaptureCompleted(array $event): PaymentResult
    {
        $resource = $event['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $amount = (float) ($resource['amount']['value'] ?? 0);
        $currency = strtoupper($resource['amount']['currency_code'] ?? 'USD');
        $customId = $resource['custom_id'] ?? null;

        // Parse user_id from custom_id
        $userId = null;
        if ($customId && preg_match('/user_(\d+)/', $customId, $matches)) {
            $userId = (int) $matches[1];
        }

        // Check for duplicate (idempotency)
        $existingTransaction = CreditTransaction::where('payment_provider', 'paypal')
            ->where('payment_id', $captureId)
            ->first();

        if ($existingTransaction) {
            Log::info('Duplicate PayPal webhook event detected', [
                'event_id' => $event['id'] ?? null,
                'capture_id' => $captureId,
            ]);

            return PaymentResult::duplicate($captureId, $userId);
        }

        return PaymentResult::success(
            paymentId: $captureId,
            amount: $amount,
            currency: $currency,
            userId: $userId,
            metadata: ['custom_id' => $customId, 'order_id' => $resource['supplementary_data']['related_ids']['order_id'] ?? null]
        );
    }

    /**
     * Handle PAYMENT.CAPTURE.DENIED event.
     */
    protected function handlePaymentCaptureDenied(array $event): PaymentResult
    {
        $resource = $event['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $amount = (float) ($resource['amount']['value'] ?? 0);
        $customId = $resource['custom_id'] ?? null;

        // Parse user_id from custom_id
        $userId = null;
        if ($customId && preg_match('/user_(\d+)/', $customId, $matches)) {
            $userId = (int) $matches[1];
        }

        Log::warning('PayPal payment denied', [
            'capture_id' => $captureId,
            'user_id' => $userId,
        ]);

        return PaymentResult::failed(
            paymentId: $captureId,
            amount: $amount,
            errorCode: 'DENIED',
            errorMessage: 'PayPal payment was denied.',
            userId: $userId
        );
    }

    /**
     * Handle PAYMENT.CAPTURE.REFUNDED event.
     */
    protected function handlePaymentCaptureRefunded(array $event): PaymentResult
    {
        $resource = $event['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $refundId = $resource['refund_id'] ?? $captureId;
        $amount = (float) ($resource['amount']['value'] ?? 0);
        $customId = $resource['custom_id'] ?? null;

        // Parse user_id from custom_id
        $userId = null;
        if ($customId && preg_match('/user_(\d+)/', $customId, $matches)) {
            $userId = (int) $matches[1];
        }

        Log::info('PayPal payment refunded', [
            'capture_id' => $captureId,
            'refund_id' => $refundId,
            'user_id' => $userId,
        ]);

        return PaymentResult::refund(
            refundId: $refundId,
            amount: $amount,
            userId: $userId
        );
    }

    /**
     * Handle unknown event type.
     */
    protected function handleUnknownEvent(array $event): PaymentResult
    {
        Log::info('Unhandled PayPal webhook event type', [
            'event_id' => $event['id'] ?? null,
            'event_type' => $event['event_type'] ?? 'UNKNOWN',
        ]);

        return PaymentResult::ignored();
    }

    /**
     * Format amount for PayPal API (string format).
     */
    protected function formatAmount(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);

        // Zero-decimal currencies don't have decimal places
        if (in_array($currency, self::ZERO_DECIMAL_CURRENCIES)) {
            return (string) (int) round($amount);
        }

        // Standard formatting with 2 decimal places
        return number_format($amount, 2, '.', '');
    }

    /**
     * Handle PayPal API error response.
     */
    protected function handleApiError($response, string $defaultMessage): void
    {
        $errorData = $response->json();
        $errorMessage = $errorData['message'] ?? $defaultMessage;
        $errorCode = $errorData['name'] ?? 'API_ERROR';

        Log::error('PayPal API error', [
            'status' => $response->status(),
            'error' => $errorMessage,
            'code' => $errorCode,
        ]);

        // Handle rate limiting
        if ($response->status() === 429) {
            throw new PayPalPaymentException(
                'Rate limit exceeded. Please wait a moment and try again.',
                'RATE_LIMIT',
                [],
                429
            );
        }

        // Handle authentication errors
        if ($response->status() === 401) {
            throw new PayPalPaymentException(
                'PayPal authentication failed.',
                'AUTH_ERROR',
                [],
                401
            );
        }

        throw new PayPalPaymentException($errorMessage, $errorCode);
    }
}
