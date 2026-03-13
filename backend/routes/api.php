<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\CompetitorController;
use App\Http\Controllers\Api\V1\ComparisonController;
use App\Http\Controllers\Api\V1\IssueTypeController;
use App\Http\Controllers\Api\V1\GSCDataController;
use App\Http\Controllers\Api\V1\KeywordController;
use App\Http\Controllers\Api\V1\KeywordsApiController;
use App\Http\Controllers\Api\V1\RankingsApiController;
use App\Http\Controllers\Api\V1\SitesApiController;
use App\Http\Controllers\Api\V1\ReportsApiController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\SchedulerController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReportTemplateController;
use App\Http\Controllers\Api\V1\SerpFeatureController;
use App\Http\Controllers\Api\V1\APIProxyController;
use App\Http\Controllers\Api\GSCConnectionController;
use App\Http\Controllers\Api\IndicatorsController;
use App\Http\Controllers\Api\V1\SuggestionController;
use App\Http\Controllers\Api\V1\AnnotationController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\AnalysisJobController;
use App\Http\Controllers\Api\V1\AlertSettingsController;
use App\Http\Controllers\Api\V1\KeywordAlertSettingsController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\AlertUnsubscribeController;
use App\Http\Controllers\Api\V1\AnalysisHistoryController;
use App\Http\Controllers\Api\V1\UserDashboardController;
use App\Http\Controllers\Api\V1\AISuggestionController;
use App\Http\Controllers\Api\V1\CrawlController;
use App\Http\Controllers\Api\V1\ToolExportController;
use App\Http\Controllers\Api\V1\PublicAuditController;
use App\Http\Controllers\Api\V1\CreditController;
use App\Http\Controllers\Api\V1\AuditController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Deploy and health endpoints for production management.
|
*/

// Health check (public)
Route::get('/health', [DeployController::class, 'health']);

// Indicators placeholder (TASK-1-22) - public
Route::get('/indicators', [IndicatorsController::class, 'index']);

// Deploy endpoints (protected by deploy key + rate limiting + logging)
// SPEC-SEC-2026-02-21-3: Rate limiting (5 req/15min) and failed auth logging
Route::middleware(['throttle:deploy', 'log-deploy-auth'])->group(function () {
    Route::post('/deploy/migrate', [DeployController::class, 'migrate']);
    Route::post('/deploy/rollback', [DeployController::class, 'rollback']);
});

