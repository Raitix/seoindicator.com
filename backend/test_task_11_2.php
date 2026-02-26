#!/usr/bin/env php
<?php
/**
 * Test script for TASK-11-2: Project Members Table
 * Validates schema, constraints, and CRUD operations
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TASK-11-2: Project Members Table Tests ===\n\n";

$passed = 0;
$failed = 0;
$tests = [];

// Helper function
function runTest($name, $callback) {
    global $passed, $failed, $tests;
    echo "Testing: $name... ";
    try {
        $result = $callback();
        if ($result === true) {
            echo "PASS\n";
            $passed++;
            $tests[] = ['name' => $name, 'result' => 'PASS'];
        } else {
            echo "FAIL: $result\n";
            $failed++;
            $tests[] = ['name' => $name, 'result' => 'FAIL', 'error' => $result];
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $failed++;
        $tests[] = ['name' => $name, 'result' => 'ERROR', 'error' => $e->getMessage()];
    }
}

function setupTestData() {
    // Clean up any existing test data
    DB::table('project_members')->where('project_id', '>=', 9000)->delete();
    DB::table('project_members')->where('user_id', '>=', 9000)->delete();
    DB::table('projects')->where('id', '>=', 9000)->delete();
    DB::table('users')->where('id', '>=', 9000)->delete();
    
    // Create test projects
    DB::table('projects')->insert([
        ['id' => 9001, 'name' => 'Test Project 1', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9002, 'name' => 'Test Project 2', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9003, 'name' => 'Test Project 3', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9004, 'name' => 'Test Project 4', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9005, 'name' => 'Test Project 5', 'created_at' => now(), 'updated_at' => now()],
    ]);
    
    // Create test users
    DB::table('users')->insert([
        ['id' => 9001, 'name' => 'Test User 1', 'email' => 'test9001@example.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9002, 'name' => 'Test User 2', 'email' => 'test9002@example.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9003, 'name' => 'Test User 3', 'email' => 'test9003@example.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()],
    ]);
}

function cleanupTestData() {
    DB::table('project_members')->where('project_id', '>=', 9000)->delete();
    DB::table('project_members')->where('user_id', '>=', 9000)->delete();
    DB::table('projects')->where('id', '>=', 9000)->delete();
    DB::table('users')->where('id', '>=', 9000)->delete();
}

// Initial setup
setupTestData();

// ============================================
// TC-1.x: Schema Validation Tests
// ============================================

runTest("TC-1.1: Table exists", function() {
    return Schema::hasTable('project_members') ? true : "Table not found";
});

runTest("TC-1.2: Primary key on id", function() {
    $columns = DB::select("SHOW COLUMNS FROM project_members WHERE Field = 'id'");
    return ($columns[0]->Key === 'PRI') ? true : "id is not primary key";
});

runTest("TC-1.3: Auto-increment on id", function() {
    $columns = DB::select("SHOW COLUMNS FROM project_members WHERE Field = 'id'");
    return (str_contains($columns[0]->Extra, 'auto_increment')) ? true : "id is not auto-increment";
});

runTest("TC-1.4: Role default is 'viewer'", function() {
    $columns = DB::select("SHOW COLUMNS FROM project_members WHERE Field = 'role'");
    return ($columns[0]->Default === 'viewer') ? true : "Default is not 'viewer'";
});

runTest("TC-1.5: All required columns exist", function() {
    $required = ['id', 'project_id', 'user_id', 'role', 'created_at', 'updated_at'];
    $existing = Schema::getColumnListing('project_members');
    $missing = array_diff($required, $existing);
    return empty($missing) ? true : "Missing columns: " . implode(', ', $missing);
});

// ============================================
// TC-2.x: Foreign Key Constraint Tests
// ============================================

runTest("TC-2.1: Foreign key to projects exists", function() {
    $fks = DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'project_members' 
        AND COLUMN_NAME = 'project_id' 
        AND REFERENCED_TABLE_NAME = 'projects'");
    return !empty($fks) ? true : "FK to projects not found";
});

runTest("TC-2.2: Foreign key to users exists", function() {
    $fks = DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'project_members' 
        AND COLUMN_NAME = 'user_id' 
        AND REFERENCED_TABLE_NAME = 'users'");
    return !empty($fks) ? true : "FK to users not found";
});

runTest("TC-2.5: Insert with non-existent project_id fails", function() {
    try {
        DB::table('project_members')->insert(['project_id' => 99999, 'user_id' => 9001]);
        return "FK constraint not enforced - insert succeeded";
    } catch (Exception $e) {
        return true; // Expected to fail
    }
});

runTest("TC-2.6: Insert with non-existent user_id fails", function() {
    try {
        DB::table('project_members')->insert(['project_id' => 9001, 'user_id' => 99999]);
        return "FK constraint not enforced - insert succeeded";
    } catch (Exception $e) {
        return true; // Expected to fail
    }
});

// ============================================
// TC-3.x: Unique Constraint Tests
// ============================================

runTest("TC-3.1: Unique constraint on (project_id, user_id)", function() {
    $indexes = DB::select("SHOW INDEX FROM project_members WHERE Key_name = 'idx_project_user'");
    return !empty($indexes) ? true : "Unique index idx_project_user not found";
});

runTest("TC-3.2: Duplicate project-user pair rejected", function() {
    // First insert should succeed
    DB::table('project_members')->insert(['project_id' => 9001, 'user_id' => 9001, 'role' => 'viewer']);
    
    // Second insert with same pair should fail
    try {
        DB::table('project_members')->insert(['project_id' => 9001, 'user_id' => 9001, 'role' => 'editor']);
        return "Duplicate allowed - unique constraint not working";
    } catch (Exception $e) {
        return true; // Expected to fail
    }
});

runTest("TC-3.3: Same user can be in multiple projects", function() {
    $r1 = DB::table('project_members')->insert(['project_id' => 9002, 'user_id' => 9001, 'role' => 'viewer']);
    $r2 = DB::table('project_members')->insert(['project_id' => 9003, 'user_id' => 9001, 'role' => 'editor']);
    return ($r1 && $r2) ? true : "Failed to insert same user in multiple projects";
});

runTest("TC-3.4: Multiple users can be in same project", function() {
    $r1 = DB::table('project_members')->insert(['project_id' => 9004, 'user_id' => 9002, 'role' => 'viewer']);
    $r2 = DB::table('project_members')->insert(['project_id' => 9004, 'user_id' => 9003, 'role' => 'editor']);
    return ($r1 && $r2) ? true : "Failed to insert multiple users in same project";
});

// ============================================
// TC-4.x: Role Enum Tests
// ============================================

runTest("TC-4.1: Role 'editor' is valid", function() {
    $id = DB::table('project_members')->insertGetId(['project_id' => 9005, 'user_id' => 9002, 'role' => 'editor']);
    return $id > 0 ? true : "Failed to insert with role 'editor'";
});

runTest("TC-4.2: Role 'viewer' is valid", function() {
    $id = DB::table('project_members')->insertGetId(['project_id' => 9005, 'user_id' => 9003, 'role' => 'viewer']);
    return $id > 0 ? true : "Failed to insert with role 'viewer'";
});

runTest("TC-4.4: Default role is 'viewer'", function() {
    $id = DB::table('project_members')->insertGetId(['project_id' => 9002, 'user_id' => 9002]);
    $record = DB::table('project_members')->where('id', $id)->first();
    return ($record && $record->role === 'viewer') ? true : "Default role is not 'viewer'";
});

// ============================================
// TC-5.x: CRUD Operations Tests
// ============================================

runTest("TC-5.1: Insert valid record", function() {
    $id = DB::table('project_members')->insertGetId(['project_id' => 9003, 'user_id' => 9002, 'role' => 'editor']);
    return $id > 0 ? true : "Insert failed";
});

runTest("TC-5.2: Update role", function() {
    $affected = DB::table('project_members')
        ->where('project_id', 9003)
        ->where('user_id', 9002)
        ->update(['role' => 'viewer']);
    return $affected > 0 ? true : "Update failed";
});

runTest("TC-5.3: Delete record", function() {
    $deleted = DB::table('project_members')
        ->where('project_id', 9003)
        ->where('user_id', 9002)
        ->delete();
    return $deleted > 0 ? true : "Delete failed";
});

runTest("TC-5.4: Select by project_id", function() {
    // Insert test records first
    DB::table('project_members')->insert([
        ['project_id' => 9001, 'user_id' => 9002, 'role' => 'viewer', 'created_at' => now(), 'updated_at' => now()],
        ['project_id' => 9001, 'user_id' => 9003, 'role' => 'editor', 'created_at' => now(), 'updated_at' => now()],
    ]);
    $records = DB::table('project_members')->where('project_id', 9001)->get();
    return $records->count() >= 2 ? true : "Expected at least 2 records, got " . $records->count();
});

runTest("TC-5.5: Select by user_id", function() {
    // User 9001 is in projects 9001, 9002, 9003
    $records = DB::table('project_members')->where('user_id', 9001)->get();
    return $records->count() >= 2 ? true : "Expected at least 2 records, got " . $records->count();
});

// ============================================
// TC-2.3 & TC-2.4: Cascade Delete Tests
// ============================================

runTest("TC-2.3: ON DELETE CASCADE for project_id", function() {
    // Create temp project with member
    DB::table('projects')->insert(['id' => 9991, 'name' => 'Cascade Test Project', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('project_members')->insert(['project_id' => 9991, 'user_id' => 9001, 'role' => 'viewer', 'created_at' => now(), 'updated_at' => now()]);
    
    // Verify member exists
    $before = DB::table('project_members')->where('project_id', 9991)->count();
    if ($before === 0) return "Setup failed - member not created";
    
    // Delete project
    DB::table('projects')->where('id', 9991)->delete();
    
    // Verify member is gone
    $after = DB::table('project_members')->where('project_id', 9991)->count();
    return $after === 0 ? true : "Member not deleted when project deleted";
});

runTest("TC-2.4: ON DELETE CASCADE for user_id", function() {
    // Create temp user with membership
    DB::table('users')->insert(['id' => 9991, 'name' => 'Cascade User', 'email' => 'cascade9991@example.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('project_members')->insert(['project_id' => 9001, 'user_id' => 9991, 'role' => 'viewer', 'created_at' => now(), 'updated_at' => now()]);
    
    // Verify member exists
    $before = DB::table('project_members')->where('user_id', 9991)->count();
    if ($before === 0) return "Setup failed - member not created";
    
    // Delete user
    DB::table('users')->where('id', 9991)->delete();
    
    // Verify member is gone
    $after = DB::table('project_members')->where('user_id', 9991)->count();
    return $after === 0 ? true : "Member not deleted when user deleted";
});

// ============================================
// Model Tests
// ============================================

runTest("Model: ProjectMember class exists", function() {
    return class_exists(\App\Models\ProjectMember::class) ? true : "ProjectMember model not found";
});

runTest("Model: Valid roles constant", function() {
    $roles = \App\Models\ProjectMember::getValidRoles();
    return ($roles === ['editor', 'viewer']) ? true : "Invalid roles: " . implode(', ', $roles);
});

runTest("Model: isValidRole method", function() {
    return (\App\Models\ProjectMember::isValidRole('editor') && \App\Models\ProjectMember::isValidRole('viewer') && !\App\Models\ProjectMember::isValidRole('admin')) ? true : "Role validation failed";
});

runTest("Model: Relationship to Project", function() {
    $member = \App\Models\ProjectMember::first();
    if (!$member) return "No project_members record found";
    try {
        $project = $member->project;
        return ($project && $project instanceof \App\Models\Project) ? true : "Project relationship failed";
    } catch (Exception $e) {
        return "Relationship error: " . $e->getMessage();
    }
});

runTest("Model: Relationship to User", function() {
    $member = \App\Models\ProjectMember::first();
    if (!$member) return "No project_members record found";
    try {
        $user = $member->user;
        return ($user && $user instanceof \App\Models\User) ? true : "User relationship failed";
    } catch (Exception $e) {
        return "Relationship error: " . $e->getMessage();
    }
});

// ============================================
// Cleanup
// ============================================

cleanupTestData();

// ============================================
// Summary
// ============================================

echo "\n=== Test Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\nFailed tests:\n";
    foreach ($tests as $test) {
        if ($test['result'] !== 'PASS') {
            echo "  - {$test['name']}: {$test['result']}" . (isset($test['error']) ? " - {$test['error']}" : "") . "\n";
        }
    }
}

exit($failed > 0 ? 1 : 0);
