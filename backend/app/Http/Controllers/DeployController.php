<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DeployController extends Controller
{
    /**
     * Run database migrations.
     * 
     * Protected by DEPLOY_KEY from .env
     */
    public function migrate(Request $request)
    {
        // Verify deploy key
        $deployKey = env('DEPLOY_KEY');
        $providedKey = $request->input('key') ?? $request->header('X-Deploy-Key');
        
        if (!$deployKey || !$providedKey || !hash_equals($deployKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid deploy key'
            ], 403);
        }

        try {
            // Put application in maintenance mode
            Artisan::call('down', ['--message' => 'Running migrations', '--retry' => 60]);
            
            // Run migrations
            $exitCode = Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);
            
            $output = Artisan::output();
            
            // Bring application back up
            Artisan::call('up');
            
            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Migrations completed successfully',
                    'output' => $output
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Migration failed',
                    'output' => $output
                ], 500);
            }
            
        } catch (\Exception $e) {
            // Ensure app is back up
            Artisan::call('up');
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check endpoint.
     */
    public function health()
    {
        $dbStatus = 'unknown';
        
        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            $dbStatus = 'disconnected';
        }

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'database' => $dbStatus,
            'app' => env('APP_NAME', 'SEOIndicator'),
            'env' => app()->environment(),
        ]);
    }

    /**
     * Rollback last migration batch.
     */
    public function rollback(Request $request)
    {
        // Verify deploy key
        $deployKey = env('DEPLOY_KEY');
        $providedKey = $request->input('key') ?? $request->header('X-Deploy-Key');
        
        if (!$deployKey || !$providedKey || !hash_equals($deployKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid deploy key'
            ], 403);
        }

        try {
            $exitCode = Artisan::call('migrate:rollback', [
                '--force' => true,
            ]);
            
            $output = Artisan::output();
            
            return response()->json([
                'success' => $exitCode === 0,
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
