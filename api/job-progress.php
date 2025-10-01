<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

function json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$job_id = trim($_GET['job_id'] ?? '');
if ($job_id === '') {
    json_error('job_id is required');
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    json_error('Database connection failed', 500);
}
$conn->set_charset('utf8mb4');

// Get job info
$stmt = $conn->prepare("SELECT session_id FROM jobs WHERE job_id=?");
if (!$stmt) {
    $conn->close();
    json_error('Job not found', 404);
}
$stmt->bind_param('s', $job_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    $stmt->close();
    $conn->close();
    json_error('Job not found', 404);
}
$job = $res->fetch_assoc();
$stmt->close();

// Verify session
if ($job['session_id'] !== session_id()) {
    $conn->close();
    json_error('Forbidden', 403);
}

// Get results
$results = [];
$done = 0;
$total = 0;
$achieved = 0;
$possible = 0;

$q = $conn->prepare("SELECT indicator, status, score, details, suggestion FROM job_results WHERE job_id=?");
if ($q) {
    $q->bind_param('s', $job_id);
    $q->execute();
    $rs = $q->get_result();
    while ($row = $rs->fetch_assoc()) {
        $results[$row['indicator']] = [
            'status' => $row['status'],
            'score' => is_null($row['score']) ? null : (int)$row['score'],
            'details' => $row['details'],
            'suggestion' => $row['suggestion']
        ];
        $total++;
        if ($row['status'] === 'done') {
            $done++;
            if (is_numeric($row['score'])) {
                $achieved += (int)$row['score'];
            }
            $possible += 3;
        }
    }
    $q->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'job_id' => $job_id,
    'results' => $results,
    'progress' => ['done' => $done, 'total' => $total],
    'overall' => ['achieved' => $achieved, 'possible' => $possible]
]);
exit;
?>
