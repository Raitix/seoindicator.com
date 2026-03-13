# DevOps.md â€” Project Lessons Learned

Knowledge base for DevOps agent working on SEOIndicator project.

---

## Deployment Issues

_(Document deployment problems and solutions)_

---

## Rollback History

_(Document rollback events and causes)_

---

## Deployment Notes

### TASK-25-23 (OAuth Integration Tests)
- **Date:** 2026-03-01
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22547036649
- **Result:** Successful
- **Duration:** ~2 minutes (build: 1m22s, deploy: 33s)
- **Commit:** `dcb4829`
- **Health Check:** 200 OK, database connected
- **Note:** Integration tests for OAuth flow (18 tests, 50 assertions). Tests cover state management, user creation/linking, token authentication, and error handling.

### TASK-25-7 (GoogleOAuthService)
- **Date:** 2026-03-01
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22545442333
- **Result:** Successful
- **Duration:** ~2 minutes (build: 1m22s, deploy: 29s)
- **Deployed Files:**
  - `GoogleOAuthService.php` - 366 lines
  - 5 exception classes
  - 2 migrations (add_avatar_to_users, make_password_nullable)
  - Test files
- **Health Check:** 200 OK, database connected
- **Note:** OAuth service implementation for Google sign-in. Migrations add avatar support and nullable password for OAuth-only users.

### TASK-25-6 (Google OAuth Credentials Configuration)
- **Date:** 2026-03-01
- **Type:** Configuration task (not code deployment)
- **Result:** No code deployment needed
- **Note:** This is an external service configuration task. Most acceptance criteria require manual user action in Google Cloud Console. Code-level work (env vars, services.php) was already complete. No code changes to deploy (.env is gitignored). OAuth functionality depends on user obtaining real credentials from Google Cloud Console.

### TASK-25-3 (AuthController - Register, Login, Logout)
- **Date:** 2026-03-01
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22544253623
- **Result:** Successful
- **Note:** Changes were already committed by Developer. Deployment triggered via `git push origin main`. Health check confirmed.

### TASK-25-12 (AnalysisHistory Model & Repository)
- **Date:** 2026-03-01
- **Method:** GitHub Actions (automatic)
- **Result:** Successful
- **Note:** Changes were already pushed to origin/main by Developer. Deployment triggered automatically. Health check confirmed.

### TASK-28-3 (shadcn/ui Components)
- **Date:** 2026-03-04
- **Method:** Already deployed (commit 27e3c55 was in origin/main)
- **Result:** Successful
- **Components:** skeleton, progress, badge, tooltip, avatar, dropdown-menu, sheet, tabs, separator
- **Health Check:** 200 OK, database connected
- **Note:** Components were installed by Developer as prerequisite for TASK-28-13. No additional deployment needed.

### TASK-21-11 (AI Suggestion Frontend Integration)
- **Date:** 2026-03-05
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22713584536
- **Result:** Successful
- **Duration:** ~3 minutes (build: 1m37s, deploy: ~30s)
- **Commit:** 696b716
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - AISuggestionButton component
  - AISuggestionPanel component
  - AI suggestion API integration
  - ToolWidget integration with AI suggestions
  - 179 tests passing
- **Note:** Frontend code for AI suggestions was already in origin/main from earlier commits. This deployment triggered the final status update.

### TASK-21-6 (AI Caching Layer)
- **Date:** 2026-03-05
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22715459300
- **Result:** Successful
- **Duration:** ~3 minutes (build: 1m42s, deploy: 35s)
- **Commit:** 8666edd
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - AISuggestionCache service (SHA-256 hashing, TTL management)
  - AICacheController (admin endpoints)
  - AICacheClear artisan command
  - Cache configuration in config/ai.php
- **Cache Features:**
  - Hash generation: SHA-256 of normalized JSON
  - Default TTL: 24 hours (configurable)
  - Admin endpoints: stats, invalidate, clear
  - Performance: <5ms hash, <1ms get, <2ms set
- **Note:** Implementation already committed by Developer. Deployment triggered via push to origin/main.

### TASK-24-7 (PWASuggestionService)
- **Date:** 2026-03-05
- **Method:** Already deployed (code was in origin/main)
- **Result:** Successful
- **Commits:** 4b9982cd (service), ab75665e + e013c8b8 (tests)
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - PWASuggestionService - generates actionable suggestions for PWA analysis
  - 51 unit tests covering all suggestion scenarios
  - Priority-based sorting (high â†’ medium â†’ low)
- **Note:** Service and tests were already in origin/main from Developer/LocalTester work. No additional deployment needed.

