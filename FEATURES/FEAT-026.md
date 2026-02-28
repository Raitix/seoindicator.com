# FEAT-026: Billing, Credits & Model Selection

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Description
Add paid credits, Stripe/PayPal top‑ups, pricing display, and ability to select AI model with dynamic cost shown (including OpenRouter free‑tier limits).

## User Story
As a user, I want to buy credits and choose an AI model with clear pricing before running analysis.

## Acceptance Criteria
- [ ] Stripe + PayPal credit purchase (min $5).
- [ ] Balance displayed in USD.
- [ ] Model picker with cost per analysis.
- [ ] Pricing page with OpenRouter model pricing and fees.

## Notes
- Source: user ToDo (billing + pricing + model choice).

## Comments

### 2026-02-28 - Architect
- Created SPEC-026.md with full technical specification
- Risk: HIGH (payment processing, external APIs)
- Architecture: Credit-based system with Stripe (primary) and PayPal (secondary)
- 27 tasks across 7 phases defined
- Key components: CreditService, StripeGatewayService, PayPalGatewayService, ModelPricingService
- Database tables: credits, credit_transactions, payment_methods, credit_packages, ai_models, user_model_preferences
- Minimum purchase: $5 USD

---
*Created by Lead from individual user request.*