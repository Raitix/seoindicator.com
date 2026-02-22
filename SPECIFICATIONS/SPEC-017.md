# SPEC-017: Performance Optimization — GSC Ingest, Comparison Summary, Web Vitals

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: (Performance Optimization Initiative — no FEAT file)
- Tasks: (To be created by SpecSystemAnalyst)
- Related Specs: SPEC-012 (GSC Integration), SPEC-003 (Competitor Comparison)

---

## Overview

This specification targets the highest-impact performance bottlenecks identified in backend data ingestion and comparison summaries, plus adds frontend Web Vitals telemetry for evidence-driven tuning.

---

## Evidence & Impact

### GA (Last 30 Days)
- **Bounce Rate:** ~79%
- **Avg Session Duration:** ~60.6s
- **Top Pages:** `/` (113 views, bounce ~78%), `/seo-guides/*` (avg duration ~1.8s, bounce 60–72%)
- **Devices:** Desktop-heavy (123 sessions) with smaller mobile segment (30 sessions)

**Interpretation:** High bounce + short guide durations suggests performance/first-impression risk. We need Web Vitals visibility per route before optimizing UI/UX further.

---

## Goals
1. Reduce DB load and sync time during GSC ingestion.
2. Eliminate N+1 and in-memory aggregation in comparison summary.
3. Introduce Web Vitals telemetry for real user monitoring (RUM).
4. Preserve free-tools constraint (no paid services).

## Non-Goals
- No UI redesign.
- No paid analytics tooling.
- No new external APIs.

---

## Backend Optimizations

### 1) Batch Upsert for GSC Data Sync
**Problem:** `GSCDataSync::storeData()` uses **per-row upsert**, causing up to 25k writes per chunk.

**Optimization:**
- Build an array of rows and upsert in **chunks** (e.g., 500–1000 rows).
- Use a single `GSCData::upsert($rows, uniqueKeys, updateCols)` per chunk.
- Wrap chunk batch in a DB transaction.

**Expected Gains:** 10–100× fewer DB operations; faster syncs and less contention.

**Notes:** Ensure the unique keys match DB schema (site_id + query + page + data_date).

---

### 2) Comparison Summary Aggregation in SQL
**Problem:** `ComparisonService::getSummary()` loads all keywords + competitor rankings and loops in PHP → high memory & N+1 risk.

**Optimization Approach:**
- Replace in-memory loops with **SQL aggregate counts** using joins/subqueries.
- Compute latest user ranking (from `ranking_history`) and competitor rankings (from `competitor_rankings`) per keyword.
- Use `SUM(CASE WHEN ... THEN 1 END)` for each status bucket.

**Expected Gains:** O(1) query, low memory, predictable latency for large datasets.

---

### 3) API Pagination for Competitor Lists
**Problem:** `/api/v1/competitors` returns all competitors unpaginated.

**Optimization:**
- Use `simplePaginate` or `cursorPaginate` with `limit` and `cursor` params.
- Return minimal columns in list (id, domain, display_name, updated_at).

**Expected Gains:** Reduced payloads and improved response time with scale.

---

## Frontend Performance Telemetry

### 4) Web Vitals (RUM) to GA
**Problem:** No route-level performance telemetry in GA.

**Optimization:**
- Implement `reportWebVitals` in Next.js to send LCP, CLS, INP, TTFB to GA as custom events.
- Tag events with route/path and device category.

**Expected Gains:** Actionable performance data to prioritize UI optimizations by route.

---

## Database Index Review
- Verify/adjust indexes for summary queries:
  - `ranking_history (keyword_id, checked_at)` **already present**.
  - Add/ensure composite index on `competitor_rankings (keyword_id, checked_at)` for latest ranking selection.
- Confirm `gsc_data` composite index aligns with upsert keys.

---

## Acceptance Criteria
- GSC sync handles 25k rows with **≤ 50 DB writes** per chunk.
- Comparison summary endpoint completes in **< 200ms** for 10k keywords.
- GA receives **Web Vitals events** per route (LCP/CLS/INP/TTFB).
- Competitor list API supports pagination with stable response time.

---

## Rollout Plan
1. Backend batch upsert (safe, isolated change).
2. SQL aggregation for summary (requires validation vs existing logic).
3. Pagination change (update API docs + UI call sites).
4. Add Web Vitals reporting; validate events in GA DebugView.

---

## Risks & Mitigations
- **Risk:** SQL aggregation logic mismatch with current counts.
  - **Mitigation:** Write comparison tests and validate with small fixture datasets.
- **Risk:** Pagination breaks existing clients.
  - **Mitigation:** Default limit + backward-compatible response shape.

---

## Comments
_Agent log_

### [2026-02-22] Agent: PerformanceOptimizer
Created SPEC-017 from daily performance audit. Focused on GSC ingest batching, SQL aggregation for comparison summary, API pagination, and Web Vitals telemetry to GA.
