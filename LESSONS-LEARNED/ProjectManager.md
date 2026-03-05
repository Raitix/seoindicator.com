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

Last updated: 2026-03-05

---

## Documentation Task Patterns

### TASK-26-27 Documentation for Billing (2026-03-05)
- **Pattern:** Documentation tasks for large features should be assessed early for blocker status
- **Key Insight:** Check file sizes of dependency tasks - empty files (0 bytes) indicate incomplete pipeline
- **Discovery:** 12 of 27 FEAT-026 tasks (44.4%) have empty files - they were never populated by SpecSystemAnalyst
- **Impact:** Documentation task correctly remains blocked until:
  1. Empty task files are populated with proper specifications
  2. ≥80% of tasks reach `Done` status
  3. All frontend and testing phases complete
- **Lesson:** Always verify dependency claims by checking actual file sizes, not just task file status. Empty files (0 bytes) indicate workflow integrity issues requiring SpecSystemAnalyst attention.
