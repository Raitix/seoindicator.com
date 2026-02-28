<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\Api\V1\CompetitorController;
use App\Http\Controllers\Api\V1\ComparisonController;
use App\Http\Controllers\Api\V1\IssueTypeController;
use App\Http\Controllers\Api\V1\GSCDataController;
use App\Http\Controllers\Api\V1\KeywordController;
use App\Http\Controllers\Api\V1\KeywordsApiController;
use App\Http\Controllers\Api\V1\RankingsApiController;
use App\Http\Controllers\Api\V1\SitesApiController;
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
});

/*
|--------------------------------------------------------------------------
| GSC Connection Routes (TASK-12-7)
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

    // Lead Capture (TASK-16-5)
    Route::post('tools/capture-email', [\App\Http\Controllers\Api\V1\LeadCaptureController::class, 'store']);
});

// Authenticated V1 API Routes
Route::prefix('v1')->middleware(['auth'])->group(function () {
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
    Route::apiResource('issue-types', IssueTypeController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('issue-types/categories', [IssueTypeController::class, 'categories']);

    // GSC Data (TASK-12-8)
    Route::get('projects/{projectId}/gsc', [GSCDataController::class, 'index']);
    Route::get('projects/{projectId}/gsc/opportunities', [GSCDataController::class, 'opportunities']);

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

    // Admin: Lead Management (TASK-16-6)
    Route::prefix('admin/leads')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'index']);
        Route::get('/stats', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'stats']);
        Route::get('/export', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'export']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'destroy']);
        Route::post('/{id}/restore', [\App\Http\Controllers\Api\V1\Admin\LeadController::class, 'restore']);
    });

    // Webhooks Management (TASK-6-13)
    Route::get('webhooks', [\App\Http\Controllers\Api\V1\WebhookController::class, 'index']);
    Route::post('webhooks', [\App\Http\Controllers\Api\V1\WebhookController::class, 'store']);
    Route::get('webhooks/{id}', [\App\Http\Controllers\Api\V1\WebhookController::class, 'show']);
    Route::put('webhooks/{id}', [\App\Http\Controllers\Api\V1\WebhookController::class, 'update']);
    Route::delete('webhooks/{id}', [\App\Http\Controllers\Api\V1\WebhookController::class, 'destroy']);
    Route::post('webhooks/{id}/test', [\App\Http\Controllers\Api\V1\WebhookController::class, 'test']);

    // Onboarding (SPEC-017, FEAT-017)
    Route::get('onboarding', [OnboardingController::class, 'index']);
    Route::post('onboarding', [OnboardingController::class, 'store']);
    Route::post('onboarding/complete', [OnboardingController::class, 'complete']);
    Route::post('onboarding/skip', [OnboardingController::class, 'skip']);

    // Analysis Jobs (SPEC-019, FEAT-019)
    Route::get('sites/{siteId}/analysis/jobs', [AnalysisJobController::class, 'index']);
    Route::post('sites/{siteId}/analysis/jobs', [AnalysisJobController::class, 'store']);
    Route::get('analysis/jobs/{jobId}', [AnalysisJobController::class, 'show']);
    Route::delete('analysis/jobs/{jobId}', [AnalysisJobController::class, 'destroy']);
    Route::get('analysis/indicators', [AnalysisJobController::class, 'indicators']);
});
