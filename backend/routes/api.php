<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\Api\V1\CompetitorController;
use App\Http\Controllers\Api\V1\IssueTypeController;
use App\Http\Controllers\Api\V1\GSCDataController;
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

// Deploy endpoints (protected by deploy key)
Route::post('/deploy/migrate', [DeployController::class, 'migrate']);
Route::post('/deploy/rollback', [DeployController::class, 'rollback']);

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

Route::prefix('v1')->middleware(['auth'])->group(function () {
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
});
