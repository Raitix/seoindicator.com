# SPEC-001: Keyword Rank Tracking

## Overview

This specification defines the technical implementation for keyword rank tracking functionality in SEOIndicator. This is the core feature that enables users to monitor their search engine positions over time.

## Status
`To Develop`

## Feature Reference
- Feature: [FEAT-001](../FEATURES/FEAT-001.md)
- Created: 2026-02-19
- Architect: Architect

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Keyword Rank Tracking                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │   Frontend  │────▶│    API      │────▶│  Services   │   │
│  │   (React)   │     │  (Fastify)  │     │  (Backend)  │   │
│  └─────────────┘     └─────────────┘     └──────┬──────┘   │
│                                                 │          │
│  ┌──────────────────────────────────────────────┘          │
│  │                                                          │
│  ▼                                                          │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  Scheduler  │────▶│   Queue     │────▶│  SERP       │   │
│  │  (Cron)     │     │  (BullMQ)   │     │  Fetcher    │   │
│  └─────────────┘     └─────────────┘     └──────┬──────┘   │
│                                                 │          │
│  ┌──────────────────────────────────────────────┘          │
│  │                                                          │
│  ▼                                                          │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Database (SQLite/PostgreSQL)            │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Tech Stack (MVP)
- **Backend**: Node.js + TypeScript + Fastify
- **Frontend**: Next.js + React + TypeScript
- **Database**: SQLite (migratable to PostgreSQL)
- **Queue**: BullMQ with Redis (or SQLite-based queue for MVP)
- **SERP Source**: Mock data for MVP, free tier APIs for production

---

## Database Schema

### Keywords Table
```sql
CREATE TABLE keywords (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,              -- Future: multi-tenant
    site_id INTEGER NOT NULL,              -- Associated website
    keyword TEXT NOT NULL,
    target_url TEXT,                       -- User's target URL
    search_engine TEXT NOT NULL DEFAULT 'google',
    location TEXT NOT NULL DEFAULT 'us',   -- Country code
    device TEXT NOT NULL DEFAULT 'desktop',
    status TEXT NOT NULL DEFAULT 'active', -- active, paused, deleted
    last_checked_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE INDEX idx_keywords_site_id ON keywords(site_id);
CREATE INDEX idx_keywords_status ON keywords(status);
CREATE INDEX idx_keywords_last_checked ON keywords(last_checked_at);
CREATE UNIQUE INDEX idx_keywords_unique ON keywords(site_id, keyword, search_engine, location, device);
```

### Ranking History Table
```sql
CREATE TABLE ranking_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    keyword_id INTEGER NOT NULL,
    position INTEGER,                      -- NULL = not in top 100
    url_found TEXT,                        -- Actual ranking URL
    serp_features TEXT,                    -- JSON: featured snippet, local pack, etc.
    checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    check_duration_ms INTEGER,             -- Performance tracking
    error TEXT,                            -- Error message if failed
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE
);

CREATE INDEX idx_ranking_keyword_id ON ranking_history(keyword_id);
CREATE INDEX idx_ranking_checked_at ON ranking_history(checked_at);
CREATE INDEX idx_ranking_keyword_date ON ranking_history(keyword_id, checked_at);
```

### Sites Table (Reference)
```sql
CREATE TABLE sites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url TEXT NOT NULL UNIQUE,
    name TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## API Endpoints

### Keywords CRUD

#### List Keywords
```http
GET /api/v1/sites/:siteId/keywords

Query Parameters:
  - status: string (active, paused, all)
  - page: number (default: 1)
  - perPage: number (default: 20, max: 100)
  - sortBy: string (keyword, position, lastChecked)
  - sortOrder: string (asc, desc)

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "seo tools",
      "targetUrl": "https://example.com/seo-tools",
      "searchEngine": "google",
      "location": "us",
      "device": "desktop",
      "status": "active",
      "currentPosition": 5,
      "previousPosition": 7,
      "positionChange": -2,
      "lastCheckedAt": "2026-02-19T17:00:00Z"
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "perPage": 20
  }
}
```

#### Add Keyword
```http
POST /api/v1/sites/:siteId/keywords
Content-Type: application/json

