<?php
// Production test script for TASK-8-7: CRUD Endpoints for Projects

require __DIR__ . '/../../../backend/vendor/autoload.php';

$app = require_once __DIR__ . '/../../../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

echo "=== PRODUCTION TEST: TASK-8-7 Project CRUD Endpoints ===\n\n";

// Get first user for testing
$user = User::first();
if (!$user) {
    echo "ERROR: No users found in database\n";
    exit(1);
}

echo "Test User: {$user->email} (ID: {$user->id})\n\n";

// Generate a test token for API calls
$token = $user->createToken('test-token')->plainTextToken;
echo "Auth Token: " . substr($token, 0, 20) . "...\n\n";

// Test results
$results = [
    'passed' => 0,
    'failed' => 0,
    'tests' => []
];

function testEndpoint($name, $method, $url, $expectedStatus, $headers = []) {
    global $results, $token;
    
    $ch = curl_init();
    $fullUrl = "https://seoindicator.com/api/v1" . $url;
    
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ], $headers));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = $httpCode === $expectedStatus ? 'PASS' : 'FAIL';
    if ($status === 'PASS') {
        $results['passed']++;
    } else {
        $results['failed']++;
    }
    
    $results['tests'][] = [
        'name' => $name,
        'status' => $status,
        'expected' => $expectedStatus,
        'actual' => $httpCode,
        'url' => $fullUrl
    ];
    
    echo "[$status] $name: HTTP $httpCode (expected $expectedStatus)\n";
    
    return json_decode($response, true);
}

// ============================================
// AUTHENTICATION TESTS (SEC-001, SEC-002)
// ============================================
echo "--- Authentication Tests ---\n";

// SEC-001: Unauthenticated access returns 401
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = $httpCode === 401 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'SEC-001: Unauthenticated GET returns 401', 'status' => $status, 'expected' => 401, 'actual' => $httpCode];
echo "[$status] SEC-001: Unauthenticated GET returns 401: HTTP $httpCode\n";

// SEC-002: Unauthenticated POST returns 401
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => 'Test']));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = $httpCode === 401 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'SEC-002: Unauthenticated POST returns 401', 'status' => $status, 'expected' => 401, 'actual' => $httpCode];
echo "[$status] SEC-002: Unauthenticated POST returns 401: HTTP $httpCode\n\n";

// ============================================
// CREATE PROJECT TESTS (CP)
// ============================================
echo "--- Create Project Tests ---\n";

// CP-001: Create project with valid data
$createData = testEndpoint('CP-001: Create project with valid data', 'POST', '/projects', 201, [
    'Content-Type: application/json'
]);
// We need to send the body separately
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Test Project ' . Str::random(8),
    'domain' => 'https://example.com',
    'notes' => 'Production test project'
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$createResult = json_decode($response, true);
$createdProjectId = $createResult['data']['id'] ?? null;
$status = $httpCode === 201 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'CP-001: Create project with valid data', 'status' => $status, 'expected' => 201, 'actual' => $httpCode];
echo "[$status] CP-001: Create project with valid data: HTTP $httpCode\n";
if ($createdProjectId) echo "    Created Project ID: $createdProjectId\n";

// CP-004: Create project without name
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = $httpCode === 422 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'CP-004: Create without name returns 422', 'status' => $status, 'expected' => 422, 'actual' => $httpCode];
echo "[$status] CP-004: Create without name returns 422: HTTP $httpCode\n";

// CP-005: Create project with invalid domain
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Test Project',
    'domain' => 'not-a-valid-url'
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = $httpCode === 422 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'CP-005: Create with invalid domain returns 422', 'status' => $status, 'expected' => 422, 'actual' => $httpCode];
echo "[$status] CP-005: Create with invalid domain returns 422: HTTP $httpCode\n\n";

// ============================================
// READ PROJECT TESTS (RP)
// ============================================
echo "--- Read Project Tests ---\n";

