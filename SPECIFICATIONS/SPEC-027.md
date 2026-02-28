# SPEC-027: Admin Dashboard (Ops + Usage)

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`Medium`

---

## Overview

Create an admin dashboard that provides a centralized view of platform operations, user metrics, usage statistics, and system health. Enables administrators to monitor the platform's performance, track resource consumption, and identify potential issues.

---

## Architecture

### High-Level Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        Admin Dashboard Frontend                          │
├─────────────────────────────────────────────────────────────────────────┤
│  User Stats  │  Usage Metrics  │  Credit Balances  │  System Health    │
└────────┬─────┴────────┬─────────┴────────┬──────────┴────────┬──────────┘
         │              │                 │                   │
         ▼              ▼                 ▼                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         Admin API Endpoints                              │
├─────────────────────────────────────────────────────────────────────────┤
│  /admin/users  │  /admin/usage  │  /admin/credits  │  /admin/health    │
└────────┬───────┴────────┬────────┴────────┬─────────┴────────┬──────────┘
         │               │                 │                   │
         ▼               ▼                 ▼                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                           Aggregation Layer                              │
├─────────────────────────────────────────────────────────────────────────┤
│  UserAggregator │ UsageAggregator │ CreditAggregator │ HealthAggregator │
└────────┬───────┴────────┬─────────┴────────┬─────────┴────────┬──────────┘
         │               │                 │                   │
         ▼               ▼                 ▼                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          Database Tables                                 │
├─────────────────────────────────────────────────────────────────────────┤
│  users  │  credit_transactions  │  analysis_jobs  │  queues/queues     │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Admin Activity Log Table

```sql
CREATE TABLE admin_activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_admin_logs_admin_id (admin_user_id),
    INDEX idx_admin_logs_action (action),
    INDEX idx_admin_logs_created_at (created_at)
);
```

### System Metrics Cache Table

```sql
CREATE TABLE system_metrics_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_key VARCHAR(100) NOT NULL UNIQUE,
    metric_value DECIMAL(15, 2) NOT NULL,
    metadata JSON NULL,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    
    INDEX idx_metrics_key (metric_key),
    INDEX idx_metrics_expires (expires_at)
);
```

---

## API Endpoints

### Admin Routes Prefix
All admin routes are prefixed with `/api/v1/admin/*` and require admin role authorization.

### User Statistics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/users/stats` | User counts and growth metrics |
| GET | `/api/v1/admin/users/recent` | Recently registered users |
| GET | `/api/v1/admin/users/active` | Active users (logged in last 30 days) |

**Users Stats Response:**
```json
{
  "total": 1250,
  "active": 890,
  "newThisMonth": 156,
  "newThisWeek": 42,
  "newToday": 8,
  "growthRate": {
    "monthly": 14.2,
    "weekly": 5.8,
    "daily": 1.2
  },
  "byPlan": {
    "free": 1000,
    "pro": 200,
    "enterprise": 50
  }
}
```

### Usage Metrics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/usage/overview` | Aggregated usage statistics |
| GET | `/api/v1/admin/usage/requests` | Request counts by endpoint |
| GET | `/api/v1/admin/usage/tokens` | Token usage statistics |
| GET | `/api/v1/admin/usage/trends` | Usage trends over time |

**Usage Overview Response:**
```json
{
  "requests": {
    "total": 156789,
    "today": 2340,
    "thisWeek": 15680,
    "thisMonth": 48920
  },
  "tokens": {
    "total": 12500000,
    "today": 156000,
    "thisWeek": 890000,
    "thisMonth": 3200000,
    "byModel": {
      "gpt-4": 5000000,
      "gpt-3.5-turbo": 6000000,
      "claude-3": 1500000
    }
  },
  "analyses": {
    "total": 12340,
    "today": 156,
    "thisWeek": 890,
    "thisMonth": 3450
  }
}
```

### Credit & Billing Metrics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/credits/overview` | Credit balances and spend |
| GET | `/api/v1/admin/credits/transactions` | Recent transactions |
| GET | `/api/v1/admin/credits/revenue` | Revenue metrics |

**Credits Overview Response:**
```json
{
  "balances": {
    "totalHeld": 15678.50,
    "averagePerUser": 12.54,
    "usersWithBalance": 890,
    "lowBalanceCount": 234
  },
  "spend": {
    "total": 45678.90,
    "today": 567.80,
    "thisWeek": 3456.70,
    "thisMonth": 12345.60
  },
  "revenue": {
    "total": 78900.00,
    "thisMonth": 15600.00,
    "thisWeek": 3900.00,
    "today": 780.00
  }
}
```

