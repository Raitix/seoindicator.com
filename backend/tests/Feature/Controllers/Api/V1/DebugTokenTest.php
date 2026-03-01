<?php

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DebugTokenTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_with_token_helper(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $plainTextToken = $token->plainTextToken;

        // Use withToken
        $response = $this->withToken($plainTextToken)
            ->getJson('/api/v1/auth/user');

        // Debug output
        if ($response->status() !== 200) {
            echo "Status: " . $response->status() . PHP_EOL;
            echo "Token: " . $plainTextToken . PHP_EOL;
            echo "Token format check: " . (str_contains($plainTextToken, '|') ? 'contains |' : 'missing |') . PHP_EOL;
        }

        $response->assertStatus(200);
    }

    /** @test */
    public function test_explicit_bearer_header(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $plainTextToken = $token->plainTextToken;

        // Set the Authorization header explicitly
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainTextToken,
        ])->getJson('/api/v1/auth/user');

        $response->assertStatus(200);
    }

    /** @test */
    public function test_sanctum_acting_as(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');
        $response->assertStatus(200);
    }
}
