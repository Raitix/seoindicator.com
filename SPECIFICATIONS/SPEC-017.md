# SPEC-017: User Onboarding & Activation Flow

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Related Feature
- **FEAT-017**: User Onboarding & Activation Flow

---

## Executive Summary

Technical specification for implementing a guided onboarding experience that converts visitors into active users. The system provides a 5-step wizard that guides new users through setting up their first keyword, connecting their site, and understanding core value within 5 minutes.

---

## Architecture Overview

### System Flow
```
User Registration → Onboarding Modal → Multi-step Wizard → First Keyword Added → Activation Event
                                          ↓
                            Progress stored in user_onboarding table
```

### Component Architecture
```
┌─────────────────────────────────────────────────────────────────┐
│                      Frontend (Next.js)                         │
├─────────────────────────────────────────────────────────────────┤
│  OnboardingProvider (Context)                                   │
│  ├── OnboardingModal (Entry Point)                              │
│  ├── OnboardingWizard (Container)                               │
│  │   ├── Step1_Website (Website URL Input)                      │
│  │   ├── Step2_Keyword (First Keyword Input)                    │
│  │   ├── Step3_Preview (Ranking Preview)                        │
│  │   ├── Step4_Alerts (Alert Preferences)                       │
│  │   └── Step5_Complete (Summary)                               │
│  └── OnboardingBanner (Resume Prompt)                           │
└─────────────────────────────────────────────────────────────────┘
                              ↓ REST API
┌─────────────────────────────────────────────────────────────────┐
│                      Backend (Laravel API)                       │
├─────────────────────────────────────────────────────────────────┤
│  OnboardingController                                            │
│  ├── GET  /api/v1/onboarding (Get progress)                     │
│  ├── POST /api/v1/onboarding (Start/Update progress)            │
│  ├── POST /api/v1/onboarding/complete (Mark complete)           │
│  └── POST /api/v1/onboarding/skip (Skip step)                   │
│                                                                  │
│  OnboardingService                                               │
│  ├── Progress tracking                                          │
│  ├── Step validation                                            │
│  └── Activation event dispatch                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Database (SQLite/MySQL)                     │
├─────────────────────────────────────────────────────────────────┤
│  user_onboarding table (progress storage)                        │
│  keywords table (first keyword creation)                         │
│  sites table (site creation/linking)                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### user_onboarding Table
```sql
CREATE TABLE user_onboarding (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    current_step TINYINT UNSIGNED DEFAULT 1,
    completed_steps JSON NULL,
    website_url VARCHAR(500) NULL,
    first_keyword VARCHAR(255) NULL,
    keyword_id BIGINT UNSIGNED NULL,
    alert_preferences JSON NULL,
    completed_at TIMESTAMP NULL,
    skipped_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE SET NULL,
    
    UNIQUE KEY idx_user_onboarding_user (user_id)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | FK to users.id (unique - one onboarding per user) |
| `project_id` | BIGINT UNSIGNED | FK to projects.id (nullable - linked after step 1) |
| `current_step` | TINYINT UNSIGNED | Current step number (1-5) |
| `completed_steps` | JSON | Array of completed step numbers |
| `website_url` | VARCHAR(500) | User's website URL (step 1) |
| `first_keyword` | VARCHAR(255) | First keyword to track (step 2) |
| `keyword_id` | BIGINT UNSIGNED | FK to keywords.id (after keyword created) |
| `alert_preferences` | JSON | Alert settings: `{email: bool, weekly: bool}` |
| `completed_at` | TIMESTAMP | When onboarding was completed |
| `skipped_at` | TIMESTAMP | When user skipped onboarding |

**Indexes:**
- `idx_user_onboarding_user` unique on `user_id`
- `idx_user_onboarding_completed` on `completed_at`

---

## API Endpoints

### 1. Get Onboarding Progress
```http
GET /api/v1/onboarding
Authorization: Bearer {token}
```

**Response (200):**
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
    "progress_percentage": 20
  }
}
```

**Response (404 - Not Started):**
```json
{
  "success": true,
  "data": null
}
```

### 2. Start/Update Onboarding
```http
POST /api/v1/onboarding
Authorization: Bearer {token}
Content-Type: application/json

