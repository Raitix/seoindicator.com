# Email Setup Guide for SEOIndicator

This guide explains how to configure Gmail SMTP for sending alert emails from SEOIndicator.

## Gmail SMTP Configuration

SEOIndicator uses Gmail's free SMTP service for sending ranking change alerts and notifications. Gmail allows approximately **500 emails per day** on free accounts.

### Prerequisites

- A Google account (Gmail or Google Workspace)
- 2-Factor Authentication (2FA) enabled on your Google account
- An App Password generated for SEOIndicator

---

## Step 1: Enable 2-Factor Authentication

1. Go to your [Google Account settings](https://myaccount.google.com/)
2. Navigate to **Security** in the left sidebar
3. Under "How you sign in to Google", click **2-Step Verification**
4. If not already enabled, click **Get Started** and follow the prompts
5. Complete the setup using your preferred method (SMS, authenticator app, etc.)

**Note:** 2FA is required to generate App Passwords, which are needed for SMTP authentication.

---

## Step 2: Generate an App Password

1. Go to your [Google Account settings](https://myaccount.google.com/)
2. Navigate to **Security** in the left sidebar
3. Under "How you sign in to Google", click **2-Step Verification**
4. Scroll down to **App passwords** and click it (you may need to sign in again)
5. Click **Create app password**
6. Enter a name for the password, e.g., "SEOIndicator"
7. Click **Create**
8. Google will display a 16-character password (format: xxxx-xxxx-xxxx-xxxx)
9. **Copy this password immediately** - you won't be able to see it again

**Important:** Treat this App Password like a regular password. Store it securely.

---

## Step 3: Configure Environment Variables

Add the following variables to your `.env` file in the SEOIndicator backend:

```env
# Mail Configuration
MAIL_MAILER=gmail
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password-here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Variable Explanation

| Variable | Value | Description |
|----------|-------|-------------|
| `MAIL_MAILER` | `gmail` | The mailer name (custom mailer defined in config) |
| `MAIL_HOST` | `smtp.gmail.com` | Gmail's SMTP server |
| `MAIL_PORT` | `587` | TLS port for secure email |
| `MAIL_USERNAME` | Your Gmail address | Full email including @gmail.com |
| `MAIL_PASSWORD` | App Password | The 16-character App Password (remove spaces) |
| `MAIL_ENCRYPTION` | `tls` | Encryption protocol |
| `MAIL_FROM_ADDRESS` | Your Gmail address | Sender email address |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Sender display name |

---

## Step 4: Test Email Sending

After configuring the environment variables:

1. **Clear the config cache:**
   ```bash
   php artisan config:clear
   ```

2. **Test via Tinker:**
   ```bash
   php artisan tinker
   ```
   
   Then run:
   ```php
   Mail::raw('Test email from SEOIndicator', function($message) {
       $message->to('recipient@example.com')
               ->subject('SEOIndicator Email Test');
   });
   ```

3. Check the recipient inbox for the test email.

---

## Daily Sending Limits

| Provider | Daily Limit | Notes |
|----------|-------------|-------|
| Gmail (free) | ~500 emails | May vary by account age/usage |
| Google Workspace | ~2,000 emails | Depends on plan |

### Best Practices

- **Batch notifications:** For high-volume alerts, consider digest mode (daily/weekly summaries)
- **Monitor usage:** Check Gmail sent folder if emails stop sending
- **Stagger sends:** Avoid sending hundreds of emails within minutes

---

## Alternative: Brevo (Sendinblue)

If Gmail limits are insufficient, Brevo offers a generous free tier:

### Brevo Configuration

```env
MAIL_MAILER=brevo
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your-brevo-login@email.com
MAIL_PASSWORD=your-brevo-api-key
MAIL_ENCRYPTION=tls
```

### Brevo Free Tier

- **300 emails/day** (free forever)
- No credit card required
- Better deliverability for transactional emails

### Getting Started with Brevo

1. Sign up at [brevo.com](https://www.brevo.com/)
2. Verify your sender domain (optional but recommended)
3. Generate an SMTP key in Settings > SMTP & API
4. Configure SEOIndicator with the SMTP credentials

---

## Troubleshooting

### "Connection refused" or "Connection timed out"

- Verify `MAIL_HOST` and `MAIL_PORT` are correct
- Check if your server blocks outbound port 587
- Try port 465 with `MAIL_ENCRYPTION=ssl` instead

### "Authentication failed"

- Ensure 2FA is enabled on your Google account
- Regenerate the App Password and try again
- Check that the password doesn't contain spaces

### "Daily limit exceeded"

- Wait 24 hours for the limit to reset
- Consider switching to Brevo for higher limits
- Enable digest mode to reduce email volume

### Emails going to spam

- Add your sending address to contacts
- Set up SPF/DKIM records (Google Workspace)
- Use Brevo for better deliverability

---

## Security Notes

- **Never commit `.env` files** to version control
- App Passwords provide access to your Gmail account - store securely
- Rotate App Passwords periodically for security
- Consider using a dedicated email address for SEOIndicator notifications

---

## Support

For issues with email configuration:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Test with a simple email via Tinker
3. Verify firewall allows outbound SMTP connections
4. Contact support if problems persist
