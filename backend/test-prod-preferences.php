<?php

// Test script for user_model_preferences production testing

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Production Test: User Model Preferences ===\n\n";

// Test 1: Check if migration ran
echo "1. Checking migration status...\n";
$migrations = DB::table('migrations')
    ->where('migration', 'like', '%user_model_preferences%')
    ->get();

if ($migrations->count() > 0) {
    echo "   ✅ user_model_preferences migration found\n";
} else {
    echo "   ❌ user_model_preferences migration NOT found\n";
}

// Test 2: Check if table exists
echo "\n2. Checking table structure...\n";
try {
    $columns = DB::select("DESCRIBE user_model_preferences");
    echo "   ✅ Table exists with columns:\n";
    foreach ($columns as $col) {
        echo "      - {$col->Field} ({$col->Type})\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Table does not exist: {$e->getMessage()}\n";
}

// Test 3: Check foreign keys
echo "\n3. Checking foreign key constraints...\n";
$fks = DB::select("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_model_preferences'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if (count($fks) > 0) {
    echo "   ✅ Foreign keys found:\n";
    foreach ($fks as $fk) {
        echo "      - {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}\n";
    }
} else {
    echo "   ⚠️ No foreign keys found\n";
}

// Test 4: Check unique constraint
echo "\n4. Checking unique constraint on (user_id, model_id)...\n";
$indexes = DB::select("
    SHOW INDEX FROM user_model_preferences
    WHERE Key_name != 'PRIMARY'
    AND Non_unique = 0
");

$hasUserModelUnique = false;
foreach ($indexes as $idx) {
    if (strpos($idx->Key_name, 'user_id') !== false || strpos($idx->Key_name, 'model_id') !== false) {
        $hasUserModelUnique = true;
        echo "   ✅ Unique constraint found: {$idx->Key_name}\n";
    }
}

if (!$hasUserModelUnique && count($indexes) > 0) {
    echo "   ✅ Unique constraints found\n";
}

// Test 5: Check ai_models table
echo "\n5. Checking ai_models table...\n";
try {
    $modelCount = DB::table('ai_models')->count();
    echo "   ✅ ai_models table exists with {$modelCount} records\n";
} catch (\Exception $e) {
    echo "   ❌ ai_models table error: {$e->getMessage()}\n";
}

// Test 6: Check if UserModelPreference model exists
echo "\n6. Checking UserModelPreference model...\n";
if (class_exists(\App\Models\UserModelPreference::class)) {
    echo "   ✅ Model class exists\n";
    
    // Test relationships
    $testUser = \App\Models\User::first();
    if ($testUser) {
        echo "   Testing with user ID: {$testUser->id}\n";
    }
} else {
    echo "   ❌ Model class not found\n";
}

// Test 7: Create test user if needed
echo "\n7. Setting up test data...\n";
$testUser = \App\Models\User::first();
if (!$testUser) {
    $testUser = \App\Models\User::create([
        'name' => 'Test User',
        'email' => 'test-prod-' . time() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    echo "   Created test user: {$testUser->email}\n";
} else {
    echo "   Using existing user: {$testUser->email}\n";
}

// Test 8: Check if there are any active AI models
$activeModels = \App\Models\AIModel::where('is_active', true)->get();
echo "\n8. Active AI models: {$activeModels->count()}\n";

if ($activeModels->count() > 0) {
    echo "   ✅ Active models available for testing\n";
} else {
    echo "   ⚠️ No active models - need to seed data for full testing\n";
}

// Test 9: Generate auth token for API testing
echo "\n9. Generating test auth token...\n";
$token = $testUser->createToken('prod-test-token')->plainTextToken;
echo "   Token: " . substr($token, 0, 20) . "...\n";

// Test 10: Test the API endpoint with token
echo "\n10. Testing API endpoint with authentication...\n";
$baseUrl = 'https://seoindicator.com';

// Test GET preferences
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/models/preferences');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   GET /api/v1/models/preferences: HTTP {$httpCode}\n";
if ($httpCode === 200) {
    echo "   ✅ Authenticated request successful\n";
    $data = json_decode($response, true);
    echo "   Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   Response: {$response}\n";
}

echo "\n=== Production Test Complete ===\n";
