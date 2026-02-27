# SPEC-006: API Access & Webhooks

## Overview
This specification defines the technical implementation for REST API access and webhook notifications. This enables programmatic integration with external systems, automation tools, and custom dashboards.

## STATUS
`Prioritization In Progress`

## Feature Reference
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)
- Created: 2026-02-19
- Architect: Architect
- Extends: All previous features

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    API & Webhooks Layer                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  External    │     │   API       │     │  Rate       │   │
│  │  Clients     │────▶│   Gateway   │────▶│  Limiter    │   │
│  └─────────────┘     └─────────────┘     └──────┬──────┘   │
│                                                 │          │
│  ┌──────────────────────────────────────────────┘          │
│  │                                                          │
│  ▼                                                          │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │   Auth      │     │  Services   │     │  Webhook    │   │
│  │   Layer     │     │  (Existing) │     │  Dispatcher │   │
│  └─────────────┘     └─────────────┘     └──────┬──────┘   │
│                                                 │          │
│  ┌──────────────────────────────────────────────┘          │
│  │                                                          │
│  ▼                                                          │
│  ┌─────────────┐     ┌─────────────┐                        │
│  │   API Keys  │     │  Webhook    │                        │
│  │   Table     │     │  Logs       │                        │
│  └─────────────┘     └─────────────┘                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### API Keys Table
```sql
CREATE TABLE api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    key_hash TEXT NOT NULL UNIQUE,
    key_prefix TEXT NOT NULL,              -- First 8 chars for identification
    permissions TEXT,                       -- JSON: ["keywords:read", "keywords:write"]
    rate_limit INTEGER DEFAULT 60,         -- Requests per minute
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_api_keys_user ON api_keys(user_id);
CREATE INDEX idx_api_keys_prefix ON api_keys(key_prefix);
CREATE INDEX idx_api_keys_revoked ON api_keys(revoked_at);
```

### Webhooks Table
```sql
CREATE TABLE webhooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    url TEXT NOT NULL,
    secret TEXT NOT NULL,                   -- For signature verification
    events TEXT NOT NULL,                   -- JSON: ["ranking.changed", "alert.triggered"]
    enabled INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_webhooks_user ON webhooks(user_id);
CREATE INDEX idx_webhooks_enabled ON webhooks(enabled);
```

### Webhook Deliveries Table
```sql
CREATE TABLE webhook_deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    webhook_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,
    payload TEXT NOT NULL,                  -- JSON payload
    response_status INTEGER,
    response_body TEXT,
    attempt_count INTEGER DEFAULT 1,
    delivered_at DATETIME,
    next_retry_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
);

CREATE INDEX idx_webhook_deliveries_webhook ON webhook_deliveries(webhook_id);
CREATE INDEX idx_webhook_deliveries_retry ON webhook_deliveries(next_retry_at);
CREATE INDEX idx_webhook_deliveries_status ON webhook_deliveries(response_status);
```

### API Request Logs Table
```sql
CREATE TABLE api_request_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key_id INTEGER NOT NULL,
    endpoint TEXT NOT NULL,
    method TEXT NOT NULL,
    status_code INTEGER,
    response_time_ms INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
);

CREATE INDEX idx_api_logs_key ON api_request_logs(api_key_id);
CREATE INDEX idx_api_logs_created ON api_request_logs(created_at);
```

---

## API Endpoints

### Authentication
All API requests require `Authorization: Bearer <api_key>` header.

### Keywords API

#### List Keywords
```http
GET /api/v1/keywords

Query Parameters:
  - site_id: number
  - status: string (active, paused, all)
  - page: number (default: 1)
  - per_page: number (default: 20, max: 100)

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "seo tools",
      "site_id": 1,
      "target_url": "https://example.com/seo-tools",
      "search_engine": "google",
      "location": "us",
      "device": "desktop",
      "status": "active",
      "current_position": 5,
      "previous_position": 7,
      "last_checked_at": "2026-02-19T17:00:00Z"
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "per_page": 20,
    "total_pages": 3
  }
}
```

#### Create Keyword
```http
POST /api/v1/keywords
Content-Type: application/json

{
  "site_id": 1,
  "keyword": "seo tools",
  "target_url": "https://example.com/seo-tools",
  "search_engine": "google",
  "location": "us",
  "device": "desktop"
}

Response 201:
{
  "success": true,
  "data": {
    "id": 1,
    "keyword": "seo tools",
    ...
  }
}

Response 400:
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input",
    "details": [
      { "field": "keyword", "message": "Keyword is required" }
    ]
  }
}
```

