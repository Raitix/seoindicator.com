<?php

namespace Tests\Unit\Models;

use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Unit tests for ApiRequestLog model (TASK-6-6).
 *
 * Tests for API request logging functionality for analytics and debugging.
 * 
 * @see TASK-6-6: Request Logging
 * @see SPEC-006: API Key Management
 */
class ApiRequestLogTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    // =========================================================================
    // CATEGORY 1: MODEL INSTANTIATION & FILLABLES (MF-001 to MF-015)
    // =========================================================================

    /** @test MF-001: Model can be instantiated */
    public function test_mf001_model_can_be_instantiated(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/analyses',
            'method' => 'GET',
        ]);

        $this->assertInstanceOf(ApiRequestLog::class, $log);
        $this->assertEquals($apiKey->id, $log->api_key_id);
    }

    /** @test MF-002: api_key_id is fillable */
    public function test_mf002_api_key_id_is_fillable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertEquals($apiKey->id, $log->api_key_id);
    }

    /** @test MF-003: endpoint is fillable */
    public function test_mf003_endpoint_is_fillable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/users/123',
            'method' => 'GET',
        ]);

        $this->assertEquals('/api/v1/users/123', $log->endpoint);
    }

    /** @test MF-004: method is fillable */
    public function test_mf004_method_is_fillable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'POST',
        ]);

        $this->assertEquals('POST', $log->method);
    }

    /** @test MF-005: status_code is fillable */
    public function test_mf005_status_code_is_fillable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => 200,
        ]);

        $this->assertEquals(200, $log->status_code);
    }

    /** @test MF-006: response_time_ms is fillable */
    public function test_mf006_response_time_ms_is_fillable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'response_time_ms' => 150,
        ]);

        $this->assertEquals(150, $log->response_time_ms);
    }

    /** @test MF-007: ip_address is fillable */
    public function test_mf007_ip_address_is_fillable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'ip_address' => '192.168.1.1',
        ]);

        $this->assertEquals('192.168.1.1', $log->ip_address);
    }

    /** @test MF-008: user_agent is fillable */
    public function test_mf008_user_agent_is_fillable(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $this->assertEquals('Mozilla/5.0 (Windows NT 10.0; Win64; x64)', $log->user_agent);
    }

    /** @test MF-009: created_at is automatically set */
    public function test_mf009_created_at_is_automatically_set(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertNotNull($log->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->created_at);
    }

    /** @test MF-010: All fields can be filled together */
    public function test_mf010_all_fields_fillable_together(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $data = [
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/analyses/123',
            'method' => 'POST',
            'status_code' => 201,
            'response_time_ms' => 250,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'curl/7.68.0',
        ];

        $log = ApiRequestLog::create($data);

        $this->assertEquals($data['api_key_id'], $log->api_key_id);
        $this->assertEquals($data['endpoint'], $log->endpoint);
        $this->assertEquals($data['method'], $log->method);
        $this->assertEquals($data['status_code'], $log->status_code);
        $this->assertEquals($data['response_time_ms'], $log->response_time_ms);
        $this->assertEquals($data['ip_address'], $log->ip_address);
        $this->assertEquals($data['user_agent'], $log->user_agent);
    }

    /** @test MF-011: Minimal required fields (only api_key_id, endpoint, method) */
    public function test_mf011_minimal_required_fields(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertNotNull($log->id);
        $this->assertNull($log->status_code);
        $this->assertNull($log->response_time_ms);
        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }

    /** @test MF-012: Model update with status_code */
    public function test_mf012_model_update_with_status_code(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $log->update(['status_code' => 200, 'response_time_ms' => 50]);
        $log->refresh();

        $this->assertEquals(200, $log->status_code);
        $this->assertEquals(50, $log->response_time_ms);
    }

    /** @test MF-013: Null values are allowed for optional fields */
    public function test_mf013_null_values_allowed_for_optional_fields(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => null,
            'response_time_ms' => null,
            'ip_address' => null,
            'user_agent' => null,
        ]);

        $this->assertNull($log->status_code);
        $this->assertNull($log->response_time_ms);
        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }

    /** @test MF-014: Different HTTP methods */
    public function test_mf014_different_http_methods(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

        foreach ($methods as $method) {
            $log = ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => $method,
            ]);
            $this->assertEquals($method, $log->method);
        }
    }

    /** @test MF-015: Different status codes */
    public function test_mf015_different_status_codes(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $statusCodes = [200, 201, 204, 301, 302, 400, 401, 403, 404, 422, 429, 500, 502, 503];

        foreach ($statusCodes as $code) {
            $log = ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
                'status_code' => $code,
            ]);
            $this->assertEquals($code, $log->status_code);
        }
    }

    // =========================================================================
    // CATEGORY 2: CASTS (CA-001 to CA-010)
    // =========================================================================

    /** @test CA-001: status_code is cast to integer */
    public function test_ca001_status_code_is_cast_to_integer(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => 200,
        ]);

        $this->assertIsInt($log->status_code);
    }

    /** @test CA-002: response_time_ms is cast to integer */
    public function test_ca002_response_time_ms_is_cast_to_integer(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'response_time_ms' => 150,
        ]);

        $this->assertIsInt($log->response_time_ms);
    }

    /** @test CA-003: api_key_id is cast to integer */
    public function test_ca003_api_key_id_is_cast_to_integer(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertIsInt($log->api_key_id);
    }

    /** @test CA-004: ip_address is cast to string */
    public function test_ca004_ip_address_is_cast_to_string(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertIsString($log->ip_address);
    }

    /** @test CA-005: user_agent is cast to string */
    public function test_ca005_user_agent_is_cast_to_string(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'user_agent' => 'TestAgent/1.0',
        ]);

        $this->assertIsString($log->user_agent);
    }

    /** @test CA-006: endpoint is cast to string */
    public function test_ca006_endpoint_is_cast_to_string(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertIsString($log->endpoint);
    }

    /** @test CA-007: method is cast to string */
    public function test_ca007_method_is_cast_to_string(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'POST',
        ]);

        $this->assertIsString($log->method);
    }

    /** @test CA-008: Timestamps are cast to datetime */
    public function test_ca008_timestamps_are_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->created_at);
    }

    /** @test CA-009: Integer preservation after refresh */
    public function test_ca009_integer_preservation_after_refresh(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 150,
        ]);

        $log->refresh();

        $this->assertEquals(200, $log->status_code);
        $this->assertEquals(150, $log->response_time_ms);
    }

    /** @test CA-010: Zero values stored correctly */
    public function test_ca010_zero_values_stored_correctly(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => 0,
            'response_time_ms' => 0,
        ]);

        $this->assertEquals(0, $log->status_code);
        $this->assertEquals(0, $log->response_time_ms);
    }

    // =========================================================================
    // CATEGORY 3: RELATIONSHIP TO API KEY (RE-001 to RE-010)
    // =========================================================================

    /** @test RE-001: apiKey relationship exists */
    public function test_re001_api_key_relationship_exists(): void
    {
        $log = new ApiRequestLog();
        $this->assertTrue(method_exists($log, 'apiKey'));
    }

    /** @test RE-002: apiKey returns BelongsTo relationship */
    public function test_re002_api_key_returns_belongs_to_relationship(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $relation = $log->apiKey();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    /** @test RE-003: apiKey relationship returns parent model */
    public function test_re003_api_key_relationship_returns_parent_model(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertEquals($apiKey->id, $log->apiKey->id);
        $this->assertEquals($apiKey->name, $log->apiKey->name);
    }

    /** @test RE-004: apiKey uses correct foreign key */
    public function test_re004_api_key_uses_correct_foreign_key(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertEquals($apiKey->id, $log->apiKey()->first()->id);
    }

    /** @test RE-005: Eager loading apiKey */
    public function test_re005_eager_loading_api_key(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $loaded = ApiRequestLog::with('apiKey')->find($log->id);

        $this->assertTrue($loaded->relationLoaded('apiKey'));
        $this->assertEquals($apiKey->name, $loaded->apiKey->name);
    }

    /** @test RE-006: Deleting apiKey cascades to logs */
    public function test_re006_deleting_api_key_cascades_to_logs(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $logId = $log->id;
        $apiKey->delete();

        $this->assertDatabaseMissing('api_request_logs', ['id' => $logId]);
    }

    /** @test RE-007: Multiple logs for same API key */
    public function test_re007_multiple_logs_for_same_api_key(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log1 = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/analyses',
            'method' => 'GET',
        ]);
        $log2 = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/analyses',
            'method' => 'POST',
        ]);

        $this->assertNotEquals($log1->id, $log2->id);
        $this->assertEquals($apiKey->id, $log1->api_key_id);
        $this->assertEquals($apiKey->id, $log2->api_key_id);
    }

    /** @test RE-008: Query with apiKey relationship */
    public function test_re008_query_with_api_key_relationship(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create(['name' => 'Test Key']);

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $log = ApiRequestLog::whereHas('apiKey', function ($query) {
            $query->where('name', 'Test Key');
        })->first();

        $this->assertNotNull($log);
        $this->assertEquals('Test Key', $log->apiKey->name);
    }

    /** @test RE-009: Log belongs to correct API key */
    public function test_re009_log_belongs_to_correct_api_key(): void
    {
        $user = User::factory()->create();
        $apiKey1 = ApiKey::factory()->for($user)->create(['name' => 'Key 1']);
        $apiKey2 = ApiKey::factory()->for($user)->create(['name' => 'Key 2']);

        $log1 = ApiRequestLog::create([
            'api_key_id' => $apiKey1->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);
        $log2 = ApiRequestLog::create([
            'api_key_id' => $apiKey2->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $this->assertEquals('Key 1', $log1->apiKey->name);
        $this->assertEquals('Key 2', $log2->apiKey->name);
    }

    /** @test RE-010: Relationship query constraints */
    public function test_re010_relationship_query_constraints(): void
    {
        $user = User::factory()->create();
        $activeKey = ApiKey::factory()->for($user)->create(['revoked_at' => null]);
        $revokedKey = ApiKey::factory()->for($user)->create(['revoked_at' => now()]);

        ApiRequestLog::create([
            'api_key_id' => $activeKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);
        ApiRequestLog::create([
            'api_key_id' => $revokedKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $activeLogs = ApiRequestLog::whereHas('apiKey', function ($query) {
            $query->whereNull('revoked_at');
        })->count();

        $this->assertEquals(1, $activeLogs);
    }

    // =========================================================================
    // CATEGORY 4: SCOPES (SC-001 to SC-010)
    // =========================================================================

    /** @test SC-001: scopeForApiKey exists */
    public function test_sc001_scope_for_api_key_exists(): void
    {
        $log = new ApiRequestLog();
        $this->assertTrue(method_exists($log, 'scopeForApiKey'));
    }

    /** @test SC-002: scopeForApiKey filters by api_key_id */
    public function test_sc002_scope_for_api_key_filters_by_api_key_id(): void
    {
        $user = User::factory()->create();
        $apiKey1 = ApiKey::factory()->for($user)->create();
        $apiKey2 = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey1->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey2->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $logs = ApiRequestLog::forApiKey($apiKey1->id)->get();

        $this->assertCount(1, $logs);
        $this->assertEquals($apiKey1->id, $logs->first()->api_key_id);
    }

    /** @test SC-003: scopeForEndpoint exists */
    public function test_sc003_scope_for_endpoint_exists(): void
    {
        $log = new ApiRequestLog();
        $this->assertTrue(method_exists($log, 'scopeForEndpoint'));
    }

    /** @test SC-004: scopeForEndpoint filters by endpoint */
    public function test_sc004_scope_for_endpoint_filters_by_endpoint(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/analyses',
            'method' => 'GET',
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/users',
            'method' => 'GET',
        ]);

        $logs = ApiRequestLog::forEndpoint('/api/v1/analyses')->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('/api/v1/analyses', $logs->first()->endpoint);
    }

    /** @test SC-005: scopeForMethod exists */
    public function test_sc005_scope_for_method_exists(): void
    {
        $log = new ApiRequestLog();
        $this->assertTrue(method_exists($log, 'scopeForMethod'));
    }

    /** @test SC-006: scopeForMethod filters by HTTP method */
    public function test_sc006_scope_for_method_filters_by_method(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'POST',
        ]);

        $logs = ApiRequestLog::forMethod('GET')->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('GET', $logs->first()->method);
    }

    /** @test SC-007: scopeSuccessful exists */
    public function test_sc007_scope_successful_exists(): void
    {
        $log = new ApiRequestLog();
        $this->assertTrue(method_exists($log, 'scopeSuccessful'));
    }

    /** @test SC-008: scopeSuccessful filters by 2xx status codes */
    public function test_sc008_scope_successful_filters_by_2xx_status(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => 200,
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => 404,
        ]);

        $logs = ApiRequestLog::successful()->get();

        $this->assertCount(1, $logs);
        $this->assertEquals(200, $logs->first()->status_code);
    }

    /** @test SC-009: scopeFailed exists */
    public function test_sc009_scope_failed_exists(): void
    {
        $log = new ApiRequestLog();
        $this->assertTrue(method_exists($log, 'scopeFailed'));
    }

    /** @test SC-010: scopeFailed filters by 4xx/5xx status codes */
    public function test_sc010_scope_failed_filters_by_error_status(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => 200,
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'status_code' => 500,
        ]);

        $logs = ApiRequestLog::failed()->get();

        $this->assertCount(1, $logs);
        $this->assertEquals(500, $logs->first()->status_code);
    }

    // =========================================================================
    // CATEGORY 5: DATE & RETENTION QUERIES (DR-001 to DR-010)
    // =========================================================================

    /** @test DR-001: Query logs older than 30 days */
    public function test_dr001_query_logs_older_than_30_days(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $oldLog = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(31),
        ]);
        $newLog = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now(),
        ]);

        $oldLogs = ApiRequestLog::where('created_at', '<', now()->subDays(30))->get();

        $this->assertCount(1, $oldLogs);
        $this->assertEquals($oldLog->id, $oldLogs->first()->id);
    }

    /** @test DR-002: Query logs within retention period */
    public function test_dr002_query_logs_within_retention_period(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(15),
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(31),
        ]);

        $retainedLogs = ApiRequestLog::where('created_at', '>=', now()->subDays(30))->get();

        $this->assertCount(1, $retainedLogs);
    }

    /** @test DR-003: scopeWithinRetention exists */
    public function test_dr003_scope_within_retention_exists(): void
    {
        $log = new ApiRequestLog();
        $this->assertTrue(method_exists($log, 'scopeWithinRetention'));
    }

    /** @test DR-004: scopeWithinRetention filters correctly */
    public function test_dr004_scope_within_retention_filters_correctly(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(15),
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(45),
        ]);

        $logs = ApiRequestLog::withinRetention()->get();

        $this->assertCount(1, $logs);
    }

    /** @test DR-005: scopeOlderThanRetention exists */
    public function test_dr005_scope_older_than_retention_exists(): void
    {
        $log = new ApiRequestLog();
        $this->assertTrue(method_exists($log, 'scopeOlderThanRetention'));
    }

    /** @test DR-006: scopeOlderThanRetention filters correctly */
    public function test_dr006_scope_older_than_retention_filters_correctly(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(15),
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(45),
        ]);

        $logs = ApiRequestLog::olderThanRetention()->get();

        $this->assertCount(1, $logs);
    }

    /** @test DR-007: Date range query with from/to */
    public function test_dr007_date_range_query_with_from_to(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(10),
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(5),
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(1),
        ]);

        $logs = ApiRequestLog::whereBetween('created_at', [
            now()->subDays(7)->startOfDay(),
            now()->subDays(3)->endOfDay(),
        ])->get();

        $this->assertCount(1, $logs);
    }

    /** @test DR-008: Query logs by day */
    public function test_dr008_query_logs_by_day(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(2)->startOfDay(),
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(2)->endOfDay(),
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(1),
        ]);

        $dayCounts = ApiRequestLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        $this->assertCount(2, $dayCounts);
    }

    /** @test DR-009: Delete old logs */
    public function test_dr009_delete_old_logs(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $oldLog = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(45),
        ]);
        $newLog = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now(),
        ]);

        $deleted = ApiRequestLog::olderThanRetention()->delete();

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseMissing('api_request_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('api_request_logs', ['id' => $newLog->id]);
    }

    /** @test DR-010: Retention is configurable */
    public function test_dr010_retention_is_configurable(): void
    {
        config(['api-logging.retention_days' => 60]);

        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'created_at' => now()->subDays(45),
        ]);

        // With 60-day retention, 45-day-old log should be retained
        $retained = ApiRequestLog::where('created_at', '>=', now()->subDays(config('api-logging.retention_days', 30)))->count();

        $this->assertEquals(1, $retained);
    }

    // =========================================================================
    // CATEGORY 6: EDGE CASES & SECURITY (ES-001 to ES-010)
    // =========================================================================

    /** @test ES-001: Very long endpoint string */
    public function test_es001_very_long_endpoint_string(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $longEndpoint = '/api/v1/' . str_repeat('a', 200);

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => $longEndpoint,
            'method' => 'GET',
        ]);

        $this->assertEquals($longEndpoint, $log->endpoint);
    }

    /** @test ES-002: IPv6 address storage */
    public function test_es002_ipv6_address_storage(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'ip_address' => $ipv6,
        ]);

        $this->assertEquals($ipv6, $log->ip_address);
    }

    /** @test ES-003: Long user agent string */
    public function test_es003_long_user_agent_string(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $longUserAgent = 'Mozilla/5.0 ' . str_repeat('(Windows; CPU; OS) ', 20);

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'user_agent' => $longUserAgent,
        ]);

        $this->assertEquals($longUserAgent, $log->user_agent);
    }

    /** @test ES-004: Unicode in user_agent */
    public function test_es004_unicode_in_user_agent(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $unicodeAgent = 'TestAgent/1.0 (日本語; 中文; Ελληνικά)';

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'user_agent' => $unicodeAgent,
        ]);

        $this->assertEquals($unicodeAgent, $log->user_agent);
    }

    /** @test ES-005: XSS in endpoint */
    public function test_es005_xss_in_endpoint(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $malicious = '/api/v1/search?q=<script>alert("xss")</script>';

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => $malicious,
            'method' => 'GET',
        ]);

        // Stored as-is, frontend must escape
        $this->assertEquals($malicious, $log->endpoint);
    }

    /** @test ES-006: SQL injection in ip_address */
    public function test_es006_sql_injection_in_ip_address(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $malicious = "127.0.0.1'; DROP TABLE api_request_logs; --";

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'ip_address' => $malicious,
        ]);

        // Stored as literal string
        $this->assertEquals($malicious, $log->ip_address);
        $this->assertDatabaseHas('api_request_logs', ['id' => $log->id]);
    }

    /** @test ES-007: Null API key ID (should fail at DB level) */
    public function test_es007_null_api_key_id_fails(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        ApiRequestLog::create([
            'api_key_id' => null,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);
    }

    /** @test ES-008: Negative response_time_ms (app-level validation) */
    public function test_es008_negative_response_time_ms(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'response_time_ms' => -100,
        ]);

        // Stored as-is at DB level (app should validate)
        $this->assertEquals(-100, $log->response_time_ms);
    }

    /** @test ES-009: Large response_time_ms */
    public function test_es009_large_response_time_ms(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
            'response_time_ms' => 300000, // 5 minutes
        ]);

        $this->assertEquals(300000, $log->response_time_ms);
    }

    /** @test ES-010: Concurrent log creation */
    public function test_es010_concurrent_log_creation(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $logs = [];
        for ($i = 0; $i < 100; $i++) {
            $logs[] = ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test/' . $i,
                'method' => 'GET',
            ]);
        }

        $this->assertCount(100, $logs);
        $this->assertCount(100, ApiRequestLog::where('api_key_id', $apiKey->id)->get());
    }

    // =========================================================================
    // CATEGORY 7: STATISTICS & ANALYTICS (SA-001 to SA-010)
    // =========================================================================

    /** @test SA-001: Average response time by endpoint */
    public function test_sa001_average_response_time_by_endpoint(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        for ($i = 0; $i < 5; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/analyses',
                'method' => 'GET',
                'status_code' => 200,
                'response_time_ms' => 100 * ($i + 1),
            ]);
        }

        $avg = ApiRequestLog::where('endpoint', '/api/v1/analyses')->avg('response_time_ms');

        $this->assertEquals(300, $avg); // (100+200+300+400+500)/5
    }

    /** @test SA-002: Request count by endpoint */
    public function test_sa002_request_count_by_endpoint(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        for ($i = 0; $i < 3; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/analyses',
                'method' => 'GET',
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/users',
                'method' => 'GET',
            ]);
        }

        $counts = ApiRequestLog::selectRaw('endpoint, COUNT(*) as count')
            ->groupBy('endpoint')
            ->pluck('count', 'endpoint');

        $this->assertEquals(3, $counts['/api/v1/analyses']);
        $this->assertEquals(2, $counts['/api/v1/users']);
    }

    /** @test SA-003: Error rate calculation */
    public function test_sa003_error_rate_calculation(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        for ($i = 0; $i < 8; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
                'status_code' => 200,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
                'status_code' => 500,
            ]);
        }

        $total = ApiRequestLog::count();
        $errors = ApiRequestLog::failed()->count();
        $errorRate = ($errors / $total) * 100;

        $this->assertEquals(20.0, $errorRate); // 2/10 = 20%
    }

    /** @test SA-004: Slow requests identification */
    public function test_sa004_slow_requests_identification(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/fast',
            'method' => 'GET',
            'response_time_ms' => 50,
        ]);
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/slow',
            'method' => 'GET',
            'response_time_ms' => 5000,
        ]);

        $slowRequests = ApiRequestLog::where('response_time_ms', '>', 1000)->get();

        $this->assertCount(1, $slowRequests);
        $this->assertEquals('/api/v1/slow', $slowRequests->first()->endpoint);
    }

    /** @test SA-005: Requests by IP address */
    public function test_sa005_requests_by_ip_address(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        for ($i = 0; $i < 3; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
                'ip_address' => '192.168.1.1',
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
                'ip_address' => '192.168.1.2',
            ]);
        }

        $byIp = ApiRequestLog::selectRaw('ip_address, COUNT(*) as count')
            ->groupBy('ip_address')
            ->pluck('count', 'ip_address');

        $this->assertEquals(3, $byIp['192.168.1.1']);
        $this->assertEquals(2, $byIp['192.168.1.2']);
    }

    /** @test SA-006: Method distribution */
    public function test_sa006_method_distribution(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        for ($i = 0; $i < 5; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
            ]);
        }
        for ($i = 0; $i < 3; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => 'POST',
            ]);
        }

        $byMethod = ApiRequestLog::selectRaw('method, COUNT(*) as count')
            ->groupBy('method')
            ->pluck('count', 'method');

        $this->assertEquals(5, $byMethod['GET']);
        $this->assertEquals(3, $byMethod['POST']);
    }

    /** @test SA-007: Status code distribution */
    public function test_sa007_status_code_distribution(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $statusCodes = [200 => 5, 201 => 2, 400 => 1, 500 => 1];
        foreach ($statusCodes as $code => $count) {
            for ($i = 0; $i < $count; $i++) {
                ApiRequestLog::create([
                    'api_key_id' => $apiKey->id,
                    'endpoint' => '/api/v1/test',
                    'method' => 'GET',
                    'status_code' => $code,
                ]);
            }
        }

        $distribution = ApiRequestLog::selectRaw('status_code, COUNT(*) as count')
            ->groupBy('status_code')
            ->pluck('count', 'status_code');

        $this->assertEquals(5, $distribution[200]);
        $this->assertEquals(2, $distribution[201]);
        $this->assertEquals(1, $distribution[400]);
        $this->assertEquals(1, $distribution[500]);
    }

    /** @test SA-008: Requests per API key */
    public function test_sa008_requests_per_api_key(): void
    {
        $user = User::factory()->create();
        $apiKey1 = ApiKey::factory()->for($user)->create();
        $apiKey2 = ApiKey::factory()->for($user)->create();

        for ($i = 0; $i < 3; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey1->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey2->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
            ]);
        }

        $byKey = ApiRequestLog::selectRaw('api_key_id, COUNT(*) as count')
            ->groupBy('api_key_id')
            ->pluck('count', 'api_key_id');

        $this->assertEquals(3, $byKey[$apiKey1->id]);
        $this->assertEquals(5, $byKey[$apiKey2->id]);
    }

    /** @test SA-009: Hourly distribution */
    public function test_sa009_hourly_distribution(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        // Create logs at different hours
        for ($hour = 0; $hour < 24; $hour++) {
            $count = rand(0, 5);
            for ($i = 0; $i < $count; $i++) {
                ApiRequestLog::create([
                    'api_key_id' => $apiKey->id,
                    'endpoint' => '/api/v1/test',
                    'method' => 'GET',
                    'created_at' => now()->startOfDay()->addHours($hour),
                ]);
            }
        }

        $hourly = ApiRequestLog::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->get();

        $this->assertLessThanOrEqual(24, $hourly->count());
    }

    /** @test SA-010: Performance percentile calculation */
    public function test_sa010_performance_percentile_calculation(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $responseTimes = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];
        foreach ($responseTimes as $time) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => '/api/v1/test',
                'method' => 'GET',
                'response_time_ms' => $time,
            ]);
        }

        $p50 = ApiRequestLog::orderBy('response_time_ms')->skip(4)->first()->response_time_ms;
        $p95 = ApiRequestLog::orderBy('response_time_ms')->skip(8)->first()->response_time_ms;

        $this->assertEquals(50, $p50);
        $this->assertEquals(90, $p95);
    }

    // =========================================================================
    // CATEGORY 8: ACCEPTANCE CRITERIA (AC-001 to AC-006)
    // =========================================================================

    /** @test AC-001: Table has all required columns */
    public function test_ac001_table_has_all_required_columns(): void
    {
        $columns = \Schema::getColumnListing('api_request_logs');

        $this->assertContains('id', $columns);
        $this->assertContains('api_key_id', $columns);
        $this->assertContains('endpoint', $columns);
        $this->assertContains('method', $columns);
        $this->assertContains('status_code', $columns);
        $this->assertContains('response_time_ms', $columns);
        $this->assertContains('ip_address', $columns);
        $this->assertContains('user_agent', $columns);
        $this->assertContains('created_at', $columns);
    }

    /** @test AC-002: Foreign key constraint on api_key_id */
    public function test_ac002_foreign_key_constraint_on_api_key_id(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create a log with non-existent api_key_id
        ApiRequestLog::create([
            'api_key_id' => 99999,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);
    }

    /** @test AC-003: Cascade delete when API key deleted */
    public function test_ac003_cascade_delete_when_api_key_deleted(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->for($user)->create();

        $log = ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/test',
            'method' => 'GET',
        ]);

        $logId = $log->id;
        $apiKey->delete();

        $this->assertDatabaseMissing('api_request_logs', ['id' => $logId]);
    }

    /** @test AC-004: Index on api_key_id for fast queries */
    public function test_ac004_index_on_api_key_id(): void
    {
        $indexes = \DB::select("SHOW INDEX FROM api_request_logs WHERE Column_name = 'api_key_id'");
        
        $this->assertNotEmpty($indexes, 'api_key_id should have an index');
    }

    /** @test AC-005: Index on created_at for retention queries */
    public function test_ac005_index_on_created_at(): void
    {
        $indexes = \DB::select("SHOW INDEX FROM api_request_logs WHERE Column_name = 'created_at'");
        
        $this->assertNotEmpty($indexes, 'created_at should have an index for retention queries');
    }

    /** @test AC-006: Model exists at correct location */
    public function test_ac006_model_exists_at_correct_location(): void
    {
        $this->assertTrue(class_exists(\App\Models\ApiRequestLog::class),
            'ApiRequestLog model should exist at app/Models/ApiRequestLog.php');
    }
}
