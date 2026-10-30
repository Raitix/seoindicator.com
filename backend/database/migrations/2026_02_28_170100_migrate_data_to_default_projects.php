<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration for TASK-8-3: Data Migration for Default Projects
 *
 * This migration:
 * 1. Creates default projects for all users that don't have one
 * 2. Assigns keywords, competitors, reports to their user's default project
 * 3. Makes project_id NOT NULL after data migration
 *
 * @see TASK-8-3
 * @see SPEC-008
 * @see FEAT-008
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use transaction for atomic rollback on failure
        DB::transaction(function () {
            // Step 1: Create default projects for users without one
            $this->createDefaultProjects();
            
            // Step 2: Assign keywords to default projects
            $this->assignKeywordsToProjects();
            
            // Step 3: Assign competitors to default projects
            $this->assignCompetitorsToProjects();
            
            // Step 4: Assign reports to default projects
            $this->assignReportsToProjects();
        });
        
        // Step 5: Apply NOT NULL constraints (outside transaction for DDL)
        $this->applyNotNullConstraints();
    }

    /**
     * Create default projects for all users that don't have one.
     */
    private function createDefaultProjects(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite-compatible approach
            $usersWithoutDefault = DB::table('users')
                ->leftJoin('projects', function ($join) {
                    $join->on('users.id', '=', 'projects.user_id')
                        ->where('projects.is_default', true);
                })
                ->whereNull('projects.id')
                ->select('users.id')
                ->distinct()
                ->get();
            
            $created = 0;
            foreach ($usersWithoutDefault as $user) {
                // Check again to handle race conditions
                $exists = DB::table('projects')
                    ->where('user_id', $user->id)
                    ->where('is_default', true)
                    ->exists();
                
                if (!$exists) {
                    DB::table('projects')->insert([
                        'user_id' => $user->id,
                        'name' => 'Default Project',
                        'domain' => null,
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $created++;
                }
            }
        } else {
            // MySQL/MariaDB optimized approach
            $created = DB::insert('
                INSERT INTO projects (user_id, name, domain, is_default, created_at, updated_at)
                SELECT u.id, ?, NULL, TRUE, NOW(), NOW()
                FROM users u
                LEFT JOIN projects p ON p.user_id = u.id AND p.is_default = TRUE
                WHERE p.id IS NULL
            ', ['Default Project']);
        }
        
        Log::info("[DataMigration] Created {$created} default projects");
    }

    /**
     * Assign keywords to their user's default project.
     */
    private function assignKeywordsToProjects(): void
    {
        $driver = DB::connection()->getDriverName();
        $assigned = 0;
        
        if ($driver === 'sqlite') {
            // SQLite-compatible approach using query builder
            $keywords = DB::table('keywords')
                ->whereNull('project_id')
                ->get();
            
            foreach ($keywords as $keyword) {
                $defaultProject = DB::table('projects')
                    ->where('user_id', $keyword->user_id)
                    ->where('is_default', true)
                    ->first();
                
                if ($defaultProject) {
                    DB::table('keywords')
                        ->where('id', $keyword->id)
                        ->update(['project_id' => $defaultProject->id]);
                    $assigned++;
                }
            }
        } else {
            // MySQL/MariaDB optimized approach
            $result = DB::update('
                UPDATE keywords k
                JOIN projects p ON p.user_id = k.user_id AND p.is_default = TRUE
                SET k.project_id = p.id
                WHERE k.project_id IS NULL
            ');
            $assigned = $result;
        }
        
        Log::info("[DataMigration] Assigned {$assigned} keywords to projects");
    }

    /**
     * Assign competitors to their user's default project.
     */
    private function assignCompetitorsToProjects(): void
    {
        $driver = DB::connection()->getDriverName();
        $assigned = 0;
        
        if ($driver === 'sqlite') {
            // SQLite-compatible approach
            $competitors = DB::table('competitors')
                ->whereNull('project_id')
                ->get();
            
            foreach ($competitors as $competitor) {
                $defaultProject = DB::table('projects')
                    ->where('user_id', $competitor->user_id)
                    ->where('is_default', true)
                    ->first();
                
                if ($defaultProject) {
                    DB::table('competitors')
                        ->where('id', $competitor->id)
                        ->update(['project_id' => $defaultProject->id]);
                    $assigned++;
                }
            }
        } else {
            // MySQL/MariaDB optimized approach
            $result = DB::update('
                UPDATE competitors c
                JOIN projects p ON p.user_id = c.user_id AND p.is_default = TRUE
                SET c.project_id = p.id
                WHERE c.project_id IS NULL
            ');
            $assigned = $result;
        }
        
        Log::info("[DataMigration] Assigned {$assigned} competitors to projects");
    }

    /**
     * Assign reports to their site user's default project.
     */
    private function assignReportsToProjects(): void
    {
        $driver = DB::connection()->getDriverName();
        $assigned = 0;
        
        if ($driver === 'sqlite') {
            // SQLite-compatible approach
            $reports = DB::table('reports')
                ->whereNull('project_id')
                ->whereNotNull('site_id')
                ->get();
            
            foreach ($reports as $report) {
                $site = DB::table('sites')->where('id', $report->site_id)->first();
                
                if ($site) {
                    $defaultProject = DB::table('projects')
                        ->where('user_id', $site->user_id)
                        ->where('is_default', true)
                        ->first();
                    
                    if ($defaultProject) {
                        DB::table('reports')
                            ->where('id', $report->id)
                            ->update(['project_id' => $defaultProject->id]);
                        $assigned++;
                    }
                }
            }
        } else {
            // MySQL/MariaDB optimized approach
            $result = DB::update('
                UPDATE reports r
                JOIN sites s ON r.site_id = s.id
                JOIN projects p ON p.user_id = s.user_id AND p.is_default = TRUE
                SET r.project_id = p.id
                WHERE r.project_id IS NULL
            ');
            $assigned = $result;
        }
        
        Log::info("[DataMigration] Assigned {$assigned} reports to projects");
    }

    /**
     * Apply NOT NULL constraints to project_id columns.
     * Only applies in MySQL/MariaDB as SQLite doesn't support ALTER COLUMN.
     */
    private function applyNotNullConstraints(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // Only apply NOT NULL for MySQL/MariaDB
        // SQLite doesn't support ALTER COLUMN for NOT NULL constraints
        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                DB::statement('ALTER TABLE keywords MODIFY project_id BIGINT UNSIGNED NOT NULL');
                DB::statement('ALTER TABLE competitors MODIFY project_id BIGINT UNSIGNED NOT NULL');
                DB::statement('ALTER TABLE reports MODIFY project_id BIGINT UNSIGNED NOT NULL');
                
                Log::info('[DataMigration] Applied NOT NULL constraints');
            } catch (\Exception $e) {
                Log::warning('[DataMigration] Could not apply NOT NULL constraints: ' . $e->getMessage());
            }
        } else {
            Log::info('[DataMigration] Skipped NOT NULL constraints (SQLite not supported)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // Remove NOT NULL constraints (MySQL/MariaDB only)
        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                DB::statement('ALTER TABLE keywords MODIFY project_id BIGINT UNSIGNED NULL');
                DB::statement('ALTER TABLE competitors MODIFY project_id BIGINT UNSIGNED NULL');
                DB::statement('ALTER TABLE reports MODIFY project_id BIGINT UNSIGNED NULL');
            } catch (\Exception $e) {
                Log::warning('[DataMigration] Could not remove NOT NULL constraints: ' . $e->getMessage());
            }
        }
        
        // Note: We do NOT:
        // - Delete created default projects (data preservation)
        // - Nullify project_ids (data preservation)
        // This ensures rollback doesn't cause data loss
    }
};