#### Get Keyword
```http
GET /api/v1/keywords/:id

Response 200:
{
  "success": true,
  "data": {
    "id": 1,
    "keyword": "seo tools",
    ...
  }
}

Response 404:
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Keyword not found"
  }
}
```

#### Update Keyword
```http
PUT /api/v1/keywords/:id
Content-Type: application/json

{
  "status": "paused"
}

Response 200:
{
  "success": true,
  "data": { ... }
}
```

#### Delete Keyword
```http
DELETE /api/v1/keywords/:id

Response 204 (No Content)
```

### Rankings API

#### Get Ranking History
```http
GET /api/v1/keywords/:id/rankings

Query Parameters:
  - from: ISO date
  - to: ISO date
  - limit: number

Response 200:
{
  "success": true,
  "data": {
    "keyword_id": 1,
    "history": [
      {
        "position": 5,
        "checked_at": "2026-02-19T17:00:00Z"
      }
    ]
  }
}
```

### Sites API

#### List Sites
```http
GET /api/v1/sites

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://example.com",
      "name": "Example Site",
      "keyword_count": 50,
      "created_at": "2026-02-01T00:00:00Z"
    }
  ]
}
```

### Reports API

#### Generate Report
```http
POST /api/v1/reports
Content-Type: application/json

{
  "site_id": 1,
  "type": "weekly",
  "format": "pdf"
}

Response 202:
{
  "success": true,
  "data": {
    "job_id": "rpt-abc123",
    "status": "queued"
  }
}
```

### Webhooks Management API

#### List Webhooks
```http
GET /api/v1/webhooks

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Slack Integration",
      "url": "https://hooks.slack.com/...",
      "events": ["ranking.changed"],
      "enabled": true,
      "created_at": "2026-02-01T00:00:00Z"
    }
  ]
}
```

#### Create Webhook
```http
POST /api/v1/webhooks
Content-Type: application/json

{
  "name": "Slack Integration",
  "url": "https://hooks.slack.com/services/...",
  "events": ["ranking.changed", "alert.triggered"]
}

Response 201:
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Slack Integration",
    "secret": "whsec_abc123...",    -- Show once on creation
    ...
  }
}
```

#### Test Webhook
```http
POST /api/v1/webhooks/:id/test

Response 200:
{
  "success": true,
  "data": {
    "delivered": true,
    "response_status": 200,
    "response_time_ms": 150
  }
}
```

---

## Webhook Events

### Event Types
| Event | Trigger | Description |
|-------|---------|-------------|
| `ranking.changed` | Position change | Keyword ranking position changed |
| `alert.triggered` | Alert rule match | Alert condition met |
| `report.completed` | Report ready | Report generation finished |
| `keyword.added` | New keyword | Keyword added to tracking |
| `keyword.removed` | Keyword deleted | Keyword removed from tracking |

### Event Payload Structure
```json
{
  "id": "evt_abc123",
  "type": "ranking.changed",
  "created_at": "2026-02-19T17:00:00Z",
  "data": {
    "keyword_id": 1,
    "keyword": "seo tools",
    "site_id": 1,
    "old_position": 7,
    "new_position": 5,
    "change": -2,
    "checked_at": "2026-02-19T17:00:00Z"
  }
}
```

### Webhook Security
```typescript
// Signature header
// X-SEOIndicator-Signature: sha256=<hmac_hash>
// X-SEOIndicator-Timestamp: <unix_timestamp>

function verifyWebhook(
  payload: string,
  signature: string,
  secret: string
): boolean {
  const expectedSignature = crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');
  
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(`sha256=${expectedSignature}`)
  );
}
```

---

## Rate Limiting

### Implementation
```typescript
interface RateLimitConfig {
  windowMs: number;      // Time window in milliseconds
  maxRequests: number;   // Max requests per window
  keyGenerator: (req: Request) => string;
}

const defaultLimits: Record<string, RateLimitConfig> = {
  starter: { windowMs: 60000, maxRequests: 60 },
  pro: { windowMs: 60000, maxRequests: 300 },
  enterprise: { windowMs: 60000, maxRequests: 1000 }
};
```

### Rate Limit Headers
```http
X-RateLimit-Limit: 300
X-RateLimit-Remaining: 295
X-RateLimit-Reset: 1708368300
```

### Rate Limit Exceeded Response
```http
HTTP/1.1 429 Too Many Requests
Content-Type: application/json

{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Retry after 30 seconds.",
    "retry_after": 30
  }
}
```

---

## API Key Management

