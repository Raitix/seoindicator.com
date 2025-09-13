<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

function get_db_connection_local() {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_errno) {
        return null;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$job_id = trim($_POST['job_id'] ?? '');
if ($job_id === '') { json_error('job_id is required'); }

$conn = get_db_connection_local();
if (!$conn) { json_error('Database connection failed', 500); }

$stmt = $conn->prepare("UPDATE jobs SET cancelled=1 WHERE job_id=? AND session_id=?");
if ($stmt) {
    $sid = session_id();
    $stmt->bind_param('ss', $job_id, $sid);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => $affected > 0, 'job_id' => $job_id]);
    exit;
}

$conn->close();
json_error('Failed to cancel job', 500);
?>

