<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

/**
 * Documentation Consistency Test for TASK-14-4
 * 
 * Validates that DOCUMENTATION.md properly reflects the audits schema
 * and related tables that exist in migrations.
 * 
 * @see TESTS/TEST-14-4.md
 */
class DocumentationConsistencyTest extends TestCase
{
    private string $docPath;
    private string $migrationsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->docPath = base_path('../DOCUMENTATION.md');
        $this->migrationsPath = database_path('migrations');
    }

    /**
     * Test that DOCUMENTATION.md exists.
     */
    public function test_documentation_file_exists(): void
    {
        $this->assertFileExists($this->docPath, 'DOCUMENTATION.md should exist at project root');
    }

    /**
     * Test that DOCUMENTATION.md includes audits table documentation.
     */
    public function test_documentation_includes_audits_table(): void
    {
        $doc = File::get($this->docPath);
        
        $this->assertStringContainsString('audits', $doc, 
            'DOCUMENTATION.md should mention audits table');
        $this->assertStringContainsString('site_id', $doc, 
            'DOCUMENTATION.md should document site_id column for audits');
    }

    /**
     * Test that audits documentation uses site_id, not project_id.
     * This is a critical consistency check - the migrations use site_id
     * for schema consistency with existing tables.
     */
    public function test_audits_uses_site_id_not_project_id(): void
    {
        $doc = File::get($this->docPath);
        
        // Find audits section (case-insensitive search)
        $pattern = '/audits.*?(?=###|\Z)/is';
        
        if (preg_match($pattern, $doc, $matches)) {
            $auditsSection = $matches[0];
            
            $this->assertStringContainsString('site_id', $auditsSection,
                'Audits documentation should mention site_id column');
            $this->assertStringNotContainsString('project_id', $auditsSection,
                'Audits documentation should NOT use project_id - migrations use site_id');
        } else {
            // If no dedicated audits section, at least ensure site_id is mentioned in context
            $this->assertStringContainsString('site_id', $doc,
                'Documentation should mention site_id in context of audits');
        }
    }

    /**
     * Test that DOCUMENTATION.md includes audit_issues table documentation.
     */
    public function test_documentation_includes_audit_issues_table(): void
    {
        $doc = File::get($this->docPath);
        
        $this->assertStringContainsString('audit_issues', $doc, 
            'DOCUMENTATION.md should mention audit_issues table');
    }

    /**
     * Test that DOCUMENTATION.md includes issue_types table documentation.
     */
    public function test_documentation_includes_issue_types_table(): void
    {
        $doc = File::get($this->docPath);
        
        $this->assertStringContainsString('issue_types', $doc, 
            'DOCUMENTATION.md should mention issue_types table');
    }

    /**
     * Test that audits status enum values are documented.
     * Status values: pending, running, completed, failed
     */
    public function test_audits_status_enum_documented(): void
    {
        $doc = File::get($this->docPath);
        $expectedStatuses = ['pending', 'running', 'completed', 'failed'];
        
        foreach ($expectedStatuses as $status) {
            $this->assertStringContainsString($status, $doc,
                "DOCUMENTATION.md should document audits status: {$status}");
        }
    }

    /**
     * Test that audit_issues severity enum values are documented.
     * Severity values: critical, warning, notice
     */
    public function test_audit_issues_severity_enum_documented(): void
    {
        $doc = File::get($this->docPath);
        $expectedSeverities = ['critical', 'warning', 'notice'];
        
        foreach ($expectedSeverities as $severity) {
            $this->assertStringContainsString($severity, $doc,
                "DOCUMENTATION.md should document audit_issues severity: {$severity}");
        }
    }

    /**
     * Test that audit_issues status enum values are documented.
     * Status values: open, fixed, ignored
     */
    public function test_audit_issues_status_enum_documented(): void
    {
        $doc = File::get($this->docPath);
        $expectedStatuses = ['open', 'fixed', 'ignored'];
        
        foreach ($expectedStatuses as $status) {
            $this->assertStringContainsString($status, $doc,
                "DOCUMENTATION.md should document audit_issues status: {$status}");
        }
    }

    /**
     * Test that audit_issues foreign key relationships are documented.
     */
    public function test_audit_issues_foreign_keys_documented(): void
    {
        $doc = File::get($this->docPath);
        
        $this->assertStringContainsString('audit_id', $doc,
            'DOCUMENTATION.md should document audit_id foreign key in audit_issues');
        $this->assertStringContainsString('issue_type_id', $doc,
            'DOCUMENTATION.md should document issue_type_id foreign key in audit_issues');
    }

    /**
     * Test that audits table core columns are documented.
     */
    public function test_audits_columns_documented(): void
    {
        $doc = File::get($this->docPath);
        $requiredColumns = [
            'pages_crawled',
            'issues_found',
            'started_at',
            'completed_at',
        ];
        
        foreach ($requiredColumns as $column) {
            $this->assertStringContainsString($column, $doc,
                "DOCUMENTATION.md should document audits column: {$column}");
        }
    }

    /**
     * Test that issue_types columns are documented.
     */
    public function test_issue_types_columns_documented(): void
    {
        $doc = File::get($this->docPath);
        $requiredColumns = [
            'category',
            'severity_default',
            'recommendation_template',
        ];
        
        foreach ($requiredColumns as $column) {
            $this->assertStringContainsString($column, $doc,
                "DOCUMENTATION.md should document issue_types column: {$column}");
        }
    }

    /**
     * Test that migrations for audits tables exist.
     * This validates the test environment is set up correctly.
     */
    public function test_audits_migrations_exist(): void
    {
        $migrations = File::glob($this->migrationsPath . '/*audits*.php');
        $this->assertNotEmpty($migrations, 'Audits migration file should exist');
        
        $issueMigrations = File::glob($this->migrationsPath . '/*audit_issues*.php');
        $this->assertNotEmpty($issueMigrations, 'Audit issues migration file should exist');
        
        $typeMigrations = File::glob($this->migrationsPath . '/*issue_types*.php');
        $this->assertNotEmpty($typeMigrations, 'Issue types migration file should exist');
    }
}
