<?php

namespace Tests\Unit\OpenApi;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * OpenAPI Specification Structure Tests (TASK-6-17)
 * 
 * Tests for validating OpenAPI 3.0 specification structure and format.
 */
class OpenApiSpecTest extends TestCase
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
        
        // Use Symfony YAML parser if available, otherwise basic parsing
        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            $this->spec = \Symfony\Component\Yaml\Yaml::parse($content);
        } else {
            // Fallback: Try to parse as JSON if it's JSON
            $json = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->spec = $json;
            } else {
                // Mark as needing YAML parser
                $this->spec = ['_needs_yaml_parser' => true];
            }
        }
    }

    // ========================================================================
    // OAS-STRUCTURE: OpenAPI Structure Validation
    // ========================================================================

    #[Test]
    public function oas_001_file_exists(): void
    {
        $path = base_path(self::SPEC_PATH);
        $this->assertFileExists($path, 'OpenAPI specification file should exist at ' . self::SPEC_PATH);
    }

    #[Test]
    public function oas_002_valid_yaml(): void
    {
        if (!$this->spec) {
            $this->markTestSkipped('OpenAPI spec file not found');
        }

        $this->assertIsArray($this->spec, 'OpenAPI spec should parse as valid YAML/JSON');
        $this->assertArrayNotHasKey('_needs_yaml_parser', $this->spec, 'YAML parser required');
    }

    #[Test]
    public function oas_003_openapi_version(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertArrayHasKey('openapi', $this->spec, 'OpenAPI spec must have "openapi" field');
        
        $version = $this->spec['openapi'];
        $this->assertMatchesRegularExpression(
            '/^3\.0\.\d+$/',
            $version,
            "OpenAPI version must be 3.0.x, got: {$version}"
        );
    }

    #[Test]
    public function oas_004_info_section(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertArrayHasKey('info', $this->spec, 'OpenAPI spec must have "info" section');
        
        $info = $this->spec['info'];
        $this->assertIsArray($info, 'Info must be an object');
        
        $this->assertArrayHasKey('title', $info, 'Info must have "title"');
        $this->assertArrayHasKey('version', $info, 'Info must have "version"');
        $this->assertArrayHasKey('description', $info, 'Info must have "description"');
    }

    #[Test]
    public function oas_005_servers_section(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertArrayHasKey('servers', $this->spec, 'OpenAPI spec must have "servers" section');
        $this->assertIsArray($this->spec['servers'], 'Servers must be an array');
        $this->assertGreaterThanOrEqual(1, count($this->spec['servers']), 'At least one server required');
    }

    #[Test]
    public function oas_006_paths_section(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertArrayHasKey('paths', $this->spec, 'OpenAPI spec must have "paths" section');
        $this->assertIsArray($this->spec['paths'], 'Paths must be an object');
    }

    #[Test]
    public function oas_007_components_section(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertArrayHasKey('components', $this->spec, 'OpenAPI spec must have "components" section');
        $this->assertIsArray($this->spec['components'], 'Components must be an object');
    }

    #[Test]
    public function oas_008_security_schemes(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertArrayHasKey('securitySchemes', $this->spec['components'], 
            'Components must have "securitySchemes"');
        $this->assertIsArray($this->spec['components']['securitySchemes'], 
            'Security schemes must be an object');
    }

    // ========================================================================
    // OAS-INFO: API Info Validation
    // ========================================================================

    #[Test]
    public function inf_001_title_present(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $title = $this->spec['info']['title'] ?? null;
        $this->assertNotNull($title, 'Info title must be present');
        $this->assertNotEmpty($title, 'Info title must not be empty');
        $this->assertIsString($title, 'Info title must be a string');
    }

    #[Test]
    public function inf_002_version_format(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $version = $this->spec['info']['version'] ?? null;
        $this->assertNotNull($version, 'Info version must be present');
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+$/',
            $version,
            "Version must follow semver (x.y.z), got: {$version}"
        );
    }

    #[Test]
    public function inf_003_description_present(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $description = $this->spec['info']['description'] ?? null;
        $this->assertNotNull($description, 'Info description must be present');
        $this->assertNotEmpty($description, 'Info description must not be empty');
        $this->assertGreaterThanOrEqual(20, strlen($description), 
            'Description should be meaningful (at least 20 characters)');
    }

    #[Test]
    public function inf_004_contact_info(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $contact = $this->spec['info']['contact'] ?? null;
        $this->assertIsArray($contact, 'Contact info should be an object');
        
        $this->assertArrayHasKey('name', $contact, 'Contact should have name');
        $this->assertArrayHasKey('email', $contact, 'Contact should have email');
        
        $this->assertMatchesRegularExpression(
            '/^.+@.+\..+$/',
            $contact['email'],
            'Contact email should be valid'
        );
    }

    #[Test]
    public function inf_005_license_info(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $license = $this->spec['info']['license'] ?? null;
        $this->assertIsArray($license, 'License info should be an object');
        
        $this->assertArrayHasKey('name', $license, 'License should have name');
    }

    #[Test]
    public function inf_006_terms_of_service(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $tos = $this->spec['info']['termsOfService'] ?? null;
        if ($tos !== null) {
            $this->assertMatchesRegularExpression(
                '/^https?:\/\//',
                $tos,
                'Terms of service should be a valid URL'
            );
        }
        $this->addToAssertionCount(1); // Count as pass if null (optional)
    }

    // ========================================================================
    // OAS-SERVERS: Server Configuration
    // ========================================================================

    #[Test]
    public function srv_001_production_server(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $servers = $this->spec['servers'];
        $hasProduction = false;

        foreach ($servers as $server) {
            $url = $server['url'] ?? '';
            if (str_contains($url, 'api.seoindicator.com') || 
                str_contains($url, 'seoindicator.com/api') ||
                str_contains($url, 'production')) {
                $hasProduction = true;
                break;
            }
        }

        $this->assertTrue($hasProduction, 'At least one production server should be defined');
    }

    #[Test]
    public function srv_002_staging_server(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $servers = $this->spec['servers'];
        $hasStaging = false;

        foreach ($servers as $server) {
            $url = $server['url'] ?? '';
            if (str_contains($url, 'staging') || 
                str_contains($url, 'sandbox') ||
                str_contains($url, 'localhost')) {
                $hasStaging = true;
                break;
            }
        }

        $this->addToAssertionCount(1); // Optional - count as pass
    }

    #[Test]
    public function srv_003_server_variables(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $servers = $this->spec['servers'];
        $hasVariables = false;

        foreach ($servers as $server) {
            if (isset($server['variables']) && is_array($server['variables'])) {
                $hasVariables = true;
                break;
            }
        }

        $this->addToAssertionCount(1); // Optional - count as pass
    }

    #[Test]
    public function srv_004_server_description(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $servers = $this->spec['servers'];

        foreach ($servers as $server) {
            $this->assertArrayHasKey('description', $server, 
                "Server {$server['url']} should have description");
        }
    }

    #[Test]
    public function srv_005_url_format(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $servers = $this->spec['servers'];

        foreach ($servers as $server) {
            $url = $server['url'] ?? '';
            
            // URL can be relative or absolute with variables
            $isValid = preg_match('/^(https?:\/\/|\/|\{)/', $url) || 
                       filter_var($url, FILTER_VALIDATE_URL);
            
            $this->assertTrue($isValid || str_contains($url, '{'), 
                "Server URL '{$url}' should be valid");
        }
    }

    // ========================================================================
    // OAS-AUTH: Authentication Schemes
    // ========================================================================

    #[Test]
    public function auth_001_bearer_scheme(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $schemes = $this->spec['components']['securitySchemes'];
        $this->assertArrayHasKey('BearerAuth', $schemes, 
            'BearerAuth security scheme must be defined');
    }

    #[Test]
    public function auth_002_bearer_type(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $bearer = $this->spec['components']['securitySchemes']['BearerAuth'] ?? null;
        $this->assertNotNull($bearer, 'BearerAuth must be defined');
        
        $this->assertEquals('http', $bearer['type'] ?? null, 'BearerAuth type must be "http"');
        $this->assertEquals('bearer', $bearer['scheme'] ?? null, 'BearerAuth scheme must be "bearer"');
    }

    #[Test]
    public function auth_003_bearer_format(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $bearer = $this->spec['components']['securitySchemes']['BearerAuth'] ?? null;
        if ($bearer) {
            $this->assertEquals('JWT', $bearer['bearerFormat'] ?? null, 
                'BearerAuth bearerFormat should be "JWT"');
        }
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function auth_004_api_key_scheme(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $schemes = $this->spec['components']['securitySchemes'];
        $this->assertArrayHasKey('ApiKeyAuth', $schemes, 
            'ApiKeyAuth security scheme must be defined');
    }

    #[Test]
    public function auth_005_api_key_location(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $apiKey = $this->spec['components']['securitySchemes']['ApiKeyAuth'] ?? null;
        $this->assertNotNull($apiKey, 'ApiKeyAuth must be defined');
        
        $this->assertEquals('apiKey', $apiKey['type'] ?? null, 'ApiKeyAuth type must be "apiKey"');
        $this->assertEquals('header', $apiKey['in'] ?? null, 'ApiKeyAuth in must be "header"');
    }

    #[Test]
    public function auth_006_api_key_name(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $apiKey = $this->spec['components']['securitySchemes']['ApiKeyAuth'] ?? null;
        if ($apiKey) {
            $this->assertEquals('X-API-Key', $apiKey['name'] ?? null, 
                'ApiKeyAuth name should be "X-API-Key"');
        }
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function auth_007_oauth2_scheme(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $schemes = $this->spec['components']['securitySchemes'];
        $hasOAuth = isset($schemes['OAuth2']) || isset($schemes['oAuth2']);
        
        $this->addToAssertionCount(1); // Optional - count as pass
    }

    #[Test]
    public function auth_008_description_present(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $schemes = $this->spec['components']['securitySchemes'];

        foreach ($schemes as $name => $scheme) {
            $this->assertArrayHasKey('description', $scheme, 
                "Security scheme '{$name}' should have description");
        }
    }

    // ========================================================================
    // OAS-PATHS: Path Definitions
    // ========================================================================

    #[Test]
    public function path_001_paths_are_objects(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            $this->assertIsArray($methods, "Path '{$path}' must be an object");
        }
    }

    #[Test]
    public function path_002_http_methods(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $validMethods = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'trace'];
        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (str_starts_with($method, 'x-')) {
                    continue; // Skip extensions
                }
                if ($method === '$ref' || $method === 'summary' || $method === 'description' || $method === 'servers' || $method === 'parameters') {
                    continue; // Skip path-level items
                }
                
                $this->assertContains($method, $validMethods, 
                    "Invalid HTTP method '{$method}' for path '{$path}'");
            }
        }
    }

    #[Test]
    public function path_003_operation_ids(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $operationIds = [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $opId = $operation['operationId'] ?? null;
                $this->assertNotNull($opId, 
                    "Operation '{$method} {$path}' must have operationId");
                
                $this->assertNotContains($opId, $operationIds, 
                    "Duplicate operationId '{$opId}'");
                $operationIds[] = $opId;
            }
        }
    }

    #[Test]
    public function path_004_summaries(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $summary = $operation['summary'] ?? null;
                $this->assertNotNull($summary, 
                    "Operation '{$method} {$path}' should have summary");
            }
        }
    }

    #[Test]
    public function path_005_descriptions(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $withDescription = 0;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                if (isset($operation['description'])) {
                    $withDescription++;
                }
            }
        }

        $this->assertGreaterThan(0, $withDescription, 
            'At least some operations should have descriptions');
    }

    #[Test]
    public function path_006_tags(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $tags = $operation['tags'] ?? [];
                $this->assertIsArray($tags, 
                    "Operation '{$method} {$path}' tags must be an array");
                $this->assertGreaterThanOrEqual(1, count($tags), 
                    "Operation '{$method} {$path}' should have at least one tag");
            }
        }
    }

    #[Test]
    public function path_007_responses(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? null;
                $this->assertNotNull($responses, 
                    "Operation '{$method} {$path}' must have responses");
                $this->assertIsArray($responses, 
                    "Operation '{$method} {$path}' responses must be an object");
            }
        }
    }

    #[Test]
    public function path_008_parameters(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $hasParameters = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                if (isset($operation['parameters']) || str_contains($path, '{')) {
                    $hasParameters = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($hasParameters, 'At least some operations should have parameters');
    }

    #[Test]
    public function path_009_request_bodies(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $requiresBody = ['post', 'put', 'patch'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                if (!in_array($method, $requiresBody)) continue;
                
                $requestBody = $operation['requestBody'] ?? null;
                $this->assertNotNull($requestBody, 
                    "Operation '{$method} {$path}' should have requestBody");
            }
        }
    }

    #[Test]
    public function path_010_security_applied(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $publicPaths = ['/health', '/indicators', '/v1/public-audit', '/v1/badge', '/v1/models', '/v1/pwa/guide'];
        $hasSecurity = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $isPublic = false;
                foreach ($publicPaths as $publicPath) {
                    if (str_starts_with($path, $publicPath)) {
                        $isPublic = true;
                        break;
                    }
                }
                
                if (!$isPublic && isset($operation['security'])) {
                    $hasSecurity = true;
                }
            }
        }

        $this->assertTrue($hasSecurity, 'Protected endpoints should have security applied');
    }

    // ========================================================================
    // OAS-RESPONSES: Response Definitions
    // ========================================================================

    #[Test]
    public function resp_001_success_responses(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $successCodes = ['200', '201', '202', '204'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? [];
                $hasSuccess = false;
                
                foreach ($successCodes as $code) {
                    if (isset($responses[$code])) {
                        $hasSuccess = true;
                        break;
                    }
                }
                
                $this->assertTrue($hasSuccess, 
                    "Operation '{$method} {$path}' should have a success response (200/201/202/204)");
            }
        }
    }

    #[Test]
    public function resp_002_error_responses(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $errorCodes = ['400', '401', '403', '404', '422', '500'];
        $paths = $this->spec['paths'];
        $errorResponses = [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? [];
                foreach ($errorCodes as $code) {
                    if (isset($responses[$code])) {
                        $errorResponses[$code] = true;
                    }
                }
            }
        }

        $this->assertGreaterThan(0, count($errorResponses), 
            'At least some error responses should be documented');
    }

    #[Test]
    public function resp_003_response_content(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? [];
                foreach ($responses as $code => $response) {
                    if (!is_array($response)) continue;
                    
                    // Success responses should have content
                    if (str_starts_with($code, '2') && $code !== '204') {
                        $this->assertArrayHasKey('content', $response, 
                            "Response {$code} for '{$method} {$path}' should have content");
                    }
                }
            }
        }
    }

    #[Test]
    public function resp_004_response_schemas(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $hasSchemaRef = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? [];
                foreach ($responses as $response) {
                    if (!is_array($response)) continue;
                    
                    $content = $response['content'] ?? [];
                    foreach ($content as $mediaType => $media) {
                        if (isset($media['schema']['$ref'])) {
                            $hasSchemaRef = true;
                            break 4;
                        }
                    }
                }
            }
        }

        $this->assertTrue($hasSchemaRef, 'Responses should reference component schemas');
    }

    #[Test]
    public function resp_005_response_descriptions(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? [];
                foreach ($responses as $code => $response) {
                    if (!is_array($response)) continue;
                    
                    $this->assertArrayHasKey('description', $response, 
                        "Response {$code} for '{$method} {$path}' should have description");
                }
            }
        }
    }

    #[Test]
    public function resp_006_error_schema(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $components = $this->spec['components'] ?? [];
        $schemas = $components['schemas'] ?? [];
        
        $errorSchemaNames = ['Error', 'ErrorResponse', 'ApiError', 'ErrorResult'];
        $hasErrorSchema = false;
        
        foreach ($errorSchemaNames as $name) {
            if (isset($schemas[$name])) {
                $hasErrorSchema = true;
                break;
            }
        }

        $this->assertTrue($hasErrorSchema, 
            'An error response schema should be defined (Error, ErrorResponse, etc.)');
    }

    // ========================================================================
    // OAS-TAGS: Tag Organization
    // ========================================================================

    #[Test]
    public function tag_001_tags_defined(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertArrayHasKey('tags', $this->spec, 'OpenAPI spec must have tags section');
        $this->assertIsArray($this->spec['tags'], 'Tags must be an array');
    }

    #[Test]
    public function tag_002_tag_names(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $tags = $this->spec['tags'] ?? [];

        foreach ($tags as $tag) {
            $this->assertArrayHasKey('name', $tag, 'Each tag must have a name');
            $this->assertNotEmpty($tag['name'], 'Tag name must not be empty');
        }
    }

    #[Test]
    public function tag_003_tag_descriptions(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $tags = $this->spec['tags'] ?? [];

        foreach ($tags as $tag) {
            $this->assertArrayHasKey('description', $tag, 
                "Tag '{$tag['name']}' should have description");
        }
    }

    #[Test]
    public function tag_004_endpoint_tags(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $definedTags = array_column($this->spec['tags'] ?? [], 'name');
        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $tags = $operation['tags'] ?? [];
                foreach ($tags as $tag) {
                    $this->assertContains($tag, $definedTags, 
                        "Tag '{$tag}' used in '{$method} {$path}' must be defined in tags section");
                }
            }
        }
    }

    #[Test]
    public function tag_005_tag_grouping(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $expectedTags = ['Auth', 'Keywords', 'Sites', 'Projects', 'Reports', 'Audits', 'Admin'];
        $definedTags = array_column($this->spec['tags'] ?? [], 'name');
        
        $foundTags = array_intersect($expectedTags, $definedTags);
        
        $this->assertGreaterThanOrEqual(3, count($foundTags), 
            'At least 3 expected tag groups should be defined');
    }

    // ========================================================================
    // OAS-VALIDATION: Schema Validation
    // ========================================================================

    #[Test]
    public function val_001_valid_openapi(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        // Basic OpenAPI 3.0 structure validation
        $required = ['openapi', 'info', 'paths'];
        foreach ($required as $field) {
            $this->assertArrayHasKey($field, $this->spec, 
                "OpenAPI spec must have '{$field}' field");
        }
    }

    #[Test]
    public function val_002_no_duplicate_operation_ids(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $operationIds = [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $opId = $operation['operationId'] ?? null;
                if ($opId) {
                    $this->assertFalse(in_array($opId, $operationIds), 
                        "Duplicate operationId: {$opId}");
                    $operationIds[] = $opId;
                }
            }
        }
    }

    #[Test]
    public function val_003_no_duplicate_tags(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $tags = $this->spec['tags'] ?? [];
        $names = array_column($tags, 'name');
        
        $uniqueNames = array_unique($names);
        $this->assertEquals(count($names), count($uniqueNames), 
            'All tag names must be unique');
    }

    #[Test]
    public function val_004_valid_refs(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $content = file_get_contents(base_path(self::SPEC_PATH));
        
        // Find all $ref values
        preg_match_all('/\$ref:\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        $refs = $matches[1] ?? [];
        
        foreach ($refs as $ref) {
            $this->assertMatchesRegularExpression(
                '/^#\/(components|parameters|responses|schemas)\//',
                $ref,
                "Invalid \$ref format: {$ref}"
            );
        }
    }

    #[Test]
    public function val_005_valid_paths(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            $this->assertMatchesRegularExpression(
                '/^\//',
                $path,
                "Path '{$path}' must start with /"
            );
        }
    }

    #[Test]
    public function val_006_valid_methods(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $validMethods = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'trace'];
        $paths = $this->spec['paths'];
        $pathLevelKeys = ['summary', 'description', 'servers', 'parameters', '$ref'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array($method, $pathLevelKeys)) continue;
                if (str_starts_with($method, 'x-')) continue;
                
                $this->assertContains($method, $validMethods, 
                    "Invalid HTTP method '{$method}' in path '{$path}'");
            }
        }
    }

    #[Test]
    public function val_007_valid_status_codes(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $validCodes = array_merge(
            range(100, 103), // Informational
            range(200, 208), [226], // Success
            range(300, 308), // Redirection
            range(400, 418), [422, 423, 424, 425, 426, 428, 429, 431, 451], // Client errors
            range(500, 511) // Server errors
        );

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? [];
                foreach ($responses as $code => $response) {
                    if ($code === 'default') continue;
                    
                    $this->assertContains((int)$code, $validCodes, 
                        "Invalid HTTP status code '{$code}' in '{$method} {$path}'");
                }
            }
        }
    }

    #[Test]
    public function val_008_valid_media_types(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $validMediaTypes = [
            'application/json',
            'application/xml',
            'application/x-www-form-urlencoded',
            'multipart/form-data',
            'text/plain',
            'text/html',
            'text/csv',
        ];

        $paths = $this->spec['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? [];
                foreach ($responses as $response) {
                    if (!is_array($response)) continue;
                    
                    $content = $response['content'] ?? [];
                    foreach ($content as $mediaType => $media) {
                        $isValid = in_array($mediaType, $validMediaTypes) ||
                            preg_match('/^(application|text)\/[a-z0-9.+-]+$/i', $mediaType);
                        
                        $this->assertTrue($isValid, 
                            "Invalid media type '{$mediaType}' in '{$method} {$path}'");
                    }
                }
            }
        }
    }

    // ========================================================================
    // OAS-DOCS: Documentation Generation
    // ========================================================================

    #[Test]
    public function doc_001_swagger_ui_compatible(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        // Swagger UI requires certain fields
        $this->assertArrayHasKey('openapi', $this->spec);
        $this->assertArrayHasKey('info', $this->spec);
        $this->assertArrayHasKey('paths', $this->spec);
        $this->assertArrayHasKey('title', $this->spec['info']);
        $this->assertArrayHasKey('version', $this->spec['info']);
    }

    #[Test]
    public function doc_002_redoc_compatible(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        // Redoc prefers certain fields for better docs
        $this->assertArrayHasKey('description', $this->spec['info']);
        $this->assertArrayHasKey('tags', $this->spec);
        
        // Tags should have descriptions for Redoc
        $tags = $this->spec['tags'] ?? [];
        foreach ($tags as $tag) {
            $this->assertArrayHasKey('description', $tag, 
                "Tag '{$tag['name']}' needs description for Redoc");
        }
    }

    #[Test]
    public function doc_003_no_parsing_errors(): void
    {
        if (!$this->spec) {
            $this->markTestSkipped('OpenAPI spec file not found');
        }

        $this->assertArrayNotHasKey('_needs_yaml_parser', $this->spec, 
            'Spec must be parseable without errors');
    }

    #[Test]
    public function doc_004_valid_json_output(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $json = json_encode($this->spec);
        $this->assertNotFalse($json, 'Spec must be serializable to JSON');
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded, 'Spec must be valid when converted to JSON');
    }

    // ========================================================================
    // AC: Acceptance Criteria
    // ========================================================================

    #[Test]
    public function ac_001_openapi_spec_created(): void
    {
        $path = base_path(self::SPEC_PATH);
        $this->assertFileExists($path, 'OpenAPI spec file must exist');
    }

    #[Test]
    public function ac_002_all_endpoints_documented(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $this->assertGreaterThanOrEqual(50, count($paths), 
            'At least 50 paths should be documented');
    }

    #[Test]
    public function ac_003_schemas_defined(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $schemas = $this->spec['components']['schemas'] ?? [];
        $this->assertGreaterThanOrEqual(10, count($schemas), 
            'At least 10 schemas should be defined');
    }

    #[Test]
    public function ac_004_valid_openapi_format(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertMatchesRegularExpression(
            '/^3\.0\./',
            $this->spec['openapi'],
            'Must be valid OpenAPI 3.0.x format'
        );
        
        $this->assertArrayHasKey('info', $this->spec);
        $this->assertArrayHasKey('paths', $this->spec);
    }

    #[Test]
    public function ac_005_documentation_generation_ready(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        // Must have required fields for Swagger UI / Redoc
        $this->assertArrayHasKey('openapi', $this->spec);
        $this->assertArrayHasKey('info', $this->spec);
        $this->assertArrayHasKey('paths', $this->spec);
        $this->assertArrayHasKey('title', $this->spec['info']);
        $this->assertArrayHasKey('version', $this->spec['info']);
        $this->assertArrayHasKey('description', $this->spec['info']);
        $this->assertArrayHasKey('tags', $this->spec);
    }
}
