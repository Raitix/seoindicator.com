# SPEC-003: Competitor Rank Comparison

## Overview
Track competitor keyword rankings alongside your own. Enable side-by-side ranking comparisons for overlapping keywords, transforming rank tracking from self-monitoring into competitive intelligence.

## Technical Specification

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│              Competitor Rank Comparison System               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  Competitor │────▶│  Ranking    │────▶│ Comparison  │   │
│  │  Manager    │     │  Service    │     │  Engine     │   │
│  └─────────────┘     └─────────────┘     └─────────────┘   │
│         │                   │                   │          │
│         │                   ▼                   │          │
│         │           ┌─────────────┐             │          │
│         │           │   Gap       │             │          │
│         │           │  Analyzer   │             │          │
│         │           └─────────────┘             │          │
│         │                   │                   │          │
│         └───────────────────┼───────────────────┘          │
│                             ▼                              │
│                    ┌─────────────┐                         │
│                    │Comparison API│                        │
│                    └─────────────┘                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### API Design

#### Endpoints

##### 1. Add Competitor
```http
POST /api/v1/competitors
Content-Type: application/json

{
  "domain": "competitor.com",
  "displayName": "Main Competitor",
  "notes": "Primary market rival"
}

Response 201:
{
  "success": true,
  "data": {
    "id": 1,
    "domain": "competitor.com",
    "displayName": "Main Competitor",
    "notes": "Primary market rival",
    "createdAt": "2026-02-19T21:13:00Z"
  }
}

Response 400 (Invalid Domain):
{
  "success": false,
  "error": {
    "code": "INVALID_DOMAIN",
    "message": "Domain is not accessible or invalid"
  }
}

Response 409 (Duplicate):
{
  "success": false,
  "error": {
    "code": "COMPETITOR_EXISTS",
    "message": "This competitor is already being tracked"
  }
}
```

##### 2. List Competitors
```http
GET /api/v1/competitors

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "domain": "competitor.com",
      "displayName": "Main Competitor",
      "notes": "Primary market rival",
      "trackedKeywords": 50,
      "createdAt": "2026-02-19T21:13:00Z"
    }
  ]
}
```

##### 3. Update Competitor
```http
PUT /api/v1/competitors/:id
Content-Type: application/json

{
  "displayName": "Updated Name",
  "notes": "Updated notes"
}

Response 200:
{
  "success": true,
  "data": {
    "id": 1,
    "domain": "competitor.com",
    "displayName": "Updated Name",
    "notes": "Updated notes"
  }
}
```

##### 4. Delete Competitor
```http
DELETE /api/v1/competitors/:id

Response 204 (No Content)
```

##### 5. Get Competitor Rankings
```http
GET /api/v1/competitors/:id/rankings

Query Parameters:
  - keywordId: number (filter by specific keyword)
  - from: ISO date
  - to: ISO date

Response 200:
{
  "success": true,
  "data": {
    "competitor": {
      "id": 1,
      "domain": "competitor.com"
    },
    "rankings": [
      {
        "keywordId": 1,
        "keyword": "seo tools",
        "myPosition": 5,
        "competitorPosition": 8,
        "positionDiff": -3,
        "myUrl": "https://example.com/seo-tools",
        "competitorUrl": "https://competitor.com/seo-tools",
        "checkedAt": "2026-02-19T21:13:00Z"
      }
    ]
  }
}
```

##### 6. Get Comparison Dashboard
```http
GET /api/v1/rankings/comparison

Query Parameters:
  - competitorId: number (optional, default all)
  - filter: 'all' | 'im-winning' | 'theyre-winning' | 'gap' | 'both-rank'
  - page: number
  - perPage: number

Response 200:
{
  "success": true,
  "data": {
    "summary": {
      "totalKeywords": 100,
      "imWinning": 45,
      "theyreWinning": 30,
      "bothRank": 75,
      "onlyIRank": 10,
      "onlyTheyRank": 15
    },
    "comparisons": [
      {
        "keywordId": 1,
        "keyword": "seo tools",
        "myPosition": 5,
        "competitors": [
          {
            "competitorId": 1,
            "domain": "competitor.com",
            "position": 8,
            "diff": -3
          }
        ],
        "status": "im-winning"
      }
    ],
    "meta": {
      "page": 1,
      "perPage": 20,
      "total": 100
    }
  }
}
```

