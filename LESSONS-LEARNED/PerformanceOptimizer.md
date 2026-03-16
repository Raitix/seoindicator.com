# PerformanceOptimizer.md — Project Lessons Learned

Knowledge base for PerformanceOptimizer agent working on SEOIndicator project.

---

## Performance Issues Found

### 2026-02-24: N+1 Query in KeywordController
**File:** `backend/app/Http/Controllers/Api/V1/KeywordController.php`
**Lines:** 45-55

**Problem:**
```php
$keywords = Keyword::where('site_id', $siteId)->get();
$formatted = $keywords->map(function (Keyword $keyword) {
    [$current, $previous] = $this->getLatestRankings($keyword); // N+1!
    ...
});
```

**Impact:** For N keywords, this makes N+1 queries instead of 2.

**Fix:** Use eager loading:
```php
$keywords = Keyword::with(['rankingHistory' => fn($q) => 
    $q->orderBy('checked_at', 'desc')->limit(2)
])->where('site_id', $siteId)->get();
```

**Status:** Documented in TASK-1-20.md

---

### 2026-02-24: Memory Issue in DashboardController
**File:** `backend/app/Http/Controllers/Api/V1/DashboardController.php`
**Lines:** 26-30

**Problem:**
```php
$rankings = Ranking::whereIn('keyword_id', $keywordIds)
    ->orderBy('checked_at', 'desc')
    ->get()  // Loads ALL ranking history
    ->groupBy('keyword_id');
```

**Impact:** With 500 keywords × 30 days = 15,000 rows loaded into memory.

**Fix:** Use subquery to fetch only latest 2 per keyword, or use window functions.

**Status:** Documented in TASK-1-20.md

---

### 2026-02-24: High Bounce Rate (Frontend/UX Issue)
**Source:** Google Analytics

| Page | Bounce Rate | Issue |
|------|-------------|-------|
| Homepage (/) | 75.7% | CRITICAL - Users leaving immediately |
| SEO Guides | 62-74% | HIGH - Content not engaging |
| Overall | 68.2% | Above industry average (~40-55%) |

**Recommendation:** UX/Content review for homepage and SEO guide pages.

---

## Optimization Patterns

### Eager Loading for Related Data
```php
// Instead of N+1 queries
Model::with(['relation' => fn($q) => $q->latest()->limit(2)])->get();
```

### Cache Computed Values
```php
Cache::remember("dashboard:{$siteId}", 300, fn() => computeDashboard());
```

### Limit Result Sets
```php
// Use subqueries or window functions to limit per-group
Ranking::whereIn('keyword_id', $ids)
    ->orderBy('checked_at', 'desc')
    ->limit(2 * count($ids)) // Max 2 per keyword
    ->get();
```

---

## Benchmarks

### Target Performance Metrics

| Endpoint | Target | Current Status |
|----------|--------|----------------|
| GET /keywords | <500ms | N+1 issue - will degrade with scale |
| GET /dashboard | <300ms | Cached (5min), acceptable |
| GET /history | <200ms | Needs testing with large datasets |

### Database Indexes

| Table | Index | Purpose |
|-------|-------|---------|
| keywords | (site_id, keyword, search_engine, location, device) UNIQUE | Deduplication |
| ranking_history | (keyword_id, checked_at DESC) | Latest rankings query |
| keywords | (site_id, status) | Filter by status |

---

## 2026-02-26: Follow-up Performance Audit

### Google Analytics Trends (7-day comparison)

| Metric | Previous | Current | Trend |
|--------|----------|---------|-------|
| Bounce Rate | 68.2% | 65.4% | ✅ Improved |
| Homepage Bounce | 75.7% | 74.8% | ➡️ Still critical |
| Engagement Rate | 31.8% | 34.6% | ✅ Improved |

### Page Performance Analysis

| Page | Bounce | Avg Duration | Issue |
|------|--------|--------------|-------|
| `/` | 74.8% | 90s | CRITICAL - First impression |
| `/seo-guides/*` | 62-74% | 2s | HIGH - Content not engaging |
| `/comparison/` | 60% | 278s | ✅ Good engagement |
| `/seo-guides/aria` | 0% | 597s | ✅ Excellent |

### Backend Performance Status

**N+1 Query (KeywordController):** Still present, TASK-1-20 in development
- Lines 72-74: `getLatestRankings()` called in map loop
- Impact: 501 queries for 500 keywords

**DashboardController:** 5-minute cache active
- Cold cache still loads ALL rankings into memory
- Recommendation: Use subquery for latest 2 per keyword