/*
|--------------------------------------------------------------------------
| External REST API Routes (TASK-6-8)
|--------------------------------------------------------------------------
|
| External API endpoints for programmatic access.
| Uses API key authentication and per-key rate limiting.
|
*/
Route::prefix('v1')->middleware(['api-key.auth', 'api-key.ratelimit'])->group(function () {
    // Keywords API (TASK-6-8)
    Route::get('keywords', [KeywordsApiController::class, 'index'])->middleware('api-key.auth:keywords:read');
    Route::post('keywords', [KeywordsApiController::class, 'store'])->middleware('api-key.auth:keywords:write');
    Route::get('keywords/{id}', [KeywordsApiController::class, 'show'])->middleware('api-key.auth:keywords:read');
    Route::put('keywords/{id}', [KeywordsApiController::class, 'update'])->middleware('api-key.auth:keywords:write');
    Route::delete('keywords/{id}', [KeywordsApiController::class, 'destroy'])->middleware('api-key.auth:keywords:write');

    // Rankings API (TASK-6-9)
    Route::get('keywords/{id}/rankings', [RankingsApiController::class, 'index'])
        ->middleware('api-key.auth:rankings:read')
        ->where('id', '[0-9]+');

    // Sites API (TASK-6-10)
    Route::get('sites', [SitesApiController::class, 'index'])->middleware('api-key.auth:sites:read');

    // Reports API (TASK-6-11)
    Route::post('reports', [ReportsApiController::class, 'store'])->middleware('api-key.auth:reports:write');

    // Webhooks API (TASK-6-13)
    Route::get('webhooks', [\App\Http\Controllers\Api\V1\WebhookController::class, 'index'])->middleware('api-key.auth:webhooks:read');
    Route::post('webhooks', [\App\Http\Controllers\Api\V1\WebhookController::class, 'store'])->middleware('api-key.auth:webhooks:write');
    Route::get('webhooks/{id}', [\App\Http\Controllers\Api\V1\WebhookController::class, 'show'])->middleware('api-key.auth:webhooks:read');
    Route::put('webhooks/{id}', [\App\Http\Controllers\Api\V1\WebhookController::class, 'update'])->middleware('api-key.auth:webhooks:write');
    Route::delete('webhooks/{id}', [\App\Http\Controllers\Api\V1\WebhookController::class, 'destroy'])->middleware('api-key.auth:webhooks:write');
    Route::post('webhooks/{id}/test', [\App\Http\Controllers\Api\V1\WebhookController::class, 'test'])->middleware('api-key.auth:webhooks:write');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes (TASK-25-3)
|--------------------------------------------------------------------------
|
| User authentication endpoints for registration, login, logout, and user retrieval.
| Uses Laravel Sanctum for API token authentication.
|
*/

// Public auth routes
Route::post('/v1/auth/register', [AuthController::class, 'register']);
Route::post('/v1/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

// Google OAuth routes (TASK-25-8)
Route::post('/v1/auth/google', [GoogleAuthController::class, 'redirect']);
Route::post('/v1/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('throttle:5,1');

// Password reset routes (TASK-25-4)
Route::post('/v1/auth/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/v1/auth/reset-password', [PasswordResetController::class, 'resetPassword']);

// Email verification routes (TASK-25-5)
Route::get('/v1/auth/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify')
    ->where('id', '[0-9]+');

// Protected auth routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/v1/auth/logout', [AuthController::class, 'logout']);
    Route::get('/v1/auth/user', [AuthController::class, 'user']);

    // Email verification resend (TASK-25-5)
    Route::post('/v1/auth/resend-verification', [EmailVerificationController::class, 'resend']);
});

/*
|--------------------------------------------------------------------------
| Public Audit Routes (TASK-33-5, TASK-33-7, SPEC-33)
|--------------------------------------------------------------------------
|
| Public SEO audit endpoints for sharing audit results.
| No authentication required - access controlled via unique hash.
| Rate limited to prevent abuse.
|
*/

// Public audit routes (no authentication)
Route::prefix('v1/public-audit')->middleware(['throttle:public-audit'])->group(function () {
    Route::get('/{hash}', [PublicAuditController::class, 'show'])
        ->name('api.v1.public-audit.show')
        ->where('hash', '[a-zA-Z0-9]+');
    Route::post('/{hash}/lead', [PublicAuditController::class, 'lead'])
        ->name('api.v1.public-audit.lead')
        ->where('hash', '[a-zA-Z0-9]+');
    Route::get('/{hash}/badge', [PublicAuditController::class, 'badge'])
        ->name('api.v1.public-audit.badge')
        ->where('hash', '[a-zA-Z0-9]+');
});

/*
|--------------------------------------------------------------------------
| Badge Tracking Routes (TASK-33-7, TASK-33-17, TASK-33-18, SPEC-33)
|--------------------------------------------------------------------------
|
| Badge impression and click tracking endpoints.
| No authentication required - publicly embeddable widgets.
| Rate limited to prevent abuse.
|
*/
Route::prefix('v1/badge')->middleware(['throttle:badge-tracking'])->group(function () {
    Route::post('/impression', [\App\Http\Controllers\Api\V1\BadgeTrackingController::class, 'impression'])
        ->name('api.v1.badge.impression');
    Route::post('/click', [\App\Http\Controllers\Api\V1\BadgeTrackingController::class, 'click'])
        ->name('api.v1.badge.click');
});

/*
|--------------------------------------------------------------------------
| GSC Connection Routes (TASK-12-7, TASK-12-9)
|--------------------------------------------------------------------------
|
| Google Search Console OAuth connection management.
| All routes require authentication.
|
*/
Route::middleware(['auth'])->group(function () {
    Route::post('/gsc/connect', [GSCConnectionController::class, 'connect']);
    Route::delete('/gsc/disconnect/{propertyId}', [GSCConnectionController::class, 'disconnect']);
    Route::get('/gsc/properties', [GSCConnectionController::class, 'properties']);
    
    // OAuth flow endpoints (TASK-12-9)
    Route::get('/gsc/oauth/redirect', [GSCConnectionController::class, 'redirectToGoogle']);
    Route::get('/gsc/oauth/callback', [GSCConnectionController::class, 'handleGoogleCallback']);
    Route::post('/gsc/oauth/select-property', [GSCConnectionController::class, 'selectProperty']);
    Route::get('/gsc/oauth/available-properties', [GSCConnectionController::class, 'availableProperties']);
});

/*
|--------------------------------------------------------------------------
| V1 API Routes
|--------------------------------------------------------------------------
|
| Versioned API endpoints for the SEOIndicator application.
|
*/

Route::prefix('v1')->group(function () {
    // Models (TASK-26-17) - Public endpoint for model listing
    Route::get('models', [\App\Http\Controllers\Api\V1\ModelController::class, 'index']);

    // Model Pricing (TASK-26-18) - Public endpoint for model pricing details
    Route::get('models/{id}/pricing', [\App\Http\Controllers\Api\V1\ModelController::class, 'pricing'])
        ->where('id', '[0-9]+');

    // Model Preferences (TASK-26-19) - Requires authentication
    Route::middleware(['auth'])->group(function () {
        Route::get('models/preferences', [\App\Http\Controllers\Api\V1\ModelController::class, 'preferences']);
        Route::post('models/preferences', [\App\Http\Controllers\Api\V1\ModelController::class, 'setPreference']);
    });

    // AI Models (TASK-31-14) - Public endpoint for AI model selection
    Route::get('ai/models', [\App\Http\Controllers\Api\V1\AIModelController::class, 'index']);

    // Metadata (TASK-7-18)
    Route::get('metadata', [\App\Http\Controllers\Api\V1\MetadataController::class, 'index']);
    Route::get('metadata/serp-features', [\App\Http\Controllers\Api\V1\MetadataController::class, 'serpFeatures']);

    // Keywords (SPEC-001-4) - Site-scoped routes for web UI
    Route::get('sites/{siteId}/keywords', [KeywordController::class, 'index']);
    Route::post('sites/{siteId}/keywords', [KeywordController::class, 'store']);
    // Note: Individual keyword routes (show/update/destroy) for web UI are in the authenticated group below

    // Dashboard (SPEC-001-6)
    Route::get('sites/{siteId}/dashboard', [DashboardController::class, 'index']);

    // SERP Features (SPEC-007, TASK-7-10, TASK-7-11, TASK-7-10-SEC) - moved to authenticated group

    // Report Templates (SPEC-004-10) - public read for now
    Route::get('sites/{siteId}/report-templates', [ReportTemplateController::class, 'index']);
    Route::get('report-templates/{templateId}', [ReportTemplateController::class, 'show']);

    // Scheduled Reports (SPEC-004-11) - public read for now
    Route::get('sites/{siteId}/scheduled-reports', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'index']);
    Route::get('scheduled-reports/{scheduledId}', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'show']);

    // SEO Tools API Proxy (TASK-16-1, TASK-16-2, TASK-16-3, TASK-16-7) - public with rate limiting
    Route::post('tools/analyze', [APIProxyController::class, 'analyze']);
    Route::get('tools/captcha-config', [APIProxyController::class, 'captchaConfig']);
    Route::get('tools/rate-limit-status', [APIProxyController::class, 'rateLimitStatus']);
    Route::get('tools', [APIProxyController::class, 'tools']);

    // PWA Guide (TASK-24-9, SPEC-024) - Public endpoint
    Route::get('pwa/guide', [\App\Http\Controllers\Api\V1\PWAGuideController::class, 'index']);
    Route::get('pwa/guide/{id}', [\App\Http\Controllers\Api\V1\PWAGuideController::class, 'section']);

    // Lead Capture (TASK-16-5)
    Route::post('tools/capture-email', [\App\Http\Controllers\Api\V1\LeadCaptureController::class, 'store']);

    // AI Suggestions (TASK-21-5, TASK-21-10) - requires auth + rate limiting
    Route::post('ai/suggestions', [AISuggestionController::class, 'generate'])
        ->middleware(['auth', 'ai.ratelimit']);
});

