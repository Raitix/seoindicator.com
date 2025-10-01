<?php
// SEO Logic - Simplified Version
require_once 'db_config.php';
require_once 'config.php';

// Helper function to analyze all indicators with SINGLE OpenRouter API call
function analyze_all_indicators_single_call($final_url, $page_content, $conn) {
    if (!$page_content) {
        return get_default_indicator_results();
    }

    // Parse HTML
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    @$doc->loadHTML($page_content);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    // Extract all data
    $data = extract_all_seo_data($final_url, $page_content, $xpath);

    // Build comprehensive prompt
    $prompt = build_comprehensive_seo_prompt($data);

    // Make SINGLE OpenRouter API call
    $ai_response = call_openrouter_api($prompt);

    // Parse AI response or fallback to static
    if ($ai_response['success'] && !empty($ai_response['parsed_data'])) {
        return $ai_response['parsed_data'];
    }

    return analyze_indicators_static($data);
}

// Extract all SEO data from page
function extract_all_seo_data($final_url, $page_content, $xpath) {
    $data = [];

    // URL
    $data['url'] = ['url' => $final_url, 'https' => strpos($final_url, 'https://') === 0, 'domain_length' => strlen(parse_url($final_url, PHP_URL_HOST))];

    // Title
    $title_nodes = $xpath->query('//title');
    $data['title'] = $title_nodes->length > 0 ? trim($title_nodes->item(0)->nodeValue) : '';

    // Meta Description
    $meta_nodes = $xpath->query('//meta[@name="description"]');
    $data['meta_description'] = $meta_nodes->length > 0 ? trim($meta_nodes->item(0)->getAttribute('content')) : '';

    // Robots & Sitemap
    $data['robots'] = @file_get_contents(rtrim($final_url, '/') . '/robots.txt') !== false;
    $data['sitemap'] = @file_get_contents(rtrim($final_url, '/') . '/sitemap.xml') !== false;

    // H1
    $data['h1'] = ['count' => $xpath->query('//h1')->length];

    // Filenames
    $img_nodes = $xpath->query('//img[@src]');
    $problematic = 0; $total = 0;
    foreach ($img_nodes as $img) {
        $src = $img->getAttribute('src');
        if (strpos($src, 'data:') !== 0) {
            $total++;
            if (preg_match('/[A-Z ]|[^a-zA-Z0-9.-]/', basename(parse_url($src, PHP_URL_PATH)))) $problematic++;
        }
    }
    $data['filenames'] = ['total' => $total, 'problematic' => $problematic];

    // Image Alt
    $with_alt = 0;
    foreach ($img_nodes as $img) if (!empty(trim($img->getAttribute('alt')))) $with_alt++;
    $data['image_alt'] = ['total' => $img_nodes->length, 'with_alt' => $with_alt];

    // Links
    $link_nodes = $xpath->query('//a[@href]');
    $internal = 0; $external = 0; $empty = 0;
    $domain = parse_url($final_url, PHP_URL_HOST);
    foreach ($link_nodes as $link) {
        if (empty(trim($link->nodeValue))) $empty++;
        $href = $link->getAttribute('href');
        if (strpos($href, 'http') === 0) {
            if (strpos($href, $domain) !== false) $internal++; else $external++;
        } else $internal++;
    }
    $data['links'] = ['total' => $link_nodes->length, 'internal' => $internal, 'external' => $external, 'empty' => $empty];

    // 404 Page
    $test_url = rtrim($final_url, '/') . '/test404';
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5, 'ignore_errors' => true]]);
    @file_get_contents($test_url, false, $ctx);
    $code = 0;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $h, $m)) { $code = (int)$m[1]; break; }
        }
    }
    $data['404_page'] = ['status_code' => $code];

    // Mobile & Language
    $data['mobile_friendly'] = ['viewport' => preg_match('/<meta[^>]*name=["\']viewport["\'][^>]*>/i', $page_content), 'responsive' => preg_match('/@media|responsive/i', $page_content)];
    $html_nodes = $xpath->query('//html[@lang]');
    $data['language'] = ['lang' => $html_nodes->length > 0 ? $html_nodes->item(0)->getAttribute('lang') : ''];

    // Favicon, Canonical, Social
    $data['favicon'] = ['found' => $xpath->query('//link[@rel="icon" or @rel="shortcut icon"]')->length > 0];
    $canonical_nodes = $xpath->query('//link[@rel="canonical"]');
    $data['canonical'] = ['url' => $canonical_nodes->length > 0 ? $canonical_nodes->item(0)->getAttribute('href') : ''];
    $data['social_meta_tags'] = ['og_count' => $xpath->query('//meta[@property and starts-with(@property, "og:")]')->length, 'twitter_count' => $xpath->query('//meta[@name and starts-with(@name, "twitter:")]')->length];

    // ARIA & Speed
    $data['aria'] = ['count' => $xpath->query('//*[@aria-label or @aria-labelledby or @role]')->length];
    $data['page_speed'] = ['html_size_kb' => round(strlen($page_content) / 1024, 2)];

    return $data;
}