### Database Index Status
- ✅ `ranking_history(keyword_id, checked_at)` composite index
- ✅ `keywords` unique constraint on (site_id, keyword, search_engine, location, device)
- Consider: DESC index on `checked_at` for "latest" queries

---

## 2026-02-27: Performance Audit

### GA Metrics Improvement

| Metric | 02-26 | 02-27 | Trend |
|--------|-------|-------|-------|
| Bounce Rate | 65.4% | 60.0% | ✅ Improved |
| Homepage Bounce | 74.8% | 72.3% | ✅ Improved |
| Engagement Rate | 34.6% | 40.0% | ✅ Improved |

### Page Engagement Analysis

| Page | Bounce | Duration | Assessment |
|------|--------|----------|------------|
| `/` | 72.3% | 95s | CRITICAL - Needs UX review |
| `/seo-guides/*` | 65-75% | 1.5-2.3s | Poor - Content not engaging |
| `/comparison/` | 60% | 278s | ✅ Excellent engagement |
| `/competitors/` | 33% | 26s | ✅ Good |

### Code Changes Detected

**KeywordController::index() (lines 176-178):**
- SERP feature filtering added (batched correctly ✅)
- N+1 issue **STILL PRESENT** - `getLatestRankings()` called in map loop
- Line numbers shifted due to new code

**Task Status:** TASK-1-20 regressed from "In Development" to "To Develop"

---

## 2026-02-28: Performance Audit

### GA Metrics Trend (4-day comparison)

| Metric | 02-24 | 02-28 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 50.0% | ✅ -18.2% |
| Engagement Rate | 31.8% | 50.0% | ✅ +18.2% |
| Homepage Bounce | 75.7% | 71.7% | ✅ -4% |
| Avg Session | 200s | 160s | ➡️ Stable |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 71.7% | 97s | CRITICAL |
| `/seo-guides/*` | 65-75% | 1.5-2.3s | Poor |
| `/comparison/` | 60% | 278s | ✅ Excellent |
| `/competitors/` | 33% | 26s | ✅ Good |
| `/seo-guides/aria` | 0% | 597s | ✅ Excellent |

### Backend Status

**N+1 Query (KeywordController::index):** Still present at lines 91-93
- `getLatestRankings()` called inside map loop
- Impact: 501 queries for 500 keywords

**DashboardController:** 5-minute cache mitigates memory issue

---

## 2026-03-01: Performance Audit

### GA Metrics Trend (5-day comparison)

| Metric | 02-24 | 03-01 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 50.0% | ✅ -18.2% |
| Engagement Rate | 31.8% | 50.0% | ✅ +18.2% |
| Homepage Bounce | 75.7% | 71.1% | ✅ -4.6% |
| Avg Session | 200s | 159s | ➡️ Stable |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 71.1% | 99s | CRITICAL |
| `/seo-guides/*` | 67-78% | 1.7-2.0s | Poor |
| `/comparison/` | 60% | 278s | ✅ Excellent |
| `/competitors/` | 33% | 26s | ✅ Good |
| `/seo-guides/aria` | 0% | 597s | ✅ Excellent |

### Backend Status

**N+1 Query (KeywordController::index):** Still present at lines 106-108
- `getLatestRankings()` called inside map loop
- Impact: 501 queries for 500 keywords

**DashboardController:** 5-minute cache mitigates memory issue

---

## 2026-03-04: Performance Audit

### GA Metrics Trend (8-day comparison)

| Metric | 02-24 | 03-04 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 46.7% | ✅ -21.5% |
| Engagement Rate | 31.8% | 53.3% | ✅ +21.5% |
| Homepage Bounce | 75.7% | 68.4% | ✅ -7.3% |
| Avg Session | 200s | 514s | ✅ +314s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 68.4% | 134s | IMPROVED |
| `/competitors/` | 20% | 43s | ✅ Excellent |
| `/comparison/` | 50% | 246s | ✅ Good |
| `/dashboard/` | 0% | 3s | ✅ Good |
| `/login/` | 0% | 22s | ✅ Good |
| `/seo-guides/*` | 50-64% | 1.3-2.7s | Poor |

### Backend Status

**N+1 Query (KeywordController::index):** Still present at lines 106-108
- `getLatestRankings()` called inside map loop
- Impact: 501 queries for 500 keywords

**DashboardController:** 5-minute cache mitigates memory issue

**Notable Improvement:** Session duration increased from 200s to 514s over 8 days.

---

## 2026-03-05: Performance Audit