// RP-001: List all projects
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$listResult = json_decode($response, true);
$status = $httpCode === 200 && isset($listResult['data']) ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'RP-001: List projects returns 200', 'status' => $status, 'expected' => 200, 'actual' => $httpCode];
echo "[$status] RP-001: List projects returns 200: HTTP $httpCode\n";

// RP-002: List response includes proper structure
$hasMeta = isset($listResult['meta']) || isset($listResult['data']);
$status = $hasMeta ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'RP-002: List response has data array', 'status' => $status, 'expected' => 'data array', 'actual' => $hasMeta ? 'present' : 'missing'];
echo "[$status] RP-002: List response has data array\n";

// RP-004: Get single project
if ($createdProjectId) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/{$createdProjectId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = $httpCode === 200 ? 'PASS' : 'FAIL';
    if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
    $results['tests'][] = ['name' => 'RP-004: Get single project returns 200', 'status' => $status, 'expected' => 200, 'actual' => $httpCode];
    echo "[$status] RP-004: Get single project returns 200: HTTP $httpCode\n";
}

// RP-005: Get non-existent project
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/99999");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = $httpCode === 404 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'RP-005: Get non-existent returns 404', 'status' => $status, 'expected' => 404, 'actual' => $httpCode];
echo "[$status] RP-005: Get non-existent returns 404: HTTP $httpCode\n\n";

// ============================================
// UPDATE PROJECT TESTS (UP)
// ============================================
echo "--- Update Project Tests ---\n";

if ($createdProjectId) {
    // UP-001: Update project name
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/{$createdProjectId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'name' => 'Updated Project Name'
    ]));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = $httpCode === 200 ? 'PASS' : 'FAIL';
    if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
    $results['tests'][] = ['name' => 'UP-001: Update project returns 200', 'status' => $status, 'expected' => 200, 'actual' => $httpCode];
    echo "[$status] UP-001: Update project returns 200: HTTP $httpCode\n";
}

// UP-004: Update non-existent project
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/99999");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => 'Test']));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = $httpCode === 404 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'UP-004: Update non-existent returns 404', 'status' => $status, 'expected' => 404, 'actual' => $httpCode];
echo "[$status] UP-004: Update non-existent returns 404: HTTP $httpCode\n\n";

// ============================================
// ARCHIVE/RESTORE TESTS (AR)
// ============================================
echo "--- Archive/Restore Tests ---\n";

if ($createdProjectId) {
    // AR-001: Archive project
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/{$createdProjectId}/archive");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $archiveResult = json_decode($response, true);
    $isArchived = isset($archiveResult['data']['archived_at']) && $archiveResult['data']['archived_at'] !== null;
    $status = $httpCode === 200 && $isArchived ? 'PASS' : 'FAIL';
    if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
    $results['tests'][] = ['name' => 'AR-001: Archive project works', 'status' => $status, 'expected' => '200 + archived_at set', 'actual' => "HTTP $httpCode, archived=" . ($isArchived ? 'yes' : 'no')];
    echo "[$status] AR-001: Archive project works: HTTP $httpCode\n";
    
    // AR-003: Restore archived project
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/{$createdProjectId}/restore");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $restoreResult = json_decode($response, true);
    $isRestored = isset($restoreResult['data']['archived_at']) && $restoreResult['data']['archived_at'] === null;
    $status = $httpCode === 200 && $isRestored ? 'PASS' : 'FAIL';
    if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
    $results['tests'][] = ['name' => 'AR-003: Restore project works', 'status' => $status, 'expected' => '200 + archived_at null', 'actual' => "HTTP $httpCode, archived=" . ($isRestored ? 'no' : 'yes')];
    echo "[$status] AR-003: Restore project works: HTTP $httpCode\n";
}

