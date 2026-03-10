# ProjectManager.md — Project Lessons Learned

Knowledge base for ProjectManager agent working on SEOIndicator project.

---

## Redundant Testing Tasks Pattern (CRITICAL)

### Pattern Identification
Dedicated "tests for X" tasks become **REDUNDANT** when TDD workflow is adopted. Tests are created during each implementation task's PreTester phase.

### Affected Tasks (2026-03-02 to 2026-03-08)
| Task | Description | Resolution |
|------|-------------|------------|
| TASK-14-19 | Backend Unit Tests for Audit | Done (redundant) |
| TASK-14-20 | Backend Feature Tests for Audit | Done (redundant) |
| TASK-14-21 | Frontend Component Tests for Audit | Done (redundant) |
| TASK-30-6 | Unit Tests - Pages & Cookie Consent | Done (redundant - 439+ tests existed) |
| TASK-34-11 | Unit Tests for Services | Done (redundant - tests existed from TDD) |
| TASK-34-12 | Feature Tests for API | Done (redundant - covered by PreTester phases) |
| TASK-34-23 | Tests for Alerts | Done (redundant - tests in TASK-34-19/20/21 PreTester phases) |
| TASK-35-24 | Backend Tests for Local SEO | Cancelled (redundant - 8th redundant testing task) |
| TASK-36-15 | Unit Tests for Content Decay Services | Cancelled (redundant - 9th redundant testing task) |
| TASK-36-17 | Frontend Tests for Content Decay Components | Cancelled (redundant - 10th redundant testing task) |

### Key Lesson for SpecSystemAnalyst
When breaking down specifications into tasks:
1. **Check TDD adoption** - If TDD is used, tests are created during PreTester phases
2. **Avoid separate testing tasks** for normal feature coverage
3. **Only create separate testing tasks** for:
   - Integration testing across multiple components
   - E2E testing scenarios
   - Performance/stress testing
   - Security testing requiring specialized expertise
4. **Verify with file checks** - Check if TEST files exist before creating redundant tasks

### Coverage Mapping Example (TASK-34-12)
| Test File | Created By | When |
|-----------|------------|------|
| AIVisibilityProjectTest.php | TASK-34-6 PreTester | During Project CRUD API development |
| AIVisibilityAnalyticsTest.php | TASK-34-14 PreTester | During Analytics API development |
| AIVisibilityAlertTest.php | TASK-34-20 PreTester | During Alert API development |

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