### GA Metrics Trend (9-day comparison)

| Metric | 02-24 | 03-05 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 35.0% | ✅ -33.2% |
| Engagement Rate | 31.8% | 65.0% | ✅ +33.2% |
| Homepage Bounce | 75.7% | 63.6% | ✅ -12.1% |
| Avg Session | 200s | 1026s | ✅ +826s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 63.6% | 237s | ✅ IMPROVED |
| `/dashboard/` | 0% | 54s | ✅ Excellent |
| `/competitors/` | 20% | 43s | ✅ Excellent |
| `/comparison/` | 50% | 246s | ✅ Good |
| `/seo-guides/` | 0% | 1057s | ✅ Excellent |
| `/admin/overview/` | 0% | 44s | ✅ Good |
| `/seo-guides/*` | 57-67% | 1.1-3.0s | Poor |

### Backend Status

**N+1 Query (KeywordController::index):** Still present at lines 106-108
- `getLatestRankings()` called inside map loop
- Impact: 501 queries for 500 keywords

**DashboardController:** 5-minute cache mitigates memory issue

**MILESTONE:** Session duration exceeded 1000s for first time. Bounce rate at all-time low (35%).

---

## 2026-03-07: Performance Audit

### GA Metrics Trend (11-day comparison)

| Metric | 02-24 | 03-07 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 33.3% | ✅ -34.9% |
| Engagement Rate | 31.8% | 66.7% | ✅ +34.9% |
| Homepage Bounce | 75.7% | 59.6% | ✅ -16.1% |
| Avg Session | 200s | 900s | ✅ +700s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 59.6% | 294s | ✅ IMPROVED - Below 60% |
| `/dashboard/` | 0% | 37s | ✅ Excellent |
| `/competitors/` | 20% | 43s | ✅ Excellent |
| `/comparison/` | 43% | 211s | ✅ Good |
| `/tools/meta-tag-analyzer/` | 0% | 412s | ✅ Excellent |
| `/tools/` | 0% | 43s | ✅ Good |
| `/seo-guides/` | 0% | 1057s | ✅ Excellent |
| `/seo-guides/meta-description` | 75% | 2s | Poor |

### Backend Status

**N+1 Query (KeywordController::index):** Still present at lines 106-108
- `getLatestRankings()` called inside map loop
- Impact: 501 queries for 500 keywords

**DashboardController:** 5-minute cache mitigates memory issue

**MILESTONE:** Homepage bounce rate dropped below 60% for first time. Overall bounce rate at 33.3%.

---

## 2026-03-08: Performance Audit

### GA Metrics Trend (12-day comparison)

| Metric | 02-24 | 03-08 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 27.0% | ✅ -41.2% |
| Engagement Rate | 31.8% | 73.0% | ✅ +41.2% |
| Homepage Bounce | 75.7% | 58.1% | ✅ -17.6% |
| Avg Session | 200s | 879s | ✅ +679s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 58.1% | 292s | ✅ IMPROVED |
| `/dashboard/` | 0% | 50s | ✅ Excellent |
| `/tools/meta-tag-analyzer/` | 0% | 272s | ✅ Excellent |
| `/ai-visibility/new/` | 0% | 161s | ✅ Excellent |
| `/competitors/` | 20% | 43s | ✅ Excellent |
| `/comparison/` | 43% | 211s | ✅ Good |
| `/sites/1/audits/1/` | 0% | 2062s | ✅ Excellent |

### Backend Status

**N+1 Query (KeywordController::index):** Still present at lines 106-108
- `getLatestRankings()` called inside map loop
- Impact: 501 queries for 500 keywords

**DashboardController:** 5-minute cache mitigates memory issue

**MILESTONE:** Bounce rate dropped below 30% for first time. Engagement rate exceeded 70%.

---

## 2026-03-10: Performance Audit

### GA Metrics Trend (14-day comparison)

| Metric | 02-24 | 03-10 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 26.3% | ✅ -41.9% |
| Engagement Rate | 31.8% | 73.7% | ✅ +41.9% |
| Homepage Bounce | 75.7% | 54.7% | ✅ -21.0% |
| Avg Session | 200s | 754s | ✅ +554s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 54.7% | 290s | ✅ IMPROVED |
| `/dashboard/` | 0% | 45s | ✅ Excellent |
| `/ai-visibility/new/` | 0% | 478s | ✅ Excellent |
| `/tools/meta-tag-analyzer/` | 0% | 272s | ✅ Excellent |
| `/compare/seoindicator-vs-ahrefs/` | 0% | 142s | ✅ Excellent |
| `/competitors/` | 20% | 43s | ✅ Excellent |
| `/comparison/` | 44% | 164s | ✅ Good |
| `/sites/1/audits/1/` | 0% | 2062s | ✅ Excellent |

