<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

// Create user
$user = User::factory()->create([
    'email' => 'debug@example.com',
    'password' => bcrypt('OldPassword123'),
]);

echo "User ID: {$user->id}" . PHP_EOL;
echo "User email: {$user->email}" . PHP_EOL;

// Create reset token
$resetToken = Password::createToken($user);
echo "Reset token: {$resetToken}" . PHP_EOL;

// Simulate reset
$status = Password::reset(
    ['email' => 'debug@example.com', 'password' => 'NewSecurePassword123', 'password_confirmation' => 'NewSecurePassword123', 'token' => $resetToken],
    function ($user, $password) {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => null,
        ])->save();
        $user->tokens()->delete();
    }
);

echo "Reset status: {$status}" . PHP_EOL;

// Create new token
$freshUser = User::where('email', 'debug@example.com')->first();
$newToken = $freshUser->createToken('auth-token', ['*'], now()->addDays(7));

echo "New token: {$newToken->plainTextToken}" . PHP_EOL;
echo "Token ID: {$newToken->accessToken->id}" . PHP_EOL;
echo "Token user_id: {$newToken->accessToken->tokenable_id}" . PHP_EOL;

// Check if token exists in database
$tokenInDb = \Laravel\Sanctum\PersonalAccessToken::find($newToken->accessToken->id);
echo "Token in DB: " . ($tokenInDb ? 'YES' : 'NO') . PHP_EOL;

// Clean up
$freshUser->forceDelete();
