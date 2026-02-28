# FEAT-017: User Onboarding & Activation Flow

## STATUS
`To Prioritize`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow - [View Specification](../SPECIFICATIONS/SPEC-017.md)

## Comments
### 2026-02-28 - SpecSystemAnalyst
**Task Breakdown Complete**

Created 20 tasks organized into 4 phases:

**Phase 1: MVP Onboarding (High Priority)**
- TASK-17-1: Database Migration - User Onboarding Table
- TASK-17-2: Backend - OnboardingService and Controller
- TASK-17-3: Frontend - OnboardingProvider, Modal, and Wizard Shell
- TASK-17-4: Step 1 - Website URL Input Component
- TASK-17-5: Step 2 - First Keyword Input Component
- TASK-17-6: Step 5 - Completion Summary Component
- TASK-17-7: Progress Tracking Integration

**Phase 2: Enhanced Experience (Medium Priority)**
- TASK-17-8: Step 3 - Ranking Preview Component
- TASK-17-9: Step 4 - Alert Preferences Component
- TASK-17-10: Progress Bar Component
- TASK-17-11: Skip Functionality
- TASK-17-12: Resume Onboarding Banner

**Phase 3: Analytics & Optimization (Low Priority)**
- TASK-17-13: Analytics Event Tracking
- TASK-17-14: A/B Testing Framework Preparation
- TASK-17-15: Onboarding Completion Rate Tracking
- TASK-17-16: Time-to-First-Keyword Tracking

**Phase 4: Smart Suggestions - Post-MVP (Low Priority)**
- TASK-17-17: Keyword Suggestions from URL Parsing
- TASK-17-18: Competitor Suggestions
- TASK-17-19: Industry-Specific Onboarding Paths
- TASK-17-20: Personalized Recommendations

**Dependency Chain:**
- TASK-17-1 has no dependencies (can start immediately)
- All backend tasks depend on TASK-17-1
- All frontend tasks depend on TASK-17-2
- Phase 2+ tasks depend on Phase 1 completion

**Key Decisions:**
1. MVP includes only Steps 1, 2, 5 + progress tracking (skip Steps 3, 4)
2. Keyword created on completion (not during step 2) to prevent orphans
3. MockSERPFetcher used for Step 3 (no real SERP API calls in MVP)
4. SSRF protection on website URL validation

### 2026-02-28 - Architect
Created technical specification SPEC-017. Key decisions:
- 5-step wizard with skip/resume functionality
- `user_onboarding` table for progress tracking
- Keyword created on completion (not during step 2)
- Mock ranking preview for MVP (no real SERP API)
- Free tools only - no paid services required

**Risk Assessment:** Low risk confirmed - frontend-heavy, no complex integrations, simple database schema.

**Implementation Phases:**
- Phase 1 (MVP): Steps 1, 2, 5 + basic progress tracking
- Phase 2: Steps 3, 4 + skip/resume UI
- Phase 3: Analytics tracking
- Phase 4 (Post-MVP): Smart keyword suggestions

## PRIORITY
`High`

## RISK
`Low`

## Description
Implement a guided onboarding experience that converts visitors into active users. New users receive a step-by-step wizard to set up their first keyword, connect their site, and understand the core value proposition within 5 minutes. This directly addresses the critical conversion problem: 78% bounce rate and only 2 returning users out of 125 total users.

## User Story
As a new visitor to SEOIndicator, I want a clear, guided path to set up my account and see my first ranking data so that I understand the tool's value immediately and return to track my progress.

## Business Impact
### Revenue
- **Direct conversion impact**: Converting even 10% of the 125 monthly visitors = 12 new users/month
- **Reduced CAC**: Onboarding improves organic/trial conversion, reducing paid acquisition needs
- **Faster time-to-value**: Users who complete onboarding are 3x more likely to convert to paid
- **Trial-to-paid rate**: Industry standard is 2-5%; onboarding can push toward 5%+

### User Behavior
- **Clear first action**: New users know exactly what to do (add first keyword)
- **Quick win**: See ranking data within 5 minutes of signup
- **Feature discovery**: Guided tour reveals features users might miss
- **Reduced overwhelm**: Complex SEO tools often scare new users - onboarding simplifies

### Retention
- **Activation = retention**: Users who complete onboarding are 60% more likely to return
- **Habit formation**: First successful ranking check creates habit loop
- **Investment effect**: Users who add keywords feel invested in continuing
- **Clear next steps**: Onboarding ends with "what to do next" guidance

### Legal & Compliance
- **Minimal data collection**: Only essential signup data required
- **GDPR-compliant**: Clear consent for email notifications
- **Data retention**: Onboarding progress stored for user convenience
- **Privacy transparency**: Explain what data is collected during signup

### Complexity & Cost
- **Frontend-only**: Mostly Next.js components with local state
- **No external APIs**: All functionality is internal
- **Simple database**: One table for onboarding progress
- **Low effort**: 1-2 weeks for complete onboarding flow