### Backend Status

**N+1 Query (KeywordController::index):** Still present in map block around lines 112-114
- `getLatestRankings()` called inside map loop
- Impact remains ~501 queries for 500 keywords

**DashboardController:** 5-minute cache mitigates memory issue, but cold cache still loads full ranking history into memory

### Tooling Status

**GA helper script degraded locally:**
- `frontend/scripts/ga-analytics.mjs` currently fails because `@google-analytics/data` is missing from `frontend/node_modules`
- Direct REST call with the existing service account still works, so audit coverage is preserved

**MILESTONE:** Homepage bounce improved to 54.7%. Engagement remains above 70% while overall bounce stays below 30%.

---

## 2026-03-12: Performance Audit - FIXES DEPLOYED

### GA Metrics Trend (16-day comparison)

| Metric | 02-24 | 03-12 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 24.4% | ✅ -43.8% |
| Engagement Rate | 31.8% | 75.6% | ✅ +43.8% |
| Homepage Bounce | 75.7% | 54.0% | ✅ -21.7% |
| Avg Session | 200s | 1027s | ✅ +827s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 54.0% | 302s | ✅ IMPROVED |
| `/dashboard/` | 0% | 309s | ✅ Excellent |
| `/login/` | 0% | 549s | ✅ Excellent |
| `/register/` | 0% | 1524s | ✅ Excellent |
| `/ai-visibility/new/` | 0% | 478s | ✅ Excellent |
| `/tools/meta-tag-analyzer/` | 0% | 247s | ✅ Excellent |
| `/locations/preview/rankings/` | 0% | 1209s | ✅ Excellent |
| `/competitors/` | 20% | 43s | ✅ Excellent |

### Backend Status - FIXES DEPLOYED

**N+1 Query (KeywordController::index):** ✅ **FIXED**
- Now uses batch fetching with `getLatestRankingsForKeywords()`
- MySQL: Window function (`ROW_NUMBER() OVER PARTITION BY`)
- SQLite: Eloquent with grouping for test compatibility
- Reduces 501 queries → 2 queries for 500 keywords

**DashboardController Memory Issue:** ✅ **FIXED**
- Old: Loaded entire ranking history (15,000+ rows for 500 keywords × 30 days)
- New: Window function fetches only 2 latest rankings per keyword (1,000 rows)
- 5-minute cache (300s TTL) remains in place

**Deployment:** 2026-03-12, ID `20260312T114059Z-2cd59575`

### Tooling Status

**GA helper script degraded locally:**
- `frontend/scripts/ga-analytics.mjs` still fails locally
- Direct REST call with service account continues to work for audits

**MILESTONE:** Performance optimizations deployed. N+1 query fixed. Dashboard memory issue fixed. Engagement at 75.6%, bounce at 24.4%.

---

## 2026-03-14: Performance Audit

### GA Metrics Trend (18-day comparison)

| Metric | 02-24 | 03-14 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 20.5% | ✅ -47.7% |
| Engagement Rate | 31.8% | 79.5% | ✅ +47.7% |
| Homepage Bounce | 75.7% | 50.4% | ✅ -25.3% |
| Avg Session | 200s | 1049s | ✅ +849s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 50.4% | 325s | ✅ **MILESTONE - Below 50%** |
| `/login/` | 0% | 486s | ✅ Excellent |
| `/dashboard/` | 0% | 237s | ✅ Excellent |
| `/register/` | 0% | 862s | ✅ Excellent |
| `/ai-visibility/new/` | 0% | 478s | ✅ Excellent |
| `/competitors/` | 17% | 55s | ✅ Excellent |
| `/tools/meta-tag-analyzer/` | 0% | 221s | ✅ Excellent |
| `/seo-guides/` | 0% | 563s | ✅ Excellent |

### Backend Status

**N+1 Query (KeywordController::index):** ✅ **FIXED** - Deployed and stable
- Batch fetching with `getLatestRankingsForKeywords()`
- MySQL: Window function (`ROW_NUMBER() OVER PARTITION BY`)
- SQLite: Eloquent with grouping for test compatibility

**DashboardController Memory Issue:** ✅ **FIXED** - Deployed and stable
- Window function fetches only 2 latest rankings per keyword
- 5-minute cache (300s TTL) in place

