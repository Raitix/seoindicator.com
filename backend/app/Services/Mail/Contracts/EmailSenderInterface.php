<?php

namespace App\Services\Mail\Contracts;

interface EmailSenderInterface
{
    /**
     * Send an email to one or more recipients.
     *
     * @param string|array $to Single email or array of emails
     * @param string $subject Email subject
     * @param string $html HTML body content
     * @param array $attachments Array of attachment paths or ['path' => string, 'name' => string, 'mime' => string]
     * @param string|null $mailer Mailer to use (gmail, brevo, default)
     * @return bool True if sent successfully
     * @throws \App\Services\Mail\Exceptions\EmailSendException
     */
    public function send(
        string|array $to,
        string $subject,
        string $html,
        array $attachments = [],
        ?string $mailer = null
    ): bool;

    /**
     * Get the current mailer configuration.
     *
     * @param string $mailer Mailer name (gmail, brevo)
     * @return array Configuration array
     */
    public function getMailerConfig(string $mailer): array;

    /**
     * Validate that a mailer is properly configured.
     *
     * @param string $mailer Mailer name
     * @return bool True if configured
     */
    public function isMailerConfigured(string $mailer): bool;

    /**
     * Get available mailers.
     *
     * @return array List of configured mailer names
     */
    public function getAvailableMailers(): array;
}