{
  "step": 1,
  "data": {
    "website_url": "https://example.com"
  }
}
```

**Validation Rules:**
| Step | Field | Rules |
|------|-------|-------|
| 1 | `website_url` | Required, valid URL, max 500 chars |
| 2 | `first_keyword` | Required, string, max 255 chars |
| 3 | (preview step - no data) | |
| 4 | `alert_preferences.email` | Boolean |
| 4 | `alert_preferences.weekly` | Boolean |
| 5 | (completion step) | |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "current_step": 2,
    "completed_steps": [1],
    "website_url": "https://example.com"
  }
}
```

### 3. Complete Onboarding
```http
POST /api/v1/onboarding/complete
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "completed_at": "2026-02-28T10:00:00Z",
    "keyword_id": 1
  }
}
```

**Side Effects:**
- Creates keyword record in `keywords` table (if `first_keyword` provided)
- Dispatches `UserActivated` event
- Sets `completed_at` timestamp

### 4. Skip Onboarding
```http
POST /api/v1/onboarding/skip
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "skipped_at": "2026-02-28T10:00:00Z"
  }
}
```

---

## Frontend Components

### Component Structure
```
frontend/components/onboarding/
├── OnboardingProvider.tsx      # Context provider for state
├── OnboardingModal.tsx         # Entry point modal
├── OnboardingWizard.tsx        # Main wizard container
├── steps/
│   ├── Step1_Website.tsx       # Website URL input
│   ├── Step2_Keyword.tsx       # First keyword input
│   ├── Step3_Preview.tsx       # Ranking preview (mock/real)
│   ├── Step4_Alerts.tsx        # Alert preferences
│   └── Step5_Complete.tsx      # Summary & CTA
├── OnboardingBanner.tsx        # Resume prompt banner
├── ProgressBar.tsx             # Step progress indicator
└── Tooltip.tsx                 # Feature explanation tooltips
```

### State Management
```typescript
interface OnboardingState {
  isOpen: boolean;
  currentStep: number;
  completedSteps: number[];
  data: {
    websiteUrl: string | null;
    firstKeyword: string | null;
    keywordId: number | null;
    alertPreferences: {
      email: boolean;
      weekly: boolean;
    } | null;
  };
  isLoading: boolean;
  error: string | null;
}
```

### Onboarding Provider
```typescript
// Context for managing onboarding state across components
const OnboardingContext = createContext<{
  state: OnboardingState;
  startOnboarding: () => void;
  nextStep: (data?: StepData) => Promise<void>;
  prevStep: () => void;
  skipOnboarding: () => Promise<void>;
  completeOnboarding: () => Promise<void>;
}>(...);
```

---

## Onboarding Steps Detail

### Step 1: Welcome & Website
**Purpose:** Collect user's website URL for tracking context.

**UI Elements:**
- Welcome message: "Welcome to SEOIndicator! Let's get you set up in 5 minutes."
- URL input field with validation
- Tooltip: "We'll track rankings for pages on this site"
- "Continue" button (disabled until valid URL)
- "Skip for now" link

**Validation:**
- Valid URL format (http/https)
- Max 500 characters
- SSRF protection (block localhost, private IPs)

**Backend Action:**
- Store `website_url` in `user_onboarding`
- Create/link project if website matches existing project

### Step 2: First Keyword
**Purpose:** Get user's first keyword to track.

**UI Elements:**
- Input field for keyword
- Keyword suggestions based on website URL (optional, post-MVP)
- Tooltip: "This is the search term you want to rank for"
- Example keywords (gray placeholder)
- "Continue" button
- "Skip for now" link

**Validation:**
- Required, max 255 characters
- Normalize: lowercase, trim whitespace

**Backend Action:**
- Store `first_keyword` in `user_onboarding`
- Keyword NOT created yet (created on completion)

### Step 3: Ranking Preview
**Purpose:** Show immediate value - current ranking for keyword.

**UI Elements:**
- Loading state while fetching rank
- Ranking display:
  - Position number (or "Not in top 100")
  - Brief explanation