### TASK-32-2 (Comparison Registry and Data Loading)
- **Date:** 2026-03-05
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22725116735
- **Result:** Successful
- **Duration:** ~2 minutes (build: 1m37s, deploy: 30s)
- **Commit:** 9d3b7d3d
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - `frontend/lib/compare/registry.ts` - Registry with helper functions (getCompetitorById, getComparisonBySlug, parseSlug, generateComparisonData, validateCompetitorData, validateComparisonData)
  - `frontend/lib/compare/competitors/seoindicator.ts` - SEOIndicator competitor data with pricing, features, pros/cons, use cases
  - 200 unit tests (150 for registry, 50 for SEOIndicator data)
- **Note:** Comparison registry system for programmatic SEO competitor comparison pages. Part of SPEC-32 Phase 1 foundation.

### TASK-14-10 (BrokenLinkChecker Service)
- **Date:** 2026-03-06
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22780786659
- **Result:** Successful
- **Duration:** ~2m20s (build: 1m46s, deploy: 34s)
- **Commit:** 96986bc2
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - `BrokenLinkChecker.php` - Main service for detecting broken links in HTML
  - `BrokenLinkResult.php` - Result DTO with factory methods
  - `CheckSummary.php` - Summary DTO for aggregated results
  - `audit.php` config - Audit configuration
  - 2 migrations for audit options and cancelled status
  - 141 tests (275 assertions)
- **Pre-Deployment Fixes:**
  - Added missing factory methods to BrokenLinkResult (valid, broken, redirect)
  - Fixed test helper default for elementType
- **Note:** DevOps had to fix test failures before deployment. The Developer had implemented the core logic but tests expected factory methods that were missing. Always verify tests pass before deployment.

### TASK-14-13 (AuditController - API Endpoints)
- **Date:** 2026-03-06
- **Method:** GitHub Actions (automatic)
- **Commit:** 90227c59
- **Result:** Successful (code was already in origin/main)
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - `AuditController.php` - 4 endpoints for audit management
  - `StoreAuditRequest.php` - Request validation
  - Rate limiter `audit-trigger` (5/hour per user)
  - Routes in `routes/api.php`
- **API Endpoints:**
  - `POST /api/v1/sites/{siteId}/audits` - Trigger new audit
  - `GET /api/v1/audits/{auditId}` - Get audit status
  - `GET /api/v1/sites/{siteId}/audits` - List audits for site
  - `DELETE /api/v1/audits/{auditId}` - Cancel running audit
- **Tests:** 71 tests, 164 assertions - all passing
- **Note:** Code was already committed and pushed by Developer. DevOps verified deployment status and updated task to "To Test Prod".

---

Last updated: 2026-03-07
### TASK-33-29 (Badge Analytics Dashboard)
- **Date:** 2026-03-08
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22819076567
- **Result:** Successful
- **Duration:** ~3 minutes (build: 2m15s, deploy: 38s)
- **Commit:** 0e7e6e6d
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - `frontend/components/dashboard/BadgeAnalyticsDashboard.tsx` - Main dashboard with charts, tables, date selector
  - `frontend/components/dashboard/BadgeStatsCard.tsx` - Stats card component with change indicators
  - 120 unit tests for dashboard components
- **Note:** Frontend-only feature for badge performance analytics. Uses Recharts for charts, responsive design with grid layouts. Part of FEAT-033 (Public SEO Audit Reports & Embeddable Score Widget).
### TASK-33-26 (JSON-LD Schema for Public Audit Pages)
- **Date:** 2026-03-08
- **Method:** GitHub Actions (automatic)
- **Commit:** 47225957 (already in origin/main)
- **Result:** Successful
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - `frontend/lib/audit/json-ld-schema.ts` - Schema generator with sanitization
  - 95 unit tests for schema structure, security, edge cases
- **Schema Types:** WebPage → Analysis → Rating (schema.org)
- **Security:** Input sanitization removes XSS vectors, no sensitive data in output
- **Note:** Code was already committed by Developer/LocalTester. DevOps verified deployment status and updated task to "To Test Prod".
### TASK-32-14 (Free Alternatives Data - GSC, GA, AnswerThePublic)
- **Date:** 2026-03-07
- **Method:** GitHub Actions (automatic)
- **Commit:** 6517f27e (pushed to origin/main)
- **Result:** Successful
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - rontend/lib/compare/competitors/gsc.ts - Google Search Console competitor data
  - rontend/lib/compare/competitors/ga.ts - Google Analytics competitor data
  - rontend/lib/compare/competitors/answerthepublic.ts - AnswerThePublic competitor data
- **Tests:** 210 tests passing (LocalTester verified)
- **Positioning:**
  - GSC: Essential complement for indexing (not replacement)
  - GA: Traffic analytics (not SEO-specific)
  - ATP: Limited free tier (3 searches/day)
- **Note:** Code was committed by LocalTester. DevOps pushed to origin/main to trigger deployment.

