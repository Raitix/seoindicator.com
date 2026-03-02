# ProjectManager.md — Project Lessons Learned

Knowledge base for ProjectManager agent working on SEOIndicator project.

---

## Priority Patterns

### SPEC-021 AI Suggestions (2026-03-01)
- 12 tasks across 3 phases (Core Infrastructure, API & Integration, Vercel Integration)
- Phase 1 tasks (21-1 to 21-4) can run in parallel after foundation
- TASK-21-1 (AI Service Structure) is foundation - blocks all others
- TASK-21-2 (OpenRouter), 21-3 (Static Fallback), 21-4 (Prompt Templates) can parallelize
- Priority distribution: 7 High, 5 Medium

### SPEC-026 Billing, Credits & Model Selection (2026-03-02)
- 27 tasks across 7 phases (Database, Credit System, Stripe, PayPal, Model Selection, Frontend, Testing)
- High Risk spec - payment processing, external APIs, financial data
- Phase 1 foundation tasks (TASK-26-1 to 26-4) all depend on migrations
- TDD approach: PreTester can design tests before dependency completes using interface-based mocking
- Model/Repository tasks can be prioritized alongside migration development

---

## Blockers Log

_(Track common blockers and resolutions)_

### SPEC-026 Billing, Credits & Model Selection (2026-03-02)
- 27 tasks across 7 phases (Database, Credit System, Stripe, PayPal, Model Selection, Frontend, Testing)
- High Risk spec - payment processing, external APIs, financial data
- Phase 1 foundation tasks (TASK-26-1 to 26-4) all depend on migrations
- TDD approach: PreTester can design tests before dependency completes using interface-based mocking
- Model/Repository tasks can be prioritized alongside migration development
- CreditService (TASK-26-5) is CRITICAL - all payment services depend on it for addCredits/deductCredits
- High risk: Race condition handling, transaction safety, idempotency for webhooks

---

Last updated: 2026-03-02