// AR-005: Archive non-existent project
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/99999/archive");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = $httpCode === 404 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'AR-005: Archive non-existent returns 404', 'status' => $status, 'expected' => 404, 'actual' => $httpCode];
echo "[$status] AR-005: Archive non-existent returns 404: HTTP $httpCode\n\n";

// ============================================
// DELETE PROJECT TESTS (DP)
// ============================================
echo "--- Delete Project Tests ---\n";

if ($createdProjectId) {
    // DP-002: Delete without confirmation
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/{$createdProjectId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = $httpCode === 400 ? 'PASS' : 'FAIL';
    if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
    $results['tests'][] = ['name' => 'DP-002: Delete without confirm returns 400', 'status' => $status, 'expected' => 400, 'actual' => $httpCode];
    echo "[$status] DP-002: Delete without confirm returns 400: HTTP $httpCode\n";
    
    // DP-001: Delete with confirmation
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/{$createdProjectId}?confirm=true");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = $httpCode === 200 ? 'PASS' : 'FAIL';
    if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
    $results['tests'][] = ['name' => 'DP-001: Delete with confirm returns 200', 'status' => $status, 'expected' => 200, 'actual' => $httpCode];
    echo "[$status] DP-001: Delete with confirm returns 200: HTTP $httpCode\n";
}

// DP-003: Delete non-existent project
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/99999?confirm=true");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = $httpCode === 404 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'DP-003: Delete non-existent returns 404', 'status' => $status, 'expected' => 404, 'actual' => $httpCode];
echo "[$status] DP-003: Delete non-existent returns 404: HTTP $httpCode\n\n";

// ============================================
// SECURITY TESTS (SEC-003, SEC-004)
// ============================================
echo "--- Security Tests ---\n";

// SEC-003: SQL injection in project name
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => "'; DROP TABLE projects;--",
    'domain' => 'https://test.com'
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Should create successfully (stored as string, not executed)
$status = $httpCode === 201 ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'SEC-003: SQL injection stored as string', 'status' => $status, 'expected' => 201, 'actual' => $httpCode];
echo "[$status] SEC-003: SQL injection stored as string: HTTP $httpCode\n";

// Clean up the SQL injection test project
if ($httpCode === 201) {
    $result = json_decode($response, true);
    $sqlInjectProjectId = $result['data']['id'] ?? null;
    if ($sqlInjectProjectId) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/{$sqlInjectProjectId}?confirm=true");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_exec($ch);
        curl_close($ch);
        echo "    Cleaned up SQL injection test project\n";
    }
}

// SEC-004: Mass assignment protection
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Mass Assignment Test',
    'user_id' => 99999  // Try to assign to different user
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
$actualUserId = $result['data']['user_id'] ?? null;
// Should be created with the authenticated user's ID, not 99999
$status = $httpCode === 201 && $actualUserId === $user->id ? 'PASS' : 'FAIL';
if ($status === 'PASS') { $results['passed']++; } else { $results['failed']++; }
$results['tests'][] = ['name' => 'SEC-004: Mass assignment blocked', 'status' => $status, 'expected' => "user_id={$user->id}", 'actual' => "user_id={$actualUserId}"];
echo "[$status] SEC-004: Mass assignment blocked: HTTP $httpCode (user_id={$actualUserId})\n";

// Clean up
if ($httpCode === 201 && isset($result['data']['id'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://seoindicator.com/api/v1/projects/{$result['data']['id']}?confirm=true");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_exec($ch);
    curl_close($ch);
    echo "    Cleaned up mass assignment test project\n";
}

// ============================================
// SUMMARY
// ============================================
echo "\n=== TEST SUMMARY ===\n";
echo "Passed: {$results['passed']}\n";
echo "Failed: {$results['failed']}\n";
echo "Total: " . ($results['passed'] + $results['failed']) . "\n";

// Clean up test token
$user->tokens()->where('name', 'test-token')->delete();

echo "\nTest token cleaned up.\n";

exit($results['failed'] > 0 ? 1 : 0);