// Authenticated V1 API Routes
Route::prefix('v1')->middleware(['auth'])->group(function () {
    // Audit Visibility Routes (TASK-33-7, SPEC-33)
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::post('audits/{id}/publish', [\App\Http\Controllers\Api\V1\AuditVisibilityController::class, 'publish'])
            ->name('api.v1.audits.publish')
            ->where('id', '[0-9]+');
        Route::delete('audits/{id}/unpublish', [\App\Http\Controllers\Api\V1\AuditVisibilityController::class, 'unpublish'])
            ->name('api.v1.audits.unpublish')
            ->where('id', '[0-9]+');
        Route::get('audits/{id}/embed-code', [\App\Http\Controllers\Api\V1\AuditVisibilityController::class, 'embedCode'])
            ->name('api.v1.audits.embed-code')
            ->where('id', '[0-9]+');
    });

    // Audit Analytics Routes (TASK-33-28, SPEC-33)
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::get('audits/{id}/analytics', [\App\Http\Controllers\Api\V1\AuditAnalyticsController::class, 'show'])
            ->name('api.v1.audits.analytics')
            ->where('id', '[0-9]+');
    });

    // Audit API Routes (TASK-14-13, SPEC-014)
    Route::post('sites/{site}/audits', [AuditController::class, 'store'])
        ->middleware('throttle:audit-trigger')
        ->where('site', '[0-9]+');
    Route::get('audits/{audit}', [AuditController::class, 'show'])
        ->where('audit', '[0-9]+');
    Route::get('sites/{site}/audits', [AuditController::class, 'index'])
        ->where('site', '[0-9]+');
    Route::delete('audits/{audit}', [AuditController::class, 'destroy'])
        ->where('audit', '[0-9]+');

    // Audit Issue API Routes (TASK-14-14, SPEC-014)
    Route::get('audits/{auditId}/issues', [\App\Http\Controllers\Api\V1\AuditIssueController::class, 'index'])
        ->where('auditId', '[0-9]+');
    Route::patch('audits/{auditId}/issues/bulk', [\App\Http\Controllers\Api\V1\AuditIssueController::class, 'bulkUpdate'])
        ->where('auditId', '[0-9]+');
    Route::get('issues/{issueId}', [\App\Http\Controllers\Api\V1\AuditIssueController::class, 'show'])
        ->where('issueId', '[0-9]+');
    Route::patch('issues/{issueId}', [\App\Http\Controllers\Api\V1\AuditIssueController::class, 'update'])
        ->where('issueId', '[0-9]+');

    // Projects (SPEC-008-7) - CRUD with archive/restore
    Route::post('projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore']);
    Route::apiResource('projects', ProjectController::class);

    // Rankings (SPEC-001-5) - requires auth + IDOR protection
    Route::get('keywords/{id}/history', [RankingController::class, 'history']);
    Route::post('keywords/{id}/check', [RankingController::class, 'check']);

    // Reports (SPEC-004-7, SPEC-004-8, SPEC-004-9) - requires auth
    Route::get('sites/{siteId}/reports', [ReportController::class, 'index']);
    Route::post('sites/{siteId}/reports/generate', [ReportController::class, 'generate']);
    Route::get('reports/{reportId}', [ReportController::class, 'show']);
    Route::delete('reports/{reportId}', [ReportController::class, 'destroy']);
    Route::get('reports/{reportId}/download', [ReportController::class, 'download']);

    // Report Templates (SPEC-004-10) - write operations require auth
    Route::post('sites/{siteId}/report-templates', [ReportTemplateController::class, 'store']);
    Route::put('report-templates/{templateId}', [ReportTemplateController::class, 'update']);
    Route::delete('report-templates/{templateId}', [ReportTemplateController::class, 'destroy']);

    // Scheduled Reports (SPEC-004-11) - write operations require auth
    Route::post('sites/{siteId}/scheduled-reports', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'store']);
    Route::put('scheduled-reports/{scheduledId}', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'update']);
    Route::patch('scheduled-reports/{scheduledId}/pause', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'pause']);
    Route::patch('scheduled-reports/{scheduledId}/resume', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'resume']);
    Route::delete('scheduled-reports/{scheduledId}', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'destroy']);

    // Report Branding (SPEC-004-14, SPEC-004-15)
    Route::get('sites/{siteId}/branding', [\App\Http\Controllers\Api\V1\ReportBrandingController::class, 'show']);
    Route::put('sites/{siteId}/branding', [\App\Http\Controllers\Api\V1\ReportBrandingController::class, 'update']);
    Route::post('sites/{siteId}/branding/logo', [\App\Http\Controllers\Api\V1\ReportBrandingController::class, 'uploadLogo']);
    Route::delete('sites/{siteId}/branding/logo', [\App\Http\Controllers\Api\V1\ReportBrandingController::class, 'deleteLogo']);
    Route::delete('sites/{siteId}/branding', [\App\Http\Controllers\Api\V1\ReportBrandingController::class, 'destroy']);

    // Logo serving (public)
    Route::get('sites/{siteId}/logo', [\App\Http\Controllers\Api\V1\ReportBrandingController::class, 'serveLogo'])->name('api.v1.branding.logo');

    // Comparison (SPEC-003-5)
    Route::get('rankings/comparison', [ComparisonController::class, 'index']);
    Route::get('rankings/comparison/history', [ComparisonController::class, 'history']);
    Route::get('competitors/{id}/rankings', [ComparisonController::class, 'competitorRankings']);
    Route::get('competitors/{id}/gaps', [ComparisonController::class, 'competitorGaps']);

    // Competitor Management (SPEC-003-2)
    Route::apiResource('competitors', CompetitorController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    // Issue Type Management (TASK-14-3)
    // Note: categories route must be before apiResource to avoid being matched by show
    Route::get('issue-types/categories', [IssueTypeController::class, 'categories']);
    Route::apiResource('issue-types', IssueTypeController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    // GSC Data (TASK-12-8)
    Route::get('projects/{projectId}/gsc', [GSCDataController::class, 'index']);
    Route::get('projects/{projectId}/gsc/opportunities', [GSCDataController::class, 'opportunities']);

    // Backlinks Overview (TASK-23-9, SPEC-023)
    Route::get('projects/{projectId}/backlinks', [\App\Http\Controllers\Api\V1\Backlinks\BacklinkController::class, 'index']);

    // Backlinks Domains & Anchors (TASK-23-10, SPEC-023)
    Route::get('projects/{projectId}/backlinks/domains', [\App\Http\Controllers\Api\V1\Backlinks\BacklinkDomainsController::class, 'index']);
    Route::get('projects/{projectId}/backlinks/anchors', [\App\Http\Controllers\Api\V1\Backlinks\BacklinkAnchorsController::class, 'index']);

    // Backlinks History & Sync (TASK-23-11, SPEC-023)
    Route::get('projects/{projectId}/backlinks/history', [\App\Http\Controllers\Api\V1\Backlinks\BacklinkHistoryController::class, 'index']);
    Route::post('projects/{projectId}/backlinks/sync', [\App\Http\Controllers\Api\V1\Backlinks\BacklinkSyncController::class, 'store']);

    // Scheduler (SPEC-001-12)
    Route::post('scheduler/rank-checks/run', [SchedulerController::class, 'runRankChecks']);

    // Keyword Suggestions (SPEC-005, TASK-5-7, TASK-5-8, TASK-5-9)
    Route::get('sites/{siteId}/suggestions', [SuggestionController::class, 'index']);
    Route::post('sites/{siteId}/suggestions/generate', [SuggestionController::class, 'generate']);
    Route::post('sites/{siteId}/suggestions/{suggestionId}/track', [SuggestionController::class, 'track']);
    Route::post('sites/{siteId}/suggestions/bulk-track', [SuggestionController::class, 'bulkTrack']);
    Route::post('sites/{siteId}/suggestions/{suggestionId}/dismiss', [SuggestionController::class, 'dismiss']);
    Route::get('sites/{siteId}/suggestions/export', [SuggestionController::class, 'export']);

    // Annotations (SPEC-009, TASK-9-4)
    Route::get('keywords/{keywordId}/annotations', [AnnotationController::class, 'index']);
    Route::post('keywords/{keywordId}/annotations', [AnnotationController::class, 'store']);
    Route::get('annotations/{id}', [AnnotationController::class, 'show']);
    Route::put('annotations/{id}', [AnnotationController::class, 'update']);
    Route::delete('annotations/{id}', [AnnotationController::class, 'destroy']);

    // SERP Features (SPEC-007, TASK-7-10, TASK-7-11, TASK-7-10-SEC) - requires auth + IDOR protection
    Route::get('keywords/{id}/serp-features', [SerpFeatureController::class, 'keywordFeatures'])->where('id', '[0-9]+');
    Route::get('sites/{siteId}/serp-features', [SerpFeatureController::class, 'siteFeatures'])->where('siteId', '[0-9]+');
    Route::get('sites/{siteId}/serp-features/summary', [SerpFeatureController::class, 'summary'])->where('siteId', '[0-9]+');
    Route::delete('sites/{siteId}/serp-features/cache', [SerpFeatureController::class, 'invalidateCache'])->where('siteId', '[0-9]+');

    // Admin: Credits API (TASK-27-10, SPEC-027) - inside auth group for backwards compat
    Route::middleware(['admin', 'throttle:60,1'])->prefix('admin/credits')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\V1\Admin\CreditsController::class, 'overview']);
        Route::get('/transactions', [\App\Http\Controllers\Api\V1\Admin\CreditsController::class, 'transactions']);
        Route::get('/revenue', [\App\Http\Controllers\Api\V1\Admin\CreditsController::class, 'revenue']);
    });

    // Admin: Health API (TASK-27-13, SPEC-027) - inside auth group for backwards compat
    Route::middleware(['admin', 'throttle:120,1'])->prefix('admin/health')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\V1\Admin\HealthController::class, 'overview']);
        Route::get('/queues', [\App\Http\Controllers\Api\V1\Admin\HealthController::class, 'queues']);
        Route::get('/database', [\App\Http\Controllers\Api\V1\Admin\HealthController::class, 'database']);
        Route::get('/alerts', [\App\Http\Controllers\Api\V1\Admin\HealthController::class, 'alerts']);
    });

    // API Key Management (TASK-6-4)
    Route::get('api-keys', [\App\Http\Controllers\Api\V1\ApiKeyController::class, 'index']);
    Route::post('api-keys', [\App\Http\Controllers\Api\V1\ApiKeyController::class, 'store']);
    Route::get('api-keys/{id}', [\App\Http\Controllers\Api\V1\ApiKeyController::class, 'show'])->where('id', '[0-9]+');
    Route::delete('api-keys/{id}', [\App\Http\Controllers\Api\V1\ApiKeyController::class, 'destroy'])->where('id', '[0-9]+');

    // Onboarding (SPEC-017, FEAT-017)
    Route::get('onboarding', [OnboardingController::class, 'index']);
    Route::post('onboarding', [OnboardingController::class, 'store']);
    Route::post('onboarding/complete', [OnboardingController::class, 'complete']);
    Route::post('onboarding/skip', [OnboardingController::class, 'skip']);

    // Analysis Jobs (SPEC-019, FEAT-019) with rate limiting (TASK-19-14)
    Route::get('sites/{siteId}/analysis/jobs', [AnalysisJobController::class, 'index']);
    Route::post('sites/{siteId}/analysis/jobs', [AnalysisJobController::class, 'store']);
    Route::get('analysis/jobs/{jobId}', [AnalysisJobController::class, 'show']);
    Route::post('analysis/jobs/{jobId}/start', [AnalysisJobController::class, 'start']);
    Route::get('analysis/jobs/{jobId}/stream', [AnalysisJobController::class, 'stream']);
    Route::get('analysis/jobs/{jobId}/progress', [AnalysisJobController::class, 'progress'])
        ->middleware('polling.ratelimit');
    Route::delete('analysis/jobs/{jobId}', [AnalysisJobController::class, 'destroy']);
    Route::get('analysis/indicators', [AnalysisJobController::class, 'indicators']);

    // Alert Settings (FEAT-002)
    Route::get('user/alert-settings', [AlertSettingsController::class, 'show']);
    Route::put('user/alert-settings', [AlertSettingsController::class, 'update']);

    // Keyword Alert Settings (FEAT-002)
    Route::get('keywords/{keyword}/alert-settings', [KeywordAlertSettingsController::class, 'show']);
    Route::put('keywords/{keyword}/alert-settings', [KeywordAlertSettingsController::class, 'update']);
    Route::delete('keywords/{keyword}/alert-settings', [KeywordAlertSettingsController::class, 'destroy']);

    // Notifications (FEAT-002)
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    // Alert Resubscribe (requires auth)
    Route::post('alerts/resubscribe', [AlertUnsubscribeController::class, 'resubscribe']);

    // Analysis History (TASK-25-14, TASK-25-16)
    Route::get('analyses', [AnalysisHistoryController::class, 'index']);
    Route::get('analyses/{id}', [AnalysisHistoryController::class, 'show'])->where('id', '[0-9]+');
    Route::delete('analyses/{id}', [AnalysisHistoryController::class, 'destroy'])->where('id', '[0-9]+');
    Route::get('analyses/{id}/costs', [AnalysisHistoryController::class, 'costs'])->where('id', '[0-9]+');

    // User Dashboard (TASK-25-15, SPEC-025)
    Route::get('dashboard', [UserDashboardController::class, 'index']);
    Route::get('dashboard/stats', [UserDashboardController::class, 'stats']);
    Route::get('dashboard/recent-analyses', [UserDashboardController::class, 'recentAnalyses']);
    Route::get('dashboard/cost-summary', [UserDashboardController::class, 'costSummary']);

    // PWA Analysis (TASK-24-8, SPEC-024)
    Route::get('sites/{siteId}/pwa', [\App\Http\Controllers\Api\V1\PWAController::class, 'show'])->where('siteId', '[0-9]+');
    Route::post('sites/{siteId}/pwa/analyze', [\App\Http\Controllers\Api\V1\PWAController::class, 'analyze'])->where('siteId', '[0-9]+');
    Route::post('sites/{siteId}/pwa/refresh', [\App\Http\Controllers\Api\V1\PWAController::class, 'refresh'])->where('siteId', '[0-9]+');

    // Tool Exports (TASK-29-3, SPEC-029)
    Route::post('tools/export', [ToolExportController::class, 'export']);
    Route::get('tools/export/download/{token}', [ToolExportController::class, 'download']);

    // Payment Methods (TASK-26-11, SPEC-026)
    Route::get('payments/methods', [\App\Http\Controllers\Api\V1\PaymentController::class, 'listMethods']);
    Route::delete('payments/methods/{id}', [\App\Http\Controllers\Api\V1\PaymentController::class, 'deleteMethod'])->where('id', '[0-9]+');

    // PayPal Orders (TASK-26-13, SPEC-026)
    Route::post('payments/paypal/order', [\App\Http\Controllers\Api\V1\Payments\PayPalOrderController::class, 'store']);

    // PayPal Capture (TASK-26-15, SPEC-026)
    Route::post('payments/paypal/capture', [\App\Http\Controllers\Api\V1\Payments\PayPalCaptureController::class, 'store']);

    // Credit Transaction History (TASK-26-7, SPEC-026)
    Route::get('credits/transactions', [CreditController::class, 'transactions']);

    // AI Visibility Projects (TASK-34-6, SPEC-034)
    Route::prefix('ai-visibility')->group(function () {
        Route::get('projects', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityProjectController::class, 'index']);
        Route::post('projects', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityProjectController::class, 'store']);
        Route::get('projects/{id}', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityProjectController::class, 'show'])->where('id', '[0-9]+');
        Route::put('projects/{id}', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityProjectController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('projects/{id}', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityProjectController::class, 'destroy'])->where('id', '[0-9]+');
        Route::post('projects/{id}/run', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityProjectController::class, 'run'])->where('id', '[0-9]+');

        // AI Visibility Analytics (TASK-34-14, SPEC-034)
        Route::get('projects/{id}/analytics', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAnalyticsController::class, 'analytics'])->where('id', '[0-9]+');
        Route::get('projects/{id}/comparison', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAnalyticsController::class, 'comparison'])->where('id', '[0-9]+');
        Route::get('projects/{id}/trend', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAnalyticsController::class, 'trend'])->where('id', '[0-9]+');
        Route::get('projects/{id}/queries', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAnalyticsController::class, 'queries'])->where('id', '[0-9]+');

        // AI Visibility Alerts (TASK-34-20, SPEC-034)
        Route::get('alerts', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAlertController::class, 'index']);
        Route::get('alerts/{id}', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAlertController::class, 'show'])->where('id', '[0-9]+');
        Route::post('alerts/{id}/read', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAlertController::class, 'markAsRead'])->where('id', '[0-9]+');
        Route::post('alerts/{id}/resolve', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAlertController::class, 'markAsResolved'])->where('id', '[0-9]+');
        Route::post('alerts/mark-all-read', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAlertController::class, 'markAllAsRead']);
        Route::delete('alerts/{id}', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAlertController::class, 'destroy'])->where('id', '[0-9]+');

        // AI Visibility Alert Preferences (TASK-34-20, SPEC-034)
        Route::get('alert-preferences', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAlertController::class, 'getPreferences']);
        Route::put('alert-preferences', [\App\Http\Controllers\Api\V1\AIVisibility\AIVisibilityAlertController::class, 'updatePreferences']);

        // AI Visibility Export (TASK-34-22, SPEC-034)
        Route::get('export', [\App\Http\Controllers\Api\V1\AIVisibility\ExportController::class, 'export']);
        Route::get('projects/{id}/export', [\App\Http\Controllers\Api\V1\AIVisibility\ExportController::class, 'exportProject'])->where('id', '[0-9]+');
    });
});

