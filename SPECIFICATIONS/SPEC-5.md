# SPEC-005: Keyword Discovery & Suggestions

## Overview
This specification defines the technical implementation for keyword discovery and suggestions functionality. This feature helps users find new keyword opportunities to track, expanding their SEO strategy.

## STATUS
`Done`

## Feature Reference
- Feature: [FEAT-005](../FEATURES/FEAT-005.md)
- Created: 2026-02-19
- Architect: Architect
- Depends On: FEAT-001 (Keyword Rank Tracking)

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                  Keyword Discovery System                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  Frontend   │────▶│    API      │────▶│  Discovery  │   │
│  │  (React)    │     │  (Fastify)  │     │  Service    │   │
│  └─────────────┘     └─────────────┘     └──────┬──────┘   │
│                                                 │          │
│  ┌──────────────────────────────────────────────┘          │
│  │                                                          │
│  ▼                                                          │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  Suggest    │     │  Competitor │     │   Domain    │   │
│  │  Fetcher    │     │  Analyzer   │     │  Scraper    │   │
│  └──────┬──────┘     └──────┬──────┘     └──────┬──────┘   │
│         │                   │                   │          │
│         └───────────────────┼───────────────────┘          │
│                             ▼                              │
│                    ┌─────────────┐                         │
│                    │  Database   │                         │
│                    └─────────────┘                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Keyword Suggestions Table
```sql
CREATE TABLE keyword_suggestions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    keyword TEXT NOT NULL,
    source TEXT NOT NULL,              -- 'suggest', 'competitor', 'related'
    search_volume INTEGER,             -- NULL in MVP
    difficulty_score INTEGER,          -- NULL in MVP (0-100)
    opportunity_score INTEGER,         -- Calculated score
    is_tracking BOOLEAN DEFAULT 0,     -- Already tracking this keyword
    is_dismissed BOOLEAN DEFAULT 0,    -- User dismissed suggestion
    discovered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE INDEX idx_suggestions_site_id ON keyword_suggestions(site_id);
CREATE INDEX idx_suggestions_keyword ON keyword_suggestions(keyword);
CREATE INDEX idx_suggestions_opportunity ON keyword_suggestions(opportunity_score DESC);
CREATE UNIQUE INDEX idx_suggestions_unique ON keyword_suggestions(site_id, keyword);
```

### Dismissed Suggestions Table
```sql
CREATE TABLE dismissed_suggestions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    keyword TEXT NOT NULL,
    dismissed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE INDEX idx_dismissed_site ON dismissed_suggestions(site_id);
```

---

## API Endpoints

### Get Suggestions
```http
GET /api/v1/sites/:siteId/suggestions

Query Parameters:
  - source: string (suggest, competitor, related, all)
  - minVolume: number
  - maxDifficulty: number
  - includeTracking: boolean (default: false)
  - page: number (default: 1)
  - perPage: number (default: 20)

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "best seo tools 2026",
      "source": "suggest",
      "searchVolume": null,
      "difficultyScore": null,
      "opportunityScore": null,
      "isTracking": false,
      "isDismissed": false,
      "discoveredAt": "2026-02-19T17:00:00Z"
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "perPage": 20
  }
}
```

### Generate Suggestions
```http
POST /api/v1/sites/:siteId/suggestions/generate
Content-Type: application/json

{
  "seedKeyword": "seo tools",
  "sources": ["suggest", "related"],
  "maxResults": 50
}

Response 202:
{
  "success": true,
  "data": {
    "jobId": "suggest-abc123",
    "status": "queued"
  }
}
```

### Add Suggestion to Tracking
```http
POST /api/v1/sites/:siteId/suggestions/:suggestionId/track

Response 201:
{
  "success": true,
  "data": {
    "keyword": {
      "id": 1,
      "keyword": "best seo tools 2026",
      "status": "active"
    },
    "suggestion": {
      "id": 1,
      "isTracking": true
    }
  }
}
```

### Bulk Add Suggestions
```http
POST /api/v1/sites/:siteId/suggestions/bulk-track
Content-Type: application/json

{
  "suggestionIds": [1, 2, 3, 4, 5]
}

Response 201:
{
  "success": true,
  "data": {
    "added": [
      { "keyword": "best seo tools 2026", "keywordId": 1 }
    ],
    "skipped": [
      { "keyword": "seo", "reason": "Already tracking" }
    ]
  }
}
```

### Dismiss Suggestion
```http
POST /api/v1/sites/:siteId/suggestions/:suggestionId/dismiss

Response 200:
{
  "success": true,
  "data": {
    "id": 1,
    "isDismissed": true
  }
}
```

### Export Suggestions
```http
GET /api/v1/sites/:siteId/suggestions/export?format=csv

Response 200 (CSV):
Content-Type: text/csv

Keyword,Source,Tracking Status
best seo tools 2026,suggest,false
seo software free,suggest,false
```

