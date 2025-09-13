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

$domain_input = trim($_POST['domain'] ?? '');
if ($domain_input === '') {
    json_error('Domain is required');
}

// Indicators (keep in sync with main app)
$indicators = [
    'url', 'title', 'meta_description', 'robots', 'sitemap', 'h1', 'filenames', 'image_alt',
    'links', '404_page', 'mobile_friendly', 'language', 'favicon', 'canonical',
    'social_meta_tags', 'aria', 'page_speed'
];

$conn = get_db_connection_local();
if (!$conn) {
    json_error('Database connection failed', 500);
}

// One-request-per-minute rate limit per IP for job start
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
// Grace window: if we just started a job in the last 5 seconds, skip DB rate-limit check
$just_started_recently = isset($_SESSION['last_job_started_at']) && (time() - (int)$_SESSION['last_job_started_at'] < 5);
if (!$just_started_recently && $ip !== 'UNKNOWN' && $ip !== '::1') {
    $stmt = $conn->prepare("SELECT last_request_time FROM ip_requests_log WHERE ip_address = ?");
    if ($stmt) {
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $last_ts = strtotime($row['last_request_time']);
            if ($last_ts > (time() - 60)) {
                $stmt->close();
                $conn->close();
                json_error('Rate limit: 1 request per minute');
            }
            $stmt->close();
            $stmtu = $conn->prepare("UPDATE ip_requests_log SET last_request_time = CURRENT_TIMESTAMP WHERE ip_address = ?");
            if ($stmtu) { $stmtu->bind_param('s', $ip); $stmtu->execute(); $stmtu->close(); }
        } else {
            if ($res) { $res->close(); }
            $stmti = $conn->prepare("INSERT INTO ip_requests_log (ip_address, last_request_time) VALUES (?, CURRENT_TIMESTAMP)");
            if ($stmti) { $stmti->bind_param('s', $ip); $stmti->execute(); $stmti->close(); }
        }
    }
}

// Create job
try {
    $job_id = bin2hex(random_bytes(16));
} catch (Exception $e) {
    $job_id = uniqid('job_', true);
}

$session_id = session_id();

// Normalize domain minimally
$normalized_domain = $domain_input;
if (!preg_match('/^https?:\/\//i', $normalized_domain)) {
    $normalized_domain = 'https://' . $normalized_domain;
}

// Attempt to resolve a final URL (best-effort, non-fatal)
$final_url = $normalized_domain;
@ini_set('user_agent', 'SEOIndicatorBot/1.0');
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'ignore_errors' => true,
        'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
    ]
]);
$headers = @get_headers($final_url, 1, $context);
if (!$headers && stripos($final_url, 'https://') === 0) {
    // try http fallback
    $alt = 'http://' . preg_replace('/^https?:\/\//i', '', $final_url);
    $headers = @get_headers($alt, 1, $context);
    if ($headers) { $final_url = $alt; }
}

// Store job (page_content left null for now; can be fetched by indicator runs later if needed)
$stmt_job = $conn->prepare("INSERT INTO jobs (job_id, session_id, domain, normalized_domain, final_url) VALUES (?, ?, ?, ?, ?)");
if ($stmt_job) {
    $stmt_job->bind_param('sssss', $job_id, $session_id, $domain_input, $normalized_domain, $final_url);
    $stmt_job->execute();
    $stmt_job->close();
}

// Seed job_results as pending
$stmt_res = $conn->prepare("INSERT INTO job_results (job_id, indicator, status) VALUES (?, ?, 'pending')");
if ($stmt_res) {
    foreach ($indicators as $ind) {
        $stmt_res->bind_param('ss', $job_id, $ind);
        $stmt_res->execute();
    }
    $stmt_res->close();
}

$conn->close();

$_SESSION['last_job_started_at'] = time();

echo json_encode([
    'success' => true,
    'job_id' => $job_id,
    'indicators' => $indicators,
    'final_url' => $final_url
]);
exit;
?>

