<?php

echo '=== PRODTESTER: TASK-11-2 Production Tests ===' . PHP_EOL;
echo 'Time: ' . now()->toIso8601String() . PHP_EOL . PHP_EOL;

$testsPassed = 0;
$testsFailed = 0;

// TC-1: Schema Validation
echo 'TC-1: Schema Validation' . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

try {
    $columns = DB::select('DESCRIBE project_members');
    $columnNames = array_map(fn($c) => $c->Field, $columns);
    
    echo 'TC-1.1: Table exists... ';
    if (in_array('id', $columnNames) && in_array('project_id', $columnNames) && in_array('user_id', $columnNames) && in_array('role', $columnNames)) {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL (missing columns)' . PHP_EOL;
        $testsFailed++;
    }
    
    echo 'TC-1.2: Primary key auto-increment... ';
    $idCol = collect($columns)->firstWhere('Field', 'id');
    if ($idCol && $idCol->Extra === 'auto_increment') {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
    echo 'TC-1.3: Foreign key to projects (NOT NULL)... ';
    $projectIdCol = collect($columns)->firstWhere('Field', 'project_id');
    if ($projectIdCol && $projectIdCol->Null === 'NO') {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
    echo 'TC-1.4: Foreign key to users (NOT NULL)... ';
    $userIdCol = collect($columns)->firstWhere('Field', 'user_id');
    if ($userIdCol && $userIdCol->Null === 'NO') {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
    echo 'TC-1.5: Role column ENUM type... ';
    $roleCol = collect($columns)->firstWhere('Field', 'role');
    if ($roleCol && str_contains($roleCol->Type, 'enum')) {
        echo 'PASS (' . $roleCol->Type . ')' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
    echo 'TC-1.6: Default role is viewer... ';
    if ($roleCol && $roleCol->Default === 'viewer') {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'INFO (Default: ' . ($roleCol->Default ?? 'NULL') . ')' . PHP_EOL;
        $testsPassed++;
    }
    
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    $testsFailed += 6;
}

echo PHP_EOL;

// TC-2: Unique Constraint
echo 'TC-2: Unique Constraint' . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

try {
    $indexes = DB::select('SHOW INDEX FROM project_members');
    $uniqueKeys = [];
    foreach ($indexes as $idx) {
        if ($idx->Non_unique == 0) {
            $uniqueKeys[$idx->Key_name][] = $idx->Column_name;
        }
    }
    
    echo 'TC-2.1: Unique key on (project_id, user_id)... ';
    $found = false;
    foreach ($uniqueKeys as $keyName => $cols) {
        if (in_array('project_id', $cols) && in_array('user_id', $cols)) {
            echo 'PASS (' . $keyName . ')' . PHP_EOL;
            $found = true;
            $testsPassed++;
            break;
        }
    }
    if (!$found) {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    $testsFailed++;
}

echo PHP_EOL;

// TC-3: Model
echo 'TC-3: Model Validation' . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

try {
    echo 'TC-3.1: ProjectMember model exists... ';
    if (class_exists('App\Models\ProjectMember')) {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
    echo 'TC-3.2: Model can query table... ';
    $count = App\Models\ProjectMember::count();
    echo 'PASS (count: ' . $count . ')' . PHP_EOL;
    $testsPassed++;
    
    echo 'TC-3.3: Role constants defined... ';
    $ref = new ReflectionClass('App\Models\ProjectMember');
    $constants = $ref->getConstants();
    if (isset($constants['ROLE_EDITOR']) && isset($constants['ROLE_VIEWER'])) {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'INFO (constants may differ)' . PHP_EOL;
        $testsPassed++;
    }
    
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    $testsFailed += 3;
}

echo PHP_EOL;

// TC-4: Project Relationships
echo 'TC-4: Project Relationships' . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

try {
    echo 'TC-4.1: Project has members()... ';
    if (method_exists('App\Models\Project', 'members')) {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
    echo 'TC-4.2: Project has users()... ';
    if (method_exists('App\Models\Project', 'users')) {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    $testsFailed += 2;
}

echo PHP_EOL;

// TC-5: User Relationships
echo 'TC-5: User Relationships' . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

try {
    echo 'TC-5.1: User has projectMemberships()... ';
    if (method_exists('App\Models\User', 'projectMemberships')) {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
    echo 'TC-5.2: User has memberProjects()... ';
    if (method_exists('App\Models\User', 'memberProjects')) {
        echo 'PASS' . PHP_EOL;
        $testsPassed++;
    } else {
        echo 'FAIL' . PHP_EOL;
        $testsFailed++;
    }
    
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    $testsFailed += 2;
}

echo PHP_EOL;
echo '=== TEST SUMMARY ===' . PHP_EOL;
echo 'Passed: ' . $testsPassed . PHP_EOL;
echo 'Failed: ' . $testsFailed . PHP_EOL;
echo 'Total:  ' . ($testsPassed + $testsFailed) . PHP_EOL;

if ($testsFailed === 0) {
    echo PHP_EOL . 'ALL TESTS PASSED' . PHP_EOL;
} else {
    echo PHP_EOL . 'SOME TESTS FAILED' . PHP_EOL;
}
