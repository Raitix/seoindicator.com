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
    [$doc, $xpath] = parse_dom_context($page_content);
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];

    switch ($indicator) {
        case 'title':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $nodes = $xpath->query('//title');
            $title = $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : '';
            if ($title === '') { $result['score']=1; $result['details']='Missing <title>'; $result['suggestion']='Add a concise, descriptive title (50–60 chars).'; }
            else {
                $len = mb_strlen($title);
                $score = ($len>=50 && $len<=60) ? 3 : (($len>=30 && $len<=70)?2:2);
                $result['score']=$score; $result['details']=$title; $result['suggestion']='Keep titles unique and include primary keyword near the start.';
            }
            break;

        case 'meta_description':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $nodes = $xpath->query('//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="description"]');
            $desc = '';
            if ($nodes && $nodes->length) { $desc = trim($nodes->item(0)->getAttribute('content')); }
            if ($desc === '') { $result['score']=1; $result['details']='Missing meta description'; $result['suggestion']='Add a compelling summary (120–160 chars) with a call-to-action.'; }
            else {
                $len = mb_strlen($desc);
                $score = ($len>=120 && $len<=160) ? 3 : (($len>=90 && $len<=200)?2:2);
                $result['score']=$score; $result['details']=$desc; $result['suggestion']='Ensure it’s unique per page and matches search intent.';
            }
            break;

        case 'robots':
            $base = parse_url($final_url);
            if (!$base || !isset($base['scheme'],$base['host'])) { $result['score']=1; $result['details']='Invalid URL'; $result['suggestion']='Provide a valid URL.'; break; }
            $robots = $base['scheme'].'://'.$base['host'].'/robots.txt';
            $ctx = stream_context_create(['http'=>['method'=>'GET','timeout'=>7,'ignore_errors'=>true,'header'=>"User-Agent: SEOIndicatorBot/1.0\r\n"]]);
            $headers = @get_headers($robots, true, $ctx);
            $ok = is_array($headers) && isset($headers[0]) && preg_match('/\s200\s/',$headers[0]);
            if ($ok) { $content = @file_get_contents($robots,false,$ctx); $len = $content!==false?strlen($content):0; $result['score']=3; $result['details']='robots.txt OK ('.($len).' bytes)'; $result['suggestion']='Ensure it references your sitemap.'; }
            else { $result['score']=1; $result['details']='robots.txt not accessible'; $result['suggestion']='Add robots.txt at /robots.txt and allow important paths.'; }
            break;

        case 'sitemap':
            $base = parse_url($final_url);
            if (!$base || !isset($base['scheme'],$base['host'])) { $result['score']=1; $result['details']='Invalid URL'; $result['suggestion']='Provide a valid URL.'; break; }
            $smap = $base['scheme'].'://'.$base['host'].'/sitemap.xml';
            $ctx = stream_context_create(['http'=>['method'=>'GET','timeout'=>7,'ignore_errors'=>true,'header'=>"User-Agent: SEOIndicatorBot/1.0\r\n"]]);
            $headers = @get_headers($smap, true, $ctx);
            $ok = is_array($headers) && isset($headers[0]) && preg_match('/\s200\s/',$headers[0]);
            if ($ok) { $result['score']=3; $result['details']='sitemap.xml accessible'; $result['suggestion']='Submit sitemap in Google Search Console.'; }
            else { $result['score']=1; $result['details']='sitemap.xml not accessible'; $result['suggestion']='Create sitemap.xml and reference it from robots.txt.'; }
            break;

        case 'h1':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $h1s = $xpath->query('//h1'); $cnt = $h1s? $h1s->length:0;
            if ($cnt===1) { $result['score']=3; $result['details']='One H1 found'; $result['suggestion']='Keep H1 unique and descriptive.'; }
            else if ($cnt>1) { $result['score']=2; $result['details']="$cnt H1 elements"; $result['suggestion']='Prefer a single H1; use H2/H3 for sections.'; }
            else { $result['score']=1; $result['details']='No H1'; $result['suggestion']='Add a single H1 summarizing the page topic.'; }
            break;

        case 'image_alt':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $imgs = $xpath->query('//img'); $total = $imgs? $imgs->length:0; $with = 0; if ($imgs) {
                foreach ($imgs as $img) { $alt = trim((string)$img->getAttribute('alt')); if ($alt !== '') $with++; }
            }
            if ($total===0) { $result['score']=2; $result['details']='No images'; $result['suggestion']='Add descriptive alt when adding images.'; }
            else { $ratio = $with/ max(1,$total); $score = $ratio>=0.9?3:($ratio>=0.6?2:1); $result['score']=$score; $result['details']="$with/$total images have alt"; $result['suggestion']='Ensure alt text is meaningful; omit for decorative images (alt="").'; }
            break;

        case 'links':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $as = $xpath->query('//a'); $total = $as? $as->length:0; $good=0; if ($as){ foreach ($as as $a){ $t = trim($a->textContent); if ($t!=='' && strcasecmp($t,'click here')!==0) $good++; } }
            if ($total===0) { $result['score']=2; $result['details']='No links'; $result['suggestion']='Add contextual internal links to related content.'; }
            else { $ratio = $good/ max(1,$total); $score = $ratio>=0.8?3:($ratio>=0.5?2:1); $result['score']=$score; $result['details']="$good/$total links have descriptive anchor text"; $result['suggestion']='Use descriptive anchors; avoid “click here”.'; }
            break;

        case 'url':
            $u = $final_url; $okhttps = stripos($u,'https://')===0; $oklen = strlen($u)<=100; $okchars = (bool)!preg_match('/[\s]/',$u);
            $score = ($okhttps + $oklen + $okchars);
            $result['score'] = $score>=3?3:($score===2?2:1);
            $result['details'] = ($okhttps?'✓ HTTPS':'✗ HTTPS').', '.($oklen?'✓ length':'✗ length>100').', '.($okchars?'✓ no spaces':'✗ spaces');
            $result['suggestion'] = 'Use HTTPS, keep URLs short (<100 chars), avoid spaces/special characters.';
            break;

        case 'page_speed':
            $resp = call_pagespeed_insights_api($final_url);
            if ($resp && $resp['success'] && isset($resp['data']['lighthouseResult']['categories']['performance']['score'])) {
                $perf = round($resp['data']['lighthouseResult']['categories']['performance']['score']*100);
                $score = $perf>=80?3:($perf>=60?2:1);
                $result['score']=$score; $result['details']='Performance ' . $perf . '/100'; $result['suggestion']='Review Lighthouse opportunities to improve loading speed.';
            } else {
                $errorMsg = isset($resp['message']) ? $resp['message'] : 'API call failed';
                $result['score']=1; $result['details']='PageSpeed API error: '.$errorMsg; $result['suggestion']='Ensure API key configured and URL accessible.';
            }
            break;

        case 'filenames':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $files = [];
            $imgs = $xpath->query('//img[@src]'); if ($imgs){ foreach ($imgs as $img){ $files[] = $img->getAttribute('src'); } }
            $links = $xpath->query('//a[@href]'); if ($links){ foreach ($links as $a){ $href = $a->getAttribute('href'); if (preg_match('/\.(pdf|docx?|xlsx?|pptx?|zip|rar|jpg|jpeg|png|gif|svg|mp3|mp4|webm)$/i',$href)) { $files[]=$href; } } }
            $bad = 0; $total = count($files);
            foreach ($files as $f) {
                $name = basename(parse_url($f, PHP_URL_PATH) ?: $f);
                if ($name === '') continue;
                if (preg_match('/[A-Z\s]/',$name) || preg_match('/IMG_\d+/i',$name) || strlen($name)>60) { $bad++; }
            }
            if ($total===0) { $result['score']=2; $result['details']='No downloadable/media files detected'; $result['suggestion']='Use descriptive, lowercase, hyphenated names when adding assets.'; }
            else { $ratioGood = ($total-$bad)/max(1,$total); $score = $ratioGood>=0.9?3:($ratioGood>=0.6?2:1); $result['score']=$score; $result['details']=($total-$bad)."/".$total.' files look SEO-friendly'; $result['suggestion']='Use lowercase, hyphens, and descriptive names (avoid spaces and camera defaults).'; }
            break;

        case '404_page':
            $base = parse_url($final_url);
            if (!$base || !isset($base['scheme'],$base['host'])) { $result['score']=1; $result['details']='Invalid URL'; $result['suggestion']='Provide a valid URL.'; break; }
            $root = $base['scheme'].'://'.$base['host'];
            $candidates = [$root.'/definitely-not-found-'.time(), $root.'/404', $root.'/page-not-found'];
            $okCustom = false; $status404 = false;
            foreach ($candidates as $u) {
                $ctx = stream_context_create(['http'=>['method'=>'GET','timeout'=>6,'ignore_errors'=>true,'header'=>"User-Agent: SEOIndicatorBot/1.0\r\n"]]);
                $headers = @get_headers($u, true, $ctx);
                if (is_array($headers) && isset($headers[0])) {
                    if (preg_match('/\s404\s/',$headers[0])) { $status404 = true; }
                    $html = @file_get_contents($u,false,$ctx);
                    if ($html!==false && stripos($html,'search')!==false || stripos($html,'home')!==false) { $okCustom = true; break; }
                }
            }
            if ($status404 && $okCustom) { $result['score']=3; $result['details']='Custom 404 page detected'; $result['suggestion']='Include helpful links and a search box for lost users.'; }
            else if ($status404) { $result['score']=2; $result['details']='Default 404 response'; $result['suggestion']='Add a custom 404 page with navigation and guidance.'; }
            else { $result['score']=1; $result['details']='Non-404 response for missing pages'; $result['suggestion']='Ensure missing pages return proper 404 status.'; }
            break;

        case 'mobile_friendly':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $viewport = $xpath->query('//meta[@name="viewport"]');
            $okViewport = false; if ($viewport && $viewport->length) { $vc = $viewport->item(0)->getAttribute('content'); $okViewport = stripos($vc,'width=device-width')!==false; }
            $hasMedia = (bool)preg_match('/@media\s*\(/i',$page_content);
            $hasTouch = (bool)preg_match('/min-height\s*:\s*(4[4-9]|[5-9]\d|\d{3,})px/i',$page_content);
            $passed = ($okViewport?1:0)+($hasMedia?1:0)+($hasTouch?1:0);
            $result['score'] = $passed>=3?3:($passed===2?2:1);
            $result['details'] = ($okViewport?'✓ viewport':'✗ viewport') . ', ' . ($hasMedia?'✓ media queries':'✗ media queries');
            $result['suggestion'] = 'Use responsive layout, readable text (≥16px), touch-friendly targets (≥44px).';
            break;

        case 'language':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $html = $xpath->query('/html'); $lang = ($html && $html->length)? $html->item(0)->getAttribute('lang'):'';
            if ($lang==='') { $result['score']=1; $result['details']='Missing html lang'; $result['suggestion']='Add <html lang="en"> (use correct ISO 639-1 code).'; }
            else { $result['score']=3; $result['details']='lang="'.$lang.'"'; $result['suggestion']='Ensure lang matches content; add hreflang for alternates if applicable.'; }
            break;

        case 'favicon':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $links = $xpath->query('//link[contains(translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"), "icon")]');
            $hasLink = $links && $links->length>0;
            $base = parse_url($final_url); $root = ($base&&isset($base['scheme'],$base['host']))? $base['scheme'].'://'.$base['host'] : '';
            $hasRootIco = false;
            if ($root) { $h = @get_headers($root.'/favicon.ico'); if ($h && isset($h[0]) && preg_match('/\s200\s/',$h[0])) $hasRootIco = true; }
            $score = ($hasLink||$hasRootIco)?3:1;
            $result['score']=$score; $result['details']=($hasLink?'link rel icon':'no icon link').', '.($hasRootIco?'root favicon.ico':'no root ico'); $result['suggestion']='Provide PNG/ICO/SVG icons; include apple-touch-icon and manifest for PWA if needed.';
            break;

        case 'canonical':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $nodes = $xpath->query('//link[@rel="canonical"]');
            if (!$nodes || !$nodes->length) { $result['score']=1; $result['details']='Missing canonical'; $result['suggestion']='Add <link rel="canonical" href="https://example.com/page"> in <head>.'; break; }
            $href = trim($nodes->item(0)->getAttribute('href'));
            $okAbs = preg_match('/^https?:\/\//i',$href);
            $sameHost = false;
            $bu = parse_url($final_url); $cu = parse_url($href);
            if ($bu && $cu && isset($bu['host'],$cu['host'])) { $sameHost = (strcasecmp($bu['host'],$cu['host'])===0); }
            $score = ($okAbs && $sameHost)?3:2;
            $result['score']=$score; $result['details']=$href ?: 'Empty canonical'; $result['suggestion']='Use absolute canonical on same host; no tracking params.';
            break;

        case 'social_meta_tags':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $og = $xpath->query('//meta[starts-with(@property, "og:")]');
            $tw = $xpath->query('//meta[starts-with(@name, "twitter:")]');
            $ok = ($og && $og->length) || ($tw && $tw->length);
            $score = $ok?3:1;
            $result['score']=$score; $result['details']='OG: '.($og?$og->length:0).', Twitter: '.($tw?$tw->length:0); $result['suggestion']='Add og:title, og:description, og:image, twitter:card, twitter:site.';
            break;

        case 'aria':
            if (!$xpath) { $result['score']=1; $result['details']='No content'; $result['suggestion']='Fetch failed.'; break; }
            $cnt = 0; $attrs = ['aria-label','aria-labelledby','aria-describedby','aria-expanded','aria-hidden','aria-live'];
            foreach ($attrs as $a) { $n = $xpath->query('//*[@'.$a.']'); if ($n) $cnt += $n->length; }
            $score = $cnt>=10?3:($cnt>=3?2:1);
            $result['score']=$score; $result['details']=$cnt.' ARIA attributes found'; $result['suggestion']='Use ARIA to enhance semantics, but prefer native semantic HTML first.';
            break;

        default:
            $result['score']=null; $result['details']='Not implemented yet'; $result['suggestion']='To be implemented in refactor phase.';
    }

    return $result;
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

