# TEST-002: Ranking Change Alerts - Master Test Overview

## Feature Reference
- Feature: [FEAT-002](../FEATURES/FEAT-002.md)
- Specification: [SPEC-002](../SPECIFICATIONS/SPEC-002.md)

## Test Summary

| Task | Description | Priority | Test Doc | Tests | Status |
|------|-------------|----------|----------|-------|--------|
| TASK-002-1 | alert_settings migration & model | High | [TEST-002-1](TEST-002-1.md) | 32 | Tests Written |
| TASK-002-2 | keyword_alert_settings migration & model | High | [TEST-002-2](TEST-002-2.md) | 30 | Tests Written |
| TASK-002-3 | notifications migration & model | High | [TEST-002-3](TEST-002-3.md) | 35 | Tests Written |
| TASK-002-4 | AlertEvaluator service | High | [TEST-002-4](TEST-002-4.md) | 35 | Tests Written |
| TASK-002-5 | NotificationDispatcher service | High | [TEST-002-5](TEST-002-5.md) | 40 | Tests Written |
| TASK-002-6 | Ranking job integration | High | [TEST-002-6](TEST-002-6.md) | 25 | Tests Written |
| TASK-002-7 | AlertSettingsController API | High | [TEST-002-7](TEST-002-7.md) | 25 | Tests Written |
| TASK-002-8 | KeywordAlertSettingsController API | High | [TEST-002-8](TEST-002-8.md) | 30 | Tests Written |
| TASK-002-9 | NotificationController API | Medium | [TEST-002-9](TEST-002-9.md) | 25 | Tests Written |
| TASK-002-10 | RankingAlertEmail mailable | High | [TEST-002-10](TEST-002-10.md) | 30 | Tests Written |
| TASK-002-11 | Gmail SMTP configuration | High | [TEST-002-11](TEST-002-11.md) | 15 | Tests Written |
| TASK-002-12 | Alert Settings page (frontend) | Medium | [TEST-002-12](TEST-002-12.md) | 30 | Tests Written |
| TASK-002-13 | Notification Center (frontend) | Medium | [TEST-002-13](TEST-002-13.md) | 30 | Tests Written |
| TASK-002-14 | Notification bell in header | Medium | [TEST-002-14](TEST-002-14.md) | 30 | Tests Written |

## Total Test Coverage

- **Total Test Scenarios:** 400+
- **Unit Tests:** 200+ (migrations, models, services)
- **Feature Tests:** 100+ (API, mail)
- **Frontend Tests:** 90+ (React components)

## Test File Structure

```
tests/
├── Unit/
│   ├── Migrations/
│   │   ├── CreateAlertSettingsTableTest.php
│   │   ├── CreateKeywordAlertSettingsTableTest.php
│   │   └── CreateNotificationsTableTest.php
│   ├── Models/
│   │   ├── AlertSettingTest.php
│   │   ├── KeywordAlertSettingTest.php
│   │   └── NotificationTest.php
│   └── Services/
│       ├── AlertEvaluatorTest.php
│       └── NotificationDispatcherTest.php
├── Feature/
│   ├── Api/
│   │   ├── AlertSettingsControllerTest.php
│   │   ├── KeywordAlertSettingsControllerTest.php
│   │   └── NotificationControllerTest.php
│   └── Mail/
│       └── RankingAlertEmailTest.php

frontend/
└── components/
    ├── AlertSettings.test.tsx
    ├── NotificationCenter.test.tsx
    └── NotificationBell.test.tsx
```

## High-Risk Areas

| Area | Risk | Test Focus |
|------|------|------------|
| AlertEvaluator | Threshold logic | Edge cases, boundary conditions |
| NotificationDispatcher | Email delivery | Error handling, retry logic |
| Ranking job integration | Reliability | Exception handling, logging |
| Frontend components | UX | Accessibility, responsive design |

## Dependencies

- FEAT-001 (Keyword Rank Tracking) must be implemented first
- Tests assume `keywords` table exists
- Tests assume `User` model with authentication

## Running Tests

```bash
# Run all backend tests
php artisan test --filter=AlertSettings|KeywordAlert|Notification

# Run specific test file
php artisan test tests/Unit/Services/AlertEvaluatorTest.php

# Run frontend tests
npm test -- AlertSettings.test.tsx
```

---

Last Updated: 2026-02-28