---

## Suggestion Sources

### 1. Google Suggest API (FREE - MVP)
```typescript
interface SuggestFetcher {
  fetch(keyword: string): Promise<string[]>;
}

class GoogleSuggestFetcher implements SuggestFetcher {
  async fetch(keyword: string): Promise<string[]> {
    const url = `http://suggestqueries.google.com/complete/search?client=firefox&q=${encodeURIComponent(keyword)}`;
    const response = await fetch(url);
    const [original, suggestions] = await response.json();
    return suggestions;
  }
}
```

### 2. Question Keywords (FREE)
```typescript
class QuestionGenerator {
  generate(keyword: string): string[] {
    const prefixes = ['what is', 'how to', 'why', 'when', 'where', 'who', 'which'];
    return prefixes.map(p => `${p} ${keyword}`);
  }
}
```

### 3. Related Searches (Post-MVP)
Requires scraping - more infrastructure needed

### 4. Competitor Keywords (Post-MVP)
Requires paid SEO API

---

## Opportunity Scoring

### MVP Formula (Simplified)
```typescript
function calculateOpportunityScore(keyword: string): number {
  let score = 50;
  
  const wordCount = keyword.split(' ').length;
  if (wordCount >= 3) score += 10;
  if (wordCount >= 5) score += 5;
  
  if (/^(what|how|why|when|where|who|which)/i.test(keyword)) {
    score += 15;
  }
  
  if (/best|top|review|compare/i.test(keyword)) {
    score += 10;
  }
  
  return Math.min(100, score);
}
```

---

## Task Breakdown

### Phase 1: Core Infrastructure
1. **TASK-005-01**: Database schema - keyword_suggestions table
2. **TASK-005-02**: Suggestion model and repository
3. **TASK-005-03**: Opportunity scorer utility

### Phase 2: Suggestion Sources
4. **TASK-005-04**: Google Suggest fetcher (MVP)
5. **TASK-005-05**: Question keyword generator
6. **TASK-005-06**: Suggestion aggregation service

### Phase 3: API Layer
7. **TASK-005-07**: Suggestions CRUD API endpoints
8. **TASK-005-08**: Generate suggestions endpoint
9. **TASK-005-09**: Track/dismiss endpoints

### Phase 4: Frontend
10. **TASK-005-10**: Suggestions list page
11. **TASK-005-11**: Generate suggestions form
12. **TASK-005-12**: Suggestion card component

### Phase 5: Testing
13. **TASK-005-13**: Unit tests
14. **TASK-005-14**: Integration tests

---

## MVP Scope

### In Scope (MVP)
- Google Suggest API only
- Question keyword generation
- No search volume/difficulty data
- Manual suggestion requests
- Single keyword add

### Post-MVP
- Search volume & difficulty data
- Related searches scraping
- Competitor keywords
- Bulk add operations
- Scheduled auto-refresh

---

## Comments

### [2026-03-05 19:45] Agent: DocSystemAnalyst
**Specification Complete**

All 14 tasks under SPEC-005 are now Done:
- TASK-5-1: Database schema migration ✓
- TASK-5-2: Suggestion model and repository ✓
- TASK-5-3: Opportunity scorer utility ✓
- TASK-5-4: Google Suggest fetcher ✓
- TASK-5-5: Question keyword generator ✓
- TASK-5-6: Suggestion aggregation service ✓
- TASK-5-7: Suggestions CRUD API endpoints ✓
- TASK-5-8: Generate suggestions endpoint ✓
- TASK-5-9: Track/dismiss endpoints ✓
- TASK-5-10: Suggestions list page ✓
- TASK-5-11: Generate suggestions form ✓
- TASK-5-12: Suggestion card component ✓
- TASK-5-13: Unit tests ✓
- TASK-5-14: Integration tests ✓

DOCUMENTATION.md updated with feature documentation. Feature FEAT-005 may now be complete pending specification review.

Status: To Develop → Done

### [2026-02-28 17:00] PreTester: Integration Tests Fixed and Verified
**TASK-5-14 Integration Tests - All 28 tests pass (73 assertions)**

**Test Fixes Applied:**
1. `rediscovery_updates_dismissed_status`: Changed from `DismissedSuggestion` model to DB facade (model not implemented)
2. `export_suggestions`: Changed from `streamedContent()` to `getContent()` (export returns regular response)

**Current Task Status:**
| Task | Status | Notes |
|------|--------|-------|
| TASK-5-1 | Done | Migration |
| TASK-5-2 | Testing Prod | Model/Repository |
| TASK-5-3 through TASK-5-9 | Done | Backend implementation |
| TASK-5-10, 5-11, 5-12 | To Develop | Frontend (TDD tests ready) |
| TASK-5-13 | Done | Unit tests |
| TASK-5-14 | Done | Integration tests |

**Remaining Work:** Frontend implementation (TASK-5-10, 5-11, 5-12)

Status: To Develop (waiting for frontend implementation)

### [2026-02-28 16:55] PreTester: Test Design Session Complete
**Created frontend test files and integration tests:**

**Frontend Test Files Created:**
- `frontend/__tests__/app/sites/[siteId]/suggestions/SuggestionsPage.test.tsx` - 35+ test scenarios
- `frontend/__tests__/components/suggestions/GenerateSuggestionsForm.test.tsx` - 40+ test scenarios
- `frontend/__tests__/components/suggestions/SuggestionCard.test.tsx` - 45+ test scenarios

**Integration Test File Created:**
- `backend/tests/Feature/Integration/KeywordDiscoveryIntegrationTest.php` - 28 test methods

**Backend Test Verification:**
- SuggestionController: 80 tests passing (192 assertions) ✓
- KeywordSuggestion model/repository/migration/service tests: 83 tests passing ✓

**Task Status Updates:**
- TASK-5-10: To Write Tests → To Develop
- TASK-5-11: To Write Tests → To Develop
- TASK-5-12: To Write Tests → To Develop
- TASK-5-14: To Write Tests → To Develop

**Specification Status:** To Write Tests → To Develop

All test design work complete. Frontend tasks ready for Developer implementation.

### [2026-02-27 17:55] PreTester: Test Coverage Complete for All Tasks
**All test designs complete - ready for Developer implementation:**

**Backend Tests (all passing):**
- TASK-5-1: 26 migration tests ✓
- TASK-5-2: 43 model/repository tests ✓
- TASK-5-3: 33 OpportunityScorer tests ✓ (Done)
- TASK-5-4: 25 GoogleSuggestFetcher tests ✓ (Done)
- TASK-5-5: 25 QuestionGenerator tests ✓ (Done)
- TASK-5-6: 14 SuggestionService tests ✓ (Done)
- TASK-5-7: 80 SuggestionController tests ✓ (Done)
- TASK-5-8: Included in SuggestionController tests ✓
- TASK-5-9: Included in SuggestionController tests ✓

**Frontend Tests (TDD mode - waiting for implementation):**
- TASK-5-10: 35+ SuggestionsPage tests (created)
- TASK-5-11: 40+ GenerateSuggestionsForm tests (created)
- TASK-5-12: 45+ SuggestionCard tests (created)

**Integration Tests:**
- TASK-5-13: Unit tests complete (included above)
- TASK-5-14: 28 integration scenarios documented

**Total Test Count:** 350+ test scenarios

**Status Summary:**
- Backend: Tests written and passing for completed implementation
- Frontend: Tests written in TDD mode, waiting for component implementation
- Integration: Documentation complete, tests to be written after implementation

Status remains: To Write Tests (frontend implementation pending)

### [2026-02-27 14:35] PreTester: Test Coverage Verification
**Tasks with tests verified/updated:**
- TASK-5-3 (OpportunityScorer): 33 tests pass (54 assertions) - fixed 2 test issues
- TASK-5-5 (QuestionGenerator): 25 tests pass (51 assertions) - all tests pass
- TASK-5-9 (SuggestionController): 41 tests pass (96 assertions) - fixed SQL injection test

**Tasks still in To Write Tests status:**
- TASK-5-10, TASK-5-11, TASK-5-12: Frontend tasks (React components)
- TASK-5-14: Integration tests (requires all implementation complete)

**Tasks in Tests Coverage In Progress:**
- TASK-5-7, TASK-5-8, TASK-5-9: Being worked on by other PreTester instances

Status remains: To Write Tests (frontend and integration tests pending)

### [2026-02-26 22:42] ProjectManager: Prioritization Complete
- Created 14 task files (TASK-5-1 through TASK-5-14)
- Priority assignments:
  - High (9 tasks): TASK-5-1, TASK-5-2, TASK-5-4, TASK-5-6, TASK-5-7, TASK-5-8, TASK-5-9, TASK-5-13
  - Medium (5 tasks): TASK-5-3, TASK-5-5, TASK-5-10, TASK-5-11, TASK-5-12, TASK-5-14
- Risk assignments:
  - Low (5 tasks): TASK-5-2, TASK-5-3, TASK-5-5, TASK-5-10, TASK-5-11, TASK-5-12, TASK-5-13, TASK-5-14
  - Medium (6 tasks): TASK-5-1, TASK-5-4, TASK-5-6, TASK-5-7, TASK-5-8, TASK-5-9
- Dependency chain: TASK-5-1 → TASK-5-2 → TASK-5-6 → TASK-5-7/8/9 → TASK-5-10/11
- Status: To Write Tests

### [2026-02-19 23:33] Architect: Specification Created
- Database schema for keyword_suggestions
- API endpoints for suggestions CRUD
- FREE sources only: Google Suggest, Question generator
- 14 implementation tasks
- Status: To Break Into Tasks
- Depends on: FEAT-001
