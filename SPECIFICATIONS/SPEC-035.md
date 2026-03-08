# SPEC-035: Local SEO & Google Business Profile Tracking

## STATUS
`Breaking Into Tasks`

## FEATURE
FEAT-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Medium`

---

## Overview

This specification defines the technical implementation for tracking local search rankings, Google Business Profile (GBP) performance, and local pack visibility. The feature expands SEOIndicator from general SEO to local SEO, capturing a significant underserved market.

---

## Architecture Decision: Free Tools Compliance

### Challenge
The feature proposal mentions DataForSEO and Google Places API, which have costs. Per CONSTITUTION.md, all features must use FREE tools only.

### Solution

| Data Type | Proposed (Paid) | Free Alternative |
|-----------|-----------------|------------------|
| GBP Insights | Google Business Profile API | **✅ FREE** - No cost |
| GBP Reviews | Google Business Profile API | **✅ FREE** - No cost |
| Local Pack Rankings | DataForSEO (~$0.002/query) | **Custom SERP Scraper** with geo-targeting |
| Local Organic Rankings | DataForSEO (~$0.002/query) | **Custom SERP Scraper** with geo-targeting |
| Geocoding | Google Geocoding API | **Nominatim** (OpenStreetMap) - FREE |
| Review Sentiment | Sentiment Analysis API | **Local PHP sentiment analysis** (free library) |

### Custom SERP Scraper Approach
- Use `google/apiclient` (free) with Custom Search JSON API (100 queries/day free)
- Fallback: Direct SERP scraping with `guzzlehttp/guzzle` (respect robots.txt, rate limit)
- Geo-targeting via `gl` and `uule` parameters
- Cache results to minimize API calls
- User-triggered refresh (no automatic polling in free tier)

---

## System Architecture

### Component Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     Frontend (Next.js)                          │
├─────────────────────────────────────────────────────────────────┤
│  LocalDashboard │ GBPInsights │ ReviewMonitor │ RankTracker    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Backend API (Laravel)                        │
├─────────────────────────────────────────────────────────────────┤
│  BusinessLocationController │ GBPController │ LocalRankController│
│  ReviewController │ LocalSEOService │ GBPService │ RankService   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     External Services                            │
├─────────────────────────────────────────────────────────────────┤
│  Google Business Profile API (FREE) │ Nominatim (FREE) │ SERP   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### New Tables

```sql
-- Business Locations
CREATE TABLE business_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    gbp_place_id VARCHAR(255) NULL,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(500) NOT NULL,
    phone VARCHAR(50) NULL,
    website VARCHAR(500) NULL,
    category VARCHAR(255) NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    gbp_connected BOOLEAN DEFAULT FALSE,
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_site_id (site_id),
    INDEX idx_gbp_place_id (gbp_place_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- GBP Insights (daily snapshots)
CREATE TABLE gbp_insights (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    views INT UNSIGNED DEFAULT 0,
    searches INT UNSIGNED DEFAULT 0,
    calls INT UNSIGNED DEFAULT 0,
    directions INT UNSIGNED DEFAULT 0,
    website_clicks INT UNSIGNED DEFAULT 0,
    photos_views INT UNSIGNED DEFAULT 0,
    insights_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_location_date (location_id, insights_date),
    INDEX idx_insights_date (insights_date),
    FOREIGN KEY (location_id) REFERENCES business_locations(id) ON DELETE CASCADE
);

-- GBP Reviews
CREATE TABLE gbp_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    google_review_id VARCHAR(255) NOT NULL,
    author_name VARCHAR(255) NULL,
    author_photo VARCHAR(500) NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review_text TEXT NULL,
    review_text_sentiment VARCHAR(20) NULL,
    owner_response TEXT NULL,
    owner_response_date TIMESTAMP NULL,
    review_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_google_review (google_review_id),
    INDEX idx_location_id (location_id),
    INDEX idx_rating (rating),
    INDEX idx_review_date (review_date),
    FOREIGN KEY (location_id) REFERENCES business_locations(id) ON DELETE CASCADE
);