/*
|--------------------------------------------------------------------------
| PayPal Webhook Route (TASK-26-14, SPEC-026)
|--------------------------------------------------------------------------
|
| PayPal webhook endpoint - NO authentication required.
| Security is provided via PayPal webhook signature verification.
|
*/
Route::post('/v1/payments/paypal/webhook', [\App\Http\Controllers\Api\V1\Payments\PayPalWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| Local SEO Rankings Routes (TASK-35-15, SPEC-035)
|--------------------------------------------------------------------------
|
| Local rank tracking and competitor management API endpoints.
| All routes require authentication and ownership verification.
|
*/
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('locations/{id}')->where(['id' => '[0-9]+'])->group(function () {
        // Rankings endpoints
        Route::get('rankings', [\App\Http\Controllers\Api\V1\LocalRankingController::class, 'rankings'])
            ->name('locations.rankings.index')
            ->middleware('throttle:60,1');
        Route::get('rankings/history', [\App\Http\Controllers\Api\V1\LocalRankingController::class, 'history'])
            ->name('locations.rankings.history')
            ->middleware('throttle:60,1');
        Route::post('rankings/check', [\App\Http\Controllers\Api\V1\LocalRankingController::class, 'checkAll'])
            ->name('locations.rankings.check-all')
            ->middleware('throttle:10,60'); // 10 per hour
        Route::post('rankings/check/{keywordId}', [\App\Http\Controllers\Api\V1\LocalRankingController::class, 'checkSingle'])
            ->name('locations.rankings.check-single')
            ->where('keywordId', '[0-9]+')
            ->middleware('throttle:10,60'); // 10 per hour
        Route::get('rankings/quota', [\App\Http\Controllers\Api\V1\LocalRankingController::class, 'quota'])
            ->name('locations.rankings.quota')
            ->middleware('throttle:60,1');

        // Competitor endpoints
        Route::get('competitors', [\App\Http\Controllers\Api\V1\LocalRankingController::class, 'competitors'])
            ->name('locations.competitors.index')
            ->middleware('throttle:60,1');
        Route::post('competitors', [\App\Http\Controllers\Api\V1\LocalRankingController::class, 'addCompetitor'])
            ->name('locations.competitors.store')
            ->middleware('throttle:30,60'); // 30 per hour
        Route::delete('competitors/{competitorId}', [\App\Http\Controllers\Api\V1\LocalRankingController::class, 'deleteCompetitor'])
            ->name('locations.competitors.destroy')
            ->where('competitorId', '[0-9]+')
            ->middleware('throttle:30,60'); // 30 per hour
    });
});