### TASK-34-17 (Frontend Competitor Comparison Page)
- **Date:** 2026-03-08
- **Method:** GitHub Actions (automatic)
- **Run ID:** 22821575609
- **Result:** Successful
- **Duration:** ~4 minutes (build: 3m9s, deploy: 33s)
- **Commit:** 391011d5
- **Health Check:** 200 OK, database connected
- **What was deployed:**
  - `frontend/app/ai-visibility/[projectId]/comparison/page.tsx` - Server component
  - `frontend/app/ai-visibility/[projectId]/comparison/page.client.tsx` - Client component
  - All comparison components from TASK-34-16
- **Tests:** 443 tests passing (LocalTester verified)
- **Note:** Frontend comparison page for AI Visibility feature. Part of SPEC-034 (AI Visibility Tracking). Uses components from TASK-34-16 for charts and comparison logic.

### TASK-35-9 (GBP Insights Sync Service)
- **Date:** 2026-03-10
- **Method:** Local/self-hosted deployment
- **Deployment ID:** 20260310T214330Z-7beae7bf
- **Result:** Successful
- **Duration:** ~3 minutes (build: 1m20s, deploy: 1m16s, smoke: 8s)
- **Commit:** aa55349f
- **Health Check:** 200 OK, production smoke passed
- **What was deployed:**
  - `GBPInsightsService.php` - Domain service for GBP insights sync
  - `GBPInsightsServiceInterface.php` - Service interface
  - `SyncInsightsJob.php` - Queue job for background sync
  - Container binding in AppServiceProvider
  - 17 tests (35 assertions)
- **Issue Encountered:** Frontend build failed with `ERR_MODULE_NOT_FOUND: Cannot find package 'satori'` because the temp worktree's node_modules was linked from the main project but `satori` wasn't installed there.
- **Fix:** Ran `npm install satori --save-dev` in the main frontend directory before retrying deployment.
- **Lesson:** When deployment fails due to missing npm packages in the temp worktree, install them in the main project's frontend/node_modules (which is linked to the temp worktree) and retry.

### TASK-28-15 (Custom SVG Icons)
- **Date:** 2026-03-12
- **Method:** Local/self-hosted deployment
- **Deployment ID:** 20260312T172514Z-3bedd1af
- **Result:** Successful
- **Duration:** ~2 minutes 17 seconds (build: 55s, deploy: 73s, smoke: 5s)
- **Commit:** b47da1f99950917aa07fd32cc37ed9cb18c76630
- **Health Check:** 200 OK, production smoke passed
- **What was deployed:**
  - `frontend/components/icons/custom/registry.ts` - Custom icon registry with types
  - `frontend/components/icons/custom/index.ts` - Barrel exports
  - Registry starts empty (Lucide covers all 8 indicators)
- **Note:** Frontend-only feature. Registry infrastructure for future custom icons. All acceptance criteria verified by LocalTester before deployment.

### TASK-4-17 (Email Service - Free SMTP)
- **Date:** 2026-03-12
- **Method:** Local/self-hosted deployment
- **Deployment ID:** 20260312T212045Z-9625efce
- **Result:** Successful
- **Duration:** ~2 minutes 21 seconds (build: 59s, deploy: 72s, smoke: 6s)
- **Commit:** f383d7a831ed
- **Health Check:** 200 OK, production smoke passed
- **What was deployed:**
  - `backend/app/Services/Mail/EmailSenderService.php` - Main email service
  - `backend/app/Services/Mail/Contracts/EmailSenderInterface.php` - Interface
  - `backend/app/Services/Mail/Exceptions/EmailSendException.php` - Exception handling
- **Features:**
  - Gmail SMTP support (500/day limit)
  - Brevo SMTP support (300/day free tier)
  - PDF/CSV attachment handling
  - Retry logic (3 attempts, 5s delay)
  - Comprehensive logging
- **Tests:** 24 tests, 44 assertions - all passing
- **Note:** Backend-only feature for email delivery. Code was already committed by Developer/LocalTester. DevOps verified deployment and updated status.

### TASK-22-10 (Link Extraction and Queue Management)
- **Date:** 2026-03-13
- **Method:** Local/self-hosted deployment
- **Deployment ID:** 20260313T164937Z-0c17c1fa
- **Result:** Successful
- **Duration:** ~3 minutes (build: 1m18s, deploy: 1m24s, smoke: 10s)
- **Commit:** 92a944d7c29f
- **Health Check:** 200 OK, production smoke passed
- **What was deployed:**
  - `CrawlQueueService.php` - In-memory queue with priority ordering, duplicate detection, retry logic
  - `LinkExtractorService.php` - Link extraction from HTML, JS onclick handling, URL resolution
  - 93 tests (148 assertions) - ALL PASS
  - DTOs: QueueItem, QueueStats
- **Issue Encountered:** Initial deployment failed due to missing `satori` package. Fixed with `npm install satori --save-dev`.
- **Note:** Part of SPEC-022 (Headless Crawler Service). Backend-only services for multi-page crawling support.

