const nodemailer = require('nodemailer');
const fs = require('fs');
const path = require('path');

/**
 * EmailSender Service
 * 
 * Supports free SMTP providers:
 * - Gmail SMTP (500 emails/day)
 * - Brevo SMTP (300 emails/day free tier)
 * 
 * Features:
 * - Attachment support (PDF, CSV)
 * - Retry logic with exponential backoff
 * - Delivery status logging
 * - Multiple recipients
 */
class EmailSender {
  constructor(config = {}) {
    this.config = {
      // Default to Gmail SMTP configuration
      host: config.host || process.env.MAIL_HOST || 'smtp.gmail.com',
      port: config.port || parseInt(process.env.MAIL_PORT) || 587,
      secure: config.secure ?? (process.env.MAIL_SECURE === 'true') ?? false,
      auth: {
        user: config.user || process.env.MAIL_USERNAME,
        pass: config.pass || process.env.MAIL_PASSWORD
      },
      // Retry configuration
      maxRetries: config.maxRetries || 3,
      retryDelayMs: config.retryDelayMs || 1000,
      // Sender info
      fromAddress: config.fromAddress || process.env.MAIL_FROM_ADDRESS || 'noreply@seoindicator.com',
      fromName: config.fromName || process.env.MAIL_FROM_NAME || 'SEOIndicator'
    };

    this.transporter = null;
    this.lastError = null;
    this.deliveryLog = [];
  }

  /**
   * Get pre-configured SMTP settings for supported providers
   */
  static get PROVIDERS() {
    return {
      gmail: {
        host: 'smtp.gmail.com',
        port: 587,
        secure: false,
        dailyLimit: 500,
        notes: 'Requires App Password. Enable 2FA and generate app password.'
      },
      brevo: {
        host: 'smtp-relay.brevo.com',
        port: 587,
        secure: false,
        dailyLimit: 300,
        notes: 'Free tier: 300 emails/day. SMTP key from Brevo dashboard.'
      },
      mailtrap: {
        host: 'sandbox.smtp.mailtrap.io',
        port: 2525,
        secure: false,
        dailyLimit: null,
        notes: 'Development only. Not for production.'
      }
    };
  }

  /**
   * Create a transporter for the specified provider
   * @param {string} provider - 'gmail', 'brevo', or 'mailtrap'
   * @param {object} credentials - { user, pass }
   */
  static createForProvider(provider, credentials) {
    const providerConfig = EmailSender.PROVIDERS[provider];
    if (!providerConfig) {
      throw new Error(`Unknown provider: ${provider}. Supported: gmail, brevo, mailtrap`);
    }

    return new EmailSender({
      host: providerConfig.host,
      port: providerConfig.port,
      secure: providerConfig.secure,
      user: credentials.user,
      pass: credentials.pass
    });
  }

  /**
   * Initialize the nodemailer transporter
   */
  async init() {
    if (this.transporter) {
      return this.transporter;
    }

    if (!this.config.auth.user || !this.config.auth.pass) {
      throw new Error('SMTP credentials not configured. Set MAIL_USERNAME and MAIL_PASSWORD environment variables.');
    }

    this.transporter = nodemailer.createTransport({
      host: this.config.host,
      port: this.config.port,
      secure: this.config.secure,
      auth: {
        user: this.config.auth.user,
        pass: this.config.auth.pass
      },
      // Connection timeout settings
      connectionTimeout: 10000,
      socketTimeout: 10000
    });

    // Verify connection
    try {
      await this.transporter.verify();
      this._log('info', 'SMTP connection verified', { host: this.config.host });
    } catch (error) {
      this._log('error', 'SMTP connection failed', { error: error.message });
      throw new Error(`Failed to connect to SMTP server: ${error.message}`);
    }

    return this.transporter;
  }

  /**
   * Send an email
   * @param {object} options - Email options
   * @param {string|string[]} options.to - Recipient(s)
   * @param {string} options.subject - Email subject
   * @param {string} options.html - HTML body
   * @param {string} [options.text] - Plain text body (fallback)
   * @param {object[]} [options.attachments] - Array of attachment objects
   * @param {string} [options.replyTo] - Reply-to address
   * @param {string} [options.cc] - CC recipients
   * @param {string} [options.bcc] - BCC recipients
   */
  async send(options) {
    const { to, subject, html, text, attachments, replyTo, cc, bcc } = options;

    // Validate required fields
    if (!to || !subject || (!html && !text)) {
      throw new Error('Missing required fields: to, subject, and html/text are required');
    }

    // Initialize transporter if needed
    if (!this.transporter) {
      await this.init();
    }

    // Normalize recipients to array
    const recipients = Array.isArray(to) ? to : [to];

    // Build mail options
    const mailOptions = {
      from: `"${this.config.fromName}" <${this.config.fromAddress}>`,
      to: recipients.join(', '),
      subject,
      html,
      text: text || this._stripHtml(html),
      attachments: this._prepareAttachments(attachments)
    };

    if (replyTo) mailOptions.replyTo = replyTo;
    if (cc) mailOptions.cc = cc;
    if (bcc) mailOptions.bcc = bcc;

    // Send with retry logic
    return await this._sendWithRetry(mailOptions);
  }