##### 7. Get Keyword Gaps (Competitor Only)
```http
GET /api/v1/competitors/:id/gaps

Query Parameters:
  - minPosition: number (default: 20, max competitor position to include)
  - page: number
  - perPage: number

Response 200:
{
  "success": true,
  "data": {
    "competitor": {
      "id": 1,
      "domain": "competitor.com"
    },
    "gaps": [
      {
        "keyword": "best seo software",
        "competitorPosition": 12,
        "competitorUrl": "https://competitor.com/best-seo-software",
        "myPosition": null,
        "opportunity": "high"
      }
    ],
    "meta": {
      "total": 25,
      "page": 1,
      "perPage": 20
    }
  }
}
```

##### 8. Get Historical Comparison
```http
GET /api/v1/rankings/comparison/history

Query Parameters:
  - keywordId: number (required)
  - competitorId: number (optional, default all)
  - from: ISO date
  - to: ISO date

Response 200:
{
  "success": true,
  "data": {
    "keyword": "seo tools",
    "keywordId": 1,
    "history": {
      "dates": ["2026-02-12", "2026-02-13", "2026-02-14", "2026-02-15", "2026-02-16"],
      "mine": [8, 7, 6, 5, 5],
      "competitors": {
        "1": {
          "domain": "competitor.com",
          "positions": [10, 9, 8, 8, 8]
        }
      }
    }
  }
}
```

### Data Model

#### Competitor Entity
```typescript
interface Competitor {
  id: number;
  userId: number;
  domain: string;
  displayName: string | null;
  notes: string | null;
  createdAt: Date;
  updatedAt: Date;
}

interface CompetitorRanking {
  id: number;
  competitorId: number;
  keywordId: number;
  position: number | null;
  url: string | null;
  checkedAt: Date;
}

interface ComparisonResult {
  keywordId: number;
  keyword: string;
  myPosition: number | null;
  myUrl: string | null;
  competitors: CompetitorComparison[];
  status: 'im-winning' | 'theyre-winning' | 'gap' | 'both-rank' | 'only-i-rank';
}

interface CompetitorComparison {
  competitorId: number;
  domain: string;
  position: number | null;
  url: string | null;
  diff: number | null;  // negative = I'm ahead, positive = they're ahead
}
```

### Database Schema

#### Competitors Table
```sql
CREATE TABLE competitors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    domain VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, domain)
);

CREATE INDEX idx_competitors_user_id ON competitors(user_id);
CREATE INDEX idx_competitors_domain ON competitors(domain);
```

#### Competitor Rankings Table
```sql
CREATE TABLE competitor_rankings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    competitor_id INTEGER NOT NULL,
    keyword_id INTEGER NOT NULL,
    position INTEGER,
    url TEXT,
    checked_at DATETIME NOT NULL,
    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    UNIQUE(competitor_id, keyword_id, checked_at)
);

CREATE INDEX idx_competitor_rankings_competitor ON competitor_rankings(competitor_id);
CREATE INDEX idx_competitor_rankings_keyword ON competitor_rankings(keyword_id);
CREATE INDEX idx_competitor_rankings_date ON competitor_rankings(checked_at);
```

### Service Layer

#### CompetitorService
```typescript
class CompetitorService {
  constructor(
    private competitorRepo: CompetitorRepository,
    private rankingRepo: RankingRepository,
    private domainValidator: DomainValidator
  ) {}

  async addCompetitor(userId: number, domain: string, displayName?: string, notes?: string): Promise<Competitor>
  async listCompetitors(userId: number): Promise<Competitor[]>
  async updateCompetitor(id: number, data: UpdateCompetitorDto): Promise<Competitor>
  async deleteCompetitor(id: number): Promise<void>
  async validateDomain(domain: string): Promise<boolean>
}
```

#### ComparisonService
```typescript
class ComparisonService {
  constructor(
    private rankingRepo: RankingRepository,
    private competitorRankingRepo: CompetitorRankingRepository,
    private keywordRepo: KeywordRepository
  ) {}

  async getComparison(userId: number, options: ComparisonOptions): Promise<ComparisonResult>
  async getKeywordGaps(userId: number, competitorId: number, options: GapOptions): Promise<KeywordGap[]>
  async getHistoricalComparison(keywordId: number, options: HistoryOptions): Promise<HistoricalComparison>
  async determineStatus(myPosition: number | null, competitorPositions: (number | null)[]): ComparisonStatus
}
```

### Ranking Check Integration

Extend the existing rank checker to also fetch competitor rankings:

```typescript
class RankCheckerService {
  async checkKeyword(keywordId: number): Promise<void> {
    const keyword = await this.keywordRepo.findById(keywordId);
    
    // Check user's ranking (existing logic)
    const userRanking = await this.fetchRanking(keyword.keyword, keyword.site.domain);
    await this.rankingRepo.save(userRanking);
    
    // Check all competitors for this site
    const competitors = await this.competitorRepo.findBySite(keyword.siteId);
    for (const competitor of competitors) {
      const competitorRanking = await this.fetchRanking(keyword.keyword, competitor.domain);
      await this.competitorRankingRepo.save({
        competitorId: competitor.id,
        keywordId: keyword.id,
        ...competitorRanking
      });
    }
  }
}
```

### Frontend Components

#### Comparison Dashboard
```typescript
// components/ComparisonDashboard.tsx
interface Props {
  competitors: Competitor[];
  comparisons: ComparisonResult[];
  summary: ComparisonSummary;
}

// Features:
// - Summary cards (I'm winning, They're winning, Gaps)
// - Filterable comparison table
// - Sort by position difference
// - Toggle competitor visibility
// - Export to CSV
```

#### Historical Chart
```typescript
// components/ComparisonChart.tsx
interface Props {
  keywordId: number;
  competitors: Competitor[];
  dateRange: DateRange;
}

// Features:
// - Multi-line chart showing position over time
// - Color-coded lines for user and each competitor
// - Interactive legend to show/hide lines
// - Hover tooltips with exact positions
```

### Free Tools Constraint

⚠️ **IMPORTANT**: This feature must use FREE alternatives only:
- No SerpAPI, DataForSEO, or other paid SERP APIs
- Use the same free alternatives as FEAT-001:
  - Manual browser-based checking (for development)
  - ScrapingDog free tier (1000 requests/month)
  - Serper.dev free tier (2500 requests/month)
  - Self-hosted scraping with rotating proxies

### Implementation Tasks

1. **Database Migration**
   - Create `competitors` table
   - Create `competitor_rankings` table

2. **Backend - Competitor Management**
   - Competitor CRUD API endpoints
   - Domain validation service
   - Competitor limit enforcement (per subscription tier)

3. **Backend - Ranking Integration**
   - Extend rank checker to fetch competitor rankings
   - Store competitor ranking history

4. **Backend - Comparison Engine**
   - Comparison service with filtering
   - Gap analysis algorithm
   - Historical comparison aggregation

5. **Frontend - Competitor Management**
   - Add/Edit/Delete competitor UI
   - Competitor list view

6. **Frontend - Comparison Dashboard**
   - Summary cards
   - Comparison table with filters
   - Status badges (winning/losing/gap)

7. **Frontend - Charts**
   - Multi-line historical comparison chart
   - Interactive legend

### Testing Strategy

#### Unit Tests
- Competitor CRUD operations
- Comparison status determination
- Gap analysis logic
- Domain validation

#### Integration Tests
- API endpoints
- Ranking check integration
- Comparison queries

#### E2E Tests
- Add competitor flow
- View comparison dashboard
- Export comparison data

### Security Considerations

1. **Domain Validation**
   - Verify domain is accessible
   - Check for valid DNS record
   - Prevent internal network access (SSRF protection)

2. **Rate Limiting**
   - Limit competitor additions per user
   - Rate limit domain validation requests

3. **Data Access**
   - Users can only see their own competitors
   - Competitor rankings only for user's keywords

### Subscription Limits

| Plan | Keywords | Competitors |
|------|----------|-------------|
| Free | 10 | 1 |
| Starter | 50 | 3 |
| Pro | 200 | 10 |
| Enterprise | Unlimited | Unlimited |

### Performance Considerations

1. **Batch Processing**
   - Check all competitor rankings in single SERP request when possible
   - Store rankings asynchronously

2. **Caching**
   - Cache comparison results (5 min TTL)
   - Cache competitor rankings per check cycle

3. **Query Optimization**
   - Indexed queries on competitor_id, keyword_id, checked_at
   - Pagination for large keyword sets

### MVP Scope

**Included:**
- Add up to 3 competitors
- Side-by-side ranking comparison table
- Basic filtering (I'm winning / They're winning)
- Historical chart overlay
- CSV export

**Phase 2:**
- Keyword gap discovery
- Advanced competitor insights
- Competitor alerts
- Unlimited competitors (higher tiers)

---

## Status Tracking
- Created: 2026-02-19
- Author: Architect
- Feature: FEAT-003
- Status: `To Prioritize`
- Tasks Created: 8 (SPEC-003-1 through SPEC-003-8)
