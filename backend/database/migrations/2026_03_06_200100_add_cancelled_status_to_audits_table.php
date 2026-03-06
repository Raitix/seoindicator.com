<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 'cancelled' status to audits table.
     * TASK-14-13: AuditController API endpoints.
     */
    public function up(): void
    {
        // For SQLite (testing), we need to recreate the table
        // For MySQL/PostgreSQL, we can ALTER the column
        
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN for enum
            // We need to recreate the table with the new enum values
            DB::statement('
                CREATE TABLE audits_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    site_id INTEGER NOT NULL,
                    max_pages INTEGER DEFAULT 500,
                    max_depth INTEGER DEFAULT 3,
                    user_agent VARCHAR(255),
                    status VARCHAR(255) DEFAULT "pending",
                    pages_crawled INTEGER DEFAULT 0,
                    issues_found INTEGER DEFAULT 0,
                    error_message TEXT,
                    visibility VARCHAR(255) DEFAULT "private",
                    public_hash VARCHAR(255) NULL,
                    public_hash_created_at TIMESTAMP NULL,
                    started_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
                )
            ');
            
            // Copy data from old table
            DB::statement('
                INSERT INTO audits_new (id, site_id, status, pages_crawled, issues_found, visibility, public_hash, public_hash_created_at, started_at, completed_at, created_at, updated_at)
                SELECT id, site_id, status, pages_crawled, issues_found, visibility, public_hash, public_hash_created_at, started_at, completed_at, created_at, updated_at
                FROM audits
            ');
            
            // Drop old table
            DB::statement('DROP TABLE audits');
            
            // Rename new table
            DB::statement('ALTER TABLE audits_new RENAME TO audits');
            
            // Recreate indexes
            DB::statement('CREATE INDEX idx_site_status ON audits(site_id, status)');
            DB::statement('CREATE INDEX audits_status_index ON audits(status)');
            DB::statement('CREATE INDEX audits_completed_at_index ON audits(completed_at)');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE audits MODIFY COLUMN status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL requires dropping and recreating the constraint
            DB::statement("ALTER TABLE audits DROP CONSTRAINT audits_status_check");
            DB::statement("ALTER TABLE audits ADD CONSTRAINT audits_status_check CHECK (status IN ('pending', 'running', 'completed', 'failed', 'cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // Recreate without cancelled
            DB::statement('
                CREATE TABLE audits_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    site_id INTEGER NOT NULL,
                    status VARCHAR(255) DEFAULT "pending",
                    pages_crawled INTEGER DEFAULT 0,
                    issues_found INTEGER DEFAULT 0,
                    visibility VARCHAR(255) DEFAULT "private",
                    public_hash VARCHAR(255) NULL,
                    public_hash_created_at TIMESTAMP NULL,
                    started_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
                )
            ');
            
            // Copy only non-cancelled records
            DB::statement('
                INSERT INTO audits_new (id, site_id, status, pages_crawled, issues_found, visibility, public_hash, public_hash_created_at, started_at, completed_at, created_at, updated_at)
                SELECT id, site_id, status, pages_crawled, issues_found, visibility, public_hash, public_hash_created_at, started_at, completed_at, created_at, updated_at
                FROM audits WHERE status != "cancelled"
            ');
            
            DB::statement('DROP TABLE audits');
            DB::statement('ALTER TABLE audits_new RENAME TO audits');
            DB::statement('CREATE INDEX idx_site_status ON audits(site_id, status)');
            DB::statement('CREATE INDEX audits_status_index ON audits(status)');
            DB::statement('CREATE INDEX audits_completed_at_index ON audits(completed_at)');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE audits MODIFY COLUMN status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE audits DROP CONSTRAINT audits_status_check");
            DB::statement("ALTER TABLE audits ADD CONSTRAINT audits_status_check CHECK (status IN ('pending', 'running', 'completed', 'failed'))");
        }
    }
};
