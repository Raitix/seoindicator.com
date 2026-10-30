<?php

namespace App\Services\Mail;

use App\Services\Mail\Contracts\EmailSenderInterface;
use App\Services\Mail\Exceptions\EmailSendException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class EmailSenderService implements EmailSenderInterface
{
    /**
     * SMTP provider configurations.
     */
    protected const PROVIDERS = [
        'gmail' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username_env' => 'MAIL_GMAIL_USERNAME',
            'password_env' => 'MAIL_GMAIL_PASSWORD',
            'daily_limit' => 500,
        ],
        'brevo' => [
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'encryption' => 'tls',
            'username_env' => 'MAIL_BREVO_USERNAME',
            'password_env' => 'MAIL_BREVO_PASSWORD',
            'daily_limit' => 300,
        ],
    ];

    /**
     * Maximum retry attempts for failed sends.
     */
    protected const MAX_RETRIES = 3;

    /**
     * Delay between retries in seconds.
     */
    protected const RETRY_DELAY = 5;

    /**
     * Maximum attachment size in bytes (10MB).
     */
    protected const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024;

    /**
     * Logger instance.
     */
    protected \Illuminate\Log\Logger $logger;

    /**
     * Create a new EmailSenderService instance.
     */
    public function __construct()
    {
        $this->logger = Log::channel('mail');
    }

    /**
     * {@inheritdoc}
     */
    public function send(
        string|array $to,
        string $subject,
        string $html,
        array $attachments = [],
        ?string $mailer = null
    ): bool {
        $recipients = is_array($to) ? $to : [$to];
        $mailer = $mailer ?? config('mail.default', 'smtp');

        // Validate recipients
        $this->validateRecipients($recipients);

        // Validate and prepare attachments
        $preparedAttachments = $this->prepareAttachments($attachments);

        // Check if mailer is configured
        if (!$this->isMailerConfigured($mailer)) {
            throw EmailSendException::mailerNotConfigured($mailer);
        }

        // Configure mailer for provider if needed
        $this->configureProviderMailer($mailer);

        // Send with retry logic
        return $this->sendWithRetry(
            $recipients,
            $subject,
            $html,
            $preparedAttachments,
            $mailer
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMailerConfig(string $mailer): array
    {
        if (isset(self::PROVIDERS[$mailer])) {
            $provider = self::PROVIDERS[$mailer];
            return [
                'host' => $provider['host'],
                'port' => $provider['port'],
                'encryption' => $provider['encryption'],
                'username' => env($provider['username_env']),
                'password' => env($provider['password_env']) ? '***' : null,
                'daily_limit' => $provider['daily_limit'],
                'configured' => $this->isMailerConfigured($mailer),
            ];
        }

        // Return default mailer config
        return [
            'host' => config("mail.mailers.{$mailer}.host"),
            'port' => config("mail.mailers.{$mailer}.port"),
            'encryption' => config("mail.mailers.{$mailer}.encryption"),
            'username' => config("mail.mailers.{$mailer}.username") ? '***' : null,
            'configured' => $this->isMailerConfigured($mailer),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isMailerConfigured(string $mailer): bool
    {
        // Array and log mailers are always available (for testing)
        if (in_array($mailer, ['array', 'log'])) {
            return true;
        }

        // Check provider-specific configuration
        if (isset(self::PROVIDERS[$mailer])) {
            $provider = self::PROVIDERS[$mailer];
            return !empty(env($provider['username_env'])) && !empty(env($provider['password_env']));
        }

        // Check default mailer configuration
        $host = config("mail.mailers.{$mailer}.host");
        $username = config("mail.mailers.{$mailer}.username");
        $password = config("mail.mailers.{$mailer}.password");

        return !empty($host) && !empty($username) && !empty($password);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableMailers(): array
    {
        $available = [];

        foreach (array_keys(self::PROVIDERS) as $provider) {
            if ($this->isMailerConfigured($provider)) {
                $available[] = $provider;
            }
        }

        // Check default SMTP
        if ($this->isMailerConfigured('smtp')) {
            $available[] = 'smtp';
        }

        // Always available (for testing/logging)
        if (config('mail.default') === 'log') {
            $available[] = 'log';
        }

        return array_unique($available);
    }

    /**
     * Validate email recipients.
     *
     * @param array $recipients
     * @throws EmailSendException
     */
    protected function validateRecipients(array $recipients): void
    {
        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw EmailSendException::invalidRecipient($email);
            }
        }
    }

    /**
     * Prepare attachments for sending.
     *
     * @param array $attachments
     * @return array Prepared attachments
     * @throws EmailSendException
     */
    protected function prepareAttachments(array $attachments): array
    {
        $prepared = [];

        foreach ($attachments as $attachment) {
            // Handle simple path string
            if (is_string($attachment)) {
                $prepared[] = $this->prepareAttachmentPath($attachment);
                continue;
            }

            // Handle array format
            if (is_array($attachment)) {
                $path = $attachment['path'] ?? null;
                if (!$path) {
                    throw EmailSendException::attachmentError('unknown', 'Missing path');
                }
                $prepared[] = [
                    'path' => $path,
                    'name' => $attachment['name'] ?? basename($path),
                    'mime' => $attachment['mime'] ?? null,
                ];
                continue;
            }

            throw EmailSendException::attachmentError('unknown', 'Invalid attachment format');
        }

        return $prepared;
    }

    /**
     * Prepare a single attachment from path.
     *
     * @param string $path
     * @return array
     * @throws EmailSendException
     */
    protected function prepareAttachmentPath(string $path): array
    {
        if (!file_exists($path)) {
            throw EmailSendException::attachmentError($path, 'File not found');
        }

        if (!is_readable($path)) {
            throw EmailSendException::attachmentError($path, 'File not readable');
        }

        $size = filesize($path);
        if ($size > self::MAX_ATTACHMENT_SIZE) {
            throw EmailSendException::attachmentError(
                $path,
                "File too large ({$size} bytes, max " . self::MAX_ATTACHMENT_SIZE . ")"
            );
        }

        return [
            'path' => $path,
            'name' => basename($path),
            'mime' => $this->detectMimeType($path),
        ];
    }

    /**
     * Detect MIME type from file extension.
     *
     * @param string $path
     * @return string|null
     */
    protected function detectMimeType(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'json' => 'application/json',
            'zip' => 'application/zip',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
        ];

        return $mimeTypes[$extension] ?? null;
    }

    /**
     * Configure the mailer for a specific provider.
     *
     * @param string $mailer
     */
    protected function configureProviderMailer(string $mailer): void
    {
        if (!isset(self::PROVIDERS[$mailer])) {
            return;
        }

        $provider = self::PROVIDERS[$mailer];
        $username = env($provider['username_env']);
        $password = env($provider['password_env']);

        if ($username && $password) {
            Config::set("mail.mailers.{$mailer}", [
                'transport' => 'smtp',
                'host' => $provider['host'],
                'port' => $provider['port'],
                'encryption' => $provider['encryption'],
                'username' => $username,
                'password' => $password,
                'timeout' => null,
            ]);
        }
    }

    /**
     * Send email with retry logic.
     *
     * @param array $recipients
     * @param string $subject
     * @param string $html
     * @param array $attachments
     * @param string $mailer
     * @return bool
     * @throws EmailSendException
     */
    protected function sendWithRetry(
        array $recipients,
        string $subject,
        string $html,
        array $attachments,
        string $mailer
    ): bool {
        $lastError = null;
        $attempts = 0;

        while ($attempts < self::MAX_RETRIES) {
            $attempts++;

            try {
                $this->logAttempt($attempts, $recipients, $subject, $mailer);

                $mailable = $this->buildMailable($recipients, $subject, $html, $attachments);

                Mail::mailer($mailer)->send($mailable);

                $this->logSuccess($recipients, $subject, $mailer);

                return true;

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                $this->logFailure($attempts, $lastError, $recipients, $mailer);

                if ($attempts < self::MAX_RETRIES) {
                    sleep(self::RETRY_DELAY);
                }
            }
        }

        throw EmailSendException::retryExhausted($attempts, $lastError);
    }

    /**
     * Build a mailable instance.
     *
     * @param array $recipients
     * @param string $subject
     * @param string $html
     * @param array $attachments
     * @return Mailable
     */
    protected function buildMailable(
        array $recipients,
        string $subject,
        string $html,
        array $attachments
    ): Mailable {
        $mailable = new class($recipients, $subject, $html, $attachments) extends Mailable {
            protected array $recipients;
            protected string $subjectText;
            protected string $htmlContent;
            protected array $attachmentFiles;

            public function __construct(
                array $recipients,
                string $subject,
                string $html,
                array $attachments
            ) {
                $this->recipients = $recipients;
                $this->subjectText = $subject;
                $this->htmlContent = $html;
                $this->attachmentFiles = $attachments;
            }

            public function build(): self
            {
                $mail = $this
                    ->to($this->recipients)
                    ->subject($this->subjectText)
                    ->html($this->htmlContent);

                foreach ($this->attachmentFiles as $attachment) {
                    $mail->attach($attachment['path'], [
                        'as' => $attachment['name'],
                        'mime' => $attachment['mime'],
                    ]);
                }

                return $mail;
            }
        };

        return $mailable;
    }

    /**
     * Log send attempt.
     */
    protected function logAttempt(int $attempt, array $recipients, string $subject, string $mailer): void
    {
        $this->logger->info('Email send attempt', [
            'attempt' => $attempt,
            'recipients' => $recipients,
            'subject' => $subject,
            'mailer' => $mailer,
        ]);
    }

    /**
     * Log successful send.
     */
    protected function logSuccess(array $recipients, string $subject, string $mailer): void
    {
        $this->logger->info('Email sent successfully', [
            'recipients' => $recipients,
            'subject' => $subject,
            'mailer' => $mailer,
        ]);
    }

    /**
     * Log failed send.
     */
    protected function logFailure(int $attempt, string $error, array $recipients, string $mailer): void
    {
        $this->logger->error('Email send failed', [
            'attempt' => $attempt,
            'error' => $error,
            'recipients' => $recipients,
            'mailer' => $mailer,
        ]);
    }
}