/*
|--------------------------------------------------------------------------
| Business Location Routes (TASK-35-13, SPEC-035)
|--------------------------------------------------------------------------
|
| Business Location API endpoints for managing locations and GBP connections.
| All routes require authentication and ownership verification.
|
*/
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Business Location CRUD Routes
    Route::get('sites/{siteId}/locations', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'index'])
        ->name('locations.index')
        ->where('siteId', '[0-9]+')
        ->middleware('throttle:60,1');
    Route::post('sites/{siteId}/locations', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'store'])
        ->name('locations.store')
        ->where('siteId', '[0-9]+')
        ->middleware('throttle:10,60'); // 10 per hour for creation
    Route::get('locations/{id}', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'show'])
        ->name('locations.show')
        ->where('id', '[0-9]+')
        ->middleware('throttle:60,1');
    Route::post('locations/{id}/sync', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'sync'])
        ->name('locations.sync')
        ->where('id', '[0-9]+')
        ->middleware('throttle:3,60');
    Route::put('locations/{id}', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'update'])
        ->name('locations.update')
        ->where('id', '[0-9]+')
        ->middleware('throttle:60,1');
    Route::delete('locations/{id}', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'destroy'])
        ->name('locations.destroy')
        ->where('id', '[0-9]+')
        ->middleware('throttle:60,1');

    // GBP OAuth Routes
    Route::post('locations/{id}/connect-gbp', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'connectGbp'])
        ->name('locations.connect-gbp')
        ->where('id', '[0-9]+')
        ->middleware('throttle:5,60'); // 5 per hour for OAuth initiation
    Route::delete('locations/{id}/disconnect-gbp', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'disconnectGbp'])
        ->name('locations.disconnect-gbp')
        ->where('id', '[0-9]+')
        ->middleware('throttle:10,60'); // 10 per hour for disconnect
});

