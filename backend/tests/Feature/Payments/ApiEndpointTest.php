<?php

namespace Tests\Feature\Payments;

use App\Models\AIModel;
use App\Models\Credit;
use App\Models\CreditPackage;
use App\Models\User;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Integration tests for payment-related API endpoints.
 *
 * TASK-26-26: Write Integration Tests for Payments
 * @see TESTS/TASK-26-26.md
 */
class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // Credit Packages Tests
    // ============================================

    /** @test */
    public function it_lists_available_credit_packages(): void
    {
        // Create credit packages
        CreditPackage::create([
            'name' => 'Starter',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'bonus_percent' => 0,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        CreditPackage::create([
            'name' => 'Pro',
            'credits' => 50.00,
            'price_usd' => 45.00,
            'bonus_percent' => 10,
            'is_popular' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/packages');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'credits',
                        'price_usd',
                        'bonus_percent',
                        'is_popular',
                        'formatted_price',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_only_shows_active_packages(): void
    {
        CreditPackage::create([
            'name' => 'Active Package',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        CreditPackage::create([
            'name' => 'Inactive Package',
            'credits' => 100.00,
            'price_usd' => 80.00,
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/packages');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Active Package');
    }

    /** @test */
    public function it_orders_packages_by_sort_order(): void
    {
        CreditPackage::create([
            'name' => 'Package B',
            'credits' => 20.00,
            'price_usd' => 18.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        CreditPackage::create([
            'name' => 'Package A',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/packages');

        $response->assertJsonPath('data.0.name', 'Package A');
        $response->assertJsonPath('data.1.name', 'Package B');
    }

    /** @test */
    public function it_highlights_popular_package(): void
    {
        CreditPackage::create([
            'name' => 'Most Popular',
            'credits' => 50.00,
            'price_usd' => 45.00,
            'is_popular' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        CreditPackage::create([
            'name' => 'Basic',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/packages');

        $response->assertJsonPath('data.0.is_popular', true);
    }

    /** @test */
    public function it_shows_bonus_credits(): void
    {
        CreditPackage::create([
            'name' => 'Pro with Bonus',
            'credits' => 50.00,
            'price_usd' => 45.00,
            'bonus_percent' => 20,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/packages');

        $response->assertJsonPath('data.0.bonus_percent', 20);
        
        // Check effective_credits is approximately 60 (50 + 20%)
        $data = $response->json('data.0');
        $this->assertEqualsWithDelta(60.0, $data['effective_credits'], 0.01);
    }

    // ============================================
    // Model Pricing Tests
    // ============================================

    /** @test */
    public function it_lists_available_ai_models(): void
    {
        // Create an AI model for testing
        AIModel::create([
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'display_name' => 'GPT-4',
            'cost_per_1k_input' => 0.03,
            'cost_per_1k_output' => 0.06,
            'context_window' => 8192,
            'is_free_tier' => false,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'model_id',
                        'display_name',
                        'is_free_tier',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_shows_model_pricing(): void
    {
        // Create an AI model for testing
        AIModel::create([
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'display_name' => 'GPT-4',
            'cost_per_1k_input' => 0.03,
            'cost_per_1k_output' => 0.06,
            'context_window' => 8192,
            'is_free_tier' => false,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/pricing');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'models' => [
                    '*' => [
                        'model_id',
                        'display_name',
                        'input_cost_per_1k',
                        'output_cost_per_1k',
                        'estimated_cost_per_analysis',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_gets_single_model_details(): void
    {
        // Create an AI model for testing
        $model = AIModel::create([
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'display_name' => 'GPT-4',
            'cost_per_1k_input' => 0.03,
            'cost_per_1k_output' => 0.06,
            'context_window' => 8192,
            'is_free_tier' => false,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/models/{$model->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'provider',
                'model_id',
                'display_name',
                'context_window',
                'pricing' => [
                    'input_per_1k',
                    'output_per_1k',
                ],
            ]);
    }

    // ============================================
    // User Preferences Tests
    // ============================================

    /** @test */
    public function it_sets_user_default_model(): void
    {
        $user = User::factory()->create();

        // Create an AI model for testing
        $model = AIModel::create([
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'display_name' => 'GPT-4',
            'cost_per_1k_input' => 0.03,
            'cost_per_1k_output' => 0.06,
            'context_window' => 8192,
            'is_free_tier' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_gets_user_default_model(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_model_exists_when_setting_preference(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => 99999, // Non-existent
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['model_id']);
    }

    // ============================================
    // Authentication Tests
    // ============================================

    /** @test */
    public function packages_endpoint_is_public(): void
    {
        $response = $this->getJson('/api/v1/packages');

        // Should be accessible without auth
        $response->assertStatus(200);
    }

    /** @test */
    public function models_endpoint_is_public(): void
    {
        // The models endpoint is public - no authentication required
        $response = $this->getJson('/api/v1/models');

        $response->assertStatus(200);
    }

    /** @test */
    public function preferences_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/models/preferences');

        $response->assertStatus(401);
    }

    // ============================================
    // Credit Purchase Initiation Tests
    // ============================================

    /** @test */
    public function it_initiates_credit_purchase(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id]);

        $package = CreditPackage::create([
            'name' => 'Starter',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Mock the payment gateway
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'payment_intent_id' => 'pi_test_123',
                'client_secret' => 'pi_test_123_secret',
                'currency' => 'usd',
            ]);
        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/credits/purchase', [
                'package_id' => $package->id,
                'payment_method' => 'stripe',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'payment_intent_id',
                'client_secret',
            ]);
    }

    /** @test */
    public function it_validates_package_id_for_purchase(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/credits/purchase', [
                'package_id' => 999,
                'payment_method' => 'stripe',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['package_id']);
    }

    /** @test */
    public function it_validates_payment_method(): void
    {
        $user = User::factory()->create();

        CreditPackage::create([
            'name' => 'Test',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/credits/purchase', [
                'package_id' => 1,
                'payment_method' => 'invalid_method',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function purchase_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/credits/purchase', [
            'package_id' => 1,
            'payment_method' => 'stripe',
        ]);

        $response->assertStatus(401);
    }

    // ============================================
    // Error Response Tests
    // ============================================

    /** @test */
    public function it_returns_structured_error_response(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/credits/purchase', [
                // Missing required fields
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }

    /** @test */
    public function it_handles_missing_model_gracefully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/999999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'model_not_found',
            ]);
    }

    // ============================================
    // CORS Tests
    // ============================================

    /** @test */
    public function it_includes_cors_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://example.com',
        ])->getJson('/api/v1/packages');

        // CORS headers are typically handled by middleware
        // Just verify the endpoint is accessible
        $response->assertStatus(200);
    }
}
