# FEAT-035: Local SEO & Google Business Profile Tracking

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Track local search rankings, Google Business Profile (GBP) performance, and local pack visibility. With 46% of Google searches having local intent and GBP becoming increasingly important (AI-powered Q&A, messaging, booking integrations in 2026), local SEO tracking is essential for businesses targeting geographic audiences. This feature expands SEOIndicator from general SEO to local SEO, capturing a massive underserved market.

## User Story
As a local business owner or agency serving local clients, I want to track my Google Business Profile rankings, local pack positions, and local search visibility so that I can optimize my local presence and attract nearby customers.

## Business Impact

### Revenue
- **Market expansion**: Local SEO is a $10B+ market, most SEO tools focus on general rankings
- **New customer segment**: Local businesses, multi-location brands, agencies with local clients
- **Premium tier driver**: Local SEO features command premium pricing ($50-200/month)
- **Higher retention**: Local businesses track ongoing performance, creating sticky usage
- **Multi-location upsell**: Chain businesses need multiple location tracking

### User Behavior
- **46% of searches are local**: Massive search volume we're not capturing
- **"Near me" searches growing 150%+ YoY**: Increasing demand for local visibility
- **GBP is #1 local ranking factor**: Google Business Profile optimization directly impacts rankings
- **Zero-click impact**: Local pack answers queries without clicks - users need visibility tracking
- **Review monitoring essential**: 88% of consumers trust online reviews as much as personal recommendations

### Retention
- **Daily monitoring**: Local rankings change frequently, users check daily
- **Review alerts**: New reviews trigger immediate user engagement
- **Competitor tracking**: Users monitor local competitor GBP performance
- **Multi-location dashboard**: Businesses with multiple locations centralize monitoring

### Legal & Compliance
- **Google API terms**: Must comply with GBP API terms of service
- **Review data**: Reviews are public data, no PII concerns
- **Business information**: Public business data, no privacy issues
- **Rate limiting**: Respect Google API quotas

### Complexity & Cost
- **Google APIs**: GBP API is free but rate-limited; may need Places API (~$0.017/call)
- **Local rank tracking**: Need geo-targeted SERP scraping (DataForSEO ~$0.002/query)
- **Multiple data sources**: GBP API, Maps results, organic local results
- **Medium effort**: 2-3 weeks for MVP tracking

## Analytics Context
- **Organic sessions (30 days)**: 1 (local SEO content could drive local traffic)
- **Homepage engagement**: 292s average (users interested in SEO tools)
- **Tool engagement**: 271s on meta-tag-analyzer (tools attract engagement)

**Hypothesis**: Adding local SEO features expands the addressable market to local businesses who need location-specific tracking, not just general keyword rankings.

## Acceptance Criteria
- [ ] User can add business locations (name, address, phone, website, category)
- [ ] System connects to Google Business Profile API for data sync
- [ ] Track GBP ranking for target keywords in local pack (map results)
- [ ] Track GBP ranking in local organic results (geo-targeted)
- [ ] Monitor GBP metrics: views, searches, actions (calls, directions, website clicks)
- [ ] Monitor GBP reviews: new review alerts, average rating, review count
- [ ] Track GBP posts engagement (when available)
- [ ] Show local pack position for tracked keywords (positions 1-3 in map pack)
- [ ] Compare local rankings vs top 3 local competitors
- [ ] Multi-location dashboard for businesses with multiple GBP listings
- [ ] Historical tracking: 90-day local ranking history
- [ ] Export local SEO reports as PDF/CSV
- [ ] Alert on significant ranking changes in local pack
- [ ] Alert on new reviews (positive/negative sentiment)
- [ ] GBP Q&A monitoring and alert system

## Local Ranking Data Sources

| Data Type | Source | Cost | Notes |
|-----------|--------|------|-------|
| GBP Insights | Google Business Profile API | Free | Views, searches, actions |
| GBP Reviews | Google Business Profile API | Free | Review text, rating, date |
| Local Pack Rankings | DataForSEO / Custom scraper | ~$0.002/query | Map pack positions |
| Local Organic Rankings | DataForSEO / Custom scraper | ~$0.002/query | Geo-targeted organic |
| GBP Posts | Google Business Profile API | Free | Post performance |
| Q&A | GBP API (limited) | Free | Questions and answers |

## Key Metrics Dashboard

