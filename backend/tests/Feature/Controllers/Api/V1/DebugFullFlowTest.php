<?php

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DebugFullFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_debug_full_flow(): void
    {
        Notification::fake();

        // 1. Register user
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        echo "Register Status: " . $registerResponse->status() . PHP_EOL;
        $this->assertEquals(201, $registerResponse->status(), 'Registration should succeed');

        // 2. Login to get token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123',
        ]);

        echo "Login Status: " . $loginResponse->status() . PHP_EOL;
        echo "Login Response: " . json_encode($loginResponse->json()) . PHP_EOL;

        $token = $loginResponse->json('data.token');
        echo "Token: " . ($token ?? 'NULL') . PHP_EOL;
        $this->assertNotNull($token, 'Token should not be null');

        // 3. Request verification email with token
        $resendResponse = $this->withToken($token)
            ->postJson('/api/v1/auth/resend-verification');

        echo "Resend Status: " . $resendResponse->status() . PHP_EOL;
        echo "Resend Response: " . json_encode($resendResponse->json()) . PHP_EOL;

        $resendResponse->assertStatus(200);
    }
}