  /**
   * Send email with retry logic
   */
  async _sendWithRetry(mailOptions, attempt = 1) {
    try {
      const info = await this.transporter.sendMail(mailOptions);

      const logEntry = {
        timestamp: new Date().toISOString(),
        to: mailOptions.to,
        subject: mailOptions.subject,
        messageId: info.messageId,
        response: info.response,
        status: 'sent',
        attempt
      };

      this.deliveryLog.push(logEntry);
      this._log('info', 'Email sent successfully', logEntry);

      return {
        success: true,
        messageId: info.messageId,
        response: info.response,
        recipients: mailOptions.to.split(', ')
      };

    } catch (error) {
      this.lastError = error;
      this._log('error', `Email send failed (attempt ${attempt}/${this.config.maxRetries})`, {
        error: error.message,
        code: error.code
      });

      // Check if we should retry
      if (this._shouldRetry(error) && attempt < this.config.maxRetries) {
        const delay = this._getRetryDelay(attempt);
        this._log('info', `Retrying in ${delay}ms...`);
        
        await this._sleep(delay);
        return this._sendWithRetry(mailOptions, attempt + 1);
      }

      // Log final failure
      const logEntry = {
        timestamp: new Date().toISOString(),
        to: mailOptions.to,
        subject: mailOptions.subject,
        status: 'failed',
        error: error.message,
        code: error.code,
        attempts: attempt
      };

      this.deliveryLog.push(logEntry);

      return {
        success: false,
        error: error.message,
        code: error.code,
        attempts: attempt
      };
    }
  }

  /**
   * Determine if error is retryable
   */
  _shouldRetry(error) {
    // Retry on network/temporary errors
    const retryableCodes = [
      'ECONNRESET',
      'ECONNREFUSED',
      'ETIMEDOUT',
      'ENOTFOUND',
      'EAI_AGAIN'
    ];

    const retryableResponses = [
      '421',  // Service not available
      '450',  // Mailbox busy
      '451',  // Local error
      '452'   // Insufficient storage
    ];

    if (retryableCodes.includes(error.code)) {
      return true;
    }

    if (error.response && retryableResponses.some(code => error.response.includes(code))) {
      return true;
    }

    return false;
  }

  /**
   * Calculate exponential backoff delay
   */
  _getRetryDelay(attempt) {
    const baseDelay = this.config.retryDelayMs;
    const maxDelay = 30000; // Max 30 seconds
    const delay = Math.min(baseDelay * Math.pow(2, attempt - 1), maxDelay);
    
    // Add jitter (±20%)
    const jitter = delay * 0.2 * (Math.random() * 2 - 1);
    return Math.round(delay + jitter);
  }

  /**
   * Prepare attachments for nodemailer
   */
  _prepareAttachments(attachments) {
    if (!attachments || !Array.isArray(attachments)) {
      return [];
    }

    return attachments.map(att => {
      // If already in nodemailer format
      if (att.path || att.content) {
        return att;
      }

      // Handle simple { filename, path } format
      if (att.filename && att.path) {
        const filePath = att.path;
        const ext = path.extname(filePath).toLowerCase();
        
        // Determine content type
        const contentTypes = {
          '.pdf': 'application/pdf',
          '.csv': 'text/csv',
          '.txt': 'text/plain',
          '.html': 'text/html',
          '.json': 'application/json',
          '.png': 'image/png',
          '.jpg': 'image/jpeg',
          '.jpeg': 'image/jpeg',
          '.gif': 'image/gif'
        };

        return {
          filename: att.filename,
          path: filePath,
          contentType: contentTypes[ext] || 'application/octet-stream'
        };
      }

      // Handle content as string/buffer
      if (att.filename && att.content) {
        return att;
      }

      this._log('warn', 'Invalid attachment format', { att });
      return null;
    }).filter(Boolean);
  }

  /**
   * Strip HTML tags for plain text fallback
   */
  _stripHtml(html) {
    return html
      .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
      .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
      .replace(/<[^>]+>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  /**
   * Sleep utility
   */
  _sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * Log delivery status
   */
  _log(level, message, data = {}) {
    const entry = {
      timestamp: new Date().toISOString(),
      level,
      message,
      ...data
    };

    // In production, this would go to a proper logger
    if (process.env.NODE_ENV !== 'test') {
      console.log(`[EmailSender][${level.toUpperCase()}] ${message}`, data);
    }

    return entry;
  }

  /**
   * Get delivery log
   */
  getDeliveryLog() {
    return [...this.deliveryLog];
  }

  /**
   * Get last error
   */
  getLastError() {
    return this.lastError;
  }

  /**
   * Clear delivery log
   */
  clearLog() {
    this.deliveryLog = [];
    this.lastError = null;
  }

  /**
   * Close transporter connection
   */
  async close() {
    if (this.transporter) {
      this.transporter.close();
      this.transporter = null;
      this._log('info', 'SMTP connection closed');
    }
  }

  /**
   * Verify SMTP credentials without sending email
   */
  async verify() {
    try {
      if (!this.transporter) {
        await this.init();
      }
      await this.transporter.verify();
      return { success: true, message: 'SMTP connection verified' };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }
}

module.exports = EmailSender;
