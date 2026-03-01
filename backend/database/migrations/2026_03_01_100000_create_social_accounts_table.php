<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration to create the social_accounts table.
 *
 * Stores OAuth provider connections for users, supporting multiple
 * providers per user (Google, GitHub, Facebook, LinkedIn, etc.).
 *
 * @see TASK-25-2
 * @see SPEC-025
 * @see FEAT-025
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to users
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // OAuth provider info
            $table->string('provider', 50);
            $table->string('provider_id', 255);

            // OAuth tokens (encrypted at model level)
            $table->text('provider_token')->nullable();
            $table->text('provider_refresh_token')->nullable();
            $table->timestamp('provider_token_expires_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Unique constraint: one provider account per provider_id
            $table->unique(['provider', 'provider_id'], 'social_accounts_provider_provider_id_unique');

            // Index for querying by user and provider
            $table->index(['user_id', 'provider'], 'social_accounts_user_id_provider_index');
        });

        // Add CHECK constraints for non-empty provider (SQLite + MySQL 8.0+)
        // Note: SQLite CHECK constraints are enforced, MySQL 8.0+ also supports them
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite requires CHECK constraints during table creation
            // We need to recreate the table with the constraint
            DB::statement('
                CREATE TABLE social_accounts_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    provider VARCHAR(50) NOT NULL CHECK(provider != ""),
                    provider_id VARCHAR(255) NOT NULL,
                    provider_token TEXT,
                    provider_refresh_token TEXT,
                    provider_token_expires_at TIMESTAMP,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE(provider, provider_id)
                )
            ');
            
            // Copy data
            DB::statement('
                INSERT INTO social_accounts_new 
                SELECT id, user_id, provider, provider_id, provider_token, 
                       provider_refresh_token, provider_token_expires_at, 
                       created_at, updated_at 
                FROM social_accounts
            ');
            
            // Drop old table
            DB::statement('DROP TABLE social_accounts');
            
            // Rename new table
            DB::statement('ALTER TABLE social_accounts_new RENAME TO social_accounts');
            
            // Recreate indexes
            DB::statement('CREATE INDEX social_accounts_user_id_provider_index ON social_accounts(user_id, provider)');
        } else {
            // MySQL 8.0+ supports CHECK constraints via ALTER TABLE
            DB::statement('ALTER TABLE social_accounts ADD CONSTRAINT chk_provider_not_empty CHECK (provider != "")');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
