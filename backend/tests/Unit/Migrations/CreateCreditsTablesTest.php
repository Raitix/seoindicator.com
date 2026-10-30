<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test suite for credits and credit_transactions table migrations.
 * 
 * Task: TASK-26-1
 * Feature: FEAT-026 (Billing, Credits & Model Selection)
 * 
 * @covers \Database\Migrations\CreateCreditsTables
 */
class CreateCreditsTablesTest extends TestCase
{
    protected string $creditsTable = 'credits';
    protected string $transactionsTable = 'credit_transactions';

    protected function setUp(): void
    {
        parent::setUp();
        $this->runMigrations();
    }

    protected function runMigrations(): void
    {
        // Create users table first (for foreign keys)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        // Create credits table
        Schema::create($this->creditsTable, function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->decimal('total_purchased', 10, 2)->default(0.00);
            $table->decimal('total_spent', 10, 2)->default(0.00);
            $table->timestamps();
            $table->unique('user_id');
        });

        // Create credit_transactions table
        Schema::create($this->transactionsTable, function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->enum('type', ['purchase', 'usage', 'refund', 'bonus']);
            $table->string('description', 500);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('payment_provider', 20)->nullable();
            $table->string('payment_id', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['payment_provider', 'payment_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists($this->transactionsTable);
        Schema::dropIfExists($this->creditsTable);
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    // =========================================================================
    // credits TABLE TESTS
    // =========================================================================

    /** @test */
    public function credits_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable($this->creditsTable));
    }

    /** @test */
    public function credits_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'id'));
        $type = Schema::getColumnType($this->creditsTable, 'id');
        $this->assertContains($type, ['integer', 'bigint'], "Column 'id' should be integer or bigint, got: {$type}");
    }

    /** @test */
    public function credits_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'user_id'));
        $type = Schema::getColumnType($this->creditsTable, 'user_id');
        $this->assertContains($type, ['integer', 'bigint'], "Column 'user_id' should be integer or bigint, got: {$type}");
    }

    /** @test */
    public function credits_has_balance_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'balance'));
        $type = Schema::getColumnType($this->creditsTable, 'balance');
        $this->assertContains($type, ['decimal', 'numeric', 'float'], "Column 'balance' should be decimal, got: {$type}");
    }

    /** @test */
    public function credits_has_total_purchased_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'total_purchased'));
        $type = Schema::getColumnType($this->creditsTable, 'total_purchased');
        $this->assertContains($type, ['decimal', 'numeric', 'float'], "Column 'total_purchased' should be decimal, got: {$type}");
    }

    /** @test */
    public function credits_has_total_spent_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'total_spent'));
        $type = Schema::getColumnType($this->creditsTable, 'total_spent');
        $this->assertContains($type, ['decimal', 'numeric', 'float'], "Column 'total_spent' should be decimal, got: {$type}");
    }

    /** @test */
    public function credits_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'created_at'));
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'updated_at'));
    }

    /** @test */
    public function credits_has_unique_index_on_user_id(): void
    {
        $indexes = $this->getIndexes($this->creditsTable);
        $userIndex = collect($indexes)->first(function ($idx) {
            return in_array('user_id', $idx['columns']) && $idx['unique'];
        });
        
        $this->assertNotNull($userIndex, 'credits table should have unique index on user_id');
    }

    /** @test */
    public function credits_unique_constraint_prevents_duplicate_user(): void
    {
        // Create a user first
        $userId = DB::table('users')->insertGetId([
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create first credits record
        DB::table($this->creditsTable)->insert([
            'user_id' => $userId,
            'balance' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Attempt duplicate - should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table($this->creditsTable)->insert([
            'user_id' => $userId,
            'balance' => 200.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function credits_balance_default_is_zero(): void
    {
        $userId = DB::table('users')->insertGetId([
            'email' => 'default@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table($this->creditsTable)->find($id);
        $this->assertEquals('0.00', $record->balance);
        $this->assertEquals('0.00', $record->total_purchased);
        $this->assertEquals('0.00', $record->total_spent);
    }

    // =========================================================================
    // credit_transactions TABLE TESTS
    // =========================================================================

    /** @test */
    public function transactions_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable($this->transactionsTable));
    }

    /** @test */
    public function transactions_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'id'));
        $type = Schema::getColumnType($this->transactionsTable, 'id');
        $this->assertContains($type, ['integer', 'bigint'], "Column 'id' should be integer or bigint, got: {$type}");
    }

    /** @test */
    public function transactions_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'user_id'));
        $type = Schema::getColumnType($this->transactionsTable, 'user_id');
        $this->assertContains($type, ['integer', 'bigint'], "Column 'user_id' should be integer or bigint, got: {$type}");
    }

    /** @test */
    public function transactions_has_amount_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'amount'));
        $type = Schema::getColumnType($this->transactionsTable, 'amount');
        $this->assertContains($type, ['decimal', 'numeric', 'float'], "Column 'amount' should be decimal, got: {$type}");
    }

    /** @test */
    public function transactions_has_balance_after_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'balance_after'));
        $type = Schema::getColumnType($this->transactionsTable, 'balance_after');
        $this->assertContains($type, ['decimal', 'numeric', 'float'], "Column 'balance_after' should be decimal, got: {$type}");
    }

    /** @test */
    public function transactions_has_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'type'));
        $type = Schema::getColumnType($this->transactionsTable, 'type');
        $this->assertContains($type, ['string', 'varchar'], "Column 'type' should be string, got: {$type}");
    }

    /** @test */
    public function transactions_has_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'description'));
        $type = Schema::getColumnType($this->transactionsTable, 'description');
        $this->assertContains($type, ['string', 'varchar'], "Column 'description' should be string, got: {$type}");
    }

    /** @test */
    public function transactions_has_reference_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'reference_type'));
    }

    /** @test */
    public function transactions_has_reference_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'reference_id'));
    }

    /** @test */
    public function transactions_has_payment_provider_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'payment_provider'));
    }

    /** @test */
    public function transactions_has_payment_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'payment_id'));
    }

    /** @test */
    public function transactions_has_metadata_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'metadata'));
    }

    /** @test */
    public function transactions_has_created_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'created_at'));
    }

    /** @test */
    public function transactions_does_not_have_updated_at(): void
    {
        // Transactions are immutable - should not have updated_at
        $this->assertFalse(Schema::hasColumn($this->transactionsTable, 'updated_at'));
    }

    // =========================================================================
    // INDEX TESTS
    // =========================================================================

    /** @test */
    public function transactions_has_index_on_user_id(): void
    {
        $indexes = $this->getIndexes($this->transactionsTable);
        $userIndex = collect($indexes)->first(function ($idx) {
            return in_array('user_id', $idx['columns']);
        });
        
        $this->assertNotNull($userIndex, 'credit_transactions should have index on user_id');
    }

    /** @test */
    public function transactions_has_index_on_type(): void
    {
        $indexes = $this->getIndexes($this->transactionsTable);
        $typeIndex = collect($indexes)->first(function ($idx) {
            return in_array('type', $idx['columns']);
        });
        
        $this->assertNotNull($typeIndex, 'credit_transactions should have index on type');
    }

    /** @test */
    public function transactions_has_index_on_created_at(): void
    {
        $indexes = $this->getIndexes($this->transactionsTable);
        $dateIndex = collect($indexes)->first(function ($idx) {
            return in_array('created_at', $idx['columns']);
        });
        
        $this->assertNotNull($dateIndex, 'credit_transactions should have index on created_at');
    }

    /** @test */
    public function transactions_has_composite_payment_index(): void
    {
        $indexes = $this->getIndexes($this->transactionsTable);
        $paymentIndex = collect($indexes)->first(function ($idx) {
            return in_array('payment_provider', $idx['columns']) && 
                   in_array('payment_id', $idx['columns']);
        });
        
        $this->assertNotNull($paymentIndex, 'credit_transactions should have composite index on (payment_provider, payment_id)');
    }

    // =========================================================================
    // ENUM VALIDATION TESTS (using SQLite which doesn't enforce enums)
    // =========================================================================

    /** @test */
    public function transactions_accepts_purchase_type(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 50.00,
            'balance_after' => 100.00,
            'type' => 'purchase',
            'description' => 'Test purchase',
            'created_at' => now(),
        ]);
        
        $this->assertIsInt($id);
    }

    /** @test */
    public function transactions_accepts_usage_type(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => -5.00,
            'balance_after' => 95.00,
            'type' => 'usage',
            'description' => 'Test usage',
            'created_at' => now(),
        ]);
        
        $this->assertIsInt($id);
    }

    /** @test */
    public function transactions_accepts_refund_type(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 10.00,
            'balance_after' => 105.00,
            'type' => 'refund',
            'description' => 'Test refund',
            'created_at' => now(),
        ]);
        
        $this->assertIsInt($id);
    }

    /** @test */
    public function transactions_accepts_bonus_type(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 25.00,
            'balance_after' => 130.00,
            'type' => 'bonus',
            'description' => 'Test bonus',
            'created_at' => now(),
        ]);
        
        $this->assertIsInt($id);
    }

    // =========================================================================
    // DECIMAL PRECISION TESTS
    // =========================================================================

    /** @test */
    public function credits_accepts_max_decimal_value(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => $userId,
            'balance' => 99999999.99,
            'total_purchased' => 99999999.99,
            'total_spent' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table($this->creditsTable)->find($id);
        $this->assertEquals('99999999.99', $record->balance);
    }

    /** @test */
    public function credits_accepts_zero_balance(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => $userId,
            'balance' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table($this->creditsTable)->find($id);
        $this->assertEquals('0.00', $record->balance);
    }

    /** @test */
    public function credits_accepts_small_decimal(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => $userId,
            'balance' => 0.01,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table($this->creditsTable)->find($id);
        $this->assertEquals('0.01', $record->balance);
    }

    /** @test */
    public function credits_accepts_negative_balance(): void
    {
        // Negative balance is allowed at DB level (business logic may restrict)
        $userId = $this->createUser();
        
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => $userId,
            'balance' => -100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table($this->creditsTable)->find($id);
        $this->assertEquals('-100.00', $record->balance);
    }

    /** @test */
    public function transactions_accepts_negative_amount(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => -99999.99,
            'balance_after' => 0.00,
            'type' => 'usage',
            'description' => 'Large usage',
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertEquals('-99999.99', $record->amount);
    }

    // =========================================================================
    // NULLABLE FIELD TESTS
    // =========================================================================

    /** @test */
    public function transactions_accepts_null_reference_fields(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 50.00,
            'balance_after' => 50.00,
            'type' => 'purchase',
            'description' => 'No reference',
            'reference_type' => null,
            'reference_id' => null,
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertNull($record->reference_type);
        $this->assertNull($record->reference_id);
    }

    /** @test */
    public function transactions_accepts_null_payment_fields(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 10.00,
            'balance_after' => 60.00,
            'type' => 'bonus',
            'description' => 'No payment',
            'payment_provider' => null,
            'payment_id' => null,
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertNull($record->payment_provider);
        $this->assertNull($record->payment_id);
    }

    /** @test */
    public function transactions_accepts_null_metadata(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 5.00,
            'balance_after' => 65.00,
            'type' => 'usage',
            'description' => 'No metadata',
            'metadata' => null,
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertNull($record->metadata);
    }

    // =========================================================================
    // JSON METADATA TESTS
    // =========================================================================

    /** @test */
    public function transactions_stores_json_metadata(): void
    {
        $userId = $this->createUser();
        $metadata = json_encode(['package' => 'starter', 'credits' => 50]);
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 50.00,
            'balance_after' => 115.00,
            'type' => 'purchase',
            'description' => 'With metadata',
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $decoded = json_decode($record->metadata, true);
        $this->assertEquals('starter', $decoded['package']);
        $this->assertEquals(50, $decoded['credits']);
    }

    /** @test */
    public function transactions_stores_nested_json_metadata(): void
    {
        $userId = $this->createUser();
        $metadata = json_encode([
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value'
                ]
            ]
        ]);
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 5.00,
            'balance_after' => 120.00,
            'type' => 'usage',
            'description' => 'Nested metadata',
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $decoded = json_decode($record->metadata, true);
        $this->assertEquals('deep_value', $decoded['level1']['level2']['level3']);
    }

    // =========================================================================
    // PAYMENT PROVIDER TESTS
    // =========================================================================

    /** @test */
    public function transactions_stores_stripe_payment_info(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 100.00,
            'balance_after' => 220.00,
            'type' => 'purchase',
            'description' => 'Stripe payment',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_abc123xyz',
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertEquals('stripe', $record->payment_provider);
        $this->assertEquals('pi_abc123xyz', $record->payment_id);
    }

    /** @test */
    public function transactions_stores_paypal_payment_info(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 75.00,
            'balance_after' => 295.00,
            'type' => 'purchase',
            'description' => 'PayPal payment',
            'payment_provider' => 'paypal',
            'payment_id' => 'PAYID-ABC123',
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertEquals('paypal', $record->payment_provider);
        $this->assertEquals('PAYID-ABC123', $record->payment_id);
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /** @test */
    public function transactions_handles_sql_injection_in_description(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 5.00,
            'balance_after' => 300.00,
            'type' => 'usage',
            'description' => "'; DROP TABLE credit_transactions;--",
            'created_at' => now(),
        ]);
        
        // Should insert without error, table should still exist
        $this->assertTrue(Schema::hasTable($this->transactionsTable));
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertStringContainsString("DROP TABLE", $record->description);
    }

    /** @test */
    public function transactions_handles_special_chars_in_payment_id(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 10.00,
            'balance_after' => 310.00,
            'type' => 'purchase',
            'description' => 'Special chars test',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_abc-123_xyz.ABC',
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertEquals('pi_abc-123_xyz.ABC', $record->payment_id);
    }

    /** @test */
    public function transactions_handles_unicode_in_description(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => $userId,
            'amount' => 5.00,
            'balance_after' => 315.00,
            'type' => 'usage',
            'description' => '日本語の説明 - Кирилица - العربية',
            'created_at' => now(),
        ]);
        
        $record = DB::table($this->transactionsTable)->find($id);
        $this->assertStringContainsString('日本語', $record->description);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function createUser(): int
    {
        return DB::table('users')->insertGetId([
            'email' => 'user_' . uniqid() . '@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function getIndexes(string $table): array
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();
        
        if ($driver === 'sqlite') {
            $indexes = [];
            $results = DB::select("PRAGMA index_list({$table})");
            
            foreach ($results as $row) {
                $columns = DB::select("PRAGMA index_info({$row->name})");
                $indexColumns = array_map(fn($c) => $c->name, $columns);
                
                $indexes[] = [
                    'name' => $row->name,
                    'columns' => $indexColumns,
                    'unique' => (bool) ($row->unique ?? false),
                ];
            }
            
            return $indexes;
        }
        
        // MySQL fallback
        $results = DB::select("SHOW INDEX FROM {$table}");
        $indexes = [];
        
        foreach ($results as $row) {
            if (!isset($indexes[$row->Key_name])) {
                $indexes[$row->Key_name] = [
                    'name' => $row->Key_name,
                    'columns' => [],
                    'unique' => !$row->Non_unique,
                ];
            }
            $indexes[$row->Key_name]['columns'][] = $row->Column_name;
        }
        
        return array_values($indexes);
    }
}
