# TESTS/SPEC-7-14.md — Tests for Alert Integration for Feature Changes

## Task Reference
- Task: [TASK-7-14](../TASKS/TASK-7-14.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Medium
- Priority: Medium

---

## Overview

Tests for integrating SERP feature changes with the existing alert system (FEAT-002). When a feature is gained or lost, alerts should be generated.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/services/serp-feature-alert.test.js` | Alert service unit tests |
| `tests/integration/serp-feature-alerts.test.js` | Alert integration tests |

---

## Test Scenarios

### 1. Alert Types

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| AT-001 | serp_feature_gained defined | Alert type exists in system |
| AT-002 | serp_feature_lost defined | Alert type exists in system |
| AT-003 | Alert type validation | Invalid type rejected |

### 2. Alert Generation - Gained

| ID | Scenario | Trigger | Expected Alert |
|----|----------|---------|----------------|
| AG-001 | Featured snippet gained | Change event | Alert generated |
| AG-002 | Local pack gained | Change event | Alert generated |
| AG-003 | Any feature gained | Change event | Alert with correct type |
| AG-004 | Alert includes keyword | Feature gained | keyword name in alert |
| AG-005 | Alert includes feature type | Feature gained | featureType in alert |
| AG-006 | Alert includes timestamp | Feature gained | detectedAt in alert |

### 3. Alert Generation - Lost

| ID | Scenario | Trigger | Expected Alert |
|----|----------|---------|----------------|
| AL-001 | Featured snippet lost | Change event | Alert generated |
| AL-002 | Local pack lost | Change event | Alert generated |
| AL-003 | Any feature lost | Change event | Alert with correct type |
| AL-004 | Alert includes keyword | Feature lost | keyword name in alert |
| AL-005 | Alert includes feature type | Feature lost | featureType in alert |
| AL-006 | Alert includes previous URL | Feature lost | previousUrl in alert |

### 4. Alert Templates

| ID | Scenario | Alert Type | Expected Content |
|----|----------|------------|------------------|
| TM-001 | Gained subject | serp_feature_gained | Contains feature name |
| TM-002 | Gained body | serp_feature_gained | Contains keyword, CTR impact |
| TM-003 | Lost subject | serp_feature_lost | Contains feature name |
| TM-004 | Lost body | serp_feature_lost | Contains keyword |
| TM-005 | Template variables replaced | Both types | {{keyword}} replaced with actual |

### 5. User Preferences

| ID | Scenario | User Setting | Expected Behavior |
|----|----------|--------------|-------------------|
| UP-001 | Alerts enabled | serpFeatureAlerts: true | Alerts sent |
| UP-002 | Alerts disabled | serpFeatureAlerts: false | No alerts sent |
| UP-003 | Specific features only | featured_snippet only | Only snippet alerts |
| UP-004 | Per-site settings | Site A: on, Site B: off | Respects per-site |

### 6. Site Notification Settings

| ID | Scenario | Site Setting | Expected Behavior |
|----|----------|--------------|-------------------|
| SN-001 | Email enabled | emailNotifications: true | Email sent |
| SN-002 | Email disabled | emailNotifications: false | No email |
| SN-003 | In-app enabled | inAppNotifications: true | In-app alert |
| SN-004 | In-app disabled | inAppNotifications: false | No in-app |

### 7. Alert Delivery

| ID | Scenario | Channel | Expected Behavior |
|----|----------|---------|-------------------|
| AD-001 | Email delivery | Email | Email sent with template |
| AD-002 | In-app delivery | In-app | Alert stored and visible |
| AD-003 | Both channels | Email + In-app | Both delivered |
| AD-004 | Delivery failure | Email fails | Logged, in-app still works |

### 8. Event Integration

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| EI-001 | Event emitted on gain | 'serp_feature_gained' event |
| EI-002 | Event emitted on loss | 'serp_feature_lost' event |
| EI-003 | Event contains correct data | keywordId, featureType, detectedAt |
| EI-004 | Listener processes event | Alert created from event |

### 9. Deduplication

| ID | Scenario | Data Setup | Expected Behavior |
|----|----------|------------|-------------------|
| DD-001 | Same gain in short time | 2 gains within 1 min | Single alert |
| DD-002 | Multiple features same keyword | 2 features gained | 2 alerts (different types) |
| DD-003 | Rapid gain/loss/gain | Quick succession | Last state alert |

### 10. Error Handling

| ID | Scenario | Error | Expected Behavior |
|----|----------|-------|-------------------|
| EH-001 | Alert service unavailable | Connection error | Logged, retry queued |
| EH-002 | Invalid event data | Missing keywordId | Alert not created, logged |
| EH-003 | Template rendering fails | Missing variable | Fallback template used |

---

## Mock Data

### Feature Gained Event
```javascript
{
  type: 'serp_feature_gained',
  keywordId: 1,
  keyword: 'seo tools',
  featureType: 'featured_snippet',
  featureName: 'Featured Snippet',
  currentUrl: 'https://ours.com/seo-tools',
  detectedAt: new Date('2026-02-26T10:00:00Z')
}
```

### Feature Lost Event
```javascript
{
  type: 'serp_feature_lost',
  keywordId: 5,
  keyword: 'keyword tracker',
  featureType: 'local_pack',
  featureName: 'Local Pack',
  previousUrl: 'https://ours.com/location',
  detectedAt: new Date('2026-02-26T09:00:00Z')
}
```

### Expected Email (Gained)
```
Subject: 🎉 New SERP Feature: Featured Snippet

Your page now appears in the Featured Snippet for "seo tools". 
This can increase CTR by +30-50%.

Detected: 2026-02-26 10:00 UTC
URL: https://ours.com/seo-tools
```

### Expected Email (Lost)
```
Subject: ⚠️ Lost SERP Feature: Local Pack

Your page no longer appears in the Local Pack for "keyword tracker".
Check your page and competitor activity.

Lost: 2026-02-26 09:00 UTC
Previous URL: https://ours.com/location
```

---

## Coverage Targets

| Component | Target |
|-----------|--------|
| Alert type definition | 100% |
| Gained alerts | 100% |
| Lost alerts | 100% |
| Templates | 100% |
| User preferences | 100% |
| Site settings | 100% |
| Delivery | 100% |
| Error handling | 100% |

---

## Test Execution

```bash
npm test -- tests/unit/services/serp-feature-alert.test.js
npm test -- tests/integration/serp-feature-alerts.test.js
```

---

## Dependencies

- TASK-7-8 (Change tracker)
- TASK-7-13 (Workflow integration)
- FEAT-002 (Alert system)

---

## Comments

### [2026-02-26 18:52] Agent: PreTester
Created test documentation for TASK-7-14 Alert Integration.

Test coverage: 37 scenarios covering alert types, generation, templates, user preferences, site settings, delivery, events, deduplication, and error handling.

Ready for Developer implementation.
