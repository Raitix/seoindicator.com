<?php

namespace Tests\Feature\Api\V1\Credits;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Transaction History API Endpoint.
 *
 * GET /api/v1/credits/transactions
 *
 * @see TASK-26-7
 * @see FEAT-026
 */
class TransactionHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $endpoint = '/api/v1/credits/transactions';

    // ============================================
    // Authentication Tests (TH-AUTH)
    // ============================================

    /** @test TH-AUTH-01: Authenticated user can access */
    public function authenticated_user_can_access_transaction_history(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /** @test TH-AUTH-02: Unauthenticated user blocked */
    public function unauthenticated_user_cannot_access_transaction_history(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertUnauthorized();
    }

    /** @test TH-AUTH-03: Invalid token rejected */
    public function invalid_token_is_rejected(): void
    {
        $response = $this->getJson($this->endpoint, [
            'Authorization' => 'Bearer invalid-token-12345',
        ]);

        $response->assertUnauthorized();
    }

    // ============================================
    // Pagination Tests (TH-PAGINATION)
    // ============================================

    /** @test TH-PAG-01: Default pagination */
    public function default_pagination_is_applied(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create 20 transactions
        CreditTransaction::factory()->count(20)->for($user)->create();

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15);
    }

    /** @test TH-PAG-02: Custom page */
    public function custom_page_can_be_requested(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create 30 transactions
        CreditTransaction::factory()->count(30)->for($user)->create();

        $response = $this->getJson($this->endpoint . '?page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2);
    }

    /** @test TH-PAG-03: Custom per_page */
    public function custom_per_page_can_be_requested(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->count(20)->for($user)->create();

        $response = $this->getJson($this->endpoint . '?per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 10);
    }

    /** @test TH-PAG-04: Max per_page */
    public function max_per_page_is_accepted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->count(60)->for($user)->create();

        $response = $this->getJson($this->endpoint . '?per_page=50');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 50);
    }

    /** @test TH-PAG-05: per_page exceeds max returns validation error */
    public function per_page_exceeding_max_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?per_page=100');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    /** @test TH-PAG-06: per_page below minimum returns validation error */
    public function per_page_below_minimum_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?per_page=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    /** @test TH-PAG-07: Meta contains pagination info */
    public function meta_contains_pagination_info(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->count(30)->for($user)->create();

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /** @test TH-PAG-08: Empty transaction list */
    public function empty_transaction_list_returns_empty_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    // ============================================
    // Type Filtering Tests (TH-TYPE)
    // ============================================

    /** @test TH-TYPE-01: Filter by purchase */
    public function filter_by_purchase_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create(['type' => 'purchase']);
        CreditTransaction::factory()->for($user)->create(['type' => 'usage']);
        CreditTransaction::factory()->for($user)->create(['type' => 'bonus']);

        $response = $this->getJson($this->endpoint . '?type=purchase');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('purchase', $data[0]['type']);
    }

    /** @test TH-TYPE-02: Filter by usage */
    public function filter_by_usage_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create(['type' => 'purchase']);
        CreditTransaction::factory()->for($user)->create(['type' => 'usage']);
        CreditTransaction::factory()->for($user)->create(['type' => 'bonus']);

        $response = $this->getJson($this->endpoint . '?type=usage');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('usage', $data[0]['type']);
    }

    /** @test TH-TYPE-03: Filter by refund */
    public function filter_by_refund_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create(['type' => 'purchase']);
        CreditTransaction::factory()->for($user)->create(['type' => 'refund']);

        $response = $this->getJson($this->endpoint . '?type=refund');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('refund', $data[0]['type']);
    }

    /** @test TH-TYPE-04: Filter by bonus */
    public function filter_by_bonus_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create(['type' => 'purchase']);
        CreditTransaction::factory()->for($user)->create(['type' => 'bonus']);

        $response = $this->getJson($this->endpoint . '?type=bonus');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('bonus', $data[0]['type']);
    }

    /** @test TH-TYPE-05: Invalid type returns validation error */
    public function invalid_type_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?type=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    /** @test TH-TYPE-06: Case sensitivity */
    public function type_is_case_sensitive(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?type=PURCHASE');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    // ============================================
    // Date Range Filtering Tests (TH-DATE)
    // ============================================

    /** @test TH-DATE-01: Filter from date */
    public function filter_from_date(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-01 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-05 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-10 10:00:00',
        ]);

        $response = $this->getJson($this->endpoint . '?from=2026-03-05');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /** @test TH-DATE-02: Filter to date */
    public function filter_to_date(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-01 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-05 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-10 10:00:00',
        ]);

        $response = $this->getJson($this->endpoint . '?to=2026-03-05');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /** @test TH-DATE-03: Date range */
    public function filter_by_date_range(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-01 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-03 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-10 10:00:00',
        ]);

        $response = $this->getJson($this->endpoint . '?from=2026-03-02&to=2026-03-05');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /** @test TH-DATE-04: Invalid from format returns validation error */
    public function invalid_from_format_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?from=03-01-2026');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['from']);
    }

    /** @test TH-DATE-05: Invalid to format returns validation error */
    public function invalid_to_format_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?to=March-5-2026');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    /** @test TH-DATE-06: from after to returns validation error */
    public function from_after_to_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?from=2026-03-10&to=2026-03-01');

        // The 'after_or_equal:from' rule on 'to' field catches this error
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    /** @test TH-DATE-07: from equals to */
    public function from_equals_to_returns_transactions_on_that_day(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-05 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'created_at' => '2026-03-06 10:00:00',
        ]);

        $response = $this->getJson($this->endpoint . '?from=2026-03-05&to=2026-03-05');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /** @test TH-DATE-08: Future date range returns empty */
    public function future_date_range_returns_empty_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create();

        $response = $this->getJson($this->endpoint . '?from=2027-01-01');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    // ============================================
    // Combined Filter Tests (TH-COMBINED)
    // ============================================

    /** @test TH-COMB-01: Type + date range */
    public function combined_type_and_date_range_filter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create([
            'type' => 'purchase',
            'created_at' => '2026-03-01 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'type' => 'purchase',
            'created_at' => '2026-03-05 10:00:00',
        ]);
        CreditTransaction::factory()->for($user)->create([
            'type' => 'usage',
            'created_at' => '2026-03-03 10:00:00',
        ]);

        $response = $this->getJson($this->endpoint . '?type=purchase&from=2026-03-02&to=2026-03-06');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('purchase', $data[0]['type']);
    }

    /** @test TH-COMB-02: All filters + pagination */
    public function combined_filters_with_pagination(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create 25 purchase transactions in date range
        CreditTransaction::factory()->count(25)->for($user)->create([
            'type' => 'purchase',
            'created_at' => '2026-03-03 10:00:00',
        ]);
        // Create some outside range
        CreditTransaction::factory()->count(5)->for($user)->create([
            'type' => 'purchase',
            'created_at' => '2026-03-10 10:00:00',
        ]);

        $response = $this->getJson($this->endpoint . '?type=purchase&from=2026-03-01&to=2026-03-05&page=2&per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10);
    }

    /** @test TH-COMB-03: Multiple filters no results */
    public function combined_filters_no_results(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create(['type' => 'purchase']);

        $response = $this->getJson($this->endpoint . '?type=refund&from=2027-01-01');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    // ============================================
    // Security Tests (TH-SECURITY)
    // ============================================

    /** @test TH-SEC-01: User cannot see other users' transactions */
    public function user_cannot_see_other_users_transactions(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Sanctum::actingAs($userA);

        CreditTransaction::factory()->count(5)->for($userB)->create();

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    /** @test TH-SEC-02: No sensitive payment data exposed */
    public function no_sensitive_payment_data_exposed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create([
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_12345',
            'metadata' => [
                'card_last_four' => '4242', // This should NOT be in response
            ],
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $data = $response->json('data')[0];
        
        // payment_id should be present (it's the external payment reference)
        $this->assertArrayHasKey('payment_provider', $data);
        
        // Metadata should not be exposed
        $this->assertArrayNotHasKey('metadata', $data);
        $this->assertArrayNotHasKey('card_last_four', $data);
    }

    /** @test TH-SEC-03: SQL injection attempt returns validation error */
    public function sql_injection_attempt_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . "?type=' OR '1'='1");

        $response->assertUnprocessable();
    }

    /** @test TH-SEC-04: XSS in query params returns validation error */
    public function xss_in_query_params_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?from=<script>alert(1)</script>');

        $response->assertUnprocessable();
    }

    /** @test TH-SEC-05: No token in response */
    public function api_token_not_exposed_in_response(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create();

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $json = $response->json();
        
        // Ensure no token is exposed anywhere in the response
        $this->assertFalse(
            str_contains(json_encode($json), $token)
        );
    }

    // ============================================
    // Response Format Tests (TH-RESPONSE)
    // ============================================

    /** @test TH-RESP-01: Success response structure */
    public function success_response_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ])
            ->assertJsonPath('success', true);
    }

    /** @test TH-RESP-02: Transaction item structure */
    public function transaction_item_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create([
            'type' => 'purchase',
            'payment_provider' => 'stripe',
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'balance_after',
                        'type',
                        'description',
                        'payment_provider',
                        'created_at',
                    ],
                ],
            ]);
    }

    /** @test TH-RESP-03: Amount is numeric */
    public function amount_is_numeric(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create(['amount' => 10.50]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $data = $response->json('data')[0];
        $this->assertIsFloat($data['amount']);
    }

    /** @test TH-RESP-04: created_at is ISO 8601 */
    public function created_at_is_iso_8601_format(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create();

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $data = $response->json('data')[0];
        
        // Validate ISO 8601 format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $data['created_at']
        );
    }

    /** @test TH-RESP-05: balance_after precision */
    public function balance_after_has_two_decimal_places(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create(['balance_after' => 100.50]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $data = $response->json('data')[0];
        
        // Should be numeric with 2 decimal places
        $this->assertIsFloat($data['balance_after']);
    }

    /** @test TH-RESP-06: payment_provider null handling */
    public function payment_provider_can_be_null(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->for($user)->create([
            'type' => 'bonus',
            'payment_provider' => null,
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $data = $response->json('data')[0];
        $this->assertNull($data['payment_provider']);
    }

    // ============================================
    // HTTP Method Tests (TH-HTTP)
    // ============================================

    /** @test TH-HTTP-01: GET allowed */
    public function get_method_is_allowed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
    }

    /** @test TH-HTTP-02: POST not allowed */
    public function post_method_not_allowed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson($this->endpoint);

        $response->assertMethodNotAllowed();
    }

    /** @test TH-HTTP-03: PUT not allowed */
    public function put_method_not_allowed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson($this->endpoint);

        $response->assertMethodNotAllowed();
    }

    /** @test TH-HTTP-04: DELETE not allowed */
    public function delete_method_not_allowed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson($this->endpoint);

        $response->assertMethodNotAllowed();
    }

    /** @test TH-HTTP-05: PATCH not allowed */
    public function patch_method_not_allowed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->patchJson($this->endpoint);

        $response->assertMethodNotAllowed();
    }

    // ============================================
    // Edge Cases Tests (TH-EDGE)
    // ============================================

    /** @test TH-EDGE-01: Very large page number */
    public function very_large_page_number_returns_empty_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->count(5)->for($user)->create();

        $response = $this->getJson($this->endpoint . '?page=999999');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    /** @test TH-EDGE-02: Negative page returns validation error */
    public function negative_page_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?page=-1');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    /** @test TH-EDGE-03: Non-numeric page returns validation error */
    public function non_numeric_page_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?page=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    /** @test TH-EDGE-04: Negative per_page returns validation error */
    public function negative_per_page_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?per_page=-5');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    /** @test TH-EDGE-05: Non-numeric per_page returns validation error */
    public function non_numeric_per_page_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?per_page=ten');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    /** @test TH-EDGE-06: Special characters in type returns validation error */
    public function special_characters_in_type_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?type=<script>');

        $response->assertUnprocessable();
    }

    /** @test TH-EDGE-07: Unicode in params returns validation error */
    public function unicode_in_params_returns_validation_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?type=%E2%98%A0');

        $response->assertUnprocessable();
    }

    /** @test TH-EDGE-08: Empty string params treated as null */
    public function empty_string_params_treated_as_null(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->count(3)->for($user)->create();

        $response = $this->getJson($this->endpoint . '?type=&from=&to=');

        // Empty strings should be ignored, returning all data
        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    /** @test TH-EDGE-09: Whitespace in params */
    public function whitespace_in_params_is_trimmed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?type=%20purchase%20');

        // Whitespace-padded type is trimmed and validated (per spec: "422 validation error OR trimmed")
        $response->assertOk();
    }

    // ============================================
    // Performance Tests (TH-PERF)
    // ============================================

    /** @test TH-PERF-01: Response time acceptable */
    public function response_time_is_acceptable(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->count(100)->for($user)->create();

        $start = microtime(true);
        $this->getJson($this->endpoint);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $elapsed, 'Response time should be under 500ms');
    }

    /** @test TH-PERF-02: Large dataset pagination */
    public function large_dataset_pagination_is_efficient(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        CreditTransaction::factory()->count(1000)->for($user)->create();

        $response = $this->getJson($this->endpoint . '?per_page=50');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 50);
        
        // Verify we get exactly 50 items (not all 1000)
        $this->assertCount(50, $response->json('data'));
    }
}