- CTA: "Add more keywords" or "Continue"
- "Skip for now" link

**Backend Action:**
- Use `MockSERPFetcher` for MVP (no real API calls)
- Display mock position for demonstration
- Post-MVP: Use real SERP fetcher

### Step 4: Alert Setup
**Purpose:** Configure notification preferences.

**UI Elements:**
- Toggle: "Email alerts for ranking changes"
- Toggle: "Weekly summary reports"
- Tooltip: "Stay informed without checking constantly"
- "Continue" button
- "Skip for now" link

**Backend Action:**
- Store `alert_preferences` JSON in `user_onboarding`

### Step 5: All Set!
**Purpose:** Confirm completion and guide next steps.

**UI Elements:**
- Success message: "You're all set! Here's what happens next:"
- Next steps list:
  - "Check back in 24h for updated rankings"
  - "Add more keywords in the Keywords tab"
  - "Explore competitor tracking"
- CTA: "Go to Dashboard" (primary button)

**Backend Action:**
- Set `completed_at` timestamp
- Create keyword record in `keywords` table
- Dispatch `UserActivated` event

---

## Event System

### UserActivated Event
Dispatched when user completes onboarding.

**Event Data:**
```php
class UserActivated
{
    public function __construct(
        public User $user,
        public int $keywordId,
        public ?int $projectId
    ) {}
}
```

**Listeners:**
| Listener | Action |
|----------|--------|
| `SendWelcomeEmail` | Send welcome email (post-MVP) |
| `TrackActivationAnalytics` | Log to analytics system |
| `QueueInitialRankCheck` | Queue rank check for first keyword |

---

## Analytics Tracking

### Events to Track
| Event Name | Properties | Description |
|------------|------------|-------------|
| `onboarding_started` | user_id, timestamp | User opened onboarding modal |
| `onboarding_step_completed` | user_id, step_number, timestamp | User completed a step |
| `onboarding_skipped` | user_id, step_number, timestamp | User skipped onboarding |
| `onboarding_completed` | user_id, duration_seconds, timestamp | User finished all steps |
| `onboarding_resumed` | user_id, current_step, timestamp | User resumed onboarding |

### Success Metrics
| Metric | Current | Target (30d) | Target (90d) |
|--------|---------|--------------|--------------|
| Completion rate | 0% | 40% | 60% |
| Returning users | 2 (1.6%) | 15 (10%) | 50 (20%) |
| Time to first keyword | N/A | <5 min | <3 min |
| Trial-to-paid conversion | 0% | 2% | 5% |
| Bounce rate | 78% | 65% | 50% |

---

## Security Considerations

### URL Validation (SSRF Protection)
- Block localhost/127.x.x.x
- Block private IP ranges (10.x, 172.16-31.x, 192.168.x)
- Block cloud metadata endpoint (169.254.169.254)
- Block file:// protocol
- Validate URL format with `filter_var($url, FILTER_VALIDATE_URL)`

### Input Sanitization
- `website_url`: Validate URL, max 500 chars
- `first_keyword`: Sanitize with `htmlspecialchars()`, max 255 chars
- `alert_preferences`: Validate JSON structure, boolean values only

### Authorization
- All endpoints require authentication
- User can only access their own onboarding data
- Cross-user access returns 403 Forbidden

---

## Testing Strategy

### Unit Tests

**OnboardingServiceTest:**
| Test | Description |
|------|-------------|
| Start onboarding | Creates user_onboarding record |
| Update step | Updates current_step and completed_steps |
| Complete onboarding | Sets completed_at, creates keyword |
| Skip onboarding | Sets skipped_at |
| Get progress | Returns current progress |
| Already completed | Handles re-completion gracefully |

**OnboardingControllerTest:**
| Test | Description |
|------|-------------|
| GET /onboarding (authenticated) | Returns progress |
| GET /onboarding (not started) | Returns null |
| POST /onboarding (valid) | Updates progress |
| POST /onboarding (invalid URL) | Returns 422 |
| POST /onboarding/complete | Completes onboarding |
| POST /onboarding/skip | Skips onboarding |
| Authorization checks | 401 for unauthenticated |