### System Health

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/health/overview` | System health summary |
| GET | `/api/v1/admin/health/queues` | Queue status and backlog |
| GET | `/api/v1/admin/health/database` | Database metrics |
| GET | `/api/v1/admin/health/alerts` | Active alerts and warnings |

**Health Overview Response:**
```json
{
  "status": "healthy",
  "uptime": 99.98,
  "lastChecked": "2026-02-28T10:00:00Z",
  "services": {
    "database": {
      "status": "healthy",
      "latencyMs": 12,
      "connections": 45
    },
    "queue": {
      "status": "healthy",
      "pending": 23,
      "failed": 2,
      "workers": 5
    },
    "cache": {
      "status": "healthy",
      "hitRate": 94.5
    },
    "storage": {
      "status": "healthy",
      "usedPercent": 34
    }
  },
  "alerts": [
    {
      "level": "warning",
      "message": "Queue processing delayed",
      "since": "2026-02-28T09:45:00Z"
    }
  ]
}
```

---

## Backend Services

### AdminStatsService

```php
class AdminStatsService
{
    public function getUserStats(): array;
    public function getUsageStats(): array;
    public function getCreditStats(): array;
    public function getHealthStats(): array;
    public function getCachedMetric(string $key, callable $calculator, int $ttl = 300): mixed;
}
```

### AdminAuthorizationMiddleware

```php
class AdminAuthorizationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return $next($request);
    }
}
```

### MetricsAggregator

```php
class MetricsAggregator
{
    public function aggregateUsers(): array;
    public function aggregateUsage(): array;
    public function aggregateCredits(): array;
    public function aggregateHealth(): array;
    public function refreshAllCaches(): void;
}
```

---

## Frontend Components

### Dashboard Overview

- **Metric Cards**: Key metrics displayed prominently
  - Total Users
  - Active Users Today
  - Total Requests
  - Revenue This Month
  - System Health Status

### User Metrics Section

- User growth chart (line chart)
- New registrations by day/week/month
- User breakdown by plan
- Active vs inactive users

### Usage Metrics Section

- Requests over time chart
- Token usage by model (pie chart)
- Analysis count trends
- Top endpoints by usage

### Credit & Revenue Section

- Total balance held
- Spend trends
- Revenue chart
- Recent transactions table

### System Health Section

- Service status indicators (green/yellow/red)
- Queue status with pending/failed counts
- Database metrics
- Active alerts panel

---

## Security Considerations

### Authorization

| Measure | Implementation |
|---------|----------------|
| Role Check | `isAdmin()` method on User model |
| Middleware | AdminAuthorizationMiddleware on all admin routes |
| Activity Logging | All admin actions logged to admin_activity_logs |

### Data Protection

| Measure | Implementation |
|---------|----------------|
| No PII in Responses | Emails/names excluded from aggregated stats |
| Rate Limiting | 60 requests/minute for admin endpoints |
| IP Logging | All admin requests logged with IP |

### Access Control

| Role | Access Level |
|------|--------------|
| Super Admin | Full access to all admin features |
| Admin | Read-only access + user management |
| Moderator | Read-only access |

---

## Performance Considerations

### Caching Strategy

| Data | Cache TTL | Refresh Strategy |
|------|-----------|------------------|
| User counts | 5 minutes | Background refresh |
| Usage totals | 5 minutes | Background refresh |
| Credit totals | 1 minute | Background refresh |
| Health status | 30 seconds | Real-time check |

### Query Optimization

- Use materialized views for frequently accessed aggregates
- Pre-compute daily/hourly statistics via scheduled job
- Index all timestamp columns for range queries

---

## Tasks Breakdown

### Phase 1: Backend Infrastructure (3 tasks)
1. **TASK-27-1**: Create admin_activity_logs and system_metrics_cache tables migration
2. **TASK-27-2**: Implement AdminAuthorizationMiddleware with role check
3. **TASK-27-3**: Create AdminStatsService with caching layer

### Phase 2: User Statistics (2 tasks)
4. **TASK-27-4**: Implement user stats aggregation queries
5. **TASK-27-5**: Create admin user stats API endpoint

### Phase 3: Usage Metrics (3 tasks)
6. **TASK-27-6**: Implement request tracking middleware
7. **TASK-27-7**: Implement usage aggregation service
8. **TASK-27-8**: Create admin usage API endpoints

### Phase 4: Credit & Billing (2 tasks)
9. **TASK-27-9**: Implement credit/balance aggregation (depends on FEAT-026)
10. **TASK-27-10**: Create admin credits API endpoints

### Phase 5: System Health (3 tasks)
11. **TASK-27-11**: Implement health check service
12. **TASK-27-12**: Create queue monitoring integration
13. **TASK-27-13**: Create admin health API endpoints

### Phase 6: Frontend (4 tasks)
14. **TASK-27-14**: Create admin dashboard layout and routing
15. **TASK-27-15**: Create metric cards and overview section
16. **TASK-27-16**: Create usage charts and tables
17. **TASK-27-17**: Create system health monitoring panel

### Phase 7: Testing & Documentation (2 tasks)
18. **TASK-27-18**: Write unit and feature tests
19. **TASK-27-19**: Update DOCUMENTATION.md

---

## Dependencies

### Internal
- **FEAT-026**: Credits/Billing system for credit metrics
- **FEAT-019**: Job Queue system for queue metrics
- User authentication system (existing)

### External
- None (all internal data sources)

---

## Environment Variables

```env
# Admin Dashboard
ADMIN_CACHE_TTL=300
ADMIN_RATE_LIMIT=60
ADMIN_ACTIVITY_LOG_ENABLED=true
```

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Performance impact on production | Medium | Medium | Caching, read replicas |
| Unauthorized access | Low | High | Role middleware, activity logging |
| Data inconsistency | Low | Medium | Cache invalidation on updates |
| Alert fatigue | Medium | Low | Configurable thresholds |

---

## Deferred Features (Post-MVP)

- Real-time WebSocket updates for health status
- Detailed per-user analytics
- Custom dashboard configuration
- Export reports (CSV/PDF)
- Admin notification system
- Audit log viewer UI

---

## Comments

### 2026-02-28 - Architect
- Created initial technical specification
- Medium risk due to admin access requirements
- Depends on FEAT-026 credits system for billing metrics
- Caching strategy critical for performance
- Activity logging for audit trail

---
*Created by Architect from FEAT-027*
