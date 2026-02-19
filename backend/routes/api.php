<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeployController;

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
