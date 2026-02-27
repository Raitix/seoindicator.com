# SPEC-004-17: Email Service (Free SMTP)

## STATUS
`To Develop`

## PRIORITY
`Low`

## RISK
`Medium`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-17.md

## Description
Implement email sending service using free SMTP providers. Supports Gmail SMTP or Brevo (300/day free) for report delivery.

## Acceptance Criteria
- [ ] EmailSender service class
- [ ] send(to, subject, html, attachments) method
- [ ] Configuration for multiple SMTP providers
- [ ] Support for Gmail SMTP (500/day limit)
- [ ] Support for Brevo SMTP (300/day free tier)
- [ ] Attachment handling for PDF/CSV files
- [ ] Error handling and retry logic
- [ ] Logging for delivery status
- [ ] Unit tests with mock mailer

## Dependencies
- None (infrastructure task)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Use Laravel Mail (SwiftMailer)
- Configure in config/mail.php
- Environment variables: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
- Consider rate limiting for bulk sends

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 6 task. Post-MVP feature for email delivery. Medium risk due to deliverability concerns.