**Deployment:** 2026-03-12, ID `20260312T114059Z-2cd59575`

### Tooling Status

**GA helper script degraded locally:**
- `frontend/scripts/ga-analytics.mjs` still fails locally
- Direct REST call with service account continues to work for audits

**MILESTONE:** Homepage bounce rate dropped below 50% for the first time (50.4%). Overall bounce at 20.5%. Engagement at 79.5%. All fixes stable.

---

## 2026-03-15: Performance Audit

### GA Metrics Trend (19-day comparison)

| Metric | 02-24 | 03-15 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 19.6% | ✅ -48.6% |
| Engagement Rate | 31.8% | 80.4% | ✅ +48.6% |
| Homepage Bounce | 75.7% | 35.6% | ✅ -40.1% |
| Avg Session | 200s | 1022s | ✅ +822s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 35.6% | 469s | ✅ **MAJOR BREAKTHROUGH** |
| `/login/` | 0% | 480s | ✅ Excellent |
| `/dashboard/` | 0% | 227s | ✅ Excellent |
| `/register/` | 0% | 777s | ✅ Excellent |
| `/ai-visibility/new/` | 0% | 478s | ✅ Excellent |
| `/competitors/` | 17% | 55s | ✅ Excellent |
| `/tools/meta-tag-analyzer/` | 0% | 221s | ✅ Excellent |
| `/seo-guides/` | 0% | 563s | ✅ Excellent |

### Backend Status

**N+1 Query (KeywordController::index):** ✅ **FIXED** - Deployed and stable
- Batch fetching with `getLatestRankingsForKeywords()`
- MySQL: Window function (`ROW_NUMBER() OVER PARTITION BY`)
- SQLite: Eloquent with grouping for test compatibility

**DashboardController Memory Issue:** ✅ **FIXED** - Deployed and stable
- Window function fetches only 2 latest rankings per keyword
- 5-minute cache (300s TTL) in place

**Deployment:** 2026-03-12, ID `20260312T114059Z-2cd59575`

### Tooling Status

**GA helper script degraded locally:**
- `frontend/scripts/ga-analytics.mjs` still fails locally
- Direct REST call with service account continues to work for audits

**MILESTONE:** Homepage bounce dropped below 40% (35.6%) - a 14.8 point improvement in one day. Engagement exceeded 80% (80.4%). Bounce rate at all-time low (19.6%).

---

## 2026-03-16: Performance Audit

### GA Metrics Trend (20-day comparison)

| Metric | 02-24 | 03-16 | Change |
|--------|-------|-------|--------|
| Bounce Rate | 68.2% | 20.5% | ✅ -47.7% |
| Engagement Rate | 31.8% | 79.5% | ✅ +47.7% |
| Homepage Bounce | 75.7% | 33.3% | ✅ -42.4% |
| Avg Session | 200s | 1064s | ✅ +864s |

### Page Performance

| Page | Bounce | Duration | Status |
|------|--------|----------|--------|
| `/` | 33.3% | 476s | ✅ **BELOW 35%** |
| `/login/` | 0% | 458s | ✅ Excellent |
| `/dashboard/` | 0% | 227s | ✅ Excellent |
| `/register/` | 0% | 777s | ✅ Excellent |
| `/ai-visibility/new/` | 0% | 478s | ✅ Excellent |
| `/competitors/` | 17% | 55s | ✅ Excellent |
| `/tools/meta-tag-analyzer/` | 0% | 221s | ✅ Excellent |
| `/seo-guides/` | 0% | 563s | ✅ Excellent |

### Backend Status

**N+1 Query (KeywordController::index):** ✅ **FIXED** - Deployed and stable
- Batch fetching with `getLatestRankingsForKeywords()`
- MySQL: Window function (`ROW_NUMBER() OVER PARTITION BY`)
- SQLite: Eloquent with grouping for test compatibility

**DashboardController Memory Issue:** ✅ **FIXED** - Deployed and stable
- Window function fetches only 2 latest rankings per keyword
- 5-minute cache (300s TTL) in place

**Deployment:** 2026-03-12, ID `20260312T114059Z-2cd59575`

### Tooling Status

**GA helper script degraded locally:**
- `frontend/scripts/ga-analytics.mjs` still fails locally
- Direct REST call with service account continues to work for audits

**MILESTONE:** Homepage bounce dropped below 35% (33.3%). Session duration at 1064s (17.7 minutes). Engagement near 80%. All fixes stable.

---

Last updated: 2026-03-16
