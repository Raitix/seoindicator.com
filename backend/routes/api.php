<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\Api\V1\CompetitorController;

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
});