### GBP Performance
| Metric | Description | Target |
|--------|-------------|--------|
| GBP Views | Profile views per month | Growing trend |
| GBP Searches | Searches that showed profile | Growing trend |
| Actions | Calls + Directions + Website clicks | >5% conversion |
| Average Rating | Review average | >4.5 stars |
| Review Count | Total reviews | Growing trend |
| Response Rate | % of reviews with owner response | >80% |

### Local Rankings
| Metric | Description | Target |
|--------|-------------|--------|
| Local Pack Position | Position in map pack (1-3) | Top 3 |
| Local Organic Position | Geo-targeted organic ranking | Top 10 |
| Keyword Coverage | % of keywords in local pack | >30% |
| Competitor Gap | Keywords where competitors outrank | Decreasing |

## Technical Implementation

### Database Schema
```sql
-- Business Locations
business_locations: id, user_id, gbp_place_id, name, address, phone, website, category, lat, lng, created_at

-- GBP Insights (daily snapshots)
gbp_insights: id, location_id, views, searches, calls, directions, website_clicks, photos_views, snapshot_date

-- GBP Reviews
gbp_reviews: id, location_id, google_review_id, author, rating, text, response, review_date, created_at

-- Local Rankings
local_rankings: id, location_id, keyword, local_pack_position, local_organic_position, search_lat, search_lng, checked_at

-- Local Competitors
local_competitors: id, location_id, competitor_name, competitor_gbp_id, created_at
```

### Local Ranking Check Flow
```
User adds location → Geocode address →
For each tracked keyword:
  → Check local pack ranking (geo-targeted search)
  → Check local organic ranking
  → Compare vs competitors
  → Store results
```

### GBP API Integration
```javascript
// OAuth flow for GBP access
const gbpAuth = await getGoogleOAuthToken(user);
const accounts = await listGBPAccounts(gbpAuth);
const locations = await listGBPLocations(accounts);
const insights = await getGBPInsights(locations, dateRange);
const reviews = await getGBPReviews(locations);
```

## Free Tools Constraint Compliance

**Challenge**: Local ranking tracking requires geo-targeted SERP data.

**Mitigation Strategies**:
1. **GBP API is free**: Core GBP metrics (views, searches, reviews) cost nothing
2. **Limited local tracking**: Free tier gets 50 local ranking checks/month
3. **User-provided location**: Users specify their location, reducing API calls
4. **Weekly snapshots**: Instead of daily to reduce costs
5. **Manual refresh**: Users trigger ranking checks (no automatic polling in free tier)

**MVP Free Tier**:
- GBP insights: Unlimited (free API)
- GBP reviews: Unlimited (free API)
- Local ranking checks: 50/month (paid tier for more)

## Competitor Comparison

| Tool | GBP Tracking | Local Pack Rankings | Review Monitoring | Multi-Location |
|------|--------------|---------------------|-------------------|----------------|
| BrightLocal | ✅ | ✅ | ✅ | ✅ |
| Localo | ✅ | ✅ | ✅ | ✅ |
| Whitespark | ✅ | ✅ | ❌ | ✅ |
| Merchynt | ✅ | ✅ | ✅ | ✅ |
| Ahrefs | ❌ | ❌ | ❌ | ❌ |
| SEMrush | Limited | ❌ | ❌ | ❌ |
| SEOIndicator | **✅ (proposed)** | **✅ (proposed)** | **✅ (proposed)** | **✅ (proposed)** |

**Competitive Advantage**: Most general SEO tools don't have local features. Local SEO tools don't have general SEO features. We combine both in one platform.

## Local SEO Ranking Factors (2026)

| Factor | Weight | Tracking Coverage |
|--------|--------|-------------------|
| Google Business Profile | 25% | ✅ Full tracking |
| Reviews (quantity, rating, recency) | 15% | ✅ Full tracking |
| On-page SEO (NAP, location pages) | 15% | ⚠️ Partial (site audit) |
| Links | 10% | ✅ Existing backlink tracking |
| Citations (NAP consistency) | 10% | ❌ Not tracked (Phase 2) |
| User behavior (CTR, dwell time) | 5% | ⚠️ Partial (GSC) |
| Personalization | 5% | ❌ Cannot track |

## MVP Scope

**Phase 1 (MVP):**
- Add business location with GBP connection
- GBP insights tracking (views, searches, actions)
- GBP review monitoring with alerts
- Local pack ranking for up to 20 keywords
- Single location per user (multi-location in Phase 2)