{
  "keyword": "seo tools",
  "targetUrl": "https://example.com/seo-tools",
  "searchEngine": "google",
  "location": "us",
  "device": "desktop"
}

Response 201:
{
  "success": true,
  "data": {
    "id": 1,
    "keyword": "seo tools",
    "targetUrl": "https://example.com/seo-tools",
    "searchEngine": "google",
    "location": "us",
    "device": "desktop",
    "status": "active",
    "createdAt": "2026-02-19T17:00:00Z"
  }
}
```

#### Update Keyword
```http
PUT /api/v1/keywords/:id
Content-Type: application/json

{
  "targetUrl": "https://example.com/new-seo-tools",
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
DELETE /api/v1/keywords/:id?keepHistory=true

Response 204 (No Content)
```

### Ranking Operations

#### Get Ranking History
```http
GET /api/v1/keywords/:id/history

Query Parameters:
  - from: ISO date (default: 30 days ago)
  - to: ISO date (default: now)
  - limit: number (default: 100)

Response 200:
{
  "success": true,
  "data": {
    "keyword": {
      "id": 1,
      "keyword": "seo tools"
    },
    "history": [
      {
        "position": 5,
        "urlFound": "https://example.com/seo-tools",
        "checkedAt": "2026-02-19T17:00:00Z"
      },
      {
        "position": 7,
        "urlFound": "https://example.com/seo-tools",
        "checkedAt": "2026-02-18T17:00:00Z"
      }
    ],
    "summary": {
      "currentPosition": 5,
      "bestPosition": 3,
      "worstPosition": 15,
      "averagePosition": 7.2,
      "totalChecks": 30
    }
  }
}
```

#### Manual Rank Check
```http
POST /api/v1/keywords/:id/check

Response 202:
{
  "success": true,
  "data": {
    "jobId": "check-abc123",
    "status": "queued"
  }
}
```

### Dashboard

#### Get Dashboard Summary
```http
GET /api/v1/sites/:siteId/dashboard

Response 200:
{
  "success": true,
  "data": {
    "totalKeywords": 50,
    "activeKeywords": 45,
    "rankedKeywords": 38,
    "notRankedKeywords": 7,
    "averagePosition": 12.5,
    "positionChanges": {
      "improved": 15,
      "declined": 8,
      "unchanged": 20,
      "new": 2
    },
    "topKeywords": [
      {
        "keyword": "seo tools",
        "position": 3,
        "change": -2
      }
    ],
    "alerts": [
      {
        "type": "dropped",
        "keyword": "keyword tracker",
        "previousPosition": 5,
        "currentPosition": 25,
        "change": 20
      }
    ]
  }
}
```

---

## SERP Fetcher Service

### FREE Implementation Options

#### Option 1: Mock Data (MVP/Development)
```typescript
interface MockSERPFetcher {
  fetch(keyword: string, options: SERPOptions): Promise<SERPResult>;
}

// Returns simulated SERP results for testing
// Randomized positions within realistic ranges
// No external API dependency
```

#### Option 2: Google Custom Search API (Free Tier)
- **Free Quota**: 100 queries/day
- **Limitation**: Only 10 results per query
- **Use Case**: Development and low-volume production

```typescript
interface GoogleCSEFetcher {
  fetch(keyword: string, options: SERPOptions): Promise<SERPResult>;
}
```

#### Option 3: Self-Hosted Scraper (Production)
- **Library**: Playwright or Puppeteer
- **Rate Limiting**: Respect robots.txt, add delays
- **Risk**: IP blocking, CAPTCHA challenges
- **Mitigation**: Proxy rotation, user-agent rotation

```typescript
interface ScraperSERPFetcher {
  fetch(keyword: string, options: SERPOptions): Promise<SERPResult>;
}
```

### SERP Result Structure
```typescript
interface SERPResult {
  keyword: string;
  searchEngine: string;
  location: string;
  device: string;
  results: SERPEntry[];
  checkedAt: Date;
  error?: string;
}

interface SERPEntry {
  position: number;
  url: string;
  title: string;
  description: string;
  isOurSite: boolean;  // Matches target URL
}

interface SERPOptions {
  searchEngine: 'google' | 'bing';
  location: string;
  device: 'desktop' | 'mobile';
  targetUrl?: string;
}
```

---

## Job Queue System

### Queue Configuration
```typescript
// For MVP: SQLite-based job queue
// For Production: Redis + BullMQ

interface CheckJob {
  id: string;
  type: 'single' | 'batch' | 'site';
  keywordId?: number;
  siteId?: number;
  priority: 'high' | 'normal' | 'low';
  status: 'pending' | 'running' | 'completed' | 'failed';
  attempts: number;
  maxAttempts: number;
  createdAt: Date;
  startedAt?: Date;
  completedAt?: Date;
  error?: string;
}
```

### Scheduler Configuration
```typescript
interface SchedulerConfig {
  enabled: boolean;
  defaultIntervalMinutes: number;  // Default: 1440 (24h)
  maxConcurrent: number;           // Max concurrent checks
  retryAttempts: number;           // Failed job retries
  retryDelayMs: number;            // Delay between retries
}

const defaultConfig: SchedulerConfig = {
  enabled: true,
  defaultIntervalMinutes: 1440,
  maxConcurrent: 5,
  retryAttempts: 3,
  retryDelayMs: 60000
};
```

---

## Implementation Files

### Backend Structure
```
backend/
├── src/
│   ├── api/
│   │   └── routes/
│   │       ├── keywords.ts
│   │       ├── rankings.ts
│   │       └── dashboard.ts
│   │
│   ├── services/
│   │   ├── keyword-service.ts
│   │   ├── ranking-service.ts
│   │   └── dashboard-service.ts
│   │
│   ├── workers/
│   │   ├── rank-checker.ts
│   │   └── scheduler.ts
│   │
│   ├── fetchers/
│   │   ├── serp-fetcher.ts        # Interface
│   │   ├── mock-fetcher.ts        # MVP implementation
│   │   └── google-cse-fetcher.ts  # Free tier option
│   │
│   ├── repositories/
│   │   ├── keyword-repo.ts
│   │   └── ranking-repo.ts
│   │
│   └── models/
│       ├── keyword.ts
│       └── ranking.ts
│
└── tests/
    ├── unit/
    └── integration/
```

### Frontend Structure
```
frontend/
├── src/
│   ├── app/
│   │   └── (dashboard)/
│   │       └── sites/
│   │           └── [siteId]/
│   │               └── keywords/
│   │                   ├── page.tsx
│   │                   ├── [keywordId]/
│   │                   │   └── page.tsx
│   │                   └── add/
│   │                       └── page.tsx
│   │
│   ├── components/
│   │   ├── keyword-list.tsx
│   │   ├── keyword-form.tsx
│   │   ├── ranking-chart.tsx
│   │   └── dashboard-summary.tsx
│   │
│   └── lib/
│       └── api/
│           └── keywords.ts
```

---

## Task Breakdown

### Phase 1: Core Infrastructure
1. **TASK-001-01**: Database setup - Create tables and migrations
2. **TASK-001-02**: Keyword model and repository
3. **TASK-001-03**: Ranking model and repository

### Phase 2: API Layer
4. **TASK-001-04**: Keywords CRUD API endpoints
5. **TASK-001-05**: Ranking history API endpoints
6. **TASK-001-06**: Dashboard API endpoint

### Phase 3: SERP Integration
7. **TASK-001-07**: SERP fetcher interface and mock implementation
8. **TASK-001-08**: Google CSE fetcher (free tier)
9. **TASK-001-09**: Self-hosted scraper (optional)

### Phase 4: Background Processing
10. **TASK-001-10**: Job queue setup (SQLite-based for MVP)
11. **TASK-001-11**: Rank checker worker
12. **TASK-001-12**: Scheduler for automated checks

### Phase 5: Frontend
13. **TASK-001-13**: Keyword list page
14. **TASK-001-14**: Add/edit keyword forms
15. **TASK-001-15**: Ranking history chart component
16. **TASK-001-16**: Dashboard summary component

### Phase 6: Testing & Polish
17. **TASK-001-17**: Unit tests for services
18. **TASK-001-18**: Integration tests for API
19. **TASK-001-19**: E2E tests for critical flows
20. **TASK-001-20**: Performance optimization

---

## MVP Scope Definition

### In Scope (MVP)
- Single search engine (Google)
- Desktop results only
- Daily updates only
- Top 100 results
- Mock SERP data for development
- Basic dashboard

### Post-MVP Enhancements
- Multiple search engines (Bing, Yahoo)
- Mobile results
- Hourly/real-time updates
- Extended result tracking (top 200+)
- Real SERP API integration
- Advanced analytics and alerts
- Competitor tracking
- Local SEO features

---

## Risk Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| SERP API rate limits | High | Implement caching, use mock data for dev |
| Database growth | Medium | Implement data archiving, limit history retention |
| Job queue failures | Medium | Retry logic, dead letter queue |
| IP blocking (scraper) | High | Use API first, scraper as fallback |
| Position accuracy | Medium | Cross-reference with multiple sources |

---

## Success Metrics

1. **Feature Completion**: All acceptance criteria from FEAT-001 met
2. **Performance**: Keyword list loads in <500ms
3. **Reliability**: 99% successful rank checks
4. **User Adoption**: Users add average 20+ keywords within first week
5. **Engagement**: Users check dashboard 3+ times per week

---

## Comments

### [2026-02-22 16:00] Agent: Developer
**Implementation Progress**

- Completed tasks SPEC-001-1..3 (migrations, keyword/ranking models+repos, factories)
- Tests: php artisan test --filter "KeywordTest|RankingTest|KeywordRepositoryTest|RankingRepositoryTest" ✅
- Tasks 1-1..1-3 set to **To Test Locally** for LocalTester
- Remaining tasks 4-20 still **To Develop**

### [2026-02-20 19:30] Agent: PreTester
**Test Coverage Design Complete** ✓

Created comprehensive test documentation and test files for all 20 tasks.

**Test Documentation Created:**
| File | Description |
|------|-------------|
| TEST-001-1.md | Database Migration Tests (7 scenarios) |
| TEST-001-2.md | Keyword Model/Repository Tests (22 scenarios) |
| TEST-001-3.md | Ranking Model/Repository Tests (16 scenarios) |
| TEST-001-4.md | Keywords CRUD API Tests (43 scenarios) |
| TEST-001-5.md | Ranking History API Tests (14 scenarios) |
| TEST-001-6.md | Dashboard API Tests (24 scenarios) |
| TEST-001-7.md | SERP Fetcher Mock Tests (17 scenarios) |
| TEST-001-8.md | Google CSE Fetcher Tests (14 scenarios) |
| TEST-001-9.md | Self-Hosted Scraper Tests (18 scenarios) |
| TEST-001-10.md | Job Queue Tests (22 scenarios) |
| TEST-001-11.md | Rank Checker Worker Tests (18 scenarios) |
| TEST-001-12.md | Scheduler Tests (15 scenarios) |
| TEST-001-13.md | Keyword List Page Tests (23 scenarios) |
| TEST-001-14.md | Add/Edit Keyword Forms Tests (14 scenarios) |
| TEST-001-15.md | Ranking History Chart Tests (16 scenarios) |
| TEST-001-16.md | Dashboard Summary Component Tests (21 scenarios) |
| TEST-001-17.md | Unit Tests Coverage Requirements |
| TEST-001-18.md | API Integration Tests |
| TEST-001-19.md | E2E Tests for Critical Flows |
| TEST-001-20.md | Performance Optimization Tests |

**Test Files Created (Backend):**
- `tests/Feature/Migrations/CreateKeywordsTablesTest.php`
- `tests/Unit/Models/KeywordTest.php`
- `tests/Unit/Models/RankingTest.php`
- `tests/Unit/Repositories/KeywordRepositoryTest.php`
- `tests/Unit/Repositories/RankingRepositoryTest.php`
- `tests/Feature/Api/V1/KeywordControllerTest.php`
- `tests/Feature/Api/V1/RankingControllerTest.php`
- `tests/Feature/Api/V1/DashboardControllerTest.php`

**Adversarial Testing Coverage:**
- SQL injection protection
- XSS protection
- Constraint violation handling
- NULL position handling
- Race conditions (concurrent creates)
- Large dataset handling

**Test Priority Order (for Developer):**
1. Task 1: Database migrations (foundation)
2. Tasks 2-3: Models and repositories (data layer)
3. Tasks 4-6: API endpoints (backend core)
4. Task 7: SERP fetcher mock (enables testing)
5. Tasks 10-12: Background processing (worker/scheduler)
6. Tasks 13-16: Frontend components
7. Tasks 8-9: Optional SERP fetchers
8. Tasks 17-20: Testing and optimization

**Status Updated:** All tasks → "To Develop"
**Handoff:** → Developer for implementation

### [2026-02-20 09:55] Agent: ProjectManager
**Prioritization Complete** ✓

All 20 tasks prioritized and ready for PreTester.

**Priority Distribution:**
| Priority | Count | Tasks |
|----------|-------|-------|
| High | 14 | 1-7, 10-16 (Core MVP) |
| Medium | 4 | 8, 17-19 (Secondary) |
| Low | 2 | 9, 20 (Optional) |

**Risk Distribution:**
| Risk | Count | Notes |
|------|-------|-------|
| High | 1 | SPEC-001-9 (Self-hosted scraper - ToS risk) |
| Medium | 5 | 1, 4, 8, 10, 11 (DB, API, Queue changes) |
| Low | 14 | Models, UI, Tests |

**Critical Path (High Priority):**
```
SPEC-001-1 (DB) ──► SPEC-001-2/3 (Models) ──► SPEC-001-4/5/6 (API)
       │
       └──► SPEC-001-7 (SERP Interface) ──► SPEC-001-10/11/12 (Queue/Worker)
```

**Parallel Development Opportunities:**
- Tasks 2-3 can run in parallel after Task 1
- Tasks 4-6 can run in parallel after Tasks 2-3
- Tasks 7-9 can run in parallel with Tasks 4-6
- Tasks 13-16 (Frontend) can run in parallel after APIs ready

**⚠️ Security Note:** SPEC-001-9 (Self-Hosted Scraper) is HIGH RISK due to potential ToS violations. Recommend completing MVP with mock/Google CSE fetchers first.

**Handoff:** → PreTester for test design on all 20 tasks

### [2026-02-19 21:17] Architect: Specification Created
- Analyzed feature requirements from FEAT-001
- Designed database schema for keywords and ranking history
- Defined API endpoints for CRUD operations
- Created SERP fetcher architecture with FREE options only (mock, Google CSE, self-hosted scraper)
- Defined job queue system for background processing
- Broke down into 20 implementation tasks
- Status updated to "To Break Into Tasks"
- Ready for ProjectManager to prioritize tasks

### Critical Constraint
⚠️ **FREE TOOLS ONLY**: Per system constraints, no paid SERP APIs allowed. 
MVP will use mock data for development, with Google CSE free tier (100/day) for production testing.