### Feature Tests

**OnboardingFlowTest:**
| Test | Description |
|------|-------------|
| Complete full onboarding flow | End-to-end wizard completion |
| Skip and resume | Skip, then resume later |
| Partial completion | Stop at step 3, resume from step 3 |
| Create keyword on completion | Keyword exists after completion |
| SSRF protection | Blocked URLs rejected |

---

## Implementation Phases

### Phase 1: MVP Onboarding (Week 1)
- [ ] Database migration: `user_onboarding` table
- [ ] Backend: OnboardingService + Controller
- [ ] Frontend: OnboardingProvider + Modal + Wizard shell
- [ ] Steps 1, 2, 5 implementation
- [ ] Basic progress tracking

### Phase 2: Enhanced Experience (Week 2)
- [ ] Step 3: Ranking preview (mock)
- [ ] Step 4: Alert preferences
- [ ] Progress bar component
- [ ] Skip functionality
- [ ] Resume onboarding banner

### Phase 3: Analytics & Optimization (Week 3)
- [ ] Analytics event tracking
- [ ] A/B testing framework preparation
- [ ] Onboarding completion rate tracking
- [ ] Time-to-first-keyword tracking

### Phase 4: Smart Suggestions (Week 4, Post-MVP)
- [ ] Keyword suggestions from URL parsing
- [ ] Competitor suggestions
- [ ] Industry-specific onboarding paths
- [ ] Personalized recommendations

---

## Dependencies

### Required (Exist)
| Dependency | Status | Notes |
|------------|--------|-------|
| User authentication | ✅ Exists | Laravel Sanctum |
| Projects table | ✅ Exists | TASK-8-1 |
| Keywords table | ✅ Exists | TASK-1-1 |
| Sites table | ✅ Exists | TASK-1-1 |
| MockSERPFetcher | ✅ Exists | TASK-1-7 |

### Created by This Spec
| Component | Type | Notes |
|-----------|------|-------|
| user_onboarding table | Database | New table |
| OnboardingService | Backend | New service |
| OnboardingController | Backend | New controller |
| OnboardingProvider | Frontend | New context |
| OnboardingWizard | Frontend | New component |

---

## Free Tools Constraint Compliance

All components use FREE solutions only:

| Component | Implementation | Cost |
|-----------|----------------|------|
| Frontend | Next.js (React) | Free |
| Backend | Laravel API | Free |
| Database | SQLite (dev) / MySQL (prod) | Free tier |
| Ranking Preview | MockSERPFetcher | Free |
| Analytics | Self-hosted events | Free |

**No paid APIs or services required.**

---

## Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Users skip onboarding | High | Medium | Make onboarding valuable, not tedious; add resume prompts |
| Onboarding feels forced | Medium | Medium | Allow skip, show value at each step |
| Technical issues during setup | Low | High | Graceful error handling, clear next steps |
| Users don't have keyword ready | Medium | Low | Provide keyword suggestions, examples |
| Low completion rate | Medium | High | Analytics tracking, iterate on UX |

---

## Handoff Notes

### For SpecSystemAnalyst
- Break into tasks by phase (Phase 1 is MVP)
- Each step can be a separate task
- Consider frontend/backend split for each phase

### Key Decisions
1. **One onboarding per user**: Unique constraint on user_id
2. **Keyword created on completion**: Not during step 2 (prevents orphan keywords)
3. **Mock ranking preview**: No real API calls in MVP
4. **Skip allowed**: User can skip entire flow or individual steps

---

## Acceptance Criteria

- [ ] New user sees welcome modal after registration
- [ ] Step 1: User enters their website URL
- [ ] Step 2: User adds their first keyword to track
- [ ] Step 3: User sees a sample ranking result (mock)
- [ ] Step 4: User configures basic alert preferences
- [ ] Step 5: User sees "You're all set!" summary with next steps
- [ ] Onboarding can be skipped and resumed later
- [ ] Progress bar shows completion percentage
- [ ] Tooltips explain each feature during onboarding
- [ ] Success metrics tracked: onboarding completion rate, time to first keyword

---

*Created by Architect agent on 2026-02-28*