-- Local Rankings
CREATE TABLE local_rankings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    keyword_id BIGINT UNSIGNED NOT NULL,
    search_location VARCHAR(255) NOT NULL,
    search_lat DECIMAL(10, 8) NULL,
    search_lng DECIMAL(11, 8) NULL,
    local_pack_position TINYINT UNSIGNED NULL,
    local_organic_position INT UNSIGNED NULL,
    competitors_in_pack JSON NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_location_keyword (location_id, keyword_id),
    INDEX idx_checked_at (checked_at),
    FOREIGN KEY (location_id) REFERENCES business_locations(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE
);

-- Local Competitors
CREATE TABLE local_competitors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    competitor_name VARCHAR(255) NOT NULL,
    competitor_gbp_id VARCHAR(255) NULL,
    competitor_address VARCHAR(500) NULL,
    competitor_rating DECIMAL(2, 1) NULL,
    competitor_review_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_location_id (location_id),
    FOREIGN KEY (location_id) REFERENCES business_locations(id) ON DELETE CASCADE
);

-- GBP Posts
CREATE TABLE gbp_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    google_post_id VARCHAR(255) NOT NULL,
    post_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NULL,
    content TEXT NULL,
    media_url VARCHAR(500) NULL,
    call_to_action VARCHAR(100) NULL,
    call_to_action_url VARCHAR(500) NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_google_post (google_post_id),
    INDEX idx_location_id (location_id),
    FOREIGN KEY (location_id) REFERENCES business_locations(id) ON DELETE CASCADE
);

