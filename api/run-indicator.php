<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../seo_logic.php';

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
$indicator = trim($_POST['indicator'] ?? '');

if ($job_id === '' || $indicator === '') {
    json_error('job_id and indicator are required');
}

$conn = get_db_connection_local();
if (!$conn) {
    json_error('Database connection failed', 500);
}

// Validate job belongs to this session
$stmt = $conn->prepare("SELECT session_id, final_url, page_content, cancelled FROM jobs WHERE job_id = ?");
if (!$stmt) { $conn->close(); json_error('Job not found', 404); }
$stmt->bind_param('s', $job_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) { $stmt->close(); $conn->close(); json_error('Job not found', 404); }
$job = $res->fetch_assoc();
$stmt->close();

if (!empty($job['cancelled'])) { $conn->close(); json_error('Job cancelled', 409); }
if ($job['session_id'] !== session_id()) { $conn->close(); json_error('Forbidden', 403); }

// Release session lock to allow concurrent requests
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

// Mark running
$stmtr = $conn->prepare("UPDATE job_results SET status='running', updated_at=CURRENT_TIMESTAMP WHERE job_id=? AND indicator=?");
if ($stmtr) { $stmtr->bind_param('ss', $job_id, $indicator); $stmtr->execute(); $stmtr->close(); }

$started = microtime(true);

// Build minimal context (reuse final URL; fetch content if missing)
$final_url = $job['final_url'];
$page_content = $job['page_content'];
if ($page_content === null || $page_content === '') {
    $ctx = stream_context_create(['http' => ['method'=>'GET','timeout'=>10,'ignore_errors'=>true,'header'=>"User-Agent: SEOIndicatorBot/1.0\r\nAccept: text/html\r\n"]]);
    $page_content = @file_get_contents($final_url, false, $ctx);
    // Persist content for next runs (best-effort)
    if ($page_content !== false) {
        $stmtu = $conn->prepare("UPDATE jobs SET page_content=? WHERE job_id=?");
        if ($stmtu) { $stmtu->bind_param('ss', $page_content, $job_id); $stmtu->execute(); $stmtu->close(); }
    }
}

// Dispatch to existing logic: minimally emulate a context to run one indicator.
// Note: For now, we call the full checker and extract the requested indicator result.
// In a future refactor, split each indicator to its own function.

$results = [];
$error = null;

function parse_dom_context($page_content) {
    $doc = null; $xpath = null;
    if ($page_content) {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        @$doc->loadHTML($page_content);
        libxml_clear_errors();
        $xpath = new DOMXPath($doc);
    }
    return [$doc, $xpath];
}

function compute_indicator_simple($indicator, $final_url, $page_content) {
    // Mock ALL indicators by default - no external calls or complex logic
    return [
        'score' => null,
        'details' => '🔧 MOCKED - FUNCTION CALLED',
        'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
    ];
}

try {
    $results = compute_indicator_simple($indicator, $final_url, $page_content);
    if (!$results || !isset($results['score'])) {
        $error = 'Indicator computation failed - no result returned';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$duration_ms = (int) round((microtime(true) - $started) * 1000);

if ($error) {
    $stmte = $conn->prepare("UPDATE job_results SET status='error', error=?, duration_ms=? WHERE job_id=? AND indicator=?");
    if ($stmte) { $stmte->bind_param('siss', $error, $duration_ms, $job_id, $indicator); $stmte->execute(); $stmte->close(); }
    $conn->close();
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

// Persist success
$stmtok = $conn->prepare("UPDATE job_results SET status='done', score=?, details=?, suggestion=?, error=NULL, duration_ms=? WHERE job_id=? AND indicator=?");
if ($stmtok) {
    $score = $results['score'];
    $details = $results['details'];
    $suggestion = $results['suggestion'];
    $stmtok->bind_param('ississ', $score, $details, $suggestion, $duration_ms, $job_id, $indicator);
    $stmtok->execute();
    $stmtok->close();
}

$conn->close();

echo json_encode(['success' => true, 'job_id' => $job_id, 'indicator' => $indicator, 'result' => $results]);
exit;
?>

