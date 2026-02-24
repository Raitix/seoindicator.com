# SPEC-001-20: Performance Optimization

## STATUS
`To Develop`
## PRIORITY
`Low`

## RISK
`Low`

## Description
Optimize performance for rank tracking operations, focusing on database queries, caching, and API response times.

## Acceptance Criteria
- [ ] Keyword list loads in <500ms with 100+ keywords
- [ ] Dashboard loads in <300ms
- [ ] Ranking history query optimized with proper indexes
- [ ] Implement query result caching (5 min TTL for dashboard)
- [ ] Add database indexes based on query patterns
- [ ] Implement lazy loading for ranking history
- [ ] Profile and optimize slow queries
- [ ] Add performance monitoring/logging
- [ ] Document performance benchmarks

## Dependencies
- All previous tasks (system must be functional)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Use query explain plans to identify bottlenecks
- Consider read replicas for production (future)
- Implement pagination efficiently
- Cache computed values (position changes, averages)

---

## Comments
_Agent communication log_

### [2026-02-24 16:15] Agent: PerformanceOptimizer
**Performance Audit Findings**

**Google Analytics Data (Last 7 Days):**
| Metric | Value | Issue |
|--------|-------|-------|
| Bounce Rate | 68.2% | HIGH - Users leaving quickly |
| Homepage Bounce | 75.7% | CRITICAL - First impression issue |
| SEO Guides Duration | 1.7-2.0s | LOW - Content not engaging |
| Engagement Rate | 31.8% | LOW |

**Critical N+1 Query Found in KeywordController::index():**
```php
// Lines 45-55: N+1 Query Problem
$keywords = Keyword::where('site_id', $siteId)->get();
$formatted = $keywords->map(function (Keyword $keyword) {
    [$current, $previous] = $this->getLatestRankings($keyword); // <-- Each keyword triggers separate query!
    return $this->formatKeyword($keyword, $current, $previous);
});
```
Impact: For 500 keywords, this makes **501 queries** instead of 2.

**DashboardController Memory Issue:**
```php
// Lines 26-30: Loads ALL rankings into memory
$rankings = Ranking::whereIn('keyword_id', $keywordIds)
    ->orderBy('checked_at', 'desc')
    ->get()  // <-- Loads entire history, not just latest 2 per keyword
    ->groupBy('keyword_id');
```
Impact: With 500 keywords × 30 days history = 15,000 rows loaded unnecessarily.

**Recommendations:**
1. **URGENT**: Fix N+1 query in KeywordController using eager loading:
   ```php
   $keywords = Keyword::with(['rankingHistory' => fn($q) => $q->orderBy('checked_at', 'desc')->take(2)])
       ->where('site_id', $siteId)->get();
   ```
2. **Dashboard**: Use subquery to get only latest 2 rankings per keyword
3. **Add indexes**: Consider composite index on (keyword_id, checked_at DESC) for ranking_history
4. **Homepage**: Investigate 75.7% bounce rate - may need UX/content review

**Priority Recommendation:** Raise from Low to **Medium** due to actual N+1 query issue found.

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for performance optimization. Priority Low - polish after core functionality works.