Route::prefix('v1')->group(function () {
    Route::get('locations/{id}/gbp-callback', [\App\Http\Controllers\Api\V1\LocalSEO\BusinessLocationController::class, 'gbpCallback'])
        ->name('locations.gbp-callback')
        ->where('id', '[0-9]+')
        ->middleware('throttle:20,60'); // 20 per hour for callback
});

/*
|--------------------------------------------------------------------------
| GBP Insights & Reviews Routes (TASK-35-14, SPEC-035)
|--------------------------------------------------------------------------
|
| Google Business Profile API endpoints for insights and reviews.
| All routes require authentication and ownership verification.
|
*/
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // GBP Insights Routes
    Route::prefix('locations/{id}')->where(['id' => '[0-9]+'])->group(function () {
        Route::get('insights', [\App\Http\Controllers\Api\V1\GBP\GBPInsightsController::class, 'index'])
            ->name('locations.insights.index')
            ->middleware('throttle:60,1');
        Route::get('insights/trend', [\App\Http\Controllers\Api\V1\GBP\GBPInsightsController::class, 'trend'])
            ->name('locations.insights.trend')
            ->middleware('throttle:60,1');
        Route::post('insights/sync', [\App\Http\Controllers\Api\V1\GBP\GBPInsightsController::class, 'sync'])
            ->name('locations.insights.sync')
            ->middleware('throttle:3,60'); // 3 per hour

        // GBP Reviews Routes
        Route::get('reviews', [\App\Http\Controllers\Api\V1\GBP\GBPReviewsController::class, 'index'])
            ->name('locations.reviews.index')
            ->middleware('throttle:60,1');
        Route::get('reviews/stats', [\App\Http\Controllers\Api\V1\GBP\GBPReviewsController::class, 'stats'])
            ->name('locations.reviews.stats')
            ->middleware('throttle:60,1');
        Route::post('reviews/sync', [\App\Http\Controllers\Api\V1\GBP\GBPReviewsController::class, 'sync'])
            ->name('locations.reviews.sync')
            ->middleware('throttle:3,60'); // 3 per hour
        Route::post('reviews/{reviewId}/respond', [\App\Http\Controllers\Api\V1\GBP\GBPReviewsController::class, 'respond'])
            ->name('locations.reviews.respond')
            ->where('reviewId', '[0-9]+')
            ->middleware('throttle:10,60'); // 10 per hour
    });
});

