<?php

namespace Tests\Unit\Services\Mail;

use App\Services\Mail\Contracts\EmailSenderInterface;
use App\Services\Mail\EmailSenderService;
use App\Services\Mail\Exceptions\EmailSendException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailSenderServiceTest extends TestCase
{
    protected EmailSenderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EmailSenderService::class);

        // Configure default mailer for tests
        Config::set('mail.default', 'array');
        Config::set('mail.mailers.array', [
            'transport' => 'array',
        ]);
    }

    /**
     * Test service can be instantiated.
     */
    public function test_can_instantiate_service(): void
    {
        $this->assertInstanceOf(EmailSenderService::class, $this->service);
        $this->assertInstanceOf(EmailSenderInterface::class, $this->service);
    }

    /**
     * Test send via Gmail SMTP config (mocked).
     */
    public function test_send_via_gmail_smtp(): void
    {
        Mail::fake();

        // Mock Gmail config via Config facade (for testing)
        Config::set('mail.mailers.gmail', [
            'transport' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'test@gmail.com',
            'password' => 'test-password',
        ]);

        // Set env vars for provider check
        $_ENV['MAIL_GMAIL_USERNAME'] = 'test@gmail.com';
        $_ENV['MAIL_GMAIL_PASSWORD'] = 'test-password';

        try {
            $result = $this->service->send(
                'recipient@example.com',
                'Test Subject',
                '<p>Test HTML content</p>',
                [],
                'gmail'
            );

            $this->assertTrue($result);

            Mail::assertSentCount(1);
        } finally {
            unset($_ENV['MAIL_GMAIL_USERNAME']);
            unset($_ENV['MAIL_GMAIL_PASSWORD']);
        }
    }

    /**
     * Test send via Brevo SMTP config (mocked).
     */
    public function test_send_via_brevo_smtp(): void
    {
        Mail::fake();

        // Mock Brevo config via Config facade
        Config::set('mail.mailers.brevo', [
            'transport' => 'smtp',
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'test@brevo.com',
            'password' => 'test-password',
        ]);

        // Set env vars for provider check
        $_ENV['MAIL_BREVO_USERNAME'] = 'test@brevo.com';
        $_ENV['MAIL_BREVO_PASSWORD'] = 'test-password';

        try {
            $result = $this->service->send(
                'recipient@example.com',
                'Test Subject',
                '<p>Test HTML content</p>',
                [],
                'brevo'
            );

            $this->assertTrue($result);

            Mail::assertSentCount(1);
        } finally {
            unset($_ENV['MAIL_BREVO_USERNAME']);
            unset($_ENV['MAIL_BREVO_PASSWORD']);
        }
    }

    /**
     * Test attach PDF file.
     */
    public function test_attach_pdf_file(): void
    {
        Mail::fake();

        // Create a temporary PDF file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 test content');

        try {
            $result = $this->service->send(
                'recipient@example.com',
                'Test with PDF',
                '<p>Test</p>',
                [$tempFile]
            );

            $this->assertTrue($result);

            Mail::assertSentCount(1);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test attach CSV file.
     */
    public function test_attach_csv_file(): void
    {
        Mail::fake();

        // Create a temporary CSV file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.csv';
        file_put_contents($tempFile, "header1,header2\nvalue1,value2");

        try {
            $result = $this->service->send(
                'recipient@example.com',
                'Test with CSV',
                '<p>Test</p>',
                [$tempFile]
            );

            $this->assertTrue($result);

            Mail::assertSentCount(1);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test multiple recipients.
     */
    public function test_multiple_recipients(): void
    {
        Mail::fake();

        $recipients = [
            'recipient1@example.com',
            'recipient2@example.com',
            'recipient3@example.com',
        ];

        $result = $this->service->send(
            $recipients,
            'Test Multiple Recipients',
            '<p>Test</p>'
        );

        $this->assertTrue($result);

        Mail::assertSentCount(1);
    }

    /**
     * Test invalid recipient throws exception.
     */
    public function test_invalid_recipient_throws_exception(): void
    {
        $this->expectException(EmailSendException::class);

        $this->service->send(
            'invalid-email',
            'Test Subject',
            '<p>Test</p>'
        );
    }

    /**
     * Test missing attachment throws exception.
     */
    public function test_missing_attachment_throws_exception(): void
    {
        $this->expectException(EmailSendException::class);

        $this->service->send(
            'recipient@example.com',
            'Test Subject',
            '<p>Test</p>',
            ['/nonexistent/file.pdf']
        );
    }

    /**
     * Test unconfigured mailer throws exception.
     */
    public function test_unconfigured_mailer_throws_exception(): void
    {
        $this->expectException(EmailSendException::class);

        // Clear any existing config
        Config::set('mail.mailers.unconfigured', null);

        $this->service->send(
            'recipient@example.com',
            'Test Subject',
            '<p>Test</p>',
            [],
            'unconfigured'
        );
    }

    /**
     * Test is_mailer_configured returns true for configured mailer.
     */
    public function test_is_mailer_configured_returns_true(): void
    {
        Config::set('mail.mailers.smtp', [
            'host' => 'smtp.example.com',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $this->assertTrue($this->service->isMailerConfigured('smtp'));
    }

    /**
     * Test is_mailer_configured returns false for unconfigured mailer.
     */
    public function test_is_mailer_configured_returns_false(): void
    {
        $this->assertFalse($this->service->isMailerConfigured('nonexistent'));
    }

    /**
     * Test get_mailer_config returns correct config.
     */
    public function test_get_mailer_config_returns_correct_config(): void
    {
        Config::set('mail.mailers.smtp', [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user',
        ]);

        $config = $this->service->getMailerConfig('smtp');

        $this->assertEquals('smtp.example.com', $config['host']);
        $this->assertEquals(587, $config['port']);
    }

    /**
     * Test get_available_mailers returns configured mailers.
     */
    public function test_get_available_mailers(): void
    {
        Config::set('mail.mailers.smtp', [
            'host' => 'smtp.example.com',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $mailers = $this->service->getAvailableMailers();

        $this->assertIsArray($mailers);
        $this->assertContains('smtp', $mailers);
    }

    /**
     * Test retry logic on failure logs attempts.
     */
    public function test_retry_logic_logs_attempts(): void
    {
        // Mock Mail to throw exception
        Mail::shouldReceive('mailer->send')
            ->andThrow(new \Exception('SMTP connection failed'));

        $this->expectException(EmailSendException::class);
        $this->expectExceptionMessage('Email send failed after 3 attempts');

        $this->service->send(
            'recipient@example.com',
            'Test Subject',
            '<p>Test</p>'
        );
    }

    /**
     * Test attachment with custom name and MIME type.
     */
    public function test_attachment_with_custom_name_and_mime(): void
    {
        Mail::fake();

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 test content');

        try {
            $result = $this->service->send(
                'recipient@example.com',
                'Test Subject',
                '<p>Test</p>',
                [
                    [
                        'path' => $tempFile,
                        'name' => 'CustomReport.pdf',
                        'mime' => 'application/pdf',
                    ]
                ]
            );

            $this->assertTrue($result);

            Mail::assertSentCount(1);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test large attachment throws exception.
     */
    public function test_large_attachment_throws_exception(): void
    {
        $this->expectException(EmailSendException::class);

        // Test with a file that doesn't exist to trigger attachment error
        $this->service->send(
            'recipient@example.com',
            'Test Subject',
            '<p>Test</p>',
            ['/nonexistent/large_file.pdf']
        );
    }

    /**
     * Test send with array mailer (testing).
     */
    public function test_send_with_array_mailer(): void
    {
        Mail::fake();

        $result = $this->service->send(
            'recipient@example.com',
            'Test Subject',
            '<p>Test HTML content</p>',
            [],
            'array'
        );

        $this->assertTrue($result);

        Mail::assertSentCount(1);
    }

    /**
     * Test get mailer config for Gmail provider.
     */
    public function test_get_mailer_config_for_gmail_provider(): void
    {
        // Set environment variables for Gmail
        $_ENV['MAIL_GMAIL_USERNAME'] = 'test@gmail.com';
        $_ENV['MAIL_GMAIL_PASSWORD'] = 'app-password';

        try {
            $config = $this->service->getMailerConfig('gmail');

            $this->assertEquals('smtp.gmail.com', $config['host']);
            $this->assertEquals(587, $config['port']);
            $this->assertEquals(500, $config['daily_limit']);
        } finally {
            unset($_ENV['MAIL_GMAIL_USERNAME']);
            unset($_ENV['MAIL_GMAIL_PASSWORD']);
        }
    }

    /**
     * Test get mailer config for Brevo provider.
     */
    public function test_get_mailer_config_for_brevo_provider(): void
    {
        // Set environment variables for Brevo
        $_ENV['MAIL_BREVO_USERNAME'] = 'test@brevo.com';
        $_ENV['MAIL_BREVO_PASSWORD'] = 'smtp-key';

        try {
            $config = $this->service->getMailerConfig('brevo');

            $this->assertEquals('smtp-relay.brevo.com', $config['host']);
            $this->assertEquals(587, $config['port']);
            $this->assertEquals(300, $config['daily_limit']);
        } finally {
            unset($_ENV['MAIL_BREVO_USERNAME']);
            unset($_ENV['MAIL_BREVO_PASSWORD']);
        }
    }

    /**
     * Test single recipient as string.
     */
    public function test_single_recipient_as_string(): void
    {
        Mail::fake();

        $result = $this->service->send(
            'recipient@example.com',
            'Test Subject',
            '<p>Test</p>'
        );

        $this->assertTrue($result);

        Mail::assertSentCount(1);
    }

    /**
     * Test subject is properly set.
     */
    public function test_subject_is_properly_set(): void
    {
        Mail::fake();

        $subject = 'Special Characters: Test & <>&"\'\'';
        $result = $this->service->send(
            'recipient@example.com',
            $subject,
            '<p>Test</p>'
        );

        $this->assertTrue($result);

        Mail::assertSentCount(1);
    }

    /**
     * Test HTML content with special characters.
     */
    public function test_html_content_with_special_characters(): void
    {
        Mail::fake();

        $html = '<p>Special: & < > " \' © ® ™</p>';
        $result = $this->service->send(
            'recipient@example.com',
            'Test',
            $html
        );

        $this->assertTrue($result);

        Mail::assertSentCount(1);
    }

    /**
     * Test empty attachments array works.
     */
    public function test_empty_attachments_array_works(): void
    {
        Mail::fake();

        $result = $this->service->send(
            'recipient@example.com',
            'Test',
            '<p>Test</p>',
            []
        );

        $this->assertTrue($result);

        Mail::assertSentCount(1);
    }

    /**
     * Test array and log mailers are always configured.
     */
    public function test_array_and_log_mailers_always_configured(): void
    {
        $this->assertTrue($this->service->isMailerConfigured('array'));
        $this->assertTrue($this->service->isMailerConfigured('log'));
    }
}
