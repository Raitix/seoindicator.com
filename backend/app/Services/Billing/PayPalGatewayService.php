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
 * - Order creation
 * - Order capture
 * - Refund processing
 * - Webhook handling
 * - Customer management (PayPal uses payer_id, no customer storage)
 *
 * SECURITY CRITICAL: All webhook operations verify signatures.
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
     * PayPal webhook ID for signature verification.
     */
    protected string $webhookId;

    /**
     * Return URL for PayPal approval flow.
     */
    protected string $returnUrl;

    /**
     * Cancel URL for PayPal approval flow.
     */
    protected string $cancelUrl;

    /**
     * Cached access token.
     */
    protected ?string $accessToken = null;

    /**
     * Access token expiration time.
     */
    protected ?int $tokenExpiresAt = null;

    /**
     * Create a new PayPalGatewayService instance.
     *
     * @throws \RuntimeException If PayPal configuration is missing
     */
    public function __construct()
    {
        $this->clientId = Config::get('services.paypal.client_id') ?? '';
        $this->clientSecret = Config::get('services.paypal.client_secret') ?? '';
        $this->webhookId = Config::get('services.paypal.webhook_id') ?? '';
        $this->returnUrl = Config::get('services.paypal.return_url') ?? '';
        $this->cancelUrl = Config::get('services.paypal.cancel_url') ?? '';

        // Set API URL based on mode
        $mode = Config::get('services.paypal.mode', 'sandbox');
        $this->apiBaseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Validate that PayPal credentials are configured.
     *
     * @throws \RuntimeException If credentials are missing
     */
    protected function validateCredentials(): void
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }
    }

    /**
     * {@inheritdoc}
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
            // Get access token
            $accessToken = $this->getAccessToken();

            // Format amount as string
            $amountString = $this->formatAmount($amount, $currency);

            // Build custom_id for tracking
            $customId = "user_{$user->id}";
            if (isset($metadata['package_id'])) {
                $customId .= "_package_{$metadata['package_id']}";
            }

            // Generate idempotency key
            $idempotencyKey = 'order_' . Str::uuid()->toString();

            // Build order request
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => $amountString,
                        ],
                        'custom_id' => $customId,
                    ],
                ],
                'application_context' => [
                    'brand_name' => Config::get('app.name', 'SEOIndicator'),
                    'locale' => 'en-US',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $metadata['return_url'] ?? $this->returnUrl,
                    'cancel_url' => $metadata['cancel_url'] ?? $this->cancelUrl,
                ],
            ];

            // Create order via PayPal API
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
                'amount' => $amountString,
                'currency' => $currency,
            ]);

            return [
                'order_id' => $order['id'],
                'approval_url' => $approvalUrl,
                'amount' => $amountString,
                'currency' => $currency,
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

            // Handle network timeout
            if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Connection')) {
                throw new PaymentTimeoutException('PayPal service unavailable. Please try again.');
            }

            throw new PayPalPaymentException($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function capturePayment(string $paymentIntentId, array $options = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            // Generate idempotency key
            $idempotencyKey = 'capture_' . Str::uuid()->toString();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
                'PayPal-Request-Id' => $idempotencyKey,
            ])->post("{$this->apiBaseUrl}/v2/checkout/orders/{$paymentIntentId}/capture");

            if (!$response->successful()) {
                Log::error('PayPal capture failed', [
                    'order_id' => $paymentIntentId,
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);
                return false;
            }

            $capture = $response->json();

            Log::info('PayPal order captured', [
                'order_id' => $paymentIntentId,
                'status' => $capture['status'] ?? 'unknown',
            ]);

            return ($capture['status'] ?? '') === 'COMPLETED';
        } catch (\Exception $e) {
            Log::error('PayPal capture error', [
                'order_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refundPayment(string $paymentIntentId, ?float $amount = null): array
    {
        try {
            $accessToken = $this->getAccessToken();

            // Build refund request
            $refundData = [];
            if ($amount !== null) {
                $refundData['amount'] = [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => 'USD',
                ];
            }

            // Generate idempotency key
            $idempotencyKey = 'refund_' . Str::uuid()->toString();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
                'PayPal-Request-Id' => $idempotencyKey,
            ])->post("{$this->apiBaseUrl}/v2/payments/captures/{$paymentIntentId}/refund", $refundData);

            if (!$response->successful()) {
                $error = $response->json();
                $errorCode = $error['name'] ?? 'REFUND_FAILED';
                $errorMessage = $error['message'] ?? 'Refund failed';

                Log::error('PayPal refund failed', [
                    'capture_id' => $paymentIntentId,
                    'error' => $errorMessage,
                ]);

                throw new PayPalPaymentException(
                    $errorMessage,
                    $errorCode,
                    ['capture_id' => $paymentIntentId]
                );
            }

            $refund = $response->json();

            Log::info('PayPal refund processed', [
                'refund_id' => $refund['id'],
                'capture_id' => $paymentIntentId,
                'amount' => $refund['amount']['value'] ?? 'unknown',
            ]);

            return [
                'refund_id' => $refund['id'],
                'amount' => $refund['amount']['value'] ?? '0.00',
                'status' => $refund['status'] ?? 'PENDING',
            ];
        } catch (PayPalPaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayPal refund error', [
                'capture_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            throw new PayPalPaymentException(
                'Failed to process refund.',
                'REFUND_ERROR'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(Request $request): PaymentResult
    {
        // SECURITY CRITICAL: Verify webhook signature BEFORE processing
        // This prevents fraudulent webhook requests (fake payments, refunds, etc.)
        if (!$this->verifyWebhookSignature($request)) {
            throw new InvalidWebhookSignatureException('Invalid PayPal webhook signature.');
        }

        $payload = $request->getContent();
        $event = json_decode($payload, true);

        // Validate JSON payload
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Invalid PayPal webhook payload', [
                'error' => json_last_error_msg(),
            ]);
            return PaymentResult::failed(
                paymentId: null,
                amount: 0,
                errorCode: 'INVALID_PAYLOAD',
                errorMessage: 'Invalid JSON payload'
            );
        }

        Log::info('PayPal webhook received', [
            'event_id' => $event['id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown',
        ]);

        // Handle different event types
        return match ($event['event_type'] ?? null) {
            'CHECKOUT.ORDER.APPROVED' => $this->handleOrderApproved($event),
            'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($event),
            'PAYMENT.CAPTURE.DENIED' => $this->handleCaptureDenied($event),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handleCaptureRefunded($event),
            default => $this->handleUnknownEvent($event),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomer(User $user): ?string
    {
        // PayPal doesn't have customer objects like Stripe
        // Uses payer_id from approved orders instead
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function createCustomer(User $user): string
    {
        // PayPal doesn't support customer creation
        throw PayPalPaymentException::customerNotSupported();
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethods(User $user): array
    {
        // PayPal doesn't store payment methods like Stripe
        // Each transaction uses the payer's PayPal account directly
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        // PayPal doesn't support payment method management
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodOwner(string $paymentMethodId): ?int
    {
        // PayPal doesn't track payment method ownership
        return null;
    }

    /**
     * Verify webhook signature using PayPal headers.
     *
     * SECURITY CRITICAL: This method validates the authenticity of webhook requests.
     *
     * @param Request $request The incoming webhook request
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('PAYPAL-TRANSMISSION-SIG');
        $certUrl = $request->header('PAYPAL-CERT-URL');
        $transmissionId = $request->header('PAYPAL-TRANSMISSION-ID');
        $transmissionTime = $request->header('PAYPAL-TRANSMISSION-TIME');

        // Check required headers
        if (!$signature || !$certUrl || !$transmissionId || !$transmissionTime) {
            Log::warning('Missing PayPal webhook headers');
            return false;
        }

        // Validate certificate URL is from PayPal domains only (security)
        if (!$this->isValidCertificateUrl($certUrl)) {
            Log::warning('Invalid PayPal certificate URL', ['cert_url' => $certUrl]);
            return false;
        }

        // Validate transmission time is within tolerance
        if (!$this->isValidTransmissionTime($transmissionTime)) {
            Log::warning('PayPal webhook transmission time outside tolerance');
            return false;
        }

        try {
            $accessToken = $this->getAccessToken();
            $payload = $request->getContent();

            // Use PayPal's webhook verification API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
            ])->post("{$this->apiBaseUrl}/v1/notifications/verify-webhook-signature", [
                'transmission_id' => $transmissionId,
                'transmission_time' => $transmissionTime,
                'cert_url' => $certUrl,
                'auth_algo' => 'SHA256WithRSA',
                'transmission_sig' => $signature,
                'webhook_id' => $this->webhookId,
                'webhook_event' => json_decode($payload, true),
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return ($result['verification_status'] ?? '') === 'SUCCESS';
            }

            return false;
        } catch (\Exception $e) {
            Log::error('PayPal webhook verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Validate certificate URL is from PayPal domains only.
     *
     * SECURITY CRITICAL: Prevents malicious certificate injection.
     *
     * @param string $url The certificate URL to validate
     * @return bool True if URL is valid
     */
    public function isValidCertificateUrl(string $url): bool
    {
        // Must be HTTPS
        if (!str_starts_with($url, 'https://')) {
            return false;
        }

        // Must be from api.paypal.com or api.sandbox.paypal.com
        $validDomains = [
            'https://api.paypal.com/',
            'https://api.sandbox.paypal.com/',
        ];

        foreach ($validDomains as $domain) {
            if (str_starts_with($url, $domain)) {
                // Must have correct path
                if (str_contains($url, '/v1/notifications/certs/')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate transmission time is within tolerance.
     *
     * Prevents replay attacks by rejecting old timestamps.
     *
     * @param string|null $time The transmission time (ISO 8601)
     * @param int $toleranceSeconds Tolerance in seconds (default 5 minutes)
     * @return bool True if time is valid
     */
    public function isValidTransmissionTime(?string $time, int $toleranceSeconds = 300): bool
    {
        if (!$time) {
            return false;
        }

        try {
            $transmissionTime = \Carbon\Carbon::parse($time);
            $now = now();

            $diff = abs($now->diffInSeconds($transmissionTime));

            return $diff <= $toleranceSeconds;
        } catch (\Exception $e) {
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
        $customId = $resource['purchase_units'][0]['custom_id'] ?? '';
        $amount = $resource['purchase_units'][0]['amount']['value'] ?? '0';
        $currency = $resource['purchase_units'][0]['amount']['currency_code'] ?? 'USD';

        // Parse user_id from custom_id
        $userId = null;
        if (preg_match('/user_(\d+)/', $customId, $matches)) {
            $userId = (int) $matches[1];
        }

        Log::info('PayPal order approved', [
            'order_id' => $orderId,
            'user_id' => $userId,
            'amount' => $amount,
        ]);

        return new PaymentResult(
            success: true,
            paymentId: $orderId,
            amount: (float) $amount,
            currency: $currency,
            userId: $userId,
            requiresCapture: true
        );
    }

    /**
     * Handle PAYMENT.CAPTURE.COMPLETED event.
     */
    protected function handleCaptureCompleted(array $event): PaymentResult
    {
        $resource = $event['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $amount = $resource['amount']['value'] ?? '0';
        $currency = $resource['amount']['currency_code'] ?? 'USD';
        $customId = $resource['custom_id'] ?? '';

        // Parse user_id from custom_id
        $userId = null;
        if (preg_match('/user_(\d+)/', $customId, $matches)) {
            $userId = (int) $matches[1];
        }

        // Check for duplicate (idempotency)
        $existingTransaction = CreditTransaction::where('payment_provider', 'paypal')
            ->where('payment_id', $captureId)
            ->first();

        if ($existingTransaction) {
            Log::info('Duplicate PayPal webhook event detected', [
                'event_id' => $event['id'] ?? 'unknown',
                'capture_id' => $captureId,
            ]);

            return new PaymentResult(
                success: true,
                paymentId: $captureId,
                userId: $userId,
                isDuplicate: true
            );
        }

        Log::info('PayPal capture completed', [
            'capture_id' => $captureId,
            'user_id' => $userId,
            'amount' => $amount,
        ]);

        return PaymentResult::success(
            paymentId: $captureId,
            amount: (float) $amount,
            currency: $currency,
            userId: $userId,
            metadata: ['custom_id' => $customId]
        );
    }

    /**
     * Handle PAYMENT.CAPTURE.DENIED event.
     */
    protected function handleCaptureDenied(array $event): PaymentResult
    {
        $resource = $event['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $amount = $resource['amount']['value'] ?? '0';
        $customId = $resource['custom_id'] ?? '';

        // Parse user_id from custom_id
        $userId = null;
        if (preg_match('/user_(\d+)/', $customId, $matches)) {
            $userId = (int) $matches[1];
        }

        Log::warning('PayPal capture denied', [
            'capture_id' => $captureId,
            'user_id' => $userId,
        ]);

        return PaymentResult::failed(
            paymentId: $captureId,
            amount: (float) $amount,
            errorCode: 'DENIED',
            errorMessage: 'Payment was denied by PayPal.',
            userId: $userId
        );
    }

    /**
     * Handle PAYMENT.CAPTURE.REFUNDED event.
     */
    protected function handleCaptureRefunded(array $event): PaymentResult
    {
        $resource = $event['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $amount = $resource['amount']['value'] ?? '0';

        // Find the original transaction
        $transaction = CreditTransaction::where('payment_provider', 'paypal')
            ->where('payment_id', $captureId)
            ->first();

        $userId = $transaction?->user_id;

        Log::info('PayPal capture refunded', [
            'capture_id' => $captureId,
            'user_id' => $userId,
            'amount' => $amount,
        ]);

        // If userId is null, create a generic refund result
        if ($userId === null) {
            return new PaymentResult(
                success: true,
                paymentId: $captureId,
                amount: -(float) $amount,
                type: 'refund'
            );
        }

        return PaymentResult::refund(
            refundId: $captureId,
            amount: (float) $amount,
            userId: $userId
        );
    }

    /**
     * Handle unknown event type.
     */
    protected function handleUnknownEvent(array $event): PaymentResult
    {
        Log::info('Unhandled PayPal webhook event type', [
            'event_id' => $event['id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown',
        ]);

        return PaymentResult::ignored();
    }

    /**
     * Get PayPal access token.
     *
     * @return string Access token
     * @throws PayPalPaymentException If authentication fails
     */
    protected function getAccessToken(): string
    {
        // Validate credentials before making API call
        $this->validateCredentials();

        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->timeout(30)
                ->post("{$this->apiBaseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                $error = $response->json();
                $errorCode = $error['error'] ?? 'AUTH_FAILED';
                $errorMessage = $error['error_description'] ?? 'Authentication failed';

                Log::error('PayPal authentication failed', [
                    'error' => $errorMessage,
                    'code' => $errorCode,
                ]);

                // Check for rate limit
                if ($response->status() === 429) {
                    throw new PayPalPaymentException('Rate limit exceeded. Please try again later.', 'RATE_LIMIT');
                }

                throw new PayPalPaymentException(
                    'PayPal authentication failed.',
                    $errorCode
                );
            }

            $data = $response->json();
            $this->accessToken = $data['access_token'];
            $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600) - 60; // Buffer 60 seconds

            return $this->accessToken;
        } catch (PayPalPaymentException $e) {
            throw $e;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PayPal connection error', [
                'error' => $e->getMessage(),
            ]);

            throw new PayPalPaymentException(
                'PayPal service unavailable. Please try again.',
                'CONNECTION_ERROR'
            );
        } catch (\InvalidArgumentException $e) {
            // Handle invalid response (e.g., status code 0 from network errors)
            Log::error('PayPal invalid response', [
                'error' => $e->getMessage(),
            ]);

            throw new PayPalPaymentException(
                'PayPal service unavailable. Please try again.',
                'CONNECTION_ERROR'
            );
        } catch (\Exception $e) {
            Log::error('PayPal access token error', [
                'error' => $e->getMessage(),
            ]);

            throw new PayPalPaymentException(
                'Failed to authenticate with PayPal.',
                'AUTH_ERROR'
            );
        }
    }

    /**
     * Format amount for PayPal API.
     *
     * @param float $amount Amount in dollars
     * @param string $currency Currency code
     * @return string Formatted amount
     */
    protected function formatAmount(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);

        // Zero-decimal currencies don't have decimal places
        if (in_array($currency, self::ZERO_DECIMAL_CURRENCIES)) {
            return (string) (int) round($amount);
        }

        return number_format($amount, 2, '.', '');
    }

    /**
     * Handle PayPal API error response.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param string $context Error context for logging
     * @throws PayPalPaymentException
     */
    protected function handleApiError($response, string $context): void
    {
        $error = $response->json();
        $errorCode = $error['name'] ?? 'API_ERROR';
        $errorMessage = $error['message'] ?? 'Unknown error';

        // Check for rate limit
        if ($response->status() === 429) {
            Log::warning('PayPal rate limit hit', ['context' => $context]);
            throw new PayPalPaymentException('Rate limit exceeded. Please try again later.', 'RATE_LIMIT');
        }

        Log::error('PayPal API error', [
            'context' => $context,
            'code' => $errorCode,
            'message' => $errorMessage,
            'status' => $response->status(),
        ]);

        throw new PayPalPaymentException(
            $errorMessage,
            $errorCode
        );
    }
}
