# TASK-17-2: Backend - OnboardingService and Controller

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow

## Dependencies
- TASK-17-1 (Database Migration - User Onboarding Table)

## Description
Create the backend service layer and REST API controller for managing user onboarding progress. The OnboardingService handles business logic for progress tracking, step validation, and completion events. The controller exposes REST endpoints for the frontend.

## Acceptance Criteria
- [ ] OnboardingService created with methods:
  - [ ] `getProgress(User $user): ?UserOnboarding`
  - [ ] `startOnboarding(User $user): UserOnboarding`
  - [ ] `updateStep(User $user, int $step, array $data): UserOnboarding`
  - [ ] `completeStep(User $user, int $step): UserOnboarding`
  - [ ] `skipOnboarding(User $user): UserOnboarding`
  - [ ] `completeOnboarding(User $user): UserOnboarding`
  - [ ] `calculateProgressPercentage(UserOnboarding $onboarding): int`
- [ ] OnboardingController created with endpoints:
  - [ ] GET `/api/v1/onboarding` - Get current progress
  - [ ] POST `/api/v1/onboarding` - Start/update onboarding
  - [ ] POST `/api/v1/onboarding/complete` - Complete onboarding
  - [ ] POST `/api/v1/onboarding/skip` - Skip onboarding
- [ ] Step 1 validation: website_url (valid URL, max 500 chars, SSRF protection)
- [ ] Step 2 validation: first_keyword (required, string, max 255 chars)
- [ ] Step 4 validation: alert_preferences.email and alert_preferences.weekly (booleans)
- [ ] Authorization enforced (user can only access their own onboarding)
- [ ] UserActivated event dispatched on completion
- [ ] Keyword created in keywords table on completion (if first_keyword provided)
- [ ] Proper error handling and validation responses (422 for validation errors)
- [ ] API returns JSON with success flag and data structure

## Effort Estimate
`M` (1-2 days)

## Technical Details

### OnboardingService Methods
```php
class OnboardingService
{
    public function getProgress(User $user): ?UserOnboarding;
    public function startOnboarding(User $user): UserOnboarding;
    public function updateStep(User $user, int $step, array $data): UserOnboarding;
    public function completeStep(User $user, int $step): UserOnboarding;
    public function skipOnboarding(User $user): UserOnboarding;
    public function completeOnboarding(User $user): UserOnboarding;
    public function calculateProgressPercentage(UserOnboarding $onboarding): int;
}
```

### API Response Format
```json
{
  "success": true,
  "data": {
    "id": 1,
    "current_step": 2,
    "completed_steps": [1],
    "website_url": "https://example.com",
    "first_keyword": null,
    "progress_percentage": 20
  }
}
```

### SSRF Protection
- Block localhost/127.x.x.x
- Block private IP ranges (10.x, 172.16-31.x, 192.168.x)
- Block cloud metadata endpoint (169.254.169.254)
- Block file:// protocol
- Validate URL format with `filter_var($url, FILTER_VALIDATE_URL)`

## Testing Requirements
- Unit tests for OnboardingService (all methods)
- Unit tests for URL validation (SSRF protection)
- Feature tests for all API endpoints
- Feature tests for authorization (401/403 responses)
- Feature tests for validation errors (422 responses)
- Feature test for keyword creation on completion

## Notes
This task handles all backend business logic. Frontend components will consume these APIs.

---

## Comments

### 2026-03-15 13:05 GMT+2 - DocSystemAnalyst
**Re-verification Complete**
- Task status confirmed: `Done`
- Part of FEAT-017/SPEC-17 (User Onboarding & Activation Flow - Phase 1 MVP)
- OnboardingService and OnboardingController already deployed to production
- DOCUMENTATION.md verified at line 25643 (User Onboarding & Activation Flow section)
- Completion marker written: `logs/completed/task/TASK-17-2.json`
- Stale lock removed

---

*Created by SpecSystemAnalyst on 2026-02-28*