**Phase 2:**
- Multi-location dashboard
- Local competitor tracking
- Citation consistency checker
- GBP post scheduling

**Phase 3:**
- Review response automation
- GBP Q&A monitoring
- Local SEO audit
- White-label local reports

## Success Metrics

| Metric | Current | Target (30 days) | Target (90 days) |
|--------|---------|------------------|------------------|
| Locations added | 0 | 25 | 100 |
| GBP connections | 0 | 20 | 80 |
| Local keywords tracked | 0 | 200 | 1,000 |
| Review alerts sent | 0 | 50 | 300 |
| Premium upgrades | 0 | 5 | 25 |

## Why This Feature Now

1. **Market opportunity**: Local SEO is underserved by general SEO tools
2. **User demand**: Local businesses need location-specific tracking
3. **GBP evolution**: Google adding AI features to GBP in 2026
4. **Differentiation**: Most SEO tools lack local features
5. **Revenue potential**: Local SEO features command premium pricing
6. **Complementary**: Extends existing rank tracking to local context

## Dependencies
- Google Business Profile API access (OAuth)
- DataForSEO or custom SERP scraper for local rankings
- Existing keyword tracking infrastructure

## Related
- Extends: FEAT-001 (Keyword Rank Tracking → Local Rankings)
- Extends: FEAT-002 (Alerts → Local Ranking & Review Alerts)
- Extends: FEAT-004 (Reports → Local SEO Reports)
- Related: FEAT-007 (SERP Feature Tracking - local pack)

## Notes

### Multi-Location Use Cases
1. **Restaurant chains**: Track all locations from one dashboard
2. **Retail stores**: Monitor local visibility for each store
3. **Service area businesses**: Track multiple service areas
4. **Agencies**: Manage all client GBP profiles centrally

### GBP 2026 Features to Leverage
- AI-powered Q&A suggestions
- In-search messaging and booking
- Advanced analytics with AI insights
- Video posts and virtual tours

### Local vs National SEO
Local SEO is fundamentally different from national SEO:
- Location authority > domain authority
- Proximity is a ranking factor
- Reviews impact rankings
- GBP optimization is essential
- "Near me" queries need different tracking

---

*Created by Innovator agent on 2026-03-08*

---

## Comments

### Architect (2026-03-08)
**Specification Created: SPEC-035.md**

**Key Design Decisions:**
1. **Free Tools Compliance**: Replaced DataForSEO with custom SERP scraper; GBP API is already free
2. **MVP Scope**: Single location per user (multi-location in Phase 2)
3. **Local Ranking**: 50 free checks/month with user-triggered refresh
4. **Sentiment Analysis**: Simple keyword-based approach (no paid API)

**Architecture Highlights:**
- 6 new database tables (business_locations, gbp_insights, gbp_reviews, local_rankings, local_competitors, gbp_posts)
- Google OAuth integration for GBP connection
- Custom SERP scraper with geo-targeting
- Nominatim (OpenStreetMap) for free geocoding

**Risks Identified:**
- SERP scraping may be blocked (mitigation: rate limiting, robots.txt compliance)
- Google API changes (mitigation: version integration)

**Status**: Ready for SpecSystemAnalyst to break into tasks.

### SpecSystemAnalyst (2026-03-08)
**Task Breakdown Complete: 24 tasks created**

**Phase 1: Database & Models (TASK-35-1 to TASK-35-4)**
- 4 tasks, ~10 hours
- Foundation for all 7 database tables and Eloquent models

**Phase 2: Backend Services & API (TASK-35-5 to TASK-35-16)**
- 12 tasks, ~41 hours
- Services: BusinessLocation, Nominatim, OAuth, GBP API, Insights, Reviews, Rankings, Alerts
- Controllers for all API endpoints
- HIGH RISK: TASK-35-7 (OAuth) - security-sensitive
- MEDIUM RISK: TASK-35-11 (SERP scraper) - may need adjustment

**Phase 3: Frontend (TASK-35-17 to TASK-35-23)**
- 7 tasks, ~26 hours
- Pages: Locations, Dashboard, Insights, Reviews, Rankings, Alerts
- Components: GBP Connect Button, Charts, Review Cards, etc.

**Phase 4: Testing (TASK-35-24)**
- 1 task, ~6 hours
- Comprehensive backend tests (unit + feature)

**Total Estimated Effort: ~83 hours**