/*
|--------------------------------------------------------------------------
| Local SEO Alerts Routes (TASK-35-16, SPEC-035)
|--------------------------------------------------------------------------
|
| Local SEO alert management API endpoints.
| All routes require authentication and ownership verification.
|
*/
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Local SEO Alerts Routes
    Route::prefix('local-seo/alerts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\LocalSEO\LocalSEOAlertController::class, 'index'])
            ->name('local-seo.alerts.index')
            ->middleware('throttle:60,1');
        Route::get('/unread-count', [\App\Http\Controllers\Api\V1\LocalSEO\LocalSEOAlertController::class, 'unreadCount'])
            ->name('local-seo.alerts.unread-count')
            ->middleware('throttle:60,1');
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\V1\LocalSEO\LocalSEOAlertController::class, 'markAllAsRead'])
            ->name('local-seo.alerts.mark-all-read')
            ->middleware('throttle:10,1'); // 10 per minute
        Route::post('/{id}/read', [\App\Http\Controllers\Api\V1\LocalSEO\LocalSEOAlertController::class, 'markAsRead'])
            ->name('local-seo.alerts.read.action')
            ->where('id', '[0-9]+')
            ->middleware('throttle:60,1');
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\LocalSEO\LocalSEOAlertController::class, 'destroy'])
            ->name('local-seo.alerts.destroy.action')
            ->where('id', '[0-9]+')
            ->middleware('throttle:60,1');

        // Parameterless route-name aliases for testable URL generation.
        Route::get('/_route-name/read', static fn () => response()->noContent())
            ->name('local-seo.alerts.read');
        Route::get('/_route-name/destroy', static fn () => response()->noContent())
            ->name('local-seo.alerts.destroy');
    });

    // Location-specific alerts
    Route::get('locations/{id}/alerts', [\App\Http\Controllers\Api\V1\LocalSEO\LocalSEOAlertController::class, 'locationAlerts'])
        ->name('locations.alerts.index')
        ->where('id', '[0-9]+')
        ->middleware('throttle:60,1');
});

/*
|--------------------------------------------------------------------------
| Content Decay Routes (TASK-36-10, SPEC-036)
|--------------------------------------------------------------------------
|
| Content Decay Detection & Refresh Planner API endpoints.
| All routes require authentication and ownership verification.
|
*/
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Content Decay Summary & Settings
    Route::get('sites/{siteId}/content-decay/summary', [\App\Http\Controllers\Api\V1\ContentDecayController::class, 'summary'])
        ->name('content-decay.summary')
        ->where('siteId', '[0-9]+');
    Route::post('sites/{siteId}/content-decay/run', [\App\Http\Controllers\Api\V1\ContentDecayController::class, 'run'])
        ->name('content-decay.run')
        ->where('siteId', '[0-9]+')
        ->middleware('throttle:5,60'); // 5 per hour for manual detection runs
    Route::patch('sites/{siteId}/content-decay/settings', [\App\Http\Controllers\Api\V1\ContentDecayController::class, 'updateSettings'])
        ->name('content-decay.settings.update')
        ->where('siteId', '[0-9]+');

    // Content Decay Candidates Queue
    Route::get('sites/{siteId}/content-decay/candidates', [\App\Http\Controllers\Api\V1\ContentDecayCandidateController::class, 'index'])
        ->name('content-decay.candidates.index')
        ->where('siteId', '[0-9]+');
    Route::get('content-decay/candidates/{id}', [\App\Http\Controllers\Api\V1\ContentDecayCandidateController::class, 'show'])
        ->name('content-decay.candidates.show')
        ->where('id', '[0-9]+');
    Route::patch('content-decay/candidates/{id}', [\App\Http\Controllers\Api\V1\ContentDecayCandidateController::class, 'update'])
        ->name('content-decay.candidates.update')
        ->where('id', '[0-9]+');
    Route::post('content-decay/candidates/{id}/restore', [\App\Http\Controllers\Api\V1\ContentDecayCandidateController::class, 'restore'])
        ->name('content-decay.candidates.restore')
        ->where('id', '[0-9]+');

    // Content Refresh Tasks
    Route::get('content-decay/candidates/{candidateId}/tasks', [\App\Http\Controllers\Api\V1\ContentRefreshTaskController::class, 'index'])
        ->name('content-refresh-tasks.index')
        ->where('candidateId', '[0-9]+');
    Route::post('content-decay/candidates/{candidateId}/tasks', [\App\Http\Controllers\Api\V1\ContentRefreshTaskController::class, 'store'])
        ->name('content-refresh-tasks.store')
        ->where('candidateId', '[0-9]+');
    Route::patch('content-refresh-tasks/{id}', [\App\Http\Controllers\Api\V1\ContentRefreshTaskController::class, 'update'])
        ->name('content-refresh-tasks.update')
        ->where('id', '[0-9]+');

    // Content Decay Export
    Route::get('sites/{siteId}/content-decay/export', [\App\Http\Controllers\Api\V1\ContentDecayExportController::class, 'export'])
        ->name('content-decay.export')
        ->where('siteId', '[0-9]+');
    Route::post('content-decay/candidates/{id}/ai-brief', [\App\Http\Controllers\Api\V1\ContentDecayExportController::class, 'generateAiBrief'])
        ->name('content-decay.ai-brief')
        ->where('id', '[0-9]+')
        ->middleware('throttle:10,60'); // 10 per hour for AI brief generation
});