### Key Generation
```typescript
function generateApiKey(): { key: string; hash: string; prefix: string } {
  // Generate random 32-byte key
  const randomBytes = crypto.randomBytes(32);
  const key = `sk_live_${randomBytes.toString('base64url')}`;
  
  // Hash for storage
  const hash = crypto.createHash('sha256').update(key).digest('hex');
  
  // Prefix for identification (first 12 chars after prefix)
  const prefix = key.substring(0, 20);
  
  return { key, hash, prefix };
}
```

### Key Validation Middleware
```typescript
async function validateApiKey(req: Request, res: Response, next: NextFunction) {
  const authHeader = req.headers.authorization;
  
  if (!authHeader?.startsWith('Bearer ')) {
    return res.status(401).json({
      success: false,
      error: { code: 'UNAUTHORIZED', message: 'Missing API key' }
    });
  }
  
  const key = authHeader.substring(7);
  const keyHash = crypto.createHash('sha256').update(key).digest('hex');
  
  const apiKey = await apiKeyRepo.findByHash(keyHash);
  
  if (!apiKey || apiKey.revoked_at) {
    return res.status(401).json({
      success: false,
      error: { code: 'INVALID_API_KEY', message: 'Invalid or revoked API key' }
    });
  }
  
  // Check rate limit
  const allowed = await rateLimiter.check(apiKey.id, apiKey.rate_limit);
  if (!allowed) {
    return res.status(429).json({
      success: false,
      error: { code: 'RATE_LIMIT_EXCEEDED', message: 'Rate limit exceeded' }
    });
  }
  
  // Attach to request
  req.apiKey = apiKey;
  next();
}
```

---

## Implementation Files

### Backend Structure
```
backend/
├── src/
│   ├── api/
│   │   ├── routes/
│   │   │   ├── keywords.ts
│   │   │   ├── rankings.ts
│   │   │   ├── sites.ts
│   │   │   ├── reports.ts
│   │   │   └── webhooks.ts
│   │   │
│   │   ├── middleware/
│   │   │   ├── auth.ts
│   │   │   └── rate-limit.ts
│   │   │
│   │   └── openapi.yaml
│   │
│   ├── services/
│   │   ├── api-key-service.ts
│   │   └── webhook-dispatcher.ts
│   │
│   ├── repositories/
│   │   ├── api-key-repo.ts
│   │   └── webhook-repo.ts
│   │
│   └── workers/
│       └── webhook-worker.ts
│
└── tests/
    ├── unit/
    │   ├── api-key-service.test.ts
    │   └── webhook-dispatcher.test.ts
    │
    └── integration/
        └── api.test.ts
```

---

## Task Breakdown

### Phase 1: API Key Management
1. **TASK-006-01**: Database schema - api_keys table
2. **TASK-006-02**: API key model and repository
3. **TASK-006-03**: API key generation and validation
4. **TASK-006-04**: API key management endpoints (create, list, revoke)

### Phase 2: Rate Limiting
5. **TASK-006-05**: Rate limiter middleware
6. **TASK-006-06**: Request logging
7. **TASK-006-07**: Rate limit headers

### Phase 3: REST API
8. **TASK-006-08**: Keywords API endpoints
9. **TASK-006-09**: Rankings API endpoints
10. **TASK-006-10**: Sites API endpoints
11. **TASK-006-11**: Reports API endpoints

### Phase 4: Webhooks
12. **TASK-006-12**: Webhooks database schema
13. **TASK-006-13**: Webhook management API
14. **TASK-006-14**: Webhook dispatcher service
15. **TASK-006-15**: Webhook retry logic
16. **TASK-006-16**: Event emitters integration

### Phase 5: Documentation
17. **TASK-006-17**: OpenAPI specification
18. **TASK-006-18**: API documentation page

### Phase 6: Testing
19. **TASK-006-19**: Unit tests
20. **TASK-006-20**: Integration tests

---

## MVP Scope

### In Scope (MVP)
- API key management
- Basic rate limiting (60 req/min)
- Keywords API (CRUD)
- Rankings API (read)
- Webhooks for ranking.changed only
- Basic API documentation

### Post-MVP
- Tiered rate limits
- Reports API
- All webhook events
- OpenAPI spec
- SDK/client libraries
- Webhook delivery dashboard

---

## Comments

### [2026-02-19 23:38] Architect: Specification Created
- Database schema: api_keys, webhooks, webhook_deliveries, api_request_logs
- API endpoints: Keywords, Rankings, Sites, Reports, Webhooks
- Rate limiting with configurable limits
- Webhook security with HMAC signatures
- 20 implementation tasks across 6 phases
- Status: To Break Into Tasks
- Extends all previous features with programmatic access
