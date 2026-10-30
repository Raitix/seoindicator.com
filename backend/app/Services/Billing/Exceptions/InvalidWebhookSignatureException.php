<?php

namespace App\Services\Billing\Exceptions;

use Exception;

/**
 * Invalid Webhook Signature Exception
 *
 * SECURITY CRITICAL: Thrown when webhook signature verification fails.
 * This prevents fraudulent webhook requests from being processed.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
class InvalidWebhookSignatureException extends Exception
{
    /**
     * Create a new InvalidWebhookSignatureException instance.
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Invalid webhook signature.',
        int $code = 400,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for missing signature header.
     */
    public static function missingSignature(): self
    {
        return new self('Missing Stripe-Signature header.');
    }

    /**
     * Create exception for malformed signature.
     */
    public static function malformedSignature(): self
    {
        return new self('Malformed webhook signature.');
    }

    /**
     * Create exception for expired timestamp.
     */
    public static function expiredTimestamp(): self
    {
        return new self('Webhook signature timestamp expired. Possible replay attack.');
    }

    /**
     * Create exception for signature mismatch.
     */
    public static function signatureMismatch(): self
    {
        return new self('Webhook signature verification failed.');
    }

    /**
     * Whether this should be logged as a security event.
     */
    public function isSecurityEvent(): bool
    {
        return true;
    }

    /**
     * Get log level for this exception.
     */
    public function getLogLevel(): string
    {
        return 'warning'; // Log as warning for security monitoring
    }
}
