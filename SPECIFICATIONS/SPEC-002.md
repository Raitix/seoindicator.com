# SPEC-002: Ranking Change Alerts - Technical Specification

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`Low`

## Related
- Feature: [FEAT-002](../FEATURES/FEAT-002.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Depends on: SPEC-001 (Keyword Rank Tracking)

---

## Overview

Automated notifications for significant keyword ranking changes. Users configure thresholds and delivery channels to receive proactive alerts without manual dashboard checks.

---

## Architecture

### System Design

```
┌───────────────────────────────────────────┐
│        Ranking Update Job                  │
│   (Triggers after each ranking fetch)     │
└───────────────────────────────────────────┘
                    │
                    ▼
┌───────────────────────────────────────────┐
│        AlertEvaluator Service             │
│   - Compare old vs new positions          │
│   - Check alert thresholds                │
│   - Evaluate SERP feature changes         │
└───────────────────────────────────────────┘
                    │
                    ▼
┌───────────────────────────────────────────┐
│        NotificationDispatcher             │
│   - Queue alerts for delivery             │
│   - Respect quiet hours                   │
│   - Batch for digest mode                 │
└───────────────────────────────────────────┘
                    │
          ┌─────────┴─────────┐
          ▼                   ▼
┌──────────────────┐  ┌──────────────────┐
│   Email Channel  │  │  In-App Channel  │
│  (Laravel Mail)  │  │   (Database)     │
└──────────────────┘  └──────────────────┘
```

---

## Database Schema

### Tables

#### Table: alert_settings

```sql
CREATE TABLE alert_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    global_enabled BOOLEAN DEFAULT TRUE,
    default_threshold INT UNSIGNED DEFAULT 3,
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    digest_mode ENUM('instant', 'daily', 'weekly') DEFAULT 'instant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_user (user_id)
);
```

#### Table: keyword_alert_settings

```sql
CREATE TABLE keyword_alert_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword_id BIGINT UNSIGNED NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    custom_threshold INT UNSIGNED NULL,
    alert_events JSON NULL COMMENT '["position_drop", "position_gain", "entered_top_10"]',
    notification_channels JSON NULL COMMENT '["email", "in_app"]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    UNIQUE KEY idx_keyword (keyword_id)
);
```

#### Table: notifications

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    keyword_id BIGINT UNSIGNED NULL,
    data JSON NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, read_at)
);
```

---

## Email Delivery (Free Tier)

### Options for MVP
| Provider | Free Tier | Limit | Notes |
|----------|-----------|-------|-------|
| Gmail SMTP | ✅ | ~500/day | Requires app password |
| Brevo (Sendinblue) | ✅ | 300/day | Free forever |
| Mailtrap | ✅ | 1000/month | Good for testing |
| Amazon SES | ✅ | 62,000/month | Free in sandbox |

**Recommendation**: Start with Gmail SMTP for MVP, add Brevo as scalable free option.

---

## API Design

### Alert Settings

```yaml
# Get user alert settings
GET /api/user/alert-settings
Response:
{
    "global_enabled": true,
    "default_threshold": 3,
    "quiet_hours_start": "22:00",
    "quiet_hours_end": "07:00",
    "digest_mode": "instant"
}

# Update alert settings
PUT /api/user/alert-settings
Body: { "default_threshold": 5, "digest_mode": "daily" }
Response: { "success": true }

# Get keyword alert settings
GET /api/keywords/{keywordId}/alert-settings
Response:
{
    "enabled": true,
    "custom_threshold": null,
    "alert_events": ["position_drop", "entered_top_10"],
    "notification_channels": ["email", "in_app"]
}
```

### Notifications

```yaml
# List notifications
GET /api/notifications
Query Params: { "unread_only": true }
Response:
{
    "data": [
        {
            "id": 1,
            "type": "position_drop",
            "keyword": "seo tools",
            "data": { "old_position": 5, "new_position": 12 },
            "read_at": null,
            "created_at": "2026-02-20T10:00:00"
        }
    ],
    "meta": { "unread_count": 5 }
}

# Mark as read
PATCH /api/notifications/{id}/read
Response: { "success": true }

# Mark all as read
POST /api/notifications/read-all
Response: { "success": true }
```

---

## Alert Event Types (MVP)

| Event | Trigger | Default Threshold |
|-------|---------|-------------------|
| `position_drop` | Position decreased | 3+ positions |
| `position_gain` | Position improved | 3+ positions |
| `entered_top_10` | Position ≤ 10 (was > 10) | N/A |
| `left_top_10` | Position > 10 (was ≤ 10) | N/A |

**Phase 2**: SERP feature alerts (featured snippet, local pack).

---

## Email Template

```
Subject: [SEOIndicator] Alert: "{keyword}" {change_type}

Hi {user_name},

Your keyword ranking has changed:

Keyword: {keyword}
Previous Position: {old_position}
New Position: {new_position}
Change: {change_magnitude} positions

View details: {dashboard_url}

---
You received this because you enabled ranking alerts.
Unsubscribe: {unsubscribe_url}
```

---

## Rollout Plan

### Phase 1: MVP
- Email alerts via Gmail SMTP (free)
- Instant alerts only
- Position change events
- Default 3-position threshold

### Phase 2: Enhanced
- In-app notification center
- Digest mode (daily/weekly)
- Quiet hours
- Per-keyword custom thresholds

### Phase 3: Advanced
- SERP feature alerts
- SMS notifications (paid add-on)
- AI-prioritized alerts

---

## Comments
_Agent communication log_

### [2026-02-20 19:32] Agent: Architect
Created SPEC-002 for Ranking Change Alerts. Key decisions:
- Gmail SMTP for MVP (free, ~500 emails/day)
- In-app notifications deferred to Phase 2
- MVP focuses on position changes; SERP features later
- Digest mode deferred to Phase 2
Ready for SpecSystemAnalyst breakdown.