-- Local SEO Alerts
CREATE TABLE local_seo_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_location_id (location_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_is_read (is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES business_locations(id) ON DELETE CASCADE
);
```

---

## API Design

### Business Location Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sites/{siteId}/locations` | List all business locations |
| POST | `/api/v1/sites/{siteId}/locations` | Add new business location |
| GET | `/api/v1/locations/{id}` | Get location details |
| PUT | `/api/v1/locations/{id}` | Update location |
| DELETE | `/api/v1/locations/{id}` | Delete location |
| POST | `/api/v1/locations/{id}/connect-gbp` | Connect GBP account |
| DELETE | `/api/v1/locations/{id}/disconnect-gbp` | Disconnect GBP |

### GBP Insights Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/locations/{id}/insights` | Get GBP insights |
| GET | `/api/v1/locations/{id}/insights/trend` | Get insights trend |
| POST | `/api/v1/locations/{id}/insights/sync` | Sync from GBP API |

### GBP Reviews Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/locations/{id}/reviews` | List reviews |
| GET | `/api/v1/locations/{id}/reviews/stats` | Review statistics |
| POST | `/api/v1/locations/{id}/reviews/sync` | Sync reviews from GBP |
| POST | `/api/v1/locations/{id}/reviews/{reviewId}/respond` | Respond to review |

### Local Rankings Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/locations/{id}/rankings` | Get local rankings |
| POST | `/api/v1/locations/{id}/rankings/check` | Trigger ranking check |
| GET | `/api/v1/locations/{id}/rankings/history` | Historical rankings |

### Competitor Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/locations/{id}/competitors` | List competitors |
| POST | `/api/v1/locations/{id}/competitors` | Add competitor |
| DELETE | `/api/v1/locations/{id}/competitors/{competitorId}` | Remove competitor |

---

## Backend Services

### BusinessLocationService
- CRUD operations for business locations
- Geocoding integration (Nominatim)
- GBP connection management

### GBPService
- Google OAuth integration
- GBP API client wrapper
- Insights data fetching and caching
- Review sync with sentiment analysis

### LocalRankService
- Local pack ranking detection
- Local organic ranking tracking
- Geo-targeted SERP queries
- Competitor comparison

### LocalSEOAlertService
- Ranking change alerts
- New review notifications
- Rating drop alerts
- Competitor movement alerts

---

## Frontend Components

### Pages

| Page | Route | Description |
|------|-------|-------------|
| Locations Dashboard | `/sites/{siteId}/local` | Overview of all locations |
| Location Details | `/locations/{id}` | Single location dashboard |
| GBP Insights | `/locations/{id}/insights` | GBP metrics and trends |
| Reviews | `/locations/{id}/reviews` | Review management |
| Local Rankings | `/locations/{id}/rankings` | Local ranking tracker |

### Components

| Component | Description |
|-----------|-------------|
| `LocationCard` | Business location summary card |
| `LocationForm` | Add/edit location form |
| `GBPConnectButton` | OAuth button for GBP connection |
| `InsightsChart` | GBP insights visualization |
| `ReviewList` | Review list with filters |
| `ReviewCard` | Individual review display |
| `ReviewStats` | Rating distribution chart |
| `LocalRankTracker` | Local ranking positions |
| `LocalPackDisplay` | Map pack position visualization |
| `CompetitorCard` | Competitor comparison card |
| `LocalSEOAlert` | Alert notification component |

---

## OAuth Integration

### Google OAuth Flow

```
1. User clicks "Connect Google Business Profile"
2. Redirect to Google OAuth consent screen
3. User grants permissions (GBP read access)
4. Google redirects back with auth code
5. Exchange code for access/refresh tokens
6. Store tokens securely (encrypted)
7. Fetch GBP accounts and locations
8. Link to business_location record
```

### Required OAuth Scopes

```
https://www.googleapis.com/auth/business.manage
```

### Token Storage

```php
// Encrypted storage in database
business_locations.gbp_tokens = encrypt(json_encode([
    'access_token' => $token,
    'refresh_token' => $refreshToken,
    'expires_at' => $expiresAt,
]));
```

---

## Local Ranking Check Implementation

### Free Tier Strategy

```php
class LocalRankService
{
    private const FREE_TIER_LIMIT = 50; // checks per month
    private const CACHE_TTL = 86400; // 24 hours
    
    public function checkLocalRanking(
        BusinessLocation $location,
        Keyword $keyword
    ): ?LocalRanking {
        // Check monthly quota
        $monthlyChecks = $this->getMonthlyCheckCount($location);
        if ($monthlyChecks >= self::FREE_TIER_LIMIT) {
            throw new QuotaExceededException('Monthly ranking check limit reached');
        }
        
        // Check cache first
        $cacheKey = "local_rank:{$location->id}:{$keyword->id}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        // Perform geo-targeted search
        $results = $this->performLocalSearch(
            $keyword->keyword,
            $location->latitude,
            $location->longitude,
            $location->address
        );
        
        // Extract pack and organic positions
        $ranking = $this->extractPositions($results, $location);
        
        // Cache and return
        Cache::put($cacheKey, $ranking, self::CACHE_TTL);
        
        return $ranking;
    }
}
```

### SERP Scraper Implementation

```php
private function performLocalSearch(
    string $query,
    float $lat,
    float $lng,
    string $location
): array {
    // Build geo-targeted search URL
    $uule = $this->buildUule($location); // Google location encoding
    $url = "https://www.google.com/search?" . http_build_query([
        'q' => $query,
        'gl' => 'us',
        'uule' => $uule,
        'tbm' => 'lcl', // Local search mode
    ]);
    
    // Execute with rate limiting
    $response = $this->httpClient->get($url, [
        'headers' => $this->getBrowserHeaders(),
        'delay' => 1000, // 1 second delay for rate limiting
    ]);
    
    // Parse results
    return $this->parseLocalResults($response->getBody());
}
```

---

## Review Sentiment Analysis

### Free Local Implementation

```php
class SentimentAnalyzer
{
    private const POSITIVE_WORDS = [
        'excellent', 'amazing', 'great', 'wonderful', 'best', 'fantastic',
        'perfect', 'love', 'recommend', 'friendly', 'helpful', 'clean',
        'professional', 'outstanding', 'superb', 'awesome', 'delicious',
    ];
    
    private const NEGATIVE_WORDS = [
        'terrible', 'awful', 'worst', 'horrible', 'bad', 'disappointed',
        'rude', 'slow', 'dirty', 'expensive', 'poor', 'avoid', 'never',
        'waste', 'overpriced', 'unprofessional', 'disgusting', 'broken',
    ];
    
    public function analyze(string $text): string
    {
        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach (self::POSITIVE_WORDS as $word) {
            $positiveCount += substr_count($text, $word);
        }
        
        foreach (self::NEGATIVE_WORDS as $word) {
            $negativeCount += substr_count($text, $word);
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }
        
        return 'neutral';
    }
}
```

---

## Alert Types

| Alert Type | Trigger | Severity |
|------------|---------|----------|
| `ranking_improved` | Local pack position improved | info |
| `ranking_dropped` | Local pack position dropped | warning |
| `new_review` | New review received | info |
| `negative_review` | Review with rating ≤ 2 | warning |
| `rating_dropped` | Average rating dropped by 0.5+ | warning |
| `competitor_overtake` | Competitor moved ahead in pack | warning |
| `gbp_disconnected` | GBP connection lost | critical |

---

## MVP Scope

### Phase 1 (This Specification)
- Single location per user (multi-location in Phase 2)
- GBP connection via OAuth
- GBP insights tracking (views, searches, actions)
- GBP review monitoring with sentiment
- Local pack ranking for up to 20 keywords
- Basic alerts (new review, ranking change)
- 50 local ranking checks/month (free tier limit)

### Phase 2 (Future)
- Multi-location dashboard
- Local competitor tracking
- Citation consistency checker
- GBP post scheduling
- Review response automation

---

## Dependencies

### Internal
- FEAT-001: Keyword Rank Tracking (extends for local)
- FEAT-002: Alert System (extends for local alerts)
- FEAT-004: Reports (extends for local SEO reports)

### External (All FREE)
- Google Business Profile API (FREE)
- Nominatim Geocoding API (FREE, rate-limited)
- Custom SERP Scraper (FREE, self-hosted)

### PHP Packages
- `google/apiclient` - Google OAuth and API client
- `guzzlehttp/guzzle` - HTTP client for SERP scraping
- `league/oauth2-google` - OAuth 2.0 client

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Google API changes | Medium | High | Version API integration, monitor deprecations |
| SERP scraping blocked | Medium | Medium | Respect robots.txt, rate limit, use proxies if needed |
| OAuth token expiration | Low | Medium | Automatic token refresh, clear error messages |
| GBP data accuracy | Low | Low | Cross-validate with multiple sources |
| Local ranking volatility | Medium | Low | Daily snapshots, trend analysis |

---

## Security Considerations

### OAuth Token Security
- Encrypt tokens at rest using Laravel's encryption
- Never expose tokens in API responses
- Automatic token refresh before expiration
- Secure token revocation on disconnect

### API Security
- Rate limit all endpoints
- Validate user owns the business location
- Sanitize all user inputs
- No direct database queries (use Eloquent)

---

## Testing Strategy

### Unit Tests
- BusinessLocationService CRUD
- GBPService API interactions
- LocalRankService ranking extraction
- SentimentAnalyzer classification

### Feature Tests
- API endpoints (CRUD, sync, rankings)
- OAuth flow
- Alert generation
- Quota enforcement

### Integration Tests
- Full OAuth flow with mock Google API
- Ranking check with mock SERP
- Review sync with mock GBP API

---

## Performance Considerations

### Caching Strategy
- GBP insights: 1 hour TTL
- Local rankings: 24 hours TTL
- Geocoding results: 30 days TTL
- Review sentiment: Permanent (computed once)

### Database Indexing
- Index on `gbp_insights(location_id, insights_date)`
- Index on `local_rankings(location_id, keyword_id, checked_at)`
- Index on `gbp_reviews(location_id, review_date)`

### Rate Limiting
- GBP API: Respect Google's quotas
- Nominatim: 1 request/second max
- SERP Scraping: 1 request/second with exponential backoff

---

## Comments

### Architect Notes (2026-03-08)
- Feature approved with modifications for free tools compliance
- Local ranking scraping is the riskiest component; may need adjustment based on real-world testing
- Multi-location deferred to Phase 2 to reduce MVP complexity
- Review sentiment analysis uses simple keyword matching; can upgrade to ML model later if needed
- GBP API is stable and well-documented; low integration risk

### Architect Session (2026-03-08 20:18 GMT+2)
- Status corrected from "New Feature" to "To Break Into Tasks" (previous session had completed spec but status was incorrect)
- Specification complete and ready for SpecSystemAnalyst
- 7 database tables designed: business_locations, gbp_insights, gbp_reviews, local_rankings, local_competitors, gbp_posts, local_seo_alerts
- All dependencies are FREE tools (GBP API, Nominatim, custom SERP scraper)
- Risk level: Medium (SERP scraping volatility, API changes)

---

## Status Transitions

| Date | From | To | Agent |
|------|------|-----|-------|
| 2026-03-08 | - | Specification In Progress | Architect |
| 2026-03-08 | Specification In Progress | To Break Into Tasks | Architect |

---

*Created by Architect agent on 2026-03-08*
