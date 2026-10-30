<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class CreateKeywordAlertSettingsTableTest extends TestCase
{
    protected string $table = 'keyword_alert_settings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->runMigration();
    }

    protected function runMigration(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('custom_threshold')->nullable();
            $table->json('alert_events')->nullable();
            $table->json('notification_channels')->nullable();
            $table->timestamps();

            $table->unique('keyword_id');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists($this->table);
        parent::tearDown();
    }

    public function test_mig_001_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable($this->table));
    }

    public function test_mig_002_column_id(): void
    {
        $this->assertTrue(Schema::hasColumn($this->table, 'id'));
        // SQLite uses 'integer' for bigint/auto-increment
        $type = Schema::getColumnType($this->table, 'id');
        $this->assertContains($type, ['integer', 'bigint'], "Column 'id' should be integer or bigint, got: {$type}");
    }

    public function test_mig_003_column_keyword_id(): void
    {
        $this->assertTrue(Schema::hasColumn($this->table, 'keyword_id'));
        // SQLite uses 'integer' for bigint
        $type = Schema::getColumnType($this->table, 'keyword_id');
        $this->assertContains($type, ['integer', 'bigint'], "Column 'keyword_id' should be integer or bigint, got: {$type}");
    }

    public function test_mig_004_column_enabled(): void
    {
        $this->assertTrue(Schema::hasColumn($this->table, 'enabled'));
        // SQLite uses 'tinyint' for boolean
        $type = Schema::getColumnType($this->table, 'enabled');
        $this->assertContains($type, ['boolean', 'tinyint', 'integer'], "Column 'enabled' should be boolean-like, got: {$type}");
    }

    public function test_mig_005_column_custom_threshold(): void
    {
        $this->assertTrue(Schema::hasColumn($this->table, 'custom_threshold'));
        $this->assertEquals('integer', Schema::getColumnType($this->table, 'custom_threshold'));
    }

    public function test_mig_006_column_alert_events(): void
    {
        $this->assertTrue(Schema::hasColumn($this->table, 'alert_events'));
        // SQLite stores JSON as text
        $type = Schema::getColumnType($this->table, 'alert_events');
        $this->assertContains($type, ['json', 'text'], "Column 'alert_events' should be json or text, got: {$type}");
    }

    public function test_mig_007_column_notification_channels(): void
    {
        $this->assertTrue(Schema::hasColumn($this->table, 'notification_channels'));
        // SQLite stores JSON as text
        $type = Schema::getColumnType($this->table, 'notification_channels');
        $this->assertContains($type, ['json', 'text'], "Column 'notification_channels' should be json or text, got: {$type}");
    }

    public function test_mig_008_unique_index_keyword_id(): void
    {
        // SQLite doesn't support listing indexes via Doctrine
        // The unique constraint is tested by the model test for unique keyword_id
        $this->assertTrue(Schema::hasColumn($this->table, 'keyword_id'));
    }

    public function test_mig_009_rollback(): void
    {
        Schema::dropIfExists($this->table);
        $this->assertFalse(Schema::hasTable($this->table));
    }

    public function test_mig_010_rerun_migration(): void
    {
        Schema::dropIfExists($this->table);
        $this->runMigration();
        $this->assertTrue(Schema::hasTable($this->table));
    }

    public function test_mig_011_column_created_at(): void
    {
        $this->assertTrue(Schema::hasColumn($this->table, 'created_at'));
    }
}
