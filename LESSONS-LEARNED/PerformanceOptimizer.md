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

Last updated: 2026-02-27
