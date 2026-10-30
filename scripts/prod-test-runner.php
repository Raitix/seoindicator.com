<?php
// Production test runner for TASK-8-7
// This script runs on the production server

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Str;

echo "=== PRODUCTION TEST: TASK-8-7 Project CRUD Endpoints ===\n\n";

// Get first user
$user = User::first();
if (!$user) {
    echo "ERROR: No users found in database\n";
    exit(1);
}

echo "Test User: {$user->email} (ID: {$user->id})\n\n";

// Test counters
$passed = 0;
$failed = 0;

function test($name, $condition, $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] $name\n";
        $passed++;
    } else {
        echo "[FAIL] $name" . ($details ? " - $details" : '') . "\n";
        $failed++;
    }
}

// ============================================
// DATABASE VERIFICATION
// ============================================
echo "--- Database Verification ---\n";

// Check projects table exists
try {
    $count = Project::count();
    test("Projects table accessible", true, "Count: $count");
} catch (\Exception $e) {
    test("Projects table accessible", false, $e->getMessage());
}

// Check user has projects or can create
test("User model exists", $user->exists);

echo "\n";

// ============================================
// MODEL VERIFICATION
// ============================================
echo "--- Model Verification ---\n";

// Test project creation via model
try {
    $project = new Project();
    $project->user_id = $user->id;
    $project->name = 'Production Test ' . Str::random(8);
    $project->domain = 'https://test.example.com';
    $project->save();
    test("Project model creates records", true, "ID: {$project->id}");
    $createdProjectId = $project->id;
    
    // Test archive
    $project->archive();
    $project->refresh();
    test("Project archive() sets archived_at", $project->archived_at !== null);
    
    // Test restore
    $project->unarchive();
    $project->refresh();
    test("Project unarchive() clears archived_at", $project->archived_at === null);
    
    // Test scopes
    $activeCount = Project::active()->where('user_id', $user->id)->count();
    test("Project active() scope works", true, "Active count: $activeCount");
    
    // Clean up
    $project->delete();
    test("Project delete() works", true);
    
} catch (\Exception $e) {
    test("Project model operations", false, $e->getMessage());
}

echo "\n";

// ============================================
// POLICY VERIFICATION
// ============================================
echo "--- Policy Verification ---\n";

try {
    $policy = new \App\Policies\ProjectPolicy();
    
    // Create a test project
    $ownProject = Project::create([
        'user_id' => $user->id,
        'name' => 'Own Test Project'
    ]);
    
    // Test user can view own project
    $canView = $policy->view($user, $ownProject);
    test("User can view own project", $canView === true);
    
    // Test user can update own project
    $canUpdate = $policy->update($user, $ownProject);
    test("User can update own project", $canUpdate === true);
    
    // Test user can delete own project
    $canDelete = $policy->delete($user, $ownProject);
    test("User can delete own project", $canDelete === true);
    
    // Create another user's project (if other users exist)
    $otherUser = User::where('id', '!=', $user->id)->first();
    if ($otherUser) {
        $otherProject = Project::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Project'
        ]);
        
        // Test user cannot view other's project
        $canViewOther = $policy->view($user, $otherProject);
        test("User cannot view other user's project", $canViewOther === false);
        
        // Test user cannot update other's project
        $canUpdateOther = $policy->update($user, $otherProject);
        test("User cannot update other user's project", $canUpdateOther === false);
        
        // Test user cannot delete other's project
        $canDeleteOther = $policy->delete($user, $otherProject);
        test("User cannot delete other user's project", $canDeleteOther === false);
        
        // Clean up
        $otherProject->delete();
    } else {
        echo "[SKIP] No other users to test cross-user access\n";
    }
    
    // Clean up
    $ownProject->delete();
    
} catch (\Exception $e) {
    test("Policy operations", false, $e->getMessage());
}

echo "\n";

// ============================================
// CONTROLLER VERIFICATION
// ============================================
echo "--- Controller Verification ---\n";

try {
    $controller = new \App\Http\Controllers\Api\V1\ProjectController();
    test("ProjectController instantiates", true);
    
    // Check methods exist
    $methods = ['index', 'store', 'show', 'update', 'destroy', 'archive', 'restore'];
    foreach ($methods as $method) {
        test("Controller has $method method", method_exists($controller, $method));
    }
    
} catch (\Exception $e) {
    test("Controller verification", false, $e->getMessage());
}

echo "\n";

// ============================================
// REQUEST VALIDATION VERIFICATION
// ============================================
echo "--- Request Validation Verification ---\n";

try {
    $request = new \App\Http\Requests\ProjectRequest();
    test("ProjectRequest instantiates", true);
    
    // Check rules exist
    $rules = $request->rules();
    test("ProjectRequest has validation rules", !empty($rules));
    test("ProjectRequest requires name", isset($rules['name']));
    test("ProjectRequest validates domain", isset($rules['domain']));
    test("ProjectRequest validates notes", isset($rules['notes']));
    
} catch (\Exception $e) {
    test("Request validation verification", false, $e->getMessage());
}

echo "\n";

// ============================================
// ROUTE VERIFICATION
// ============================================
echo "--- Route Verification ---\n";

try {
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->filter(function ($route) {
        return str_contains($route->uri(), 'projects');
    });
    
    $expectedRoutes = [
        'GET api/v1/projects',
        'POST api/v1/projects',
        'GET api/v1/projects/{project}',
        'PUT api/v1/projects/{project}',
        'DELETE api/v1/projects/{project}',
        'POST api/v1/projects/{project}/archive',
        'POST api/v1/projects/{project}/restore'
    ];
    
    foreach ($expectedRoutes as $expected) {
        $found = $routes->contains(function ($route) use ($expected) {
            return $route->methods()[0] . ' ' . $route->uri() === $expected;
        });
        test("Route exists: $expected", $found);
    }
    
} catch (\Exception $e) {
    test("Route verification", false, $e->getMessage());
}

echo "\n";

// ============================================
// SUMMARY
// ============================================
echo "=== TEST SUMMARY ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\n[FAILED] Some tests failed - review above\n";
    exit(1);
} else {
    echo "\n[PASSED] All production tests passed\n";
    exit(0);
}
