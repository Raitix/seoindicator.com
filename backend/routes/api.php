<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\Api\V1\CompetitorController;
use App\Http\Controllers\Api\V1\ComparisonController;
use App\Http\Controllers\Api\V1\IssueTypeController;
use App\Http\Controllers\Api\V1\GSCDataController;
use App\Http\Controllers\Api\V1\KeywordController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\SchedulerController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReportTemplateController;
use App\Http\Controllers\Api\V1\SerpFeatureController;
use App\Http\Controllers\Api\V1\APIProxyController;
use App\Http\Controllers\Api\GSCConnectionController;

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

// Deploy endpoints (protected by deploy key + rate limiting + logging)
// SPEC-SEC-2026-02-21-3: Rate limiting (5 req/15min) and failed auth logging
Route::middleware(['throttle:deploy', 'log-deploy-auth'])->group(function () {
    Route::post('/deploy/migrate', [DeployController::class, 'migrate']);
    Route::post('/deploy/rollback', [DeployController::class, 'rollback']);
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

    // Keywords (SPEC-001-4)
    Route::get('sites/{siteId}/keywords', [KeywordController::class, 'index']);
    Route::post('sites/{siteId}/keywords', [KeywordController::class, 'store']);
    Route::get('keywords/{id}', [KeywordController::class, 'show']);
    Route::put('keywords/{id}', [KeywordController::class, 'update']);
    Route::delete('keywords/{id}', [KeywordController::class, 'destroy']);

    // Rankings (SPEC-001-5)
    Route::get('keywords/{id}/history', [RankingController::class, 'history']);
    Route::post('keywords/{id}/check', [RankingController::class, 'check']);

    // Dashboard (SPEC-001-6)
    Route::get('sites/{siteId}/dashboard', [DashboardController::class, 'index']);

    // SERP Features (SPEC-007, TASK-7-10, TASK-7-11) - public read
    Route::get('keywords/{id}/serp-features', [SerpFeatureController::class, 'keywordFeatures'])->where('id', '[0-9]+');
    Route::get('sites/{siteId}/serp-features', [SerpFeatureController::class, 'siteFeatures'])->where('siteId', '[0-9]+');
    Route::get('sites/{siteId}/serp-features/summary', [SerpFeatureController::class, 'summary'])->where('siteId', '[0-9]+');

    // Report Templates (SPEC-004-10) - public read for now
    Route::get('sites/{siteId}/report-templates', [ReportTemplateController::class, 'index']);
    Route::get('report-templates/{templateId}', [ReportTemplateController::class, 'show']);

    // Scheduled Reports (SPEC-004-11) - public read for now
    Route::get('sites/{siteId}/scheduled-reports', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'index']);
    Route::get('scheduled-reports/{scheduledId}', [\App\Http\Controllers\Api\V1\ScheduledReportController::class, 'show']);

    // SEO Tools API Proxy (TASK-16-3) - public with rate limiting
    Route::post('tools/analyze', [APIProxyController::class, 'analyze']);
    Route::get('tools/captcha-config', [APIProxyController::class, 'captchaConfig']);
    Route::get('tools/rate-limit-status', [APIProxyController::class, 'rateLimitStatus']);
});

// Authenticated V1 API Routes
Route::prefix('v1')->middleware(['auth'])->group(function () {
    // Projects (SPEC-008-7) - CRUD with archive/restore
    Route::post('projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore']);
    Route::apiResource('projects', ProjectController::class);

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
});
