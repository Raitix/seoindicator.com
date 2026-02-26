<?php

/**
 * LocalTester Test Script for TASK-11-2
 * Tests project_members table constraints and model functionality
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tests = [];
$passed = 0;
$failed = 0;

function test($name, $condition) {
    global $tests, $passed, $failed;
    if ($condition) {
        $tests[] = "✅ PASS: $name";
        $passed++;
    } else {
        $tests[] = "❌ FAIL: $name";
        $failed++;
    }
}

// Test 1: Table exists
test('TC-1.1: Table project_members exists', Schema::hasTable('project_members'));

// Test 2: Columns exist
$columns = Schema::getColumnListing('project_members');
test('TC-1.2: Column id exists', in_array('id', $columns));
test('TC-1.3: Column project_id exists', in_array('project_id', $columns));
test('TC-1.4: Column user_id exists', in_array('user_id', $columns));
test('TC-1.5: Column role exists', in_array('role', $columns));
test('TC-1.6: Column created_at exists', in_array('created_at', $columns));
test('TC-1.7: Column updated_at exists', in_array('updated_at', $columns));

// Test 3: Role enum values
use App\Models\ProjectMember;
test('TC-4.1: Role "editor" is valid', ProjectMember::isValidRole('editor'));
test('TC-4.2: Role "viewer" is valid', ProjectMember::isValidRole('viewer'));
test('TC-4.3: Invalid role "admin" rejected', !ProjectMember::isValidRole('admin'));

// Test 4: Foreign key constraints (using raw SQL with try-catch)
try {
    DB::table('project_members')->insert([
        'project_id' => 99999999,
        'user_id' => 1,
        'role' => 'viewer'
    ]);
    test('TC-2.5: FK constraint - non-existent project_id rejected', false);
} catch (\Exception $e) {
    test('TC-2.5: FK constraint - non-existent project_id rejected', true);
}

try {
    DB::table('project_members')->insert([
        'project_id' => 1,
        'user_id' => 99999999,
        'role' => 'viewer'
    ]);
    test('TC-2.6: FK constraint - non-existent user_id rejected', false);
} catch (\Exception $e) {
    test('TC-2.6: FK constraint - non-existent user_id rejected', true);
}

// Test 5: Unique constraint
// First, get a valid project and user
$project = DB::table('projects')->first();
$user = DB::table('users')->first();

if ($project && $user) {
    // Clean up any existing test record
    DB::table('project_members')->where('project_id', $project->id)->where('user_id', $user->id)->delete();
    
    // First insert should succeed
    DB::table('project_members')->insert([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'role' => 'viewer'
    ]);
    
    // Second insert with same pair should fail
    try {
        DB::table('project_members')->insert([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'editor'
        ]);
        test('TC-3.2: Unique constraint - duplicate project-user rejected', false);
    } catch (\Exception $e) {
        test('TC-3.2: Unique constraint - duplicate project-user rejected', true);
    }
    
    // Clean up
    DB::table('project_members')->where('project_id', $project->id)->where('user_id', $user->id)->delete();
} else {
    $tests[] = "⚠️ SKIP: No projects/users found for FK tests";
}

// Test 6: Model relationships
try {
    $memberModel = new ProjectMember();
    test('TC-Model: project() relationship exists', method_exists($memberModel, 'project'));
    test('TC-Model: user() relationship exists', method_exists($memberModel, 'user'));
    test('TC-Model: isEditor() method exists', method_exists($memberModel, 'isEditor'));
    test('TC-Model: isViewer() method exists', method_exists($memberModel, 'isViewer'));
} catch (\Exception $e) {
    test('TC-Model: Model relationships check', false);
}

// Test 7: Default role
if ($project && $user) {
    $id = DB::table('project_members')->insertGetId([
        'project_id' => $project->id,
        'user_id' => $user->id
    ]);
    $member = DB::table('project_members')->where('id', $id)->first();
    test('TC-4.4: Default role is "viewer"', $member && $member->role === 'viewer');
    
    // Clean up
    DB::table('project_members')->where('id', $id)->delete();
}

// Output results
echo "\n=== TASK-11-2 Local Test Results ===\n\n";
foreach ($tests as $test) {
    echo "$test\n";
}
echo "\nSummary: $passed passed, $failed failed\n";

exit($failed > 0 ? 1 : 0);
