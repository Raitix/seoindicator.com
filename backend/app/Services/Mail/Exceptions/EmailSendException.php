<?php

namespace App\Services\Mail\Exceptions;

use Exception;

class EmailSendException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = 'Failed to send email',
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context about the error.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create exception for SMTP connection failure.
     */
    public static function connectionFailed(string $mailer, ?string $reason = null): self
    {
        return new self(
            "Failed to connect to SMTP server for mailer '{$mailer}'",
            0,
            null,
            ['mailer' => $mailer, 'reason' => $reason]
        );
    }

    /**
     * Create exception for authentication failure.
     */
    public static function authenticationFailed(string $mailer): self
    {
        return new self(
            "SMTP authentication failed for mailer '{$mailer}'",
            0,
            null,
            ['mailer' => $mailer]
        );
    }

    /**
     * Create exception for invalid recipient.
     */
    public static function invalidRecipient(string $email, ?string $reason = null): self
    {
        return new self(
            "Invalid recipient email address: {$email}",
            0,
            null,
            ['email' => $email, 'reason' => $reason]
        );
    }

    /**
     * Create exception for attachment error.
     */
    public static function attachmentError(string $path, ?string $reason = null): self
    {
        return new self(
            "Failed to attach file: {$path}",
            0,
            null,
            ['path' => $path, 'reason' => $reason]
        );
    }

    /**
     * Create exception for unconfigured mailer.
     */
    public static function mailerNotConfigured(string $mailer): self
    {
        return new self(
            "Mailer '{$mailer}' is not configured",
            0,
            null,
            ['mailer' => $mailer]
        );
    }

    /**
     * Create exception for retry exhaustion.
     */
    public static function retryExhausted(int $attempts, string $lastError): self
    {
        return new self(
            "Email send failed after {$attempts} attempts",
            0,
            null,
            ['attempts' => $attempts, 'last_error' => $lastError]
        );
    }
}
