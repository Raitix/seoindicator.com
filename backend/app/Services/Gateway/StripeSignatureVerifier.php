<?php

namespace App\Services\Gateway;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\WebhookSignature as StripeWebhookSignature;
use Stripe\Exception\SignatureVerificationException;

/**
 * Stripe Webhook Signature Verifier for TASK-26-10.
 * 
 * Verifies that webhook requests are genuinely from Stripe
 * using timing-constant comparison to prevent timing attacks.
 * 
 * @see https://stripe.com/docs/webhooks/signatures
 * @see TASK-26-10
 * @see FEAT-026
 */
class StripeSignatureVerifier
{
    /**
     * The Stripe webhook secret for signature verification.
     */
    protected ?string $webhookSecret;

    /**
     * Maximum allowed age of webhook timestamp (in seconds).
     * Default: 5 minutes to account for clock drift.
     */
    protected int $tolerance;

    /**
     * Create a new signature verifier instance.
     */
    public function __construct(?string $webhookSecret = null, int $tolerance = 300)
    {
        $this->webhookSecret = $webhookSecret ?? config('services.stripe.webhook_secret');
        $this->tolerance = $tolerance;
    }

    /**
     * Verify the webhook signature from the request.
     *
     * @param Request $request
     * @return array{valid: bool, payload: string|null, error: string|null}
     */
    public function verify(Request $request): array
    {
        // Check if webhook secret is configured
        if (empty($this->webhookSecret)) {
            Log::error('Stripe webhook secret not configured');

            return [
                'valid' => false,
                'payload' => null,
                'error' => 'Webhook secret not configured',
            ];
        }

        // Get signature header
        $signatureHeader = $request->header('Stripe-Signature');

        if (empty($signatureHeader)) {
            Log::warning('Stripe webhook received without signature header', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return [
                'valid' => false,
                'payload' => null,
                'error' => 'Missing Stripe-Signature header',
            ];
        }

        // Get raw payload
        $payload = $request->getContent();

        if (empty($payload)) {
            Log::warning('Stripe webhook received with empty payload', [
                'ip' => $request->ip(),
            ]);

            return [
                'valid' => false,
                'payload' => null,
                'error' => 'Empty payload',
            ];
        }

        // Verify signature using Stripe SDK
        // This uses timing-constant comparison internally
        try {
            StripeWebhookSignature::verifyHeader(
                $payload,
                $signatureHeader,
                $this->webhookSecret,
                $this->tolerance
            );

            Log::debug('Stripe webhook signature verified', [
                'ip' => $request->ip(),
            ]);

            return [
                'valid' => true,
                'payload' => $payload,
                'error' => null,
            ];
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'payload' => null,
                'error' => 'Invalid signature: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify and return the payload or throw an exception.
     *
     * @param Request $request
     * @return string The verified payload
     * @throws SignatureVerificationException
     */
    public function verifyOrFail(Request $request): string
    {
        $result = $this->verify($request);

        if (!$result['valid']) {
            throw new SignatureVerificationException(
                $result['error'] ?? 'Signature verification failed',
                $request->header('Stripe-Signature') ?? ''
            );
        }

        return $result['payload'];
    }

    /**
     * Check if the webhook secret is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->webhookSecret);
    }

    /**
     * Get the current tolerance setting.
     *
     * @return int
     */
    public function getTolerance(): int
    {
        return $this->tolerance;
    }

    /**
     * Set a custom tolerance for timestamp verification.
     *
     * @param int $seconds
     * @return self
     */
    public function setTolerance(int $seconds): self
    {
        $this->tolerance = $seconds;

        return $this;
    }
}
