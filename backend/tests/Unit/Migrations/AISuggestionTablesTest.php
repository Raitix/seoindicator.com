<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\User;
use App\Models\AISuggestionLog;
use App\Models\AISuggestionCache;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test suite for AI suggestion database migrations and models.
 * 
 * Tests cover:
 * - ai_suggestion_logs table schema
 * - ai_suggestions_cache table schema
 * - AISuggestionLog model
 * - AISuggestionCache model
 * - Migration rollback functionality
 * 
 * @group migrations
 * @group ai-suggestions
 */
class AISuggestionTablesTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TC-1: ai_suggestion_logs Table Schema Tests
    // ============================================

    /** @test */
    public function test_ai_suggestion_logs_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('ai_suggestion_logs'),
            'Table ai_suggestion_logs should exist after migration'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'id'),
            'Column id should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_user_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'user_id'),
            'Column user_id should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_session_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'session_id'),
            'Column session_id should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_indicator_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'indicator'),
            'Column indicator should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_provider_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'provider'),
            'Column provider should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_model_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'model'),
            'Column model should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_prompt_tokens_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'prompt_tokens'),
            'Column prompt_tokens should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_completion_tokens_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'completion_tokens'),
            'Column completion_tokens should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_latency_ms_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'latency_ms'),
            'Column latency_ms should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_success_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'success'),
            'Column success should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_error_message_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'error_message'),
            'Column error_message should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_request_hash_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'request_hash'),
            'Column request_hash should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_created_at_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestion_logs', 'created_at'),
            'Column created_at should exist on ai_suggestion_logs table'
        );
    }

    /** @test */
    public function test_ai_suggestion_logs_has_user_indicator_index(): void
    {
        $indexes = $this->getTableIndexes('ai_suggestion_logs');
        $this->assertArrayHasKey('idx_user_indicator', $indexes, 
            'Index idx_user_indicator should exist on ai_suggestion_logs');
    }

    /** @test */
    public function test_ai_suggestion_logs_has_provider_created_index(): void
    {
        $indexes = $this->getTableIndexes('ai_suggestion_logs');
        $this->assertArrayHasKey('idx_provider_created', $indexes, 
            'Index idx_provider_created should exist on ai_suggestion_logs');
    }

    /** @test */
    public function test_ai_suggestion_logs_has_success_index(): void
    {
        $indexes = $this->getTableIndexes('ai_suggestion_logs');
        $this->assertArrayHasKey('idx_success', $indexes, 
            'Index idx_success should exist on ai_suggestion_logs');
    }

    /** @test */
    public function test_ai_suggestion_logs_has_request_hash_index(): void
    {
        $indexes = $this->getTableIndexes('ai_suggestion_logs');
        $this->assertArrayHasKey('idx_request_hash', $indexes, 
            'Index idx_request_hash should exist on ai_suggestion_logs');
    }

    /** @test */
    public function test_ai_suggestion_logs_user_id_foreign_key_null_on_delete(): void
    {
        $user = User::factory()->create();
        
        $log = AISuggestionLog::create([
            'user_id' => $user->id,
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 100,
            'success' => true,
        ]);
        
        $user->delete();
        
        $log->refresh();
        $this->assertNull($log->user_id, 'user_id should be null after user deletion');
    }

    // ============================================
    // TC-2: ai_suggestions_cache Table Schema Tests
    // ============================================

    /** @test */
    public function test_ai_suggestions_cache_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('ai_suggestions_cache'),
            'Table ai_suggestions_cache should exist after migration'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_has_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestions_cache', 'id'),
            'Column id should exist on ai_suggestions_cache table'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_has_request_hash_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestions_cache', 'request_hash'),
            'Column request_hash should exist on ai_suggestions_cache table'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_has_indicator_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestions_cache', 'indicator'),
            'Column indicator should exist on ai_suggestions_cache table'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_has_context_hash_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestions_cache', 'context_hash'),
            'Column context_hash should exist on ai_suggestions_cache table'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_has_response_json_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestions_cache', 'response_json'),
            'Column response_json should exist on ai_suggestions_cache table'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_has_provider_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestions_cache', 'provider'),
            'Column provider should exist on ai_suggestions_cache table'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_has_created_at_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestions_cache', 'created_at'),
            'Column created_at should exist on ai_suggestions_cache table'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_has_expires_at_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('ai_suggestions_cache', 'expires_at'),
            'Column expires_at should exist on ai_suggestions_cache table'
        );
    }

    /** @test */
    public function test_ai_suggestions_cache_request_hash_unique_constraint(): void
    {
        $hash = hash('sha256', 'test-unique-hash');
        
        AISuggestionCache::create([
            'request_hash' => $hash,
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context'),
            'response_json' => json_encode(['test' => 'data']),
            'provider' => 'openrouter',
            'expires_at' => now()->addHours(24),
        ]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AISuggestionCache::create([
            'request_hash' => $hash, // Duplicate
            'indicator' => 'meta_description',
            'context_hash' => hash('sha256', 'context2'),
            'response_json' => json_encode(['test' => 'data2']),
            'provider' => 'vercel',
            'expires_at' => now()->addHours(24),
        ]);
    }

    /** @test */
    public function test_ai_suggestions_cache_has_hash_index(): void
    {
        $indexes = $this->getTableIndexes('ai_suggestions_cache');
        $this->assertArrayHasKey('idx_hash', $indexes, 
            'Index idx_hash should exist on ai_suggestions_cache');
    }

    /** @test */
    public function test_ai_suggestions_cache_has_expires_index(): void
    {
        $indexes = $this->getTableIndexes('ai_suggestions_cache');
        $this->assertArrayHasKey('idx_expires', $indexes, 
            'Index idx_expires should exist on ai_suggestions_cache');
    }

    // ============================================
    // TC-3: AISuggestionLog Model Tests
    // ============================================

    /** @test */
    public function test_ai_suggestion_log_model_exists(): void
    {
        $this->assertTrue(
            class_exists(AISuggestionLog::class),
            'AISuggestionLog model class should exist'
        );
    }

    /** @test */
    public function test_ai_suggestion_log_is_instantiable(): void
    {
        $log = new AISuggestionLog();
        $this->assertInstanceOf(AISuggestionLog::class, $log);
    }

    /** @test */
    public function test_ai_suggestion_log_fillable_attributes(): void
    {
        $log = new AISuggestionLog();
        $fillable = $log->getFillable();
        
        $expectedFillable = [
            'user_id',
            'session_id',
            'indicator',
            'provider',
            'model',
            'prompt_tokens',
            'completion_tokens',
            'latency_ms',
            'success',
            'error_message',
            'request_hash',
        ];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable, 
                "Attribute '{$attribute}' should be in fillable array");
        }
    }

    /** @test */
    public function test_ai_suggestion_log_casts(): void
    {
        $log = new AISuggestionLog();
        $casts = $log->getCasts();
        
        $this->assertEquals('boolean', $casts['success'] ?? null, 
            'success should be cast to boolean');
        $this->assertEquals('integer', $casts['prompt_tokens'] ?? null, 
            'prompt_tokens should be cast to integer');
        $this->assertEquals('integer', $casts['completion_tokens'] ?? null, 
            'completion_tokens should be cast to integer');
        $this->assertEquals('integer', $casts['latency_ms'] ?? null, 
            'latency_ms should be cast to integer');
    }

    /** @test */
    public function test_ai_suggestion_log_user_relationship(): void
    {
        $log = new AISuggestionLog();
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $log->user(),
            'AISuggestionLog should have a belongsTo relationship to User'
        );
    }

    /** @test */
    public function test_ai_suggestion_log_can_be_created(): void
    {
        $log = AISuggestionLog::create([
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 150,
            'success' => true,
        ]);
        
        $this->assertDatabaseHas('ai_suggestion_logs', [
            'id' => $log->id,
            'indicator' => 'title',
            'provider' => 'openrouter',
        ]);
    }

    /** @test */
    public function test_ai_suggestion_log_query_by_indicator(): void
    {
        AISuggestionLog::create([
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 100,
            'success' => true,
        ]);
        
        AISuggestionLog::create([
            'indicator' => 'meta_description',
            'provider' => 'openrouter',
            'latency_ms' => 120,
            'success' => true,
        ]);
        
        $titleLogs = AISuggestionLog::where('indicator', 'title')->get();
        $this->assertCount(1, $titleLogs);
        $this->assertEquals('title', $titleLogs->first()->indicator);
    }

    /** @test */
    public function test_ai_suggestion_log_query_by_user(): void
    {
        $user = User::factory()->create();
        
        AISuggestionLog::create([
            'user_id' => $user->id,
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 100,
            'success' => true,
        ]);
        
        AISuggestionLog::create([
            'user_id' => null,
            'indicator' => 'meta_description',
            'provider' => 'vercel',
            'latency_ms' => 120,
            'success' => true,
        ]);
        
        $userLogs = AISuggestionLog::where('user_id', $user->id)->get();
        $this->assertCount(1, $userLogs);
    }

    /** @test */
    public function test_ai_suggestion_log_query_failed_requests(): void
    {
        AISuggestionLog::create([
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 100,
            'success' => true,
        ]);
        
        AISuggestionLog::create([
            'indicator' => 'meta_description',
            'provider' => 'openrouter',
            'latency_ms' => 5000,
            'success' => false,
            'error_message' => 'Timeout',
        ]);
        
        $failedLogs = AISuggestionLog::where('success', false)->get();
        $this->assertCount(1, $failedLogs);
        $this->assertEquals('Timeout', $failedLogs->first()->error_message);
    }

    // ============================================
    // TC-4: AISuggestionCache Model Tests
    // ============================================

    /** @test */
    public function test_ai_suggestion_cache_model_exists(): void
    {
        $this->assertTrue(
            class_exists(AISuggestionCache::class),
            'AISuggestionCache model class should exist'
        );
    }

    /** @test */
    public function test_ai_suggestion_cache_is_instantiable(): void
    {
        $cache = new AISuggestionCache();
        $this->assertInstanceOf(AISuggestionCache::class, $cache);
    }

    /** @test */
    public function test_ai_suggestion_cache_fillable_attributes(): void
    {
        $cache = new AISuggestionCache();
        $fillable = $cache->getFillable();
        
        $expectedFillable = [
            'request_hash',
            'indicator',
            'context_hash',
            'response_json',
            'provider',
            'expires_at',
        ];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable, 
                "Attribute '{$attribute}' should be in fillable array");
        }
    }

    /** @test */
    public function test_ai_suggestion_cache_casts(): void
    {
        $cache = new AISuggestionCache();
        $casts = $cache->getCasts();
        
        $this->assertEquals('datetime', $casts['expires_at'] ?? null, 
            'expires_at should be cast to datetime');
    }

    /** @test */
    public function test_ai_suggestion_cache_expired_scope(): void
    {
        // Create expired cache
        AISuggestionCache::create([
            'request_hash' => hash('sha256', 'expired-cache'),
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context'),
            'response_json' => json_encode(['test' => 'expired']),
            'provider' => 'openrouter',
            'expires_at' => now()->subHours(1),
        ]);
        
        // Create valid cache
        AISuggestionCache::create([
            'request_hash' => hash('sha256', 'valid-cache'),
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context2'),
            'response_json' => json_encode(['test' => 'valid']),
            'provider' => 'openrouter',
            'expires_at' => now()->addHours(24),
        ]);
        
        $expiredCache = AISuggestionCache::expired()->get();
        $this->assertCount(1, $expiredCache);
        $this->assertEquals(hash('sha256', 'expired-cache'), $expiredCache->first()->request_hash);
    }

    /** @test */
    public function test_ai_suggestion_cache_valid_scope(): void
    {
        // Create expired cache
        AISuggestionCache::create([
            'request_hash' => hash('sha256', 'expired-cache2'),
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context'),
            'response_json' => json_encode(['test' => 'expired']),
            'provider' => 'openrouter',
            'expires_at' => now()->subHours(1),
        ]);
        
        // Create valid cache
        AISuggestionCache::create([
            'request_hash' => hash('sha256', 'valid-cache2'),
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context2'),
            'response_json' => json_encode(['test' => 'valid']),
            'provider' => 'openrouter',
            'expires_at' => now()->addHours(24),
        ]);
        
        $validCache = AISuggestionCache::valid()->get();
        $this->assertCount(1, $validCache);
        $this->assertEquals(hash('sha256', 'valid-cache2'), $validCache->first()->request_hash);
    }

    /** @test */
    public function test_ai_suggestion_cache_can_be_created(): void
    {
        $cache = AISuggestionCache::create([
            'request_hash' => hash('sha256', 'test-create'),
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context'),
            'response_json' => json_encode(['suggestions' => ['Test suggestion']]),
            'provider' => 'openrouter',
            'expires_at' => now()->addHours(24),
        ]);
        
        $this->assertDatabaseHas('ai_suggestions_cache', [
            'id' => $cache->id,
            'indicator' => 'title',
        ]);
    }

    /** @test */
    public function test_ai_suggestion_cache_find_by_hash(): void
    {
        $hash = hash('sha256', 'find-by-hash');
        
        AISuggestionCache::create([
            'request_hash' => $hash,
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context'),
            'response_json' => json_encode(['test' => 'data']),
            'provider' => 'openrouter',
            'expires_at' => now()->addHours(24),
        ]);
        
        $found = AISuggestionCache::where('request_hash', $hash)->first();
        $this->assertNotNull($found);
        $this->assertEquals($hash, $found->request_hash);
    }

    // ============================================
    // TC-6: Edge Cases Tests
    // ============================================

    /** @test */
    public function test_ai_suggestion_log_null_user_id_allowed(): void
    {
        $log = AISuggestionLog::create([
            'user_id' => null,
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 100,
            'success' => true,
        ]);
        
        $this->assertNull($log->user_id);
        $this->assertDatabaseHas('ai_suggestion_logs', [
            'id' => $log->id,
            'user_id' => null,
        ]);
    }

    /** @test */
    public function test_ai_suggestion_log_long_session_id(): void
    {
        $longSessionId = str_repeat('a', 255);
        
        $log = AISuggestionLog::create([
            'session_id' => $longSessionId,
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 100,
            'success' => true,
        ]);
        
        $this->assertEquals(255, strlen($log->session_id));
    }

    /** @test */
    public function test_ai_suggestion_log_long_error_message(): void
    {
        $longError = str_repeat('Error: ', 1000); // ~7000 chars
        
        $log = AISuggestionLog::create([
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 5000,
            'success' => false,
            'error_message' => $longError,
        ]);
        
        $this->assertGreaterThan(6000, strlen($log->error_message));
    }

    /** @test */
    public function test_ai_suggestion_log_zero_latency(): void
    {
        $log = AISuggestionLog::create([
            'indicator' => 'title',
            'provider' => 'openrouter',
            'latency_ms' => 0,
            'success' => true,
        ]);
        
        $this->assertEquals(0, $log->latency_ms);
    }

    /** @test */
    public function test_ai_suggestion_cache_expired_cleanup(): void
    {
        // Create expired caches
        AISuggestionCache::create([
            'request_hash' => hash('sha256', 'cleanup-1'),
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context'),
            'response_json' => json_encode(['test' => '1']),
            'provider' => 'openrouter',
            'expires_at' => now()->subHours(1),
        ]);
        
        AISuggestionCache::create([
            'request_hash' => hash('sha256', 'cleanup-2'),
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context'),
            'response_json' => json_encode(['test' => '2']),
            'provider' => 'openrouter',
            'expires_at' => now()->addHours(24),
        ]);
        
        $deleted = AISuggestionCache::expired()->delete();
        $this->assertEquals(1, $deleted);
        
        $this->assertEquals(1, AISuggestionCache::count());
    }

    /** @test */
    public function test_ai_suggestion_cache_ttl_calculation(): void
    {
        $now = now();
        $expiresAt = $now->copy()->addHours(24);
        
        $cache = AISuggestionCache::create([
            'request_hash' => hash('sha256', 'ttl-test'),
            'indicator' => 'title',
            'context_hash' => hash('sha256', 'context'),
            'response_json' => json_encode(['test' => 'ttl']),
            'provider' => 'openrouter',
            'expires_at' => $expiresAt,
        ]);
        
        $diff = $cache->created_at->diffInHours($cache->expires_at);
        $this->assertEquals(24, $diff);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Get table indexes as associative array.
     */
    private function getTableIndexes(string $table): array
    {
        $indexes = [];
        
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            
            if ($driver === 'sqlite') {
                // SQLite: Query sqlite_master for indexes
                $results = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=?", [$table]);
                foreach ($results as $row) {
                    $indexes[$row->name] = $row->name;
                }
            } else {
                // MySQL/other: Use SHOW INDEX
                $results = DB::select("SHOW INDEX FROM {$table}");
                foreach ($results as $row) {
                    $indexName = $row->Key_name;
                    $indexes[$indexName] = $indexName;
                }
            }
        } catch (\Exception $e) {
            // Table might not exist or other issue
        }
        
        return $indexes;
    }
}
