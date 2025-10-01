<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../seo_logic.php';

function json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$domain_input = trim($_POST['domain'] ?? '');
if ($domain_input === '') {
    json_error('Domain is required');
}

// Normalize domain
if (!preg_match('/^https?:\/\//i', $domain_input)) {
    $domain_input = 'https://' . $domain_input;
}

// Generate job ID
try {
    $job_id = bin2hex(random_bytes(16));
} catch (Exception $e) {
    $job_id = uniqid('job_', true);
}

$session_id = session_id();

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    json_error('Database connection failed', 500);
}
$conn->set_charset('utf8mb4');

// Rate limiting (1 request per minute per IP)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
if ($ip !== 'UNKNOWN' && $ip !== '::1') {
    $stmt = $conn->prepare("SELECT last_request_time FROM ip_requests_log WHERE ip_address = ?");
    if ($stmt) {
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (strtotime($row['last_request_time']) > (time() - 60)) {
                $stmt->close();
                $conn->close();
                json_error('Rate limit: 1 request per minute');
            }
            $stmt->close();
            $stmt2 = $conn->prepare("UPDATE ip_requests_log SET last_request_time = CURRENT_TIMESTAMP WHERE ip_address = ?");
            if ($stmt2) { $stmt2->bind_param('s', $ip); $stmt2->execute(); $stmt2->close(); }
        } else {
            if ($res) $res->close();
            $stmt2 = $conn->prepare("INSERT INTO ip_requests_log (ip_address, last_request_time) VALUES (?, CURRENT_TIMESTAMP)");
            if ($stmt2) { $stmt2->bind_param('s', $ip); $stmt2->execute(); $stmt2->close(); }
        }
    }
}

// Fetch page content
$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true,
        'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
    ]
]);

$page_content = @file_get_contents($domain_input, false, $ctx);
if ($page_content === false) {
    // Try http fallback
    $alt_url = 'http://' . preg_replace('/^https?:\/\//i', '', $domain_input);
    $page_content = @file_get_contents($alt_url, false, $ctx);
    if ($page_content !== false) {
        $domain_input = $alt_url;
    }
}

$final_url = $domain_input;

// Store job
$stmt = $conn->prepare("INSERT INTO jobs (job_id, session_id, domain, final_url, page_content) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param('sssss', $job_id, $session_id, $domain_input, $final_url, $page_content);
    $stmt->execute();
    $stmt->close();
}

// Indicators list
$indicators = [
    'url', 'title', 'meta_description', 'robots', 'sitemap', 'h1', 'filenames', 'image_alt',
    'links', '404_page', 'mobile_friendly', 'language', 'favicon', 'canonical',
    'social_meta_tags', 'aria', 'page_speed'
];

// Initialize all indicators as pending
$stmt = $conn->prepare("INSERT INTO job_results (job_id, indicator, status) VALUES (?, ?, 'pending')");
if ($stmt) {
    foreach ($indicators as $ind) {
        $stmt->bind_param('ss', $job_id, $ind);
        $stmt->execute();
    }
    $stmt->close();
}

// Analyze all indicators with ONE OpenRouter API call
$analysis_results = analyze_all_indicators_single_call($final_url, $page_content, $conn);

// Store all results
$stmt = $conn->prepare("UPDATE job_results SET status='done', score=?, details=?, suggestion=? WHERE job_id=? AND indicator=?");
if ($stmt) {
    foreach ($analysis_results as $ind => $result) {
        $score = $result['score'];
        $details = $result['details'];
        $suggestion = $result['suggestion'];
        $stmt->bind_param('issss', $score, $details, $suggestion, $job_id, $ind);
        $stmt->execute();
    }
    $stmt->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'job_id' => $job_id,
    'final_url' => $final_url,
    'indicators' => $indicators
]);
exit;
?>
