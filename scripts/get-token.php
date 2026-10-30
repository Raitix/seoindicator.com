<?php
// Generate test token for production testing
$user = App\Models\User::first();
if (!$user) {
    echo "ERROR: No users found\n";
    exit(1);
}
$token = $user->createToken('prod-test')->plainTextToken;
echo json_encode([
    'user_id' => $user->id,
    'email' => $user->email,
    'token' => $token
]);