// Build comprehensive prompt for AI
function build_comprehensive_seo_prompt($data) {
    $prompt = "Analyze SEO indicators. Return ONLY valid JSON without markdown:\n\n";
    $prompt .= "Format: {\"url\": {\"score\": 1-3, \"suggestion\": \"advice\"}, ...}\n\n";
    $prompt .= "1. URL: " . $data['url']['url'] . " (HTTPS: " . ($data['url']['https'] ? 'yes' : 'no') . ")\n";
    $prompt .= "2. Title: '" . substr($data['title'], 0, 100) . "' (Length: " . strlen($data['title']) . ")\n";
    $prompt .= "3. Meta Description: '" . substr($data['meta_description'], 0, 100) . "' (Length: " . strlen($data['meta_description']) . ")\n";
    $prompt .= "4. Robots.txt: " . ($data['robots'] ? 'Found' : 'Not found') . "\n";
    $prompt .= "5. Sitemap.xml: " . ($data['sitemap'] ? 'Found' : 'Not found') . "\n";
    $prompt .= "6. H1: " . $data['h1']['count'] . " found\n";
    $prompt .= "7. Filenames: " . $data['filenames']['total'] . " files, " . $data['filenames']['problematic'] . " problematic\n";
    $prompt .= "8. Image Alt: " . $data['image_alt']['with_alt'] . "/" . $data['image_alt']['total'] . "\n";
    $prompt .= "9. Links: " . $data['links']['total'] . " (" . $data['links']['internal'] . " internal, " . $data['links']['empty'] . " empty)\n";
    $prompt .= "10. 404: HTTP " . $data['404_page']['status_code'] . "\n";
    $prompt .= "11. Mobile: Viewport " . ($data['mobile_friendly']['viewport'] ? 'yes' : 'no') . "\n";
    $prompt .= "12. Language: '" . $data['language']['lang'] . "'\n";
    $prompt .= "13. Favicon: " . ($data['favicon']['found'] ? 'yes' : 'no') . "\n";
    $prompt .= "14. Canonical: " . (!empty($data['canonical']['url']) ? 'set' : 'not set') . "\n";
    $prompt .= "15. Social: " . $data['social_meta_tags']['og_count'] . " OG, " . $data['social_meta_tags']['twitter_count'] . " Twitter\n";
    $prompt .= "16. ARIA: " . $data['aria']['count'] . " attributes\n";
    $prompt .= "17. Page Speed: " . $data['page_speed']['html_size_kb'] . " KB\n\n";
    $prompt .= "Return JSON with keys: url, title, meta_description, robots, sitemap, h1, filenames, image_alt, links, 404_page, mobile_friendly, language, favicon, canonical, social_meta_tags, aria, page_speed";
    return $prompt;
}

