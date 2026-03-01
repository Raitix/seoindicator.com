<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\Account;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for the brand_configs table migration.
 *
 * @see TASK-15-1
 * @see TESTS/SPEC-15-1
 */
class CreateBrandConfigsTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test brand_configs table exists after migration.
     */
    #[Test]
    public function brand_configs_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('brand_configs'),
            'brand_configs table should exist'
        );
    }

    /**
     * Test brand_configs table has id column.
     */
    #[Test]
    public function brand_configs_table_has_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'id'),
            'brand_configs table should have id column'
        );
    }

    /**
     * Test brand_configs table has account_id column.
     */
    #[Test]
    public function brand_configs_table_has_account_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'account_id'),
            'brand_configs table should have account_id column'
        );
    }

    /**
     * Test brand_configs table has logo_url column.
     */
    #[Test]
    public function brand_configs_table_has_logo_url_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'logo_url'),
            'brand_configs table should have logo_url column'
        );
    }

    /**
     * Test brand_configs table has brand_name column.
     */
    #[Test]
    public function brand_configs_table_has_brand_name_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'brand_name'),
            'brand_configs table should have brand_name column'
        );
    }

    /**
     * Test brand_configs table has primary_color column.
     */
    #[Test]
    public function brand_configs_table_has_primary_color_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'primary_color'),
            'brand_configs table should have primary_color column'
        );
    }

    /**
     * Test brand_configs table has secondary_color column.
     */
    #[Test]
    public function brand_configs_table_has_secondary_color_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'secondary_color'),
            'brand_configs table should have secondary_color column'
        );
    }

    /**
     * Test brand_configs table has favicon_url column.
     */
    #[Test]
    public function brand_configs_table_has_favicon_url_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'favicon_url'),
            'brand_configs table should have favicon_url column'
        );
    }

    /**
     * Test brand_configs table has login_background_url column.
     */
    #[Test]
    public function brand_configs_table_has_login_background_url_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'login_background_url'),
            'brand_configs table should have login_background_url column'
        );
    }

    /**
     * Test brand_configs table has footer_text column.
     */
    #[Test]
    public function brand_configs_table_has_footer_text_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'footer_text'),
            'brand_configs table should have footer_text column'
        );
    }

    /**
     * Test brand_configs table has hide_powered_by column.
     */
    #[Test]
    public function brand_configs_table_has_hide_powered_by_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'hide_powered_by'),
            'brand_configs table should have hide_powered_by column'
        );
    }

    /**
     * Test brand_configs table has custom_email_from column.
     */
    #[Test]
    public function brand_configs_table_has_custom_email_from_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'custom_email_from'),
            'brand_configs table should have custom_email_from column'
        );
    }

    /**
     * Test brand_configs table has timestamp columns.
     */
    #[Test]
    public function brand_configs_table_has_timestamp_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'created_at'),
            'brand_configs table should have created_at column'
        );
        $this->assertTrue(
            Schema::hasColumn('brand_configs', 'updated_at'),
            'brand_configs table should have updated_at column'
        );
    }

    /**
     * Test id column is auto-increment primary key.
     */
    #[Test]
    public function id_column_is_auto_increment_primary_key(): void
    {
        $connection = Schema::getConnection();
        $tableName = 'brand_configs';
        
        // Create a record and verify auto-increment
        $account = Account::factory()->create();
        
        DB::table('brand_configs')->insert([
            'account_id' => $account->id,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $firstId = DB::table('brand_configs')->first()->id;
        $this->assertIsInt($firstId);
        $this->assertGreaterThan(0, $firstId);
    }

    /**
     * Test account_id column has correct type.
     */
    #[Test]
    public function account_id_column_has_correct_type(): void
    {
        $columnType = Schema::getColumnType('brand_configs', 'account_id');
        $this->assertContains($columnType, ['bigint', 'integer', 'int']);
    }

    /**
     * Test logo_url column is nullable.
     */
    #[Test]
    public function logo_url_column_is_nullable(): void
    {
        $account = Account::factory()->create();
        
        // Should be able to insert with null logo_url
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'logo_url' => null,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $this->assertNotNull($id);
    }

    /**
     * Test brand_name column has default value.
     */
    #[Test]
    public function brand_name_column_has_default_value(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals('SEOIndicator', $brandConfig->brand_name);
    }

    /**
     * Test primary_color column has default value.
     */
    #[Test]
    public function primary_color_column_has_default_value(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => 'Test',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals('#3B82F6', $brandConfig->primary_color);
    }

    /**
     * Test secondary_color column has default value.
     */
    #[Test]
    public function secondary_color_column_has_default_value(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals('#1E40AF', $brandConfig->secondary_color);
    }

    /**
     * Test hide_powered_by column has default value.
     */
    #[Test]
    public function hide_powered_by_column_has_default_value(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals(0, $brandConfig->hide_powered_by);
    }

    /**
     * Test foreign key constraint to accounts table.
     */
    #[Test]
    public function foreign_key_constraint_to_accounts_table(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Attempt to insert with non-existent account_id
        DB::table('brand_configs')->insert([
            'account_id' => 99999,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
    }

    /**
     * Test cascade delete when account is deleted.
     */
    #[Test]
    public function cascade_delete_when_account_is_deleted(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        // Delete the account
        $account->delete();
        
        // Verify brand_config was cascade deleted
        $this->assertNull(DB::table('brand_configs')->find($id));
    }

    /**
     * Test unique constraint on account_id.
     */
    #[Test]
    public function unique_constraint_on_account_id(): void
    {
        $account = Account::factory()->create();
        
        // Insert first brand config
        DB::table('brand_configs')->insert([
            'account_id' => $account->id,
            'brand_name' => 'Test 1',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        // Attempt to insert second brand config for same account
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table('brand_configs')->insert([
            'account_id' => $account->id,
            'brand_name' => 'Test 2',
            'primary_color' => '#111111',
            'secondary_color' => '#EEEEEE',
        ]);
    }

    /**
     * Test create brand config with all fields.
     */
    #[Test]
    public function create_brand_config_with_all_fields(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'logo_url' => 'https://example.com/logo.png',
            'brand_name' => 'Agency Pro',
            'primary_color' => '#FF5733',
            'secondary_color' => '#C70039',
            'favicon_url' => 'https://example.com/favicon.ico',
            'login_background_url' => 'https://example.com/bg.jpg',
            'footer_text' => 'Powered by Agency Pro',
            'hide_powered_by' => true,
            'custom_email_from' => 'noreply@agency.pro',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        
        $this->assertEquals($account->id, $brandConfig->account_id);
        $this->assertEquals('https://example.com/logo.png', $brandConfig->logo_url);
        $this->assertEquals('Agency Pro', $brandConfig->brand_name);
        $this->assertEquals('#FF5733', $brandConfig->primary_color);
        $this->assertEquals('#C70039', $brandConfig->secondary_color);
        $this->assertEquals('https://example.com/favicon.ico', $brandConfig->favicon_url);
        $this->assertEquals('https://example.com/bg.jpg', $brandConfig->login_background_url);
        $this->assertEquals('Powered by Agency Pro', $brandConfig->footer_text);
        $this->assertEquals(1, $brandConfig->hide_powered_by);
        $this->assertEquals('noreply@agency.pro', $brandConfig->custom_email_from);
    }

    /**
     * Test create brand config with minimal fields.
     */
    #[Test]
    public function create_brand_config_with_minimal_fields(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        
        $this->assertNotNull($brandConfig);
        $this->assertEquals($account->id, $brandConfig->account_id);
        $this->assertEquals('SEOIndicator', $brandConfig->brand_name);
        $this->assertEquals('#3B82F6', $brandConfig->primary_color);
        $this->assertEquals('#1E40AF', $brandConfig->secondary_color);
    }

    /**
     * Test update brand config.
     */
    #[Test]
    public function update_brand_config(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => 'Original',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        DB::table('brand_configs')
            ->where('id', $id)
            ->update([
                'brand_name' => 'Updated',
                'primary_color' => '#FF0000',
                'logo_url' => 'https://example.com/new-logo.png',
            ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        
        $this->assertEquals('Updated', $brandConfig->brand_name);
        $this->assertEquals('#FF0000', $brandConfig->primary_color);
        $this->assertEquals('https://example.com/new-logo.png', $brandConfig->logo_url);
    }

    /**
     * Test delete brand config.
     */
    #[Test]
    public function delete_brand_config(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        DB::table('brand_configs')->where('id', $id)->delete();
        
        $this->assertNull(DB::table('brand_configs')->find($id));
    }

    /**
     * Test SQL injection prevention in logo_url.
     */
    #[Test]
    public function sql_injection_prevention_in_logo_url(): void
    {
        $account = Account::factory()->create();
        
        $maliciousUrl = "'; DROP TABLE brand_configs; --";
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'logo_url' => $maliciousUrl,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        // Verify table still exists and data was stored as literal string
        $this->assertTrue(Schema::hasTable('brand_configs'));
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals($maliciousUrl, $brandConfig->logo_url);
    }

    /**
     * Test SQL injection prevention in brand_name.
     */
    #[Test]
    public function sql_injection_prevention_in_brand_name(): void
    {
        $account = Account::factory()->create();
        
        $maliciousName = "Robert'); DROP TABLE brand_configs; --";
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => $maliciousName,
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $this->assertTrue(Schema::hasTable('brand_configs'));
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals($maliciousName, $brandConfig->brand_name);
    }

    /**
     * Test XSS payload stored as literal string in footer_text.
     */
    #[Test]
    public function xss_payload_in_footer_text(): void
    {
        $account = Account::factory()->create();
        
        $xssPayload = "<script>alert('xss')</script>";
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'footer_text' => $xssPayload,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals($xssPayload, $brandConfig->footer_text);
    }

    /**
     * Test HTML in footer_text stored as literal string.
     */
    #[Test]
    public function html_in_footer_text(): void
    {
        $account = Account::factory()->create();
        
        $htmlContent = "<p>Styled <strong>footer</strong></p>";
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'footer_text' => $htmlContent,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals($htmlContent, $brandConfig->footer_text);
    }

    /**
     * Test long logo_url accepted (500 chars).
     */
    #[Test]
    public function long_logo_url_accepted(): void
    {
        $account = Account::factory()->create();
        
        $longUrl = 'https://example.com/' . str_repeat('a', 480);
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'logo_url' => $longUrl,
            'brand_name' => 'Test',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals($longUrl, $brandConfig->logo_url);
    }

    /**
     * Test long brand_name accepted (100 chars).
     */
    #[Test]
    public function long_brand_name_accepted(): void
    {
        $account = Account::factory()->create();
        
        $longName = str_repeat('A', 100);
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => $longName,
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals($longName, $brandConfig->brand_name);
    }

    /**
     * Test invalid hex color accepted (no DB-level validation).
     */
    #[Test]
    public function invalid_hex_color_accepted(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => 'Test',
            'primary_color' => 'red',
            'secondary_color' => 'blue',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals('red', $brandConfig->primary_color);
        $this->assertEquals('blue', $brandConfig->secondary_color);
    }

    /**
     * Test empty brand_name accepted (NOT NULL but no min length).
     */
    #[Test]
    public function empty_brand_name_accepted(): void
    {
        $account = Account::factory()->create();
        
        $id = DB::table('brand_configs')->insertGetId([
            'account_id' => $account->id,
            'brand_name' => '',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
        ]);
        
        $brandConfig = DB::table('brand_configs')->find($id);
        $this->assertEquals('', $brandConfig->brand_name);
    }

    /**
     * Test migration rollback drops table.
     */
    #[Test]
    public function migration_rollback_drops_table(): void
    {
        // Get the migration file
        $migrationFile = glob(database_path('migrations/*create_brand_configs_table*.php'))[0] ?? null;
        
        if ($migrationFile) {
            // Run rollback
            $this->artisan('migrate:rollback', [
                '--path' => str_replace(base_path() . '/', '', $migrationFile),
                '--force' => true,
            ]);
            
            $this->assertFalse(
                Schema::hasTable('brand_configs'),
                'brand_configs table should be dropped after rollback'
            );
            
            // Re-run migration
            $this->artisan('migrate', [
                '--path' => str_replace(base_path() . '/', '', $migrationFile),
                '--force' => true,
            ]);
            
            $this->assertTrue(
                Schema::hasTable('brand_configs'),
                'brand_configs table should exist after re-running migration'
            );
        } else {
            $this->markTestSkipped('Migration file not found - test will run after migration is created.');
        }
    }

    /**
     * Test all expected columns exist.
     */
    #[Test]
    public function all_expected_columns_exist(): void
    {
        $expectedColumns = [
            'id',
            'account_id',
            'logo_url',
            'brand_name',
            'primary_color',
            'secondary_color',
            'favicon_url',
            'login_background_url',
            'footer_text',
            'hide_powered_by',
            'custom_email_from',
            'created_at',
            'updated_at',
        ];
        
        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('brand_configs', $column),
                "brand_configs table should have {$column} column"
            );
        }
    }
}
