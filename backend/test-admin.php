<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find admin user
$admin = \App\Models\User::where('is_admin', 1)->first();

if (!$admin) {
    echo "No admin user found. Creating one...\n";
    $admin = \App\Models\User::create([
        'name' => 'Admin',
        'email' => 'admin-test@seoindicator.com',
        'password' => bcrypt('AdminTest123!'),
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);
    echo "Created admin user with ID: {$admin->id}\n";
}

// Create a token
$token = $admin->createToken('admin-test-token')->plainTextToken;
echo "Admin ID: {$admin->id}\n";
echo "Token: {$token}\n";
