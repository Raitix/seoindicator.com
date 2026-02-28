# TEST-17: User Onboarding & Activation Flow Tests

## Feature Reference
- **FEAT-017**: User Onboarding & Activation Flow
- **SPEC-017**: Technical Specification

## Overview
Comprehensive test coverage for the user onboarding system, including database schema, backend service layer, REST API endpoints, and frontend components.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Feature/Database/UserOnboardingTableTest.php` | Feature | 15+ | 40+ |
| `backend/tests/Unit/Models/UserOnboardingTest.php` | Unit | 10+ | 25+ |
| `backend/tests/Unit/Services/OnboardingServiceTest.php` | Unit | 20+ | 50+ |
| `backend/tests/Feature/Api/V1/OnboardingControllerTest.php` | Feature | 25+ | 80+ |
| `backend/tests/Security/OnboardingSecurityTest.php` | Security | 15+ | 40+ |
| `frontend/__tests__/components/onboarding/OnboardingProvider.test.tsx` | Unit | 15+ | 40+ |
| `frontend/__tests__/components/onboarding/OnboardingModal.test.tsx` | Unit | 10+ | 25+ |
| `frontend/__tests__/components/onboarding/OnboardingWizard.test.tsx` | Unit | 12+ | 30+ |
| `frontend/__tests__/components/onboarding/steps/*.test.tsx` | Unit | 30+ | 80+ |

---

## Test Scenarios

### 1. Database Schema Tests (UserOnboardingTableTest)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DB-01 | Table exists | N/A | `user_onboarding` table created |
| DB-02 | Primary key | N/A | `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT |
| DB-03 | User ID column | N/A | `user_id` BIGINT UNSIGNED NOT NULL FK |
| DB-04 | Project ID column | N/A | `project_id` BIGINT UNSIGNED NULL FK |
| DB-05 | Current step column | N/A | `current_step` TINYINT UNSIGNED DEFAULT 1 |
| DB-06 | Completed steps column | N/A | `completed_steps` JSON NULL |
| DB-07 | Website URL column | N/A | `website_url` VARCHAR(500) NULL |
| DB-08 | First keyword column | N/A | `first_keyword` VARCHAR(255) NULL |
| DB-09 | Keyword ID column | N/A | `keyword_id` BIGINT UNSIGNED NULL FK |
| DB-10 | Alert preferences column | N/A | `alert_preferences` JSON NULL |
| DB-11 | Timestamps | N/A | `completed_at`, `skipped_at`, `created_at`, `updated_at` |
| DB-12 | Unique constraint on user_id | N/A | One onboarding per user |
| DB-13 | Index on completed_at | N/A | Index exists |
| DB-14 | Foreign key cascade delete | Delete user | Onboarding deleted |
| DB-15 | Foreign key set null on project delete | Delete project | project_id = NULL |

### 2. Model Tests (UserOnboardingTest)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| M-01 | Create model | Valid data | Model created successfully |
| M-02 | Cast completed_steps to array | JSON string | Array returned |
| M-03 | Cast alert_preferences to array | JSON string | Array returned |
| M-04 | Cast completed_at to datetime | Timestamp | Carbon instance |
| M-05 | Cast skipped_at to datetime | Timestamp | Carbon instance |
| M-06 | BelongsTo user relationship | N/A | User relationship defined |
| M-07 | BelongsTo project relationship | N/A | Project relationship defined |
| M-08 | BelongsTo keyword relationship | N/A | Keyword relationship defined |
| M-09 | Fillable attributes | Mass assignment | All fillable attributes set |
| M-10 | Default current_step | No step provided | current_step = 1 |

### 3. Service Tests (OnboardingServiceTest)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| S-01 | Get progress - exists | User with onboarding | UserOnboarding returned |
| S-02 | Get progress - not exists | User without onboarding | null returned |
| S-03 | Start onboarding | User | UserOnboarding created with step 1 |
| S-04 | Start onboarding - duplicate | User with existing onboarding | Existing onboarding returned |
| S-05 | Update step 1 - valid URL | website_url | Step updated, completed_steps = [1] |
| S-06 | Update step 2 - valid keyword | first_keyword | Step updated, completed_steps = [1,2] |
| S-07 | Update step 4 - valid preferences | alert_preferences | Step updated, completed_steps updated |
| S-08 | Complete step | Step number | Step marked complete |
| S-09 | Skip onboarding | User | skipped_at set |
| S-10 | Complete onboarding | User with keyword | completed_at set, keyword created |
| S-11 | Complete onboarding - no keyword | User without keyword | completed_at set, no keyword |
| S-12 | Calculate progress - step 1 | current_step = 1 | 20% |
| S-13 | Calculate progress - step 3 | current_step = 3 | 60% |
| S-14 | Calculate progress - completed | completed_at set | 100% |
| S-15 | SSRF protection - localhost | http://localhost/test | Validation error |
| S-16 | SSRF protection - 127.0.0.1 | http://127.0.0.1/test | Validation error |
| S-17 | SSRF protection - private IP | http://192.168.1.1/test | Validation error |
| S-18 | SSRF protection - cloud metadata | http://169.254.169.254/ | Validation error |
| S-19 | SSRF protection - file protocol | file:///etc/passwd | Validation error |
| S-20 | Valid URL accepted | https://example.com | URL stored |

### 4. Controller Tests (OnboardingControllerTest)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| C-01 | GET /onboarding - authenticated | Auth header | 200 with progress |
| C-02 | GET /onboarding - not started | Auth header, no onboarding | 200 with null data |
| C-03 | GET /onboarding - unauthenticated | No auth | 401 Unauthorized |
| C-04 | POST /onboarding - start | Auth, empty body | Onboarding created |
| C-05 | POST /onboarding - step 1 valid | website_url | 200, step 1 completed |
| C-06 | POST /onboarding - step 1 invalid URL | invalid_url | 422 validation error |
| C-07 | POST /onboarding - step 1 URL too long | 501+ char URL | 422 validation error |
| C-08 | POST /onboarding - step 2 valid | first_keyword | 200, step 2 completed |
| C-09 | POST /onboarding - step 2 empty | empty keyword | 422 validation error |
| C-10 | POST /onboarding - step 2 too long | 256+ char keyword | 422 validation error |
| C-11 | POST /onboarding - step 4 valid | alert_preferences | 200, step 4 completed |
| C-12 | POST /onboarding - step 4 invalid | non-boolean email | 422 validation error |
| C-13 | POST /onboarding/complete - success | Auth, step 2 done | 200, keyword created |
| C-14 | POST /onboarding/complete - already complete | Auth, completed_at set | 200, no duplicate keyword |
| C-15 | POST /onboarding/skip - success | Auth | 200, skipped_at set |
| C-16 | POST /onboarding/skip - already completed | Auth, completed_at | 200, no change |
| C-17 | Progress percentage | current_step = 2 | 40% in response |
| C-18 | Cross-user access prevention | Different user ID | 403 Forbidden |
| C-19 | Response structure | Valid request | success, data, progress_percentage |
| C-20 | Error response structure | Validation error | success: false, errors object |
| C-21 | UserActivated event dispatched | Complete onboarding | Event dispatched |
| C-22 | Keyword linked to onboarding | Complete with keyword | keyword_id set |
| C-23 | Resume after skip | Skipped, then start | New onboarding or resume |
| C-24 | Concurrent update handling | Simultaneous updates | Last write wins |
| C-25 | Partial completion state | Step 1 done, step 2 pending | Correct state returned |

### 5. Security Tests (OnboardingSecurityTest)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-01 | SSRF - localhost | http://localhost/ | Blocked |
| SEC-02 | SSRF - 127.0.0.1 | http://127.0.0.1/ | Blocked |
| SEC-03 | SSRF - 10.x private | http://10.0.0.1/ | Blocked |
| SEC-04 | SSRF - 172.16-31.x private | http://172.16.0.1/ | Blocked |
| SEC-05 | SSRF - 192.168.x private | http://192.168.1.1/ | Blocked |
| SEC-06 | SSRF - cloud metadata | http://169.254.169.254/ | Blocked |
| SEC-07 | SSRF - file protocol | file:///etc/passwd | Blocked |
| SEC-08 | XSS in website_url | <script>alert(1)</script> | Sanitized or blocked |
| SEC-09 | XSS in first_keyword | <script>alert(1)</script> | Sanitized or blocked |
| SEC-10 | SQL injection in URL | ' OR '1'='1 | Sanitized |
| SEC-11 | SQL injection in keyword | '; DROP TABLE users; | Sanitized |
| SEC-12 | Authorization - unauthenticated | No token | 401 |
| SEC-13 | Authorization - wrong user | Different user token | 403 |
| SEC-14 | Rate limiting | 100+ requests | 429 |
| SEC-15 | CSRF protection | Cross-site request | Blocked |

### 6. Frontend Provider Tests (OnboardingProvider)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FP-01 | Initial state | Mount | isOpen: false, currentStep: 1 |
| FP-02 | Start onboarding | startOnboarding() | isOpen: true, API called |
| FP-03 | Next step | nextStep(data) | currentStep++, API called |
| FP-04 | Previous step | prevStep() | currentStep-- |
| FP-05 | Skip onboarding | skipOnboarding() | isOpen: false, skipped_at set |
| FP-06 | Complete onboarding | completeOnboarding() | isOpen: false, completed_at set |
| FP-07 | Loading state | API in progress | isLoading: true |
| FP-08 | Error state | API error | error: error message |
| FP-09 | Fetch progress on mount | User with onboarding | Progress loaded |
| FP-10 | Resume onboarding | User with partial progress | currentStep = saved step |
| FP-11 | Completed state | User completed | isOpen: false |
| FP-12 | Skipped state | User skipped | isOpen: false |
| FP-13 | Data persistence | Close and reopen | Data restored from API |
| FP-14 | Step data update | Update step 1 data | websiteUrl updated |
| FP-15 | Alert preferences update | Update step 4 data | alertPreferences updated |

### 7. Frontend Modal Tests (OnboardingModal)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FM-01 | Render modal | isOpen: true | Modal visible |
| FM-02 | Hidden modal | isOpen: false | Modal not visible |
| FM-03 | Close button | Click close | onSkip called |
| FM-04 | Close confirmation | Close with progress | Confirmation dialog |
| FM-05 | Skip confirmation | Click skip | Confirmation dialog |
| FM-06 | Responsive design | Mobile viewport | Mobile layout |
| FM-07 | Accessibility - focus trap | Tab navigation | Focus trapped in modal |
| FM-08 | Accessibility - escape key | Press Escape | Modal closes |
| FM-09 | Accessibility - aria labels | Screen reader | Proper aria labels |
| FM-10 | Loading overlay | isLoading: true | Loading spinner shown |

### 8. Frontend Wizard Tests (OnboardingWizard)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FW-01 | Render step 1 | currentStep: 1 | Step1_Website rendered |
| FW-02 | Render step 2 | currentStep: 2 | Step2_Keyword rendered |
| FW-03 | Render step 3 | currentStep: 3 | Step3_Preview rendered |
| FW-04 | Render step 4 | currentStep: 4 | Step4_Alerts rendered |
| FW-05 | Render step 5 | currentStep: 5 | Step5_Complete rendered |
| FW-06 | Next button | Click next | onNextStep called |
| FW-07 | Back button | Click back | onPrevStep called |
| FW-08 | Back button disabled | currentStep: 1 | Back button disabled |
| FW-09 | Progress bar | currentStep: 3 | 60% filled |
| FW-10 | Skip link | Click skip | onSkip called |
| FW-11 | Error display | error: "message" | Error message shown |
| FW-12 | Loading state | isLoading: true | Buttons disabled |

---

## Mock Data

### UserOnboarding Factory
```php
UserOnboarding::factory()->create([
    'user_id' => 1,
    'current_step' => 2,
    'completed_steps' => [1],
    'website_url' => 'https://example.com',
    'first_keyword' => null,
    'keyword_id' => null,
    'alert_preferences' => null,
    'completed_at' => null,
    'skipped_at' => null,
]);
```

### API Response Mock
```json
{
  "success": true,
  "data": {
    "id": 1,
    "current_step": 2,
    "completed_steps": [1],
    "website_url": "https://example.com",
    "first_keyword": null,
    "keyword_id": null,
    "alert_preferences": null,
    "completed_at": null,
    "progress_percentage": 40
  }
}
```

### SSRF Test URLs
```php
$blockedUrls = [
    'http://localhost/test',
    'http://127.0.0.1/test',
    'http://10.0.0.1/test',
    'http://172.16.0.1/test',
    'http://192.168.1.1/test',
    'http://169.254.169.254/latest/meta-data',
    'file:///etc/passwd',
    'gopher://internal-host/test',
];
```

---

## Coverage Targets

| Category | Target | Notes |
|----------|--------|-------|
| Database Schema | 100% | All columns, constraints, indexes |
| Model | 95% | All relationships, casts, fillable |
| Service | 95% | All methods, edge cases |
| Controller | 95% | All endpoints, validation, auth |
| Security | 100% | All SSRF patterns, XSS, SQL injection |
| Frontend Provider | 90% | State management, API integration |
| Frontend Modal | 90% | Rendering, accessibility |
| Frontend Wizard | 90% | Navigation, step rendering |

---

## Test Execution

```bash
# Backend tests
cd backend
php artisan test --filter=UserOnboarding
php artisan test --filter=OnboardingService
php artisan test --filter=OnboardingController
php artisan test --filter=OnboardingSecurity

# All onboarding tests
php artisan test tests/Feature/Database/UserOnboardingTableTest.php
php artisan test tests/Unit/Models/UserOnboardingTest.php
php artisan test tests/Unit/Services/OnboardingServiceTest.php
php artisan test tests/Feature/Api/V1/OnboardingControllerTest.php
php artisan test tests/Security/OnboardingSecurityTest.php

# Frontend tests
cd frontend
npm test -- --testPathPattern=onboarding
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| User model | ✅ Exists | Required for FK |
| Project model | ✅ Exists | Optional FK |
| Keyword model | ✅ Exists | Created on completion |
| Laravel Sanctum | ✅ Exists | Authentication |

---

## Notes for Developer

### Key Implementation Points

1. **SSRF Protection**: Must block all private IP ranges and special protocols
2. **Keyword Creation**: Keyword is created on COMPLETION, not on step 2
3. **Progress Calculation**: 5 steps = 20% each
4. **Unique Constraint**: One onboarding record per user
5. **Cascade Delete**: When user is deleted, onboarding is also deleted

### Test Order

1. Run database tests first (migration must work)
2. Then model tests (model depends on migration)
3. Then service tests (service uses model)
4. Then controller tests (controller uses service)
5. Finally security tests (validates all protections)

### Adversarial Testing Checklist

- [ ] Try URL with localhost
- [ ] Try URL with 127.0.0.1
- [ ] Try URL with private IP ranges
- [ ] Try URL with cloud metadata IP
- [ ] Try file:// protocol
- [ ] Try gopher:// protocol
- [ ] Try XSS in website_url
- [ ] Try XSS in first_keyword
- [ ] Try SQL injection in URL
- [ ] Try SQL injection in keyword
- [ ] Try cross-user access
- [ ] Try unauthenticated access
- [ ] Try duplicate keyword creation
- [ ] Try concurrent updates
- [ ] Try negative step number
- [ ] Try step > 5
- [ ] Try empty completed_steps array
- [ ] Try invalid JSON in completed_steps
- [ ] Try URL longer than 500 chars
- [ ] Try keyword longer than 255 chars

---

## Comments

### [2026-02-28] PreTester
Test documentation created. Test files to be implemented next.

---

*Created by PreTester agent on 2026-02-28*
