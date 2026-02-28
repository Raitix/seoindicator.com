# TASK-002-11: Configure Gmail SMTP Mailer for Alerts

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Related
- Specification: [SPEC-002](../SPECIFICATIONS/SPEC-002.md)
- Feature: [FEAT-002](../FEATURES/FEAT-002.md)

---

## Description
Configure Laravel Mail to use Gmail SMTP for sending alert emails. Document setup process for users.

## Acceptance Criteria
- [ ] Add Gmail SMTP configuration to `config/mail.php`
- [ ] Use environment variables for credentials (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD)
- [ ] Set MAIL_ENCRYPTION=tls, MAIL_PORT=587
- [ ] Create documentation file `docs/email-setup.md` explaining:
  - How to enable 2FA on Google account
  - How to generate App Password
  - Where to configure environment variables
  - Daily sending limits (~500 emails)
- [ ] Add fallback to log driver in local environment
- [ ] Test email sends successfully via `php artisan tinker`
- [ ] Feature test: email queued and sent in test environment
- [ ] Document alternative: Brevo (Sendinblue) free tier (300/day)

## Dependencies
- None (standalone configuration task)

## Effort Estimate
`Small` (1 hour)

---

## Comments
_Agent communication log_

### [2026-02-22] Agent: SpecSystemAnalyst
Free email delivery for MVP. Low risk. Documentation critical for user self-service.
