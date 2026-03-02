<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
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
        $column = DB::getDoctrineColumn($this->creditsTable, 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function credits_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'user_id'));
        $column = DB::getDoctrineColumn($this->creditsTable, 'user_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function credits_has_balance_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'balance'));
        $column = DB::getDoctrineColumn($this->creditsTable, 'balance');
        $this->assertStringContainsString('decimal', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
        $this->assertEquals('0.00', $column->getDefault());
    }

    /** @test */
    public function credits_has_total_purchased_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'total_purchased'));
        $column = DB::getDoctrineColumn($this->creditsTable, 'total_purchased');
        $this->assertStringContainsString('decimal', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
        $this->assertEquals('0.00', $column->getDefault());
    }

    /** @test */
    public function credits_has_total_spent_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->creditsTable, 'total_spent'));
        $column = DB::getDoctrineColumn($this->creditsTable, 'total_spent');
        $this->assertStringContainsString('decimal', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
        $this->assertEquals('0.00', $column->getDefault());
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
    public function credits_has_foreign_key_to_users(): void
    {
        $foreignKeys = $this->getForeignKeys($this->creditsTable);
        $userFk = collect($foreignKeys)->firstWhere('columns', ['user_id']);
        
        $this->assertNotNull($userFk, 'credits table should have foreign key on user_id');
        $this->assertEquals('users', $userFk['foreign_table']);
        $this->assertEquals('id', $userFk['foreign_columns'][0]);
        $this->assertEquals('CASCADE', $userFk['on_delete']);
    }

    /** @test */
    public function credits_unique_constraint_prevents_duplicate_user(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Create first credits record
        DB::table($this->creditsTable)->insert([
            'user_id' => 1,
            'balance' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Attempt duplicate - should fail
        DB::table($this->creditsTable)->insert([
            'user_id' => 1,
            'balance' => 200.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function credits_balance_default_is_zero(): void
    {
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => 999,
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
        $column = DB::getDoctrineColumn($this->transactionsTable, 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function transactions_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'user_id'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'user_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function transactions_has_amount_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'amount'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'amount');
        $this->assertStringContainsString('decimal', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function transactions_has_balance_after_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'balance_after'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'balance_after');
        $this->assertStringContainsString('decimal', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function transactions_has_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'type'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'type');
        $this->assertStringContainsString('string', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function transactions_has_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'description'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'description');
        $this->assertStringContainsString('string', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function transactions_has_reference_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'reference_type'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'reference_type');
        $this->assertStringContainsString('string', $column->getType()->getName());
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function transactions_has_reference_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'reference_id'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'reference_id');
        $this->assertStringContainsString('bigint', $column->getType()->getName());
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function transactions_has_payment_provider_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'payment_provider'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'payment_provider');
        $this->assertStringContainsString('string', $column->getType()->getName());
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function transactions_has_payment_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'payment_id'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'payment_id');
        $this->assertStringContainsString('string', $column->getType()->getName());
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function transactions_has_metadata_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->transactionsTable, 'metadata'));
        $column = DB::getDoctrineColumn($this->transactionsTable, 'metadata');
        $this->assertTrue($column->isNullable());
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

    /** @test */
    public function transactions_has_foreign_key_to_users(): void
    {
        $foreignKeys = $this->getForeignKeys($this->transactionsTable);
        $userFk = collect($foreignKeys)->firstWhere('columns', ['user_id']);
        
        $this->assertNotNull($userFk, 'credit_transactions should have foreign key on user_id');
        $this->assertEquals('users', $userFk['foreign_table']);
        $this->assertEquals('id', $userFk['foreign_columns'][0]);
        $this->assertEquals('CASCADE', $userFk['on_delete']);
    }

    // =========================================================================
    // ENUM VALIDATION TESTS
    // =========================================================================

    /** @test */
    public function transactions_accepts_purchase_type(): void
    {
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
            'amount' => 25.00,
            'balance_after' => 130.00,
            'type' => 'bonus',
            'description' => 'Test bonus',
            'created_at' => now(),
        ]);
        
        $this->assertIsInt($id);
    }

    /** @test */
    public function transactions_rejects_invalid_type(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table($this->transactionsTable)->insert([
            'user_id' => 1,
            'amount' => 10.00,
            'balance_after' => 50.00,
            'type' => 'invalid_type',
            'description' => 'Invalid type test',
            'created_at' => now(),
        ]);
    }

    // =========================================================================
    // DECIMAL PRECISION TESTS
    // =========================================================================

    /** @test */
    public function credits_accepts_max_decimal_value(): void
    {
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => 998,
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
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => 997,
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
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => 996,
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
        $id = DB::table($this->creditsTable)->insertGetId([
            'user_id' => 995,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $metadata = json_encode(['package' => 'starter', 'credits' => 50]);
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $metadata = json_encode([
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value'
                ]
            ]
        ]);
        
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
    // MIGRATION ROLLBACK TESTS
    // =========================================================================

    /** @test */
    public function rollback_drops_both_tables(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/*_create_credits_tables.php',
            '--step' => 1
        ]);
        
        $this->assertFalse(Schema::hasTable($this->creditsTable));
        $this->assertFalse(Schema::hasTable($this->transactionsTable));
        
        // Re-run to restore
        $this->artisan('migrate', [
            '--path' => 'database/migrations/*_create_credits_tables.php'
        ]);
    }

    /** @test */
    public function rerun_migration_is_idempotent(): void
    {
        // Run migration again
        $this->artisan('migrate', [
            '--path' => 'database/migrations/*_create_credits_tables.php'
        ]);
        
        $this->assertTrue(Schema::hasTable($this->creditsTable));
        $this->assertTrue(Schema::hasTable($this->transactionsTable));
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /** @test */
    public function transactions_handles_sql_injection_in_description(): void
    {
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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
        $id = DB::table($this->transactionsTable)->insertGetId([
            'user_id' => 1,
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

    protected function getForeignKeys(string $table): array
    {
        $conn = DB::getDoctrineConnection();
        $sm = $conn->createSchemaManager();
        
        return collect($sm->listTableForeignKeys($table))->map(function ($fk) {
            return [
                'columns' => $fk->getLocalColumns(),
                'foreign_table' => $fk->getForeignTableName(),
                'foreign_columns' => $fk->getForeignColumns(),
                'on_delete' => $fk->getOption('onDelete'),
            ];
        })->toArray();
    }

    protected function getIndexes(string $table): array
    {
        $conn = DB::getDoctrineConnection();
        $sm = $conn->createSchemaManager();
        
        return collect($sm->listTableIndexes($table))->map(function ($idx) {
            return [
                'name' => $idx->getName(),
                'columns' => $idx->getColumns(),
                'unique' => $idx->isUnique(),
            ];
        })->toArray();
    }
}
