<?php

namespace Tests\Unit\OpenApi;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * OpenAPI Schema Definitions Tests (TASK-6-17)
 * 
 * Tests for validating component schema definitions in OpenAPI spec.
 */
class OpenApiSchemaTest extends TestCase
{
    /**
     * Path to OpenAPI specification file (relative to base_path())
     */
    private const SPEC_PATH = 'src/api/openapi.yaml';

    /**
     * Parsed OpenAPI specification
     */
    private ?array $spec = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadSpec();
    }

    /**
     * Load and parse the OpenAPI specification
     */
    private function loadSpec(): void
    {
        $path = base_path(self::SPEC_PATH);
        
        if (!file_exists($path)) {
            $this->spec = null;
            return;
        }

        $content = file_get_contents($path);
        
        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            $this->spec = \Symfony\Component\Yaml\Yaml::parse($content);
        } else {
            $this->spec = ['_needs_yaml_parser' => true];
        }
    }

    /**
     * Get all defined schemas
     */
    private function getSchemas(): array
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            return [];
        }
        
        return $this->spec['components']['schemas'] ?? [];
    }

    // ========================================================================
    // OAS-SCHEMAS: Component Schemas
    // ========================================================================

    #[Test]
    public function sch_001_user_schema(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $userSchemaNames = ['User', 'UserResource', 'UserResponse'];
        $hasUserSchema = false;
        
        foreach ($userSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $hasUserSchema = true;
                break;
            }
        }

        $this->assertTrue($hasUserSchema, 'User schema must be defined');
    }

    #[Test]
    public function sch_002_site_schema(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $siteSchemaNames = ['Site', 'SiteResource', 'SiteResponse', 'Project'];
        $hasSiteSchema = false;
        
        foreach ($siteSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $hasSiteSchema = true;
                break;
            }
        }

        $this->assertTrue($hasSiteSchema, 'Site/Project schema must be defined');
    }

    #[Test]
    public function sch_003_keyword_schema(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $keywordSchemaNames = ['Keyword', 'KeywordResource', 'KeywordResponse'];
        $hasKeywordSchema = false;
        
        foreach ($keywordSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $hasKeywordSchema = true;
                break;
            }
        }

        $this->assertTrue($hasKeywordSchema, 'Keyword schema must be defined');
    }

    #[Test]
    public function sch_004_project_schema(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $projectSchemaNames = ['Project', 'ProjectResource', 'AIVisibilityProject'];
        $hasProjectSchema = false;
        
        foreach ($projectSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $hasProjectSchema = true;
                break;
            }
        }

        $this->assertTrue($hasProjectSchema, 'Project schema must be defined');
    }

    #[Test]
    public function sch_005_report_schema(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $reportSchemaNames = ['Report', 'ReportResource', 'ReportResponse'];
        $hasReportSchema = false;
        
        foreach ($reportSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $hasReportSchema = true;
                break;
            }
        }

        $this->assertTrue($hasReportSchema, 'Report schema must be defined');
    }

    #[Test]
    public function sch_006_error_schema(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $errorSchemaNames = ['Error', 'ErrorResponse', 'ApiError', 'ErrorResult', 'ProblemDetails'];
        $hasErrorSchema = false;
        
        foreach ($errorSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $hasErrorSchema = true;
                break;
            }
        }

        $this->assertTrue($hasErrorSchema, 'Error schema must be defined');
    }

    #[Test]
    public function sch_007_pagination_schema(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paginationSchemaNames = ['PaginationMeta', 'Pagination', 'PaginatedResponse', 'Meta'];
        $hasPaginationSchema = false;
        
        foreach ($paginationSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $hasPaginationSchema = true;
                break;
            }
        }

        $this->assertTrue($hasPaginationSchema, 'Pagination schema must be defined');
    }

    #[Test]
    public function sch_008_schema_properties(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        foreach ($schemas as $name => $schema) {
            if (!is_array($schema)) continue;
            
            // Schemas should have properties (for objects) or type (for primitives)
            $hasProperties = isset($schema['properties']) && is_array($schema['properties']);
            $hasType = isset($schema['type']);
            $hasAllOf = isset($schema['allOf']);
            $hasOneOf = isset($schema['oneOf']);
            $hasAnyOf = isset($schema['anyOf']);
            $hasRef = isset($schema['$ref']);
            
            $isValid = $hasProperties || $hasType || $hasAllOf || $hasOneOf || $hasAnyOf || $hasRef;
            
            $this->assertTrue($isValid, 
                "Schema '{$name}' must have properties, type, allOf, oneOf, anyOf, or \$ref");
        }
    }

    #[Test]
    public function sch_009_required_fields(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $schemasWithRequired = 0;
        
        foreach ($schemas as $name => $schema) {
            if (!is_array($schema)) continue;
            
            if (isset($schema['required']) && is_array($schema['required'])) {
                $schemasWithRequired++;
            }
        }

        $this->assertGreaterThan(0, $schemasWithRequired, 
            'At least some schemas should have required fields defined');
    }

    #[Test]
    public function sch_010_type_definitions(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $validTypes = ['string', 'number', 'integer', 'boolean', 'array', 'object'];
        
        foreach ($schemas as $schemaName => $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            foreach ($schema['properties'] as $propName => $property) {
                if (!is_array($property)) continue;
                
                $type = $property['type'] ?? null;
                $hasRef = isset($property['$ref']);
                $hasAllOf = isset($property['allOf']);
                $hasOneOf = isset($property['oneOf']);
                
                if ($type !== null) {
                    if (is_array($type)) {
                        // Type can be an array
                        foreach ($type as $t) {
                            $this->assertContains($t, $validTypes, 
                                "Property '{$propName}' in '{$schemaName}' has invalid type: {$t}");
                        }
                    } else {
                        $this->assertContains($type, $validTypes, 
                            "Property '{$propName}' in '{$schemaName}' has invalid type: {$type}");
                    }
                }
            }
        }
    }

    #[Test]
    public function sch_011_nullable_fields(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $nullableCount = 0;
        
        foreach ($schemas as $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            foreach ($schema['properties'] as $property) {
                if (!is_array($property)) continue;
                
                if (isset($property['nullable']) || 
                    (isset($property['type']) && is_array($property['type']) && in_array('null', $property['type']))) {
                    $nullableCount++;
                }
            }
        }

        $this->assertGreaterThan(0, $nullableCount, 
            'At least some fields should be marked as nullable');
    }

    #[Test]
    public function sch_012_example_values(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $schemasWithExamples = 0;
        
        foreach ($schemas as $name => $schema) {
            if (!is_array($schema)) continue;
            
            // Check schema-level example
            if (isset($schema['example'])) {
                $schemasWithExamples++;
                continue;
            }
            
            // Check property-level examples
            if (isset($schema['properties']) && is_array($schema['properties'])) {
                foreach ($schema['properties'] as $property) {
                    if (is_array($property) && isset($property['example'])) {
                        $schemasWithExamples++;
                        break;
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $schemasWithExamples, 
            'At least some schemas should have example values');
    }

    // ========================================================================
    // Schema Property Validation
    // ========================================================================

    #[Test]
    public function string_properties_have_format_or_pattern(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $stringWithFormat = 0;
        $totalStrings = 0;
        
        foreach ($schemas as $schemaName => $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            foreach ($schema['properties'] as $propName => $property) {
                if (!is_array($property)) continue;
                
                $type = $property['type'] ?? null;
                if ($type === 'string') {
                    $totalStrings++;
                    if (isset($property['format']) || isset($property['pattern']) || isset($property['enum'])) {
                        $stringWithFormat++;
                    }
                }
            }
        }

        // At least 30% of string fields should have format/pattern/enum
        if ($totalStrings > 0) {
            $ratio = $stringWithFormat / $totalStrings;
            $this->assertGreaterThanOrEqual(0.3, $ratio, 
                'At least 30% of string properties should have format, pattern, or enum');
        }
    }

    #[Test]
    public function numeric_properties_have_constraints(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        foreach ($schemas as $schemaName => $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            foreach ($schema['properties'] as $propName => $property) {
                if (!is_array($property)) continue;
                
                $type = $property['type'] ?? null;
                if ($type === 'integer' || $type === 'number') {
                    // ID fields should be positive
                    if (str_ends_with($propName, '_id') || $propName === 'id') {
                        // Should have minimum: 1 for IDs
                        $this->assertArrayHasKey('minimum', $property, 
                            "ID property '{$propName}' in '{$schemaName}' should have minimum constraint");
                    }
                }
            }
        }
    }

    #[Test]
    public function array_properties_have_items(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        foreach ($schemas as $schemaName => $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            foreach ($schema['properties'] as $propName => $property) {
                if (!is_array($property)) continue;
                
                $type = $property['type'] ?? null;
                if ($type === 'array') {
                    $this->assertArrayHasKey('items', $property, 
                        "Array property '{$propName}' in '{$schemaName}' must have items defined");
                }
            }
        }
    }

    #[Test]
    public function datetime_properties_have_format(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $datetimeFields = ['created_at', 'updated_at', 'deleted_at', 'published_at', 'expires_at'];
        
        foreach ($schemas as $schemaName => $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            foreach ($datetimeFields as $field) {
                if (!isset($schema['properties'][$field])) continue;
                
                $property = $schema['properties'][$field];
                if (!is_array($property)) continue;
                
                $this->assertEquals('string', $property['type'] ?? null, 
                    "Field '{$field}' in '{$schemaName}' should be type string");
                $this->assertEquals('date-time', $property['format'] ?? null, 
                    "Field '{$field}' in '{$schemaName}' should have format date-time");
            }
        }
    }

    // ========================================================================
    // Schema Reference Validation
    // ========================================================================

    #[Test]
    public function all_refs_resolve(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $content = file_get_contents(base_path(self::SPEC_PATH));
        preg_match_all('/\$ref:\s*[\'"]#\/components\/schemas\/([^\'"]+)[\'"]/', $content, $matches);
        $referencedSchemas = $matches[1] ?? [];
        
        foreach ($referencedSchemas as $ref) {
            $this->assertArrayHasKey($ref, $schemas, 
                "Schema reference '#/components/schemas/{$ref}' must resolve to existing schema");
        }
    }

    #[Test]
    public function no_circular_refs(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        // Check for obvious circular refs (A -> B -> A)
        foreach ($schemas as $schemaName => $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            $refs = [];
            foreach ($schema['properties'] as $property) {
                if (!is_array($property)) continue;
                
                if (isset($property['$ref'])) {
                    preg_match('/#\/components\/schemas\/(.+)$/', $property['$ref'], $match);
                    if (isset($match[1])) {
                        $refs[] = $match[1];
                    }
                }
            }
            
            // Check if any referenced schema references back to this schema
            foreach ($refs as $ref) {
                if (!isset($schemas[$ref])) continue;
                
                $refSchema = $schemas[$ref];
                if (!is_array($refSchema) || !isset($refSchema['properties'])) continue;
                
                foreach ($refSchema['properties'] as $prop) {
                    if (!is_array($prop)) continue;
                    
                    if (isset($prop['$ref'])) {
                        preg_match('/#\/components\/schemas\/(.+)$/', $prop['$ref'], $match);
                        if (isset($match[1]) && $match[1] === $schemaName) {
                            $this->fail("Circular reference detected: {$schemaName} -> {$ref} -> {$schemaName}");
                        }
                    }
                }
            }
        }
        
        $this->addToAssertionCount(1); // Pass if no circular refs found
    }

    // ========================================================================
    // Common Schema Patterns
    // ========================================================================

    #[Test]
    public function error_schema_has_required_fields(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $errorSchemaNames = ['Error', 'ErrorResponse', 'ApiError'];
        $errorSchema = null;
        
        foreach ($errorSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $errorSchema = $schemas[$name];
                break;
            }
        }

        if ($errorSchema === null) {
            $this->markTestSkipped('Error schema not found');
        }

        $required = $errorSchema['required'] ?? [];
        $properties = $errorSchema['properties'] ?? [];

        // Error should have message
        $this->assertTrue(
            in_array('message', $required) || isset($properties['message']),
            'Error schema should have message field'
        );
    }

    #[Test]
    public function pagination_schema_has_required_fields(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paginationSchemaNames = ['PaginationMeta', 'Pagination', 'Meta'];
        $paginationSchema = null;
        
        foreach ($paginationSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $paginationSchema = $schemas[$name];
                break;
            }
        }

        if ($paginationSchema === null) {
            $this->markTestSkipped('Pagination schema not found');
        }

        $properties = $paginationSchema['properties'] ?? [];
        
        // Should have some pagination fields
        $expectedFields = ['total', 'per_page', 'current_page', 'page', 'limit', 'count'];
        $foundFields = array_intersect($expectedFields, array_keys($properties));
        
        $this->assertGreaterThanOrEqual(2, count($foundFields), 
            'Pagination schema should have at least 2 pagination fields');
    }

    #[Test]
    public function id_fields_are_integers(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        foreach ($schemas as $schemaName => $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            foreach ($schema['properties'] as $propName => $property) {
                if (!is_array($property)) continue;
                
                // ID fields should be integer
                if ($propName === 'id' || str_ends_with($propName, '_id')) {
                    $type = $property['type'] ?? null;
                    $this->assertEquals('integer', $type, 
                        "ID field '{$propName}' in '{$schemaName}' should be type integer");
                }
            }
        }
    }

    // ========================================================================
    // Additional Schema Tests
    // ========================================================================

    #[Test]
    public function enum_values_are_defined_correctly(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        foreach ($schemas as $schemaName => $schema) {
            if (!is_array($schema) || !isset($schema['properties'])) continue;
            
            foreach ($schema['properties'] as $propName => $property) {
                if (!is_array($property)) continue;
                
                if (isset($property['enum'])) {
                    $this->assertIsArray($property['enum'], 
                        "Enum for '{$propName}' in '{$schemaName}' must be an array");
                    $this->assertGreaterThan(0, count($property['enum']), 
                        "Enum for '{$propName}' in '{$schemaName}' must have values");
                }
            }
        }
    }

    #[Test]
    public function descriptions_are_meaningful(): void
    {
        $schemas = $this->getSchemas();
        if (empty($schemas)) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $describedSchemas = 0;
        
        foreach ($schemas as $name => $schema) {
            if (!is_array($schema)) continue;
            
            if (isset($schema['description']) && strlen($schema['description']) >= 10) {
                $describedSchemas++;
            }
        }

        $total = count($schemas);
        if ($total > 0) {
            $ratio = $describedSchemas / $total;
            $this->assertGreaterThanOrEqual(0.5, $ratio, 
                'At least 50% of schemas should have meaningful descriptions');
        }
    }
}