// Call OpenRouter API
function call_openrouter_api($prompt) {
    if (!defined('OPENROUTER_API_KEY') || empty(OPENROUTER_API_KEY)) {
        return ['success' => false];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://openrouter.ai/api/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . OPENROUTER_API_KEY,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode(["model" => "deepseek/deepseek-chat-v3-0324:free", "messages" => [["role" => "user", "content" => $prompt]]]),
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return ['success' => false];

    $data = json_decode($response, true);
    if (!isset($data['choices'][0]['message']['content'])) return ['success' => false];

    $content = trim($data['choices'][0]['message']['content']);
    $content = preg_replace('/^```json\s*/i', '', $content);
    $content = preg_replace('/\s*```$/i', '', $content);
    $parsed = json_decode(trim($content), true);

    return $parsed ? ['success' => true, 'parsed_data' => $parsed] : ['success' => false];
}

// Fallback static analysis
function analyze_indicators_static($d) {
    $r = [];

    // URL
    $s = ($d['url']['https'] ? 1 : 0) + ($d['url']['domain_length'] <= 15 ? 1 : 0) + 1;
    $r['url'] = ['score' => min(3, $s), 'details' => $d['url']['url'], 'suggestion' => $d['url']['https'] ? 'Good URL' : 'Use HTTPS'];

    // Title
    $len = strlen($d['title']);
    $r['title'] = ['score' => $len >= 10 && $len <= 60 ? 3 : ($len < 10 ? 1 : 2), 'details' => htmlspecialchars(substr($d['title'], 0, 100)), 'suggestion' => $len >= 10 && $len <= 60 ? 'Good title' : 'Optimize to 10-60 chars'];

    // Meta
    $len = strlen($d['meta_description']);
    $r['meta_description'] = ['score' => $len >= 120 && $len <= 160 ? 3 : 2, 'details' => htmlspecialchars(substr($d['meta_description'], 0, 100)), 'suggestion' => 'Optimize to 120-160 chars'];

    // Robots & Sitemap
    $r['robots'] = ['score' => $d['robots'] ? 3 : 2, 'details' => $d['robots'] ? 'Found' : 'Not found', 'suggestion' => $d['robots'] ? 'Present' : 'Add robots.txt'];
    $r['sitemap'] = ['score' => $d['sitemap'] ? 3 : 2, 'details' => $d['sitemap'] ? 'Found' : 'Not found', 'suggestion' => $d['sitemap'] ? 'Present' : 'Add sitemap.xml'];

    // H1
    $c = $d['h1']['count'];
    $r['h1'] = ['score' => $c === 1 ? 3 : ($c === 0 ? 1 : 2), 'details' => "$c H1 tag(s)", 'suggestion' => $c === 1 ? 'Perfect' : 'Use one H1'];

    // Filenames
    $t = $d['filenames']['total']; $p = $d['filenames']['problematic'];
    $r['filenames'] = ['score' => $t === 0 ? 3 : ($p === 0 ? 3 : ($p < $t/2 ? 2 : 1)), 'details' => "$t files, $p issues", 'suggestion' => $p === 0 ? 'SEO-friendly' : 'Use lowercase'];

    // Images
    $t = $d['image_alt']['total']; $w = $d['image_alt']['with_alt'];
    $r['image_alt'] = ['score' => $t === 0 ? 3 : ($w === $t ? 3 : ($w > $t/2 ? 2 : 1)), 'details' => "$w/$t have alt", 'suggestion' => $w === $t ? 'Excellent' : 'Add alt text'];

    // Links
    $e = $d['links']['empty']; $t = $d['links']['total'];
    $r['links'] = ['score' => $e === 0 && $t > 0 ? 3 : ($e < $t/2 ? 2 : 1), 'details' => $t . " links", 'suggestion' => $e === 0 ? 'Good' : 'Add anchor text'];

    // 404
    $c = $d['404_page']['status_code'];
    $r['404_page'] = ['score' => $c === 404 ? 3 : 2, 'details' => "HTTP $c", 'suggestion' => $c === 404 ? 'Custom 404' : 'Add 404 page'];

    // Mobile
    $v = $d['mobile_friendly']['viewport']; $re = $d['mobile_friendly']['responsive'];
    $r['mobile_friendly'] = ['score' => $v && $re ? 3 : ($v || $re ? 2 : 1), 'details' => 'Viewport:' . ($v?'yes':'no'), 'suggestion' => $v && $re ? 'Mobile-friendly' : 'Add viewport'];

    // Language
    $r['language'] = ['score' => !empty($d['language']['lang']) ? 3 : 1, 'details' => $d['language']['lang'] ?: 'None', 'suggestion' => !empty($d['language']['lang']) ? 'Declared' : 'Add lang'];

    // Favicon
    $r['favicon'] = ['score' => $d['favicon']['found'] ? 3 : 1, 'details' => $d['favicon']['found'] ? 'Found' : 'Not found', 'suggestion' => $d['favicon']['found'] ? 'Present' : 'Add favicon'];

    // Canonical
    $r['canonical'] = ['score' => !empty($d['canonical']['url']) ? 3 : 2, 'details' => !empty($d['canonical']['url']) ? 'Set' : 'Not set', 'suggestion' => !empty($d['canonical']['url']) ? 'Proper' : 'Add canonical'];

    // Social
    $og = $d['social_meta_tags']['og_count']; $tw = $d['social_meta_tags']['twitter_count'];
    $r['social_meta_tags'] = ['score' => $og >= 3 && $tw >= 2 ? 3 : ($og > 0 || $tw > 0 ? 2 : 1), 'details' => "$og OG, $tw Twitter", 'suggestion' => $og >= 3 ? 'Complete' : 'Add social tags'];

    // ARIA
    $c = $d['aria']['count'];
    $r['aria'] = ['score' => $c > 5 ? 3 : ($c > 0 ? 2 : 1), 'details' => "$c attributes", 'suggestion' => $c > 5 ? 'Good' : 'Add ARIA'];

    // Speed
    $s = $d['page_speed']['html_size_kb'];
    $r['page_speed'] = ['score' => $s < 100 ? 3 : ($s < 300 ? 2 : 1), 'details' => "$s KB", 'suggestion' => $s < 100 ? 'Fast' : 'Optimize size'];

    return $r;
}

// Default results
function get_default_indicator_results() {
    $indicators = ['url', 'title', 'meta_description', 'robots', 'sitemap', 'h1', 'filenames', 'image_alt', 'links', '404_page', 'mobile_friendly', 'language', 'favicon', 'canonical', 'social_meta_tags', 'aria', 'page_speed'];
    $r = [];
    foreach ($indicators as $i) {
        $r[$i] = ['score' => 1, 'details' => 'Unable to analyze', 'suggestion' => 'Could not fetch content'];
    }
    return $r;
}

// Helper functions
function render_stars($score, $show_text = false, $achieved = null, $max = null) {
    if ($score === null) return $show_text ? 'â˜†â˜†â˜† (N/A)' : 'â˜†â˜†â˜†';
    $s = max(0, min(3, (int)$score));
    $stars = str_repeat('â˜…', $s) . str_repeat('â˜†', 3 - $s);
    if ($show_text) {
        if ($achieved !== null && $max !== null) return $stars . ' (' . $achieved . '/' . $max . ')';
        return $stars . ' (' . $s . '/3)';
    }
    return $stars;
}

function get_score_row_class($score) {
    if ($score === null) return '';
    $s = (int)$score;
    if ($s >= 3) return 'table-success';
    if ($s === 2) return 'table-warning';
    return 'table-danger';
}

function get_db_connection() {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_errno) return null;
    $conn->set_charset('utf8mb4');
    return $conn;
}
?>