## Analytics Context
Based on Google Analytics data (last 30 days):
- **Total users**: 125
- **New users**: 123 (98.4% new, only 2 returning)
- **Homepage engagement**: 86 seconds average (good interest)
- **Overall bounce rate**: 78% (not converting)
- **Direct traffic**: 140 sessions (80% bounce)
- **Organic traffic**: 1 session (critical SEO issue)

**Hypothesis**: Users are interested (86s homepage engagement) but don't understand how to get started. A clear onboarding flow will convert this interest into action.

## Acceptance Criteria
- [ ] New user sees welcome modal after registration
- [ ] Step 1: User enters their website URL
- [ ] Step 2: User adds their first keyword to track
- [ ] Step 3: User sees a sample ranking result (or real if available)
- [ ] Step 4: User configures basic alert preferences
- [ ] Step 5: User sees "You're all set!" summary with next steps
- [ ] Onboarding can be skipped and resumed later
- [ ] Progress bar shows completion percentage
- [ ] Tooltips explain each feature during onboarding
- [ ] Success metrics tracked: onboarding completion rate, time to first keyword

## Technical Approach

### Architecture
```
User Registration → Onboarding Modal → Multi-step Wizard → First Keyword Added → Activation Event
                                          ↓
                            Progress stored in user_onboarding table
```

### Database Schema
```sql
CREATE TABLE user_onboarding (
    id BIGINT PRIMARY KEY,
    user_id BIGINT FOREIGN KEY,
    current_step INT DEFAULT 1,
    completed_steps JSON,
    website_url VARCHAR(255),
    first_keyword VARCHAR(255),
    alert_preferences JSON,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Onboarding Steps

#### Step 1: Welcome & Website
- "Welcome to SEOIndicator! Let's get you set up in 5 minutes."
- Input: Website URL
- Validation: Valid URL format
- Tooltip: "We'll track rankings for pages on this site"

#### Step 2: First Keyword
- "What keyword do you want to track?"
- Input: Keyword
- Auto-suggestion: Based on website URL parsing
- Tooltip: "This is the search term you want to rank for"

#### Step 3: Ranking Preview
- "Here's how you rank for [keyword]..."
- Show: Current position (or "Not in top 100")
- Show: Brief explanation of what it means
- CTA: "Add more keywords" or "Continue"

#### Step 4: Alert Setup
- "Get notified when your rankings change"
- Toggle: Email alerts ON/OFF
- Toggle: Weekly summary ON/OFF
- Tooltip: "Stay informed without checking constantly"

#### Step 5: All Set!
- "You're all set! Here's what happens next:"
- List: Check back in 24h for updated rankings
- List: Add more keywords in the Keywords tab
- List: Explore competitor tracking (if available)
- CTA: "Go to Dashboard"

### Skip & Resume
- "Skip for now" link on each step
- Onboarding banner on dashboard: "Complete your setup (2/5 steps)"
- Resume exactly where user left off

## Success Metrics

| Metric | Current | Target (30 days) | Target (90 days) |
|--------|---------|------------------|------------------|
| Onboarding completion rate | 0% | 40% | 60% |
| Returning users | 2 (1.6%) | 15 (10%) | 50 (20%) |
| Time to first keyword | N/A | <5 min | <3 min |
| Trial-to-paid conversion | 0% | 2% | 5% |
| Bounce rate | 78% | 65% | 50% |

## Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Users skip onboarding | High | Medium | Make onboarding valuable, not tedious; add resume prompts |
| Onboarding feels forced | Medium | Medium | Allow skip, show value at each step |
| Technical issues during setup | Low | High | Graceful error handling, clear next steps |
| Users don't have keyword ready | Medium | Low | Provide keyword suggestions, examples |

## Why This Feature Now

1. **Critical conversion problem**: 78% bounce rate with 86s homepage engagement = lost opportunity
2. **No returning users**: Only 2 out of 125 users return (1.6%) = no activation
3. **Foundation for growth**: All other features are useless if users don't activate
4. **Low complexity**: Frontend-only, no external dependencies
5. **Complements FEAT-016**: Interactive tools bring users; onboarding activates them

## Differentiation from Existing Features

- **FEAT-016 (Interactive SEO Tools)**: Attracts visitors, captures leads
- **FEAT-017 (User Onboarding)**: Activates registered users, reduces bounce

This is the **activation piece** of the user journey: Attract → Capture → **Activate** → Retain → Monetize.

## Implementation Phases

### Phase 1: MVP Onboarding (Week 1)
- Welcome modal on registration
- Step 1: Website URL input
- Step 2: First keyword input
- Step 5: "All set" summary

### Phase 2: Enhanced Experience (Week 2)
- Step 3: Ranking preview (mock or real)
- Step 4: Alert preferences
- Progress bar and skip functionality

### Phase 3: Optimization (Week 3)
- Resume onboarding prompts
- Analytics tracking
- A/B testing framework for steps

### Phase 4: Smart Suggestions (Week 4)
- Keyword suggestions from URL
- Competitor suggestions
- Industry-specific onboarding paths

## Notes

This feature addresses the most critical business problem: converting interested visitors (86s homepage engagement) into active users. Without activation, all other features are meaningless.

---

*Created by Innovator agent on 2026-02-21*