/*
|--------------------------------------------------------------------------
| Admin Routes (TASK-27-2, SPEC-027)
|--------------------------------------------------------------------------
|
| Admin routes protected by AdminAuthorizationMiddleware.
| This middleware handles both authentication and authorization.
| Supports session-based and Sanctum token-based authentication.
|
*/

Route::prefix('v1')->middleware(['throttle:60,1'])->group(function () {
    // Admin: Dashboard Stats
    Route::get('/admin/stats', [\App\Http\Controllers\Api\V1\Admin\AdminStatsController::class, 'index'])->middleware('admin');
    Route::post('/admin/stats', [\App\Http\Controllers\Api\V1\Admin\AdminStatsController::class, 'index'])->middleware('admin');

    // Admin: User Management (for middleware testing)
    Route::prefix('admin/users')->middleware('admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminUsersController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminUsersController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUsersController::class, 'show'])->where('id', '[0-9]+');
        Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUsersController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUsersController::class, 'destroy'])->where('id', '[0-9]+');
        Route::post('/{id}/impersonate', [\App\Http\Controllers\Api\V1\Admin\AdminUsersController::class, 'impersonate'])->where('id', '[0-9]+');
    });

    // Admin: Lead Management (TASK-16-6)
    Route::prefix('admin/leads')->middleware('admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'index']);
        Route::get('/stats', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'stats']);
        Route::get('/export', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'export']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'destroy']);
        Route::post('/{id}/restore', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'restore']);
    });

    // Admin: Usage API (TASK-27-8, SPEC-027)
    Route::prefix('admin/usage')->middleware('admin')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\V1\Admin\UsageController::class, 'overview']);
        Route::get('/requests', [\App\Http\Controllers\Api\V1\Admin\UsageController::class, 'requests']);
        Route::get('/tokens', [\App\Http\Controllers\Api\V1\Admin\UsageController::class, 'tokens']);
        Route::get('/trends', [\App\Http\Controllers\Api\V1\Admin\UsageController::class, 'trends']);
    });

    // Admin: AI Cache Management (TASK-21-6, SPEC-021)
    Route::prefix('admin/ai/cache')->middleware('admin')->group(function () {
        Route::post('/invalidate', [\App\Http\Controllers\Api\V1\Admin\AICacheController::class, 'invalidate']);
        Route::delete('/', [\App\Http\Controllers\Api\V1\Admin\AICacheController::class, 'clear']);
        Route::get('/stats', [\App\Http\Controllers\Api\V1\Admin\AICacheController::class, 'stats']);
        Route::post('/stats/reset', [\App\Http\Controllers\Api\V1\Admin\AICacheController::class, 'resetStats']);
    });
});

// Alert Unsubscribe (signed URL, no auth required)
Route::get('alerts/unsubscribe/{user}', [AlertUnsubscribeController::class, 'unsubscribe'])
    ->name('alerts.unsubscribe')
    ->middleware('signed');

/*
|--------------------------------------------------------------------------
| Headless Crawler Routes (TASK-22-11, SPEC-022)
|--------------------------------------------------------------------------
|
| REST API endpoints for the Headless Crawler Service.
| All routes require authentication (Sanctum) and rate limiting.
|
*/
Route::prefix('v1')->middleware(['auth:sanctum', 'crawler.ratelimit'])->group(function () {
    Route::prefix('crawler')->group(function () {
        Route::post('crawl', [CrawlController::class, 'store'])->name('api.v1.crawler.store');
        Route::get('jobs', [CrawlController::class, 'index'])->name('api.v1.crawler.index');
        Route::get('jobs/{jobId}', [CrawlController::class, 'show'])->name('api.v1.crawler.show');
        Route::delete('jobs/{jobId}', [CrawlController::class, 'destroy'])->name('api.v1.crawler.destroy');
    });

    // Content Retrieval Routes (TASK-22-14, SPEC-022)
    Route::prefix('crawler/jobs/{jobId}')->group(function () {
        // Content retrieval with pagination, filtering, field selection
        Route::get('content', [\App\Http\Controllers\Api\V1\ContentRetrievalController::class, 'content'])
            ->name('api.v1.crawler.content');
        Route::get('pages/{pageId}', [\App\Http\Controllers\Api\V1\ContentRetrievalController::class, 'page'])
            ->name('api.v1.crawler.page')
            ->where('pageId', '[0-9]+');

        // Export endpoints
        Route::get('export/json', [\App\Http\Controllers\Api\V1\ContentRetrievalController::class, 'exportJson'])
            ->name('api.v1.crawler.export.json');
        Route::get('export/csv', [\App\Http\Controllers\Api\V1\ContentRetrievalController::class, 'exportCsv'])
            ->name('api.v1.crawler.export.csv');
        Route::get('export/html', [\App\Http\Controllers\Api\V1\ContentRetrievalController::class, 'exportHtml'])
            ->name('api.v1.crawler.export.html');
    });
});
