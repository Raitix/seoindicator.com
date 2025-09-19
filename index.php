<?php
session_start(); // Start the session at the very top

// Include the SEO logic file early to make functions available
include 'seo_logic.php';

// Enable debug mode early so it's available to analysis functions
// Set debug mode in session immediately when detected from URL
if (isset($_GET['debug'])) {
    $_SESSION['debug_mode'] = true;
}

// Clear debug mode from session if no debug parameter is present and we're not in a form redirect
if (!isset($_GET['debug']) && !isset($_SESSION['is_post_redirect_layout_active'])) {
    unset($_SESSION['debug_mode']);
}

$debug_mode = isset($_GET['debug']) || (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode']);


// Handle API requests for progressive analysis
if (isset($_GET['api']) && $_GET['api'] === 'start-analysis' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any output buffers and start fresh
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    $domain = trim($_POST['domain'] ?? '');
    
    // Store debug mode in session for API calls
    if (isset($_GET['debug'])) {
        $_SESSION['debug_mode'] = true;
    }
    
    if ($domain) {
        // Generate a simple job ID
        $job_id = uniqid('job_', true);
        
        // Fetch page content
        $page_content = '';
        $final_url = $domain;
        
        // Try to fetch the page content
        $url_schemes_to_try = [];
        if (!preg_match("~^(?:f|ht)tps?://~i", $domain)) {
            $url_schemes_to_try[] = "https://{$domain}";
            $url_schemes_to_try[] = "http://{$domain}";
        } else {
            $url_schemes_to_try[] = $domain;
        }
        
        foreach ($url_schemes_to_try as $current_url_to_check) {
            $context_options = [
                'http' => [
                    'method' => "GET", 
                    'timeout' => 10, 
                    'ignore_errors' => true, 
                    'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
                ]
            ];
            
            $page_content = @file_get_contents($current_url_to_check, false, stream_context_create($context_options));
            if ($page_content !== false) {
                $final_url = $current_url_to_check;
                break;
            }
        }
        
        // Store job in session
        $_SESSION['jobs'][$job_id] = [
            'domain' => $domain,
            'final_url' => $final_url,
            'page_content' => $page_content,
            'status' => 'running',
            'progress' => ['done' => 0, 'total' => 17],
            'results' => []
        ];
        
        // Return job info
        echo json_encode([
            'success' => true,
            'job_id' => $job_id,
            'final_url' => $final_url,
            'indicators' => ['url', 'title', 'meta_description', 'robots', 'sitemap', 'h1', 'filenames', 'image_alt', 'links', '404_page', 'mobile_friendly', 'language', 'favicon', 'canonical', 'social_meta_tags', 'aria', 'page_speed']
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Domain required']);
        exit;
    }
}

if (isset($_GET['api']) && $_GET['api'] === 'run-indicator' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any output buffers and start fresh
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    $job_id = $_POST['job_id'] ?? '';
    $indicator = $_POST['indicator'] ?? '';
    
    // Check for debug mode in session
    if (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode']) {
        $_GET['debug'] = '1';
    }
    
    if ($job_id && $indicator && isset($_SESSION['jobs'][$job_id])) {
        $job = $_SESSION['jobs'][$job_id];
        $domain = $job['domain'];
        $page_content = $job['page_content'] ?? '';
        $final_url = $job['final_url'] ?? $domain;
        
        // Run actual SEO analysis for the indicator
        error_log("Run Indicator API: Processing indicator: $indicator for domain: $domain");
        $result = runSingleIndicator($indicator, $domain, $page_content, $final_url);
        error_log("Run Indicator API: Result for $indicator: " . json_encode($result));
        
        $_SESSION['jobs'][$job_id]['results'][$indicator] = $result;
        $_SESSION['jobs'][$job_id]['progress']['done']++;
        
        echo json_encode(['success' => true, 'result' => $result]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid job or indicator']);
        exit;
    }
}

if (isset($_GET['api']) && $_GET['api'] === 'job-progress' && isset($_GET['job_id'])) {
    header('Content-Type: application/json');
    $job_id = $_GET['job_id'];
    
    if (isset($_SESSION['jobs'][$job_id])) {
        $job = $_SESSION['jobs'][$job_id];
        echo json_encode([
            'success' => true,
            'progress' => $job['progress'],
            'cancelled' => false
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }
}

// Handle form submission directly in this file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain'])) {
    $domain = trim($_POST['domain']);
    if ($domain) {
        $_SESSION['last_domain_for_view'] = $domain;
        $_SESSION['is_post_redirect_layout_active'] = true;
        $_SESSION['page_loaded'] = true; // Mark as loaded to prevent session clearing
        
        // Store debug mode in session if it was set
        if (isset($_GET['debug'])) {
            $_SESSION['debug_mode'] = true;
        }
        
        // Redirect to avoid form resubmission, preserving debug parameter
        // Use meta refresh to ensure proper query parameter preservation
        $redirect_url = '/';
        if (isset($_GET['debug'])) {
            $redirect_url .= '?debug=' . urlencode($_GET['debug']);
        }
        echo '<meta http-equiv="refresh" content="0;url=' . $redirect_url . '">';
        echo '<script>window.location.href = "' . $redirect_url . '";</script>';
        exit;
    }
}

// Keep the old POST logic disabled since we're using the new progressive API system
define('SEO_LOGIC_DISABLE_POST', true);

// Helper function to store debug information
function storeDebugInfo($type, $indicator, $request, $response, $success = true, $message = '') {
    if (isset($_GET['debug']) || isset($_SESSION['debug_mode'])) {
        $_SESSION['debug_calls'][] = [
            'type' => $type,
            'indicator' => $indicator,
            'timestamp' => date('Y-m-d H:i:s'),
            'success' => $success,
            'message' => $message,
            'request' => $request,
            'response' => $response
        ];
        
        // Also log to file for debugging
        logDebugToFile($type, $indicator, $request, $response, $success, $message);
    }
}

// Helper function to log debug information to file
function logDebugToFile($type, $indicator, $request, $response, $success = true, $message = '') {
    if (isset($_GET['debug']) || isset($_SESSION['debug_mode'])) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'indicator' => $indicator,
            'success' => $success,
            'message' => $message,
            'request' => $request,
            'response' => $response
        ];
        
        $logLine = "[" . $logEntry['timestamp'] . "] " . 
                   strtoupper($type) . " - " . 
                   strtoupper($indicator) . " - " . 
                   ($success ? 'SUCCESS' : 'FAILED') . 
                   ($message ? " - {$message}" : '') . "\n";
        
        $logLine .= "REQUEST: " . (is_string($request) ? $request : json_encode($request, JSON_PRETTY_PRINT)) . "\n";
        $logLine .= "RESPONSE: " . (is_string($response) ? $response : json_encode($response, JSON_PRETTY_PRINT)) . "\n";
        $logLine .= str_repeat("-", 80) . "\n\n";
        
        // Use absolute path and add error handling
        $logFile = __DIR__ . '/log.txt';
        $result = file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Log any errors to PHP error log and also create a debug file
        if ($result === false) {
            error_log("Failed to write to debug log file: " . $logFile);
            // Create a debug file to see what's happening
            file_put_contents(__DIR__ . '/debug_error.txt', "Failed to write log at " . date('Y-m-d H:i:s') . "\nLog file path: " . $logFile . "\nCurrent dir: " . __DIR__ . "\n");
        } else {
            // Create a success file to confirm logging is working
            file_put_contents(__DIR__ . '/debug_success.txt', "Log written successfully at " . date('Y-m-d H:i:s') . "\nLog file path: " . $logFile . "\n");
        }
    }
}

// Function to run a single indicator analysis
function runSingleIndicator($indicator, $domain, $page_content, $final_url) {
    // Parse the page content to create DOMDocument and XPath
    $dom = new DOMDocument();
    $dom->loadHTML($page_content, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    
    // Call the appropriate analysis function based on the indicator
    switch ($indicator) {
        case 'title':
            return analyzeTitleIndicator($xpath);
        case 'meta_description':
            return analyzeMetaDescriptionIndicator($xpath);
        case 'url':
            return analyzeUrlIndicator($domain, $final_url);
        case 'h1':
            return analyzeHeadingsIndicator($xpath);
        case 'image_alt':
            return analyzeImageAltIndicator($xpath);
        case 'links':
            return analyzeLinksIndicator($xpath, $domain);
        case '404_page':
            return analyze404PageIndicator($final_url);
        case 'mobile_friendly':
            return analyzeMobileFriendlyIndicator($page_content);
        case 'language':
            return analyzeLanguageIndicator($xpath);
        case 'favicon':
            return analyzeFaviconIndicator($xpath, $final_url);
        case 'canonical':
            return analyzeCanonicalIndicator($xpath, $final_url);
        case 'social_meta_tags':
            return analyzeSocialMetaTagsIndicator($xpath);
        case 'aria':
            return analyzeAriaIndicator($xpath);
        case 'page_speed':
            return analyzePageSpeedIndicator($final_url);
        case 'robots':
            return analyzeRobotsTxtIndicator($domain);
        case 'sitemap':
            return analyzeSitemapXmlIndicator($domain);
        case 'filenames':
            return analyzeFilenamesIndicator($xpath);
        default:
            return ['score' => null, 'details' => 'Unknown indicator', 'suggestion' => 'Unknown indicator'];
    }
}

// Mock indicator function for debugging
function mockIndicator($indicator, $domain, $final_url) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    // Mock data for different indicators - just show they are mocked
    $mockData = [
        'url' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'title' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'meta_description' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'h1' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'image_alt' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'links' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        '404_page' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'mobile_friendly' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'language' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'favicon' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'canonical' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'social_meta_tags' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'aria' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'page_speed' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'robots' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'sitemap' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ],
        'filenames' => [
            'score' => null,
            'details' => '🔧 MOCKED - FUNCTION CALLED',
            'suggestion' => '🔧 MOCKED - FUNCTION CALLED'
        ]
    ];
    
    if (isset($mockData[$indicator])) {
        $result = $mockData[$indicator];
        
        // Don't log mock calls - they don't make external API calls
        // storeDebugInfo('MOCK', $indicator, 'Mock data generated', $result, true, 'Indicator mocked for debugging');
    } else {
        $result = [
            'score' => null,
            'details' => '🔧 MOCKED',
            'suggestion' => '🔧 MOCKED'
        ];
        
        // Don't log mock calls - they don't make external API calls
        // storeDebugInfo('MOCK', $indicator, 'Mock data generated', $result, true, 'Unknown indicator mocked for debugging');
    }
    
    return $result;
}

// Individual indicator analysis functions
function analyzeTitleIndicator($xpath) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $title_nodes = $xpath->query('//title');
    if ($title_nodes->length > 0) {
        $title_content = trim($title_nodes->item(0)->nodeValue);
        if (!empty($title_content)) {
            $title_length = strlen($title_content);
            if ($title_length >= 10 && $title_length <= 60) {
                $result['score'] = 3;
                $result['suggestion'] = 'Good title length. Consider including target keywords if not already present.';
            } elseif ($title_length < 10) {
                $result['score'] = 1;
                $result['suggestion'] = 'Title is too short. Aim for 10-60 characters with descriptive keywords.';
            } else {
                $result['score'] = 2;
                $result['suggestion'] = 'Title is too long. Keep under 60 characters to prevent truncation in search results.';
            }
            $result['details'] = htmlspecialchars($title_content);
            
            // Try to get AI suggestion for good titles
            if ($result['score'] == 3) {
                if (function_exists('call_openrouter_api')) {
                    $prompt = "Analyze this page title for SEO: '{$title_content}'. Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice).";
                    $ai_response = call_openrouter_api($prompt);
                    
                    storeDebugInfo('OpenRouter', 'title', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
                    
                    if ($ai_response['success'] && isset($ai_response['raw_response'])) {
                        $ai_data = json_decode($ai_response['raw_response'], true);
                        if ($ai_data && isset($ai_data['suggestion'])) {
                            $result['suggestion'] = $ai_data['suggestion'];
                        }
                    }
                } else {
                    storeDebugInfo('OpenRouter', 'title', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
                }
            }
        } else {
            $result['score'] = 1;
            $result['details'] = 'Title tag exists but is empty.';
            $result['suggestion'] = 'Add descriptive title content with target keywords (10-60 characters).';
        }
    } else {
        $result['score'] = 1;
        $result['details'] = 'No title tag found.';
        $result['suggestion'] = 'Add a title tag with descriptive content and target keywords (10-60 characters).';
    }
    
    return $result;
}

function analyzeMetaDescriptionIndicator($xpath) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $meta_description_nodes = $xpath->query('//meta[@name="description"]');
    if ($meta_description_nodes->length > 0) {
        $meta_content = trim($meta_description_nodes->item(0)->getAttribute('content'));
        if (!empty($meta_content)) {
            $meta_length = strlen($meta_content);
            if ($meta_length >= 120 && $meta_length <= 160) {
                $result['score'] = 3;
                $result['suggestion'] = 'Good meta description length. Consider including a call-to-action.';
            } elseif ($meta_length < 120) {
                $result['score'] = 2;
                $result['suggestion'] = 'Meta description is too short. Aim for 120-160 characters.';
            } else {
                $result['score'] = 2;
                $result['suggestion'] = 'Meta description is too long. Keep under 160 characters.';
            }
            $result['details'] = htmlspecialchars($meta_content);
            
            // Try to get AI suggestion for meta descriptions
            if (function_exists('call_openrouter_api')) {
                $prompt = "Analyze this meta description for SEO: '{$meta_content}'. Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice).";
                $ai_response = call_openrouter_api($prompt);
                
                storeDebugInfo('OpenRouter', 'meta_description', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
                
                if ($ai_response['success'] && isset($ai_response['raw_response'])) {
                    $ai_data = json_decode($ai_response['raw_response'], true);
                    if ($ai_data && isset($ai_data['suggestion'])) {
                        $result['suggestion'] = $ai_data['suggestion'];
                    }
                }
            } else {
                storeDebugInfo('OpenRouter', 'meta_description', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
            }
        } else {
            $result['score'] = 1;
            $result['details'] = 'Meta description tag exists but is empty.';
            $result['suggestion'] = 'Add descriptive meta description content (120-160 characters).';
        }
    } else {
        $result['score'] = 1;
        $result['details'] = 'No meta description tag found.';
        $result['suggestion'] = 'Add a meta description tag with compelling content (120-160 characters).';
    }
    
    return $result;
}

function analyzeUrlIndicator($domain, $final_url) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    // Debug logging
    error_log("URL Analysis: Starting analysis for domain: $domain, final_url: $final_url");
    
    $score = 0;
    $issues = [];
    
    // Check HTTPS
    if (strpos($final_url, 'https://') === 0) {
        $score += 1;
    } else {
        $issues[] = 'Not using HTTPS';
    }
    
    // Check domain length
    $domain_length = strlen(parse_url($final_url, PHP_URL_HOST));
    if ($domain_length <= 15) {
        $score += 1;
    } else {
        $issues[] = 'Domain name is long';
    }
    
    // Check for special characters
    if (!preg_match('/[^a-zA-Z0-9.-]/', parse_url($final_url, PHP_URL_HOST))) {
        $score += 1;
    } else {
        $issues[] = 'Domain contains special characters';
    }
    
    $result['score'] = $score;
    $result['details'] = htmlspecialchars($final_url);
    
    // Debug logging
    error_log("URL Analysis: Score calculated: $score, Details: " . $result['details']);
    
    // Static logic suggestion
    $static_suggestion = '';
    if ($score === 3) {
        $static_suggestion = 'Excellent URL structure.';
    } elseif ($score === 2) {
        $static_suggestion = 'Good URL structure. Consider: ' . implode(', ', $issues);
    } else {
        $static_suggestion = 'URL needs improvement: ' . implode(', ', $issues);
    }
    
    // Try to get AI suggestion for URL analysis
    if (function_exists('call_openrouter_api')) {
        $prompt = "Analyze this URL for SEO: '{$final_url}'. Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice). Current static analysis: {$static_suggestion}";
        $ai_response = call_openrouter_api($prompt);
        
        storeDebugInfo('OpenRouter', 'url', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
        
        if ($ai_response['success'] && isset($ai_response['raw_response'])) {
            $ai_data = json_decode($ai_response['raw_response'], true);
            if ($ai_data && isset($ai_data['suggestion'])) {
                $result['suggestion'] = $ai_data['suggestion'] . '<br><br><strong>Static Analysis:</strong> ' . $static_suggestion;
            } else {
                $result['suggestion'] = $static_suggestion;
            }
        } else {
            $result['suggestion'] = $static_suggestion;
        }
    } else {
        storeDebugInfo('OpenRouter', 'url', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
        $result['suggestion'] = $static_suggestion;
    }
    
    // Debug logging
    error_log("URL Analysis: Final result: " . json_encode($result));
    
    return $result;
}

function analyzeHeadingsIndicator($xpath) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $h1_nodes = $xpath->query('//h1');
    $h2_nodes = $xpath->query('//h2');
    
    $h1_count = $h1_nodes->length;
    $h2_count = $h2_nodes->length;
    
    // Static logic
    $static_suggestion = '';
    if ($h1_count === 1) {
        $result['score'] = 3;
        $result['details'] = "Found {$h1_count} H1 tag and {$h2_count} H2 tags";
        $static_suggestion = 'Perfect heading structure with single H1 tag.';
    } elseif ($h1_count === 0) {
        $result['score'] = 1;
        $result['details'] = "No H1 tag found, {$h2_count} H2 tags";
        $static_suggestion = 'Add a single H1 tag to improve SEO structure.';
    } else {
        $result['score'] = 2;
        $result['details'] = "Found {$h1_count} H1 tags and {$h2_count} H2 tags";
        $static_suggestion = 'Use only one H1 tag per page for better SEO.';
    }
    
    // Try to get AI suggestion for headings analysis
    if (function_exists('call_openrouter_api')) {
        $prompt = "Analyze this heading structure for SEO: H1 tags: {$h1_count}, H2 tags: {$h2_count}. Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice). Current static analysis: {$static_suggestion}";
        $ai_response = call_openrouter_api($prompt);
        
        storeDebugInfo('OpenRouter', 'h1', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
        
        if ($ai_response['success'] && isset($ai_response['raw_response'])) {
            $ai_data = json_decode($ai_response['raw_response'], true);
            if ($ai_data && isset($ai_data['suggestion'])) {
                $result['suggestion'] = $ai_data['suggestion'] . '<br><br><strong>Static Analysis:</strong> ' . $static_suggestion;
            } else {
                $result['suggestion'] = $static_suggestion;
            }
        } else {
            $result['suggestion'] = $static_suggestion;
        }
    } else {
        storeDebugInfo('OpenRouter', 'h1', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
        $result['suggestion'] = $static_suggestion;
    }
    
    return $result;
}

function analyzeImageAltIndicator($xpath) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $img_nodes = $xpath->query('//img');
    $total_images = $img_nodes->length;
    $images_with_alt = 0;
    $images_without_alt = 0;
    
    foreach ($img_nodes as $img) {
        $alt = $img->getAttribute('alt');
        if (!empty(trim($alt))) {
            $images_with_alt++;
        } else {
            $images_without_alt++;
        }
    }
    
    // Static logic
    $static_suggestion = '';
    if ($total_images === 0) {
        $result['score'] = 3;
        $result['details'] = 'No images found on page';
        $static_suggestion = 'No images to analyze.';
    } elseif ($images_without_alt === 0) {
        $result['score'] = 3;
        $result['details'] = "All {$total_images} images have alt text";
        $static_suggestion = 'Excellent! All images have descriptive alt text.';
    } elseif ($images_with_alt > $images_without_alt) {
        $result['score'] = 2;
        $result['details'] = "{$images_with_alt}/{$total_images} images have alt text";
        $static_suggestion = 'Add alt text to remaining images for better accessibility and SEO.';
    } else {
        $result['score'] = 1;
        $result['details'] = "Only {$images_with_alt}/{$total_images} images have alt text";
        $static_suggestion = 'Add descriptive alt text to all images for accessibility and SEO.';
    }
    
    // Try to get AI suggestion for image alt analysis
    if (function_exists('call_openrouter_api')) {
        $prompt = "Analyze image alt text for SEO: Total images: {$total_images}, With alt text: {$images_with_alt}, Without alt text: {$images_without_alt}. Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice). Current static analysis: {$static_suggestion}";
        $ai_response = call_openrouter_api($prompt);
        
        storeDebugInfo('OpenRouter', 'image_alt', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
        
        if ($ai_response['success'] && isset($ai_response['raw_response'])) {
            $ai_data = json_decode($ai_response['raw_response'], true);
            if ($ai_data && isset($ai_data['suggestion'])) {
                $result['suggestion'] = $ai_data['suggestion'] . '<br><br><strong>Static Analysis:</strong> ' . $static_suggestion;
            } else {
                $result['suggestion'] = $static_suggestion;
            }
        } else {
            $result['suggestion'] = $static_suggestion;
        }
    } else {
        storeDebugInfo('OpenRouter', 'image_alt', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
        $result['suggestion'] = $static_suggestion;
    }
    
    return $result;
}

function analyzeLinksIndicator($xpath, $domain) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $link_nodes = $xpath->query('//a[@href]');
    $total_links = $link_nodes->length;
    $internal_links = 0;
    $external_links = 0;
    $empty_anchor = 0;
    
    foreach ($link_nodes as $link) {
        $href = $link->getAttribute('href');
        $text = trim($link->nodeValue);
        
        if (empty($text)) {
            $empty_anchor++;
        }
        
        if (strpos($href, 'http') === 0) {
            if (strpos($href, $domain) !== false) {
                $internal_links++;
            } else {
                $external_links++;
            }
        } else {
            $internal_links++;
        }
    }
    
    $result['details'] = "Total: {$total_links}, Internal: {$internal_links}, External: {$external_links}";
    
    // Static logic
    $static_suggestion = '';
    if ($empty_anchor === 0 && $total_links > 0) {
        $result['score'] = 3;
        $static_suggestion = 'Good link structure with descriptive anchor text.';
    } elseif ($empty_anchor < $total_links / 2) {
        $result['score'] = 2;
        $static_suggestion = 'Some links lack descriptive anchor text.';
    } else {
        $result['score'] = 1;
        $static_suggestion = 'Many links lack descriptive anchor text.';
    }
    
    // Try to get AI suggestion for links analysis
    if (function_exists('call_openrouter_api')) {
        $prompt = "Analyze link structure for SEO: Total links: {$total_links}, Internal: {$internal_links}, External: {$external_links}, Empty anchor text: {$empty_anchor}. Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice). Current static analysis: {$static_suggestion}";
        $ai_response = call_openrouter_api($prompt);
        
        storeDebugInfo('OpenRouter', 'links', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
        
        if ($ai_response['success'] && isset($ai_response['raw_response'])) {
            $ai_data = json_decode($ai_response['raw_response'], true);
            if ($ai_data && isset($ai_data['suggestion'])) {
                $result['suggestion'] = $ai_data['suggestion'] . '<br><br><strong>Static Analysis:</strong> ' . $static_suggestion;
            } else {
                $result['suggestion'] = $static_suggestion;
            }
        } else {
            $result['suggestion'] = $static_suggestion;
        }
    } else {
        storeDebugInfo('OpenRouter', 'links', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
        $result['suggestion'] = $static_suggestion;
    }
    
    return $result;
}

function analyze404PageIndicator($final_url) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    // Test a non-existent page
    $test_url = rtrim($final_url, '/') . '/non-existent-page-404-test-' . time();
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true,
            'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
        ]
    ]);
    
    $response = @file_get_contents($test_url, false, $context);
    $http_code = 0;
    
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $http_code = (int)$matches[1];
                break;
            }
        }
    }
    
    // Static logic
    $static_suggestion = '';
    if ($http_code === 404) {
        $result['score'] = 3;
        $result['details'] = 'Custom 404 page detected';
        $static_suggestion = 'Excellent! Custom 404 page is properly configured.';
    } elseif ($http_code === 200) {
        $result['score'] = 1;
        $result['details'] = '404 page returns 200 status';
        $static_suggestion = 'Configure proper 404 page to return 404 status code.';
    } else {
        $result['score'] = 2;
        $result['details'] = '404 page status: ' . $http_code;
        $static_suggestion = 'Consider implementing a custom 404 page.';
    }
    
    // Try to get AI suggestion for 404 page analysis
    if (function_exists('call_openrouter_api')) {
        $prompt = "Analyze 404 page for SEO: HTTP status code: {$http_code}, URL tested: {$test_url}. Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice). Current static analysis: {$static_suggestion}";
        $ai_response = call_openrouter_api($prompt);
        
        storeDebugInfo('OpenRouter', '404_page', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
        
        if ($ai_response['success'] && isset($ai_response['raw_response'])) {
            $ai_data = json_decode($ai_response['raw_response'], true);
            if ($ai_data && isset($ai_data['suggestion'])) {
                $result['suggestion'] = $ai_data['suggestion'] . '<br><br><strong>Static Analysis:</strong> ' . $static_suggestion;
            } else {
                $result['suggestion'] = $static_suggestion;
            }
        } else {
            $result['suggestion'] = $static_suggestion;
        }
    } else {
        storeDebugInfo('OpenRouter', '404_page', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
        $result['suggestion'] = $static_suggestion;
    }
    
    return $result;
}

function analyzeMobileFriendlyIndicator($page_content) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $score = 0;
    $issues = [];
    
    // Check for viewport meta tag
    if (preg_match('/<meta[^>]*name=["\']viewport["\'][^>]*>/i', $page_content)) {
        $score += 1;
    } else {
        $issues[] = 'Missing viewport meta tag';
    }
    
    // Check for responsive design indicators
    if (preg_match('/@media|responsive|mobile/i', $page_content)) {
        $score += 1;
    } else {
        $issues[] = 'No responsive design detected';
    }
    
    // Check for touch-friendly elements
    if (preg_match('/touch-action|user-scalable/i', $page_content)) {
        $score += 1;
    } else {
        $issues[] = 'No touch optimization detected';
    }
    
    $result['score'] = $score;
    
    if ($score === 3) {
        $result['details'] = 'Mobile-friendly design detected';
        $result['suggestion'] = 'Excellent mobile optimization!';
    } elseif ($score === 2) {
        $result['details'] = 'Partial mobile optimization';
        $result['suggestion'] = 'Consider: ' . implode(', ', $issues);
    } else {
        $result['details'] = 'Limited mobile optimization';
        $result['suggestion'] = 'Improve mobile experience: ' . implode(', ', $issues);
    }
    
    return $result;
}

function analyzeLanguageIndicator($xpath) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $html_nodes = $xpath->query('//html[@lang]');
    if ($html_nodes->length > 0) {
        $lang = $html_nodes->item(0)->getAttribute('lang');
        if (!empty($lang)) {
            $result['score'] = 3;
            $result['details'] = 'Language declared: ' . htmlspecialchars($lang);
            $result['suggestion'] = 'Perfect! Language is properly declared.';
        } else {
            $result['score'] = 1;
            $result['details'] = 'Lang attribute is empty';
            $result['suggestion'] = 'Add proper language code to lang attribute.';
        }
    } else {
        $result['score'] = 1;
        $result['details'] = 'No language declaration found';
        $result['suggestion'] = 'Add lang attribute to html tag (e.g., lang="en").';
    }
    
    return $result;
}

function analyzeFaviconIndicator($xpath, $final_url) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $favicon_nodes = $xpath->query('//link[@rel="icon" or @rel="shortcut icon" or @rel="apple-touch-icon"]');
    
    if ($favicon_nodes->length > 0) {
        $result['score'] = 3;
        $result['details'] = 'Favicon detected';
        $result['suggestion'] = 'Excellent! Favicon is properly configured.';
    } else {
        // Check for default favicon.ico
        $favicon_url = rtrim($final_url, '/') . '/favicon.ico';
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 3,
                'ignore_errors' => true
            ]
        ]);
        
        $headers = @get_headers($favicon_url, 1, $context);
        if ($headers && strpos($headers[0], '200') !== false) {
            $result['score'] = 2;
            $result['details'] = 'Default favicon.ico found';
            $result['suggestion'] = 'Consider adding explicit favicon link tags.';
        } else {
            $result['score'] = 1;
            $result['details'] = 'No favicon detected';
            $result['suggestion'] = 'Add favicon link tags for better branding.';
        }
    }
    
    return $result;
}

function analyzeCanonicalIndicator($xpath, $final_url) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $canonical_nodes = $xpath->query('//link[@rel="canonical"]');
    if ($canonical_nodes->length > 0) {
        $canonical_url = $canonical_nodes->item(0)->getAttribute('href');
        if (!empty($canonical_url)) {
            $result['score'] = 3;
            $result['details'] = 'Canonical URL: ' . htmlspecialchars($canonical_url);
            $result['suggestion'] = 'Perfect! Canonical URL is properly set.';
        } else {
            $result['score'] = 1;
            $result['details'] = 'Canonical tag exists but href is empty';
            $result['suggestion'] = 'Add proper URL to canonical tag.';
        }
    } else {
        $result['score'] = 2;
        $result['details'] = 'No canonical tag found';
        $result['suggestion'] = 'Consider adding canonical tag to prevent duplicate content issues.';
    }
    
    return $result;
}

function analyzeSocialMetaTagsIndicator($xpath) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $og_title = $xpath->query('//meta[@property="og:title"]');
    $og_description = $xpath->query('//meta[@property="og:description"]');
    $twitter_card = $xpath->query('//meta[@name="twitter:card"]');
    
    $score = 0;
    $found_tags = [];
    
    if ($og_title->length > 0) {
        $score += 1;
        $found_tags[] = 'og:title';
    }
    if ($og_description->length > 0) {
        $score += 1;
        $found_tags[] = 'og:description';
    }
    if ($twitter_card->length > 0) {
        $score += 1;
        $found_tags[] = 'twitter:card';
    }
    
    $result['score'] = $score;
    
    if ($score === 3) {
        $result['details'] = 'Complete social media tags';
        $result['suggestion'] = 'Excellent! All major social media tags are present.';
    } elseif ($score > 0) {
        $result['details'] = 'Partial social media tags: ' . implode(', ', $found_tags);
        $result['suggestion'] = 'Add missing social media meta tags for better sharing.';
    } else {
        $result['details'] = 'No social media tags found';
        $result['suggestion'] = 'Add OpenGraph and Twitter meta tags for social sharing.';
    }
    
    return $result;
}

function analyzeAriaIndicator($xpath) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $aria_elements = $xpath->query('//*[@aria-label or @aria-labelledby or @aria-describedby or @role]');
    $form_elements = $xpath->query('//input[@type="text"] | //input[@type="email"] | //input[@type="password"] | //textarea | //select');
    
    $aria_count = $aria_elements->length;
    $form_count = $form_elements->length;
    
    if ($form_count === 0) {
        $result['score'] = 3;
        $result['details'] = 'No forms to analyze';
        $result['suggestion'] = 'No accessibility issues with forms.';
    } elseif ($aria_count > 0) {
        $result['score'] = 3;
        $result['details'] = "Found {$aria_count} ARIA elements";
        $result['suggestion'] = 'Good accessibility implementation with ARIA attributes.';
    } else {
        $result['score'] = 2;
        $result['details'] = 'Limited ARIA implementation';
        $result['suggestion'] = 'Consider adding ARIA attributes for better accessibility.';
    }
    
    return $result;
}

function analyzePageSpeedIndicator($final_url) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    // Check if Google PageSpeed API key is available
    if (!defined('GOOGLE_PAGESPEED_API_KEY') || empty(GOOGLE_PAGESPEED_API_KEY)) {
        $result['score'] = 1;
        $result['details'] = 'PageSpeed API key not configured';
        $result['suggestion'] = 'Configure Google PageSpeed API key for detailed analysis.';
        return $result;
    }
    
    // Call Google PageSpeed Insights API
    $api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=" . urlencode($final_url) . "&key=" . GOOGLE_PAGESPEED_API_KEY;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($api_url, false, $context);
    
    if ($response === false) {
        $result['score'] = 1;
        $result['details'] = 'PageSpeed API call failed';
        $result['suggestion'] = 'Unable to analyze page speed. Please try again.';
        return $result;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['lighthouseResult']['categories']['performance']['score'])) {
        $score = $data['lighthouseResult']['categories']['performance']['score'] * 100;
        
        if ($score >= 90) {
            $result['score'] = 3;
        } elseif ($score >= 50) {
            $result['score'] = 2;
        } else {
            $result['score'] = 1;
        }
        
        $result['details'] = 'Performance Score: ' . round($score) . '/100';
        $result['suggestion'] = 'Page speed analysis completed. Check details for optimization opportunities.';
    } else {
        $result['score'] = 1;
        $result['details'] = 'PageSpeed analysis failed';
        $result['suggestion'] = 'Unable to get page speed data. Please try again.';
    }
    
    return $result;
}

function analyzeRobotsTxtIndicator($domain) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $robots_url = rtrim($domain, '/') . '/robots.txt';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $robots_content = @file_get_contents($robots_url, false, $context);
    
    // Static logic
    $static_suggestion = '';
    if ($robots_content !== false) {
        $result['score'] = 3;
        $result['details'] = 'Robots.txt found';
        $static_suggestion = 'Excellent! Robots.txt is properly configured.';
    } else {
        $result['score'] = 2;
        $result['details'] = 'No robots.txt found';
        $static_suggestion = 'Consider adding robots.txt file for search engine guidance.';
    }
    
    // Try to get AI suggestion for robots.txt analysis
    if (function_exists('call_openrouter_api')) {
        $prompt = "Analyze robots.txt for SEO: URL: {$robots_url}, Found: " . ($robots_content !== false ? 'Yes' : 'No') . ". Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice). Current static analysis: {$static_suggestion}";
        $ai_response = call_openrouter_api($prompt);
        
        storeDebugInfo('OpenRouter', 'robots', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
        
        if ($ai_response['success'] && isset($ai_response['raw_response'])) {
            $ai_data = json_decode($ai_response['raw_response'], true);
            if ($ai_data && isset($ai_data['suggestion'])) {
                $result['suggestion'] = $ai_data['suggestion'] . '<br><br><strong>Static Analysis:</strong> ' . $static_suggestion;
            } else {
                $result['suggestion'] = $static_suggestion;
            }
        } else {
            $result['suggestion'] = $static_suggestion;
        }
    } else {
        storeDebugInfo('OpenRouter', 'robots', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
        $result['suggestion'] = $static_suggestion;
    }
    
    return $result;
}

function analyzeSitemapXmlIndicator($domain) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $sitemap_url = rtrim($domain, '/') . '/sitemap.xml';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $sitemap_content = @file_get_contents($sitemap_url, false, $context);
    
    // Static logic
    $static_suggestion = '';
    if ($sitemap_content !== false) {
        $result['score'] = 3;
        $result['details'] = 'Sitemap.xml found';
        $static_suggestion = 'Excellent! XML sitemap is properly configured.';
    } else {
        $result['score'] = 2;
        $result['details'] = 'No sitemap.xml found';
        $static_suggestion = 'Consider adding XML sitemap for better search engine indexing.';
    }
    
    // Try to get AI suggestion for sitemap analysis
    if (function_exists('call_openrouter_api')) {
        $prompt = "Analyze sitemap.xml for SEO: URL: {$sitemap_url}, Found: " . ($sitemap_content !== false ? 'Yes' : 'No') . ". Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice). Current static analysis: {$static_suggestion}";
        $ai_response = call_openrouter_api($prompt);
        
        storeDebugInfo('OpenRouter', 'sitemap', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
        
        if ($ai_response['success'] && isset($ai_response['raw_response'])) {
            $ai_data = json_decode($ai_response['raw_response'], true);
            if ($ai_data && isset($ai_data['suggestion'])) {
                $result['suggestion'] = $ai_data['suggestion'] . '<br><br><strong>Static Analysis:</strong> ' . $static_suggestion;
            } else {
                $result['suggestion'] = $static_suggestion;
            }
        } else {
            $result['suggestion'] = $static_suggestion;
        }
    } else {
        storeDebugInfo('OpenRouter', 'sitemap', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
        $result['suggestion'] = $static_suggestion;
    }
    
    return $result;
}

function analyzeFilenamesIndicator($xpath) {
    $result = ['score' => null, 'details' => '-', 'suggestion' => '-'];
    
    $img_nodes = $xpath->query('//img[@src]');
    $link_nodes = $xpath->query('//a[@href]');
    
    $total_files = 0;
    $problematic_files = 0;
    $file_issues = [];
    
    // Check image filenames
    foreach ($img_nodes as $img) {
        $src = $img->getAttribute('src');
        if (strpos($src, 'data:') !== 0) { // Skip base64 images
            $total_files++;
            $filename = basename(parse_url($src, PHP_URL_PATH));
            
            if (preg_match('/[A-Z]/', $filename)) {
                $problematic_files++;
                $file_issues[] = 'Uppercase letters in ' . $filename;
            }
            if (strpos($filename, ' ') !== false) {
                $problematic_files++;
                $file_issues[] = 'Spaces in ' . $filename;
            }
            if (preg_match('/[^a-zA-Z0-9.-]/', $filename)) {
                $problematic_files++;
                $file_issues[] = 'Special characters in ' . $filename;
            }
        }
    }
    
    // Check linked file filenames
    foreach ($link_nodes as $link) {
        $href = $link->getAttribute('href');
        if (preg_match('/\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar)$/i', $href)) {
            $total_files++;
            $filename = basename(parse_url($href, PHP_URL_PATH));
            
            if (preg_match('/[A-Z]/', $filename)) {
                $problematic_files++;
                $file_issues[] = 'Uppercase letters in ' . $filename;
            }
        }
    }
    
    // Static logic
    $static_suggestion = '';
    if ($total_files === 0) {
        $result['score'] = 3;
        $result['details'] = 'No files to analyze';
        $static_suggestion = 'No file naming issues detected.';
    } elseif ($problematic_files === 0) {
        $result['score'] = 3;
        $result['details'] = "All {$total_files} files have SEO-friendly names";
        $static_suggestion = 'Excellent! All files follow SEO naming conventions.';
    } elseif ($problematic_files < $total_files / 2) {
        $result['score'] = 2;
        $result['details'] = "{$problematic_files}/{$total_files} files have naming issues";
        $static_suggestion = 'Some files need better naming: ' . implode(', ', array_slice($file_issues, 0, 3));
    } else {
        $result['score'] = 1;
        $result['details'] = "{$problematic_files}/{$total_files} files have naming issues";
        $static_suggestion = 'Many files need better naming: ' . implode(', ', array_slice($file_issues, 0, 3));
    }
    
    // Try to get AI suggestion for filenames analysis
    if (function_exists('call_openrouter_api')) {
        $prompt = "Analyze file naming for SEO: Total files: {$total_files}, Problematic files: {$problematic_files}, Issues: " . implode(', ', array_slice($file_issues, 0, 5)) . ". Provide a JSON response with 'score' (1-3) and 'suggestion' (specific improvement advice). Current static analysis: {$static_suggestion}";
        $ai_response = call_openrouter_api($prompt);
        
        storeDebugInfo('OpenRouter', 'filenames', $prompt, $ai_response, $ai_response['success'] ?? false, $ai_response['message'] ?? '');
        
        if ($ai_response['success'] && isset($ai_response['raw_response'])) {
            $ai_data = json_decode($ai_response['raw_response'], true);
            if ($ai_data && isset($ai_data['suggestion'])) {
                $result['suggestion'] = $ai_data['suggestion'] . '<br><br><strong>Static Analysis:</strong> ' . $static_suggestion;
            } else {
                $result['suggestion'] = $static_suggestion;
            }
        } else {
            $result['suggestion'] = $static_suggestion;
        }
    } else {
        storeDebugInfo('OpenRouter', 'filenames', 'N/A', 'Function not available', false, 'call_openrouter_api function not found');
        $result['suggestion'] = $static_suggestion;
    }
    
    return $result;
}

$layout_class = 'initial-view'; // Default layout

// Clear session data on fresh page load (hard refresh detection)
// But don't clear if we just redirected from a form submission
if (!isset($_SESSION['page_loaded']) || $_SESSION['page_loaded'] !== true) {
    // Only clear if this is not a POST redirect and debug mode is not active
    if (!isset($_SESSION['is_post_redirect_layout_active']) && 
        !isset($_GET['debug']) && !isset($_SESSION['debug_mode'])) {
        // This is a fresh page load, clear all session data
        unset($_SESSION['seo_results_data']);
        unset($_SESSION['overall_score_data']);
        unset($_SESSION['last_domain_for_view']);
        unset($_SESSION['rate_limit_error']);
        unset($_SESSION['site_not_accessible_error']);
        unset($_SESSION['debug_calls']);
        // Don't clear debug_mode - it should persist across redirects
        // // Don't clear debug_mode - it should persist across redirects
        // unset($_SESSION['debug_mode']);
        unset($_SESSION['jobs']);
    }
    $_SESSION['page_loaded'] = true;
}

// Also clear session data if this is a hard refresh (no referrer or different referrer)
// But don't clear if debug mode is active
if ((!isset($_SERVER['HTTP_REFERER']) || 
    (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false)) &&
    !isset($_GET['debug']) && !isset($_SESSION['debug_mode'])) {
    // This might be a hard refresh, clear all session data
    unset($_SESSION['seo_results_data']);
    unset($_SESSION['overall_score_data']);
    unset($_SESSION['last_domain_for_view']);
    unset($_SESSION['rate_limit_error']);
    unset($_SESSION['site_not_accessible_error']);
    unset($_SESSION['debug_calls']);
    // Don't clear debug_mode - it should persist across redirects
        // unset($_SESSION['debug_mode']);
    unset($_SESSION['jobs']);
    unset($_SESSION['is_post_redirect_layout_active']);
}

// Additional hard refresh detection - if more than 5 minutes have passed since last activity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 300) {
    // Clear all session data for old sessions
    unset($_SESSION['seo_results_data']);
    unset($_SESSION['overall_score_data']);
    unset($_SESSION['last_domain_for_view']);
    unset($_SESSION['rate_limit_error']);
    unset($_SESSION['site_not_accessible_error']);
    unset($_SESSION['debug_calls']);
    // Don't clear debug_mode - it should persist across redirects
        // unset($_SESSION['debug_mode']);
    unset($_SESSION['jobs']);
    unset($_SESSION['is_post_redirect_layout_active']);
}
$_SESSION['last_activity'] = time();

// Check for session-based redirect AFTER session clearing
if (isset($_SESSION['is_post_redirect_layout_active']) && $_SESSION['is_post_redirect_layout_active']) {
    $layout_class = 'results-view';
    unset($_SESSION['is_post_redirect_layout_active']); // Consume the flag
}

// The helper functions (render_stars, get_score_row_class), 
// $indicator_weights, and default $indicators_results have been moved to seo_logic.php
// and should be removed from here if they exist below this comment.

// --- Load results AND overall score from session ---
$overall_score_to_display = $_SESSION['overall_score_data'] ?? null; 
// Use default from seo_logic.php if no session data, or if you define a minimal one here for the view.
// For simplicity, assuming seo_logic.php's defaults are sufficient if session is empty.
// If $indicators_results is needed here for the initial empty table structure before POST, 
// ensure it's defined (e.g., $indicators_results = $_SESSION['seo_results_data'] ?? $default_indicator_keys_for_logic; 
// where $default_indicator_keys_for_logic would come from seo_logic.php or be defined minimally here)
// For now, relying on the session load which itself relies on $default_indicator_keys_for_logic from seo_logic.php for initial POST run.
// Let's ensure a default empty state for the view if session is not set from a POST yet.
$indicators_results = array_fill_keys(
    array_keys($GLOBALS['default_indicator_keys_for_logic'] ?? [
        'url'=>'', 'title'=>'', 'meta_description'=>'', 'robots'=>'', 'sitemap'=>'', 'h1'=>'', 'filenames'=>'', 'image_alt'=>'', 'links'=>'', '404_page'=>'', 'mobile_friendly'=>'', 'language'=>'', 'favicon'=>'', 'canonical'=>'', 'social_meta_tags'=>'', 'aria'=>'', 'page_speed'=>''
    ]),
    ['score' => 'mocked', 'details' => 'mocked', 'suggestion' => 'mocked']
);

// Unmock the URL indicator - show initial values (will be overwritten by analysis if performed)
error_log("Before unmocking check - indicators_results[url]: " . json_encode($indicators_results['url'] ?? 'NOT_SET'));
if (!isset($indicators_results['url']) || $indicators_results['url']['score'] === 'mocked') {
    error_log("Unmocking URL indicator - resetting to initial values");
    $indicators_results['url'] = ['score' => null, 'details' => '-', 'suggestion' => '-'];
} else {
    error_log("URL indicator already set, not unmocking: " . json_encode($indicators_results['url']));
}

// In debug mode, analyze URL indicator if we have a domain
if (isset($_GET['debug']) && !empty($last_domain_value)) {
    $final_url = "https://{$last_domain_value}";
    
    if (function_exists('analyzeUrlIndicator')) {
        $url_result = analyzeUrlIndicator($last_domain_value, $final_url);
        $indicators_results['url'] = $url_result;
        
        // Store in session for debugging
        $_SESSION['url_analysis_debug'] = [
            'domain' => $last_domain_value,
            'final_url' => $final_url,
            'result' => $url_result,
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => 'URL analysis in debug mode'
        ];
        
        error_log("Debug mode URL analysis completed: " . json_encode($url_result));
    }
}

$overall_score_display_data = $_SESSION['overall_score_data'] ?? null;
$last_domain_value = $_SESSION['last_domain_for_view'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_domain_value = trim($_POST['domain'] ?? '');
    error_log("Form submitted with domain: " . $last_domain_value);
    
    // Directly analyze URL indicator when form is submitted
    if ($last_domain_value && !empty($last_domain_value)) {
        // Fetch page content for URL analysis
        $url_schemes_to_try = [];
        if (!preg_match("~^(?:f|ht)tps?://~i", $last_domain_value)) {
            $url_schemes_to_try[] = "https://{$last_domain_value}";
            $url_schemes_to_try[] = "http://{$last_domain_value}";
        } else {
            $url_schemes_to_try[] = $last_domain_value;
        }
        
        $final_url = $last_domain_value;
        foreach ($url_schemes_to_try as $current_url_to_check) {
            $context_options = [
                'http' => [
                    'method' => "GET", 
                    'timeout' => 10, 
                    'ignore_errors' => true, 
                    'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
                ]
            ];
            
            $page_content = @file_get_contents($current_url_to_check, false, stream_context_create($context_options));
            if ($page_content !== false) {
                $final_url = $current_url_to_check;
                break;
            }
        }
        
        // Analyze URL indicator directly
        error_log("About to analyze URL indicator for domain: $last_domain_value, final_url: $final_url");
        
        if (function_exists('analyzeUrlIndicator')) {
            error_log("analyzeUrlIndicator function exists");
            $url_result = analyzeUrlIndicator($last_domain_value, $final_url);
            $indicators_results['url'] = $url_result;
            error_log("URL analysis result: " . json_encode($url_result));
            
            // Also store in session for debugging
            $_SESSION['url_analysis_debug'] = [
                'domain' => $last_domain_value,
                'final_url' => $final_url,
                'result' => $url_result,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            error_log("analyzeUrlIndicator function does NOT exist");
            $indicators_results['url'] = ['score' => 1, 'details' => 'Function not found', 'suggestion' => 'Error: analyzeUrlIndicator function not found'];
            $_SESSION['url_analysis_debug'] = [
                'error' => 'analyzeUrlIndicator function not found',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
}
error_log("Last domain value: " . $last_domain_value);
// In debug mode, show results view to trigger progressive analysis (whether domain is set or not)
if (isset($_GET['debug']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $layout_class = 'results-view'; // Show results view to trigger progressive analysis
    error_log("Setting layout_class to results-view for debug mode");
} else {
    error_log("Layout class remains: " . $layout_class);
}
$rate_limit_error = $_SESSION['rate_limit_error'] ?? null;
if ($rate_limit_error) {
    unset($_SESSION['rate_limit_error']); // Clear it after reading
}
$site_not_accessible_error = $_SESSION['site_not_accessible_error'] ?? null;
if ($site_not_accessible_error) {
    unset($_SESSION['site_not_accessible_error']); // Clear it after reading
}
// Enable API debugging when debug parameter is present
// $debug_mode is now defined at the top of the file
if ($debug_mode) {
    $openrouter_response_data = $_SESSION['openrouter_response'] ?? null;
    if ($openrouter_response_data) {
        unset($_SESSION['openrouter_response']); // Clear after reading
    }

    $pagespeed_response_data = $_SESSION['pagespeed_response'] ?? null;
    if ($pagespeed_response_data) {
        unset($_SESSION['pagespeed_response']); // Clear after reading
    }
} else {
    // Hide API debugging for production deployment
    $openrouter_response_data = null;
    $pagespeed_response_data = null;
}

?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEOIndicator.com - Analyze your SEO parameters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> 
    <style>
      /* All custom styles have been moved to style.css */
    </style>
  </head>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-RWCSLW7EXF"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-RWCSLW7EXF');
    </script>

  <body class="<?php echo $layout_class; ?>">
    
    <!-- Contact Email Link in Top Right -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <a href="mailto:info@SEOIndicator.com" class="text-white text-decoration-none" style="transition: color 0.3s ease;" title="Contact us" onmouseover="this.style.color='#0d6efd'" onmouseout="this.style.color='white'">
            info@SEOIndicator.com
        </a>
    </div>
    
    <div id="loader-overlay" style="display:none;"></div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12 text-center"> 
                <img src="Logo-SEOIndicator-com.png" alt="SEOIndicator.com Logo" style="max-width: 200px; margin-bottom: 20px;"> 
                <h1 class="mb-4 main-title">AI Powered SEO Analysis</h1>

                <?php if ($rate_limit_error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($rate_limit_error); ?>
                </div>
                <?php endif; ?>

                <?php if ($site_not_accessible_error): ?>
                <div class="alert alert-warning" role="alert">
                    <?php echo htmlspecialchars($site_not_accessible_error); ?>
                </div>
                <?php endif; ?>

                <?php if ($debug_mode): ?>
                    <!-- Debug Information -->
                    <div class="alert alert-warning mt-3" role="alert">
                        <h5><strong>🔍 Debug Mode Active</strong></h5>
                        <p>Debug mode is enabled. API calls and responses will be shown below.</p>
                    </div>
                    
                    <?php if (isset($_SESSION['url_analysis_debug'])): ?>
                        <div class="alert alert-success mt-3" role="alert">
                            <h6><strong>🔧 URL Analysis Debug:</strong></h6>
                            <pre><?php echo htmlspecialchars(json_encode($_SESSION['url_analysis_debug'], JSON_PRETTY_PRINT)); ?></pre>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['debug_calls']) && !empty($_SESSION['debug_calls'])): ?>
                        <div class="alert alert-info mt-3" role="alert">
                            <h6><strong>📡 API Calls Made (<?php echo count($_SESSION['debug_calls']); ?>):</strong></h6>
                            <?php foreach ($_SESSION['debug_calls'] as $call): ?>
                                <div class="mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($call['type']); ?></strong> - 
                                            <span class="badge bg-<?php echo $call['success'] ? 'success' : 'danger'; ?>">
                                                <?php echo $call['success'] ? 'Success' : 'Failed'; ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">Indicator: <?php echo htmlspecialchars($call['indicator']); ?> | <?php echo htmlspecialchars($call['timestamp']); ?></small>
                                        </div>
                                    </div>
                                    
                                    <?php if ($call['message']): ?>
                                        <p class="mb-2"><strong>Message:</strong> <?php echo htmlspecialchars($call['message']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Request:</strong>
                                            <pre style="font-size: 0.8em; max-height: 200px; overflow-y: auto;"><?php echo htmlspecialchars($call['request']); ?></pre>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Response:</strong>
                                            <pre style="font-size: 0.8em; max-height: 200px; overflow-y: auto;"><?php echo htmlspecialchars(json_encode($call['response'], JSON_PRETTY_PRINT)); ?></pre>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php unset($_SESSION['debug_calls']); // Clear after displaying ?>
                    <?php endif; ?>
                    
                    <?php if ($openrouter_response_data): ?>
                        <div class="alert <?php echo $openrouter_response_data['success'] ? 'alert-info' : 'alert-danger'; ?> mt-3" role="alert">
                            <p><strong>OpenRouter API Status:</strong> <?php echo htmlspecialchars($openrouter_response_data['message']); ?></p>
                            <?php if (isset($openrouter_response_data['raw_request'])): ?>
                                <p><strong>Raw Request:</strong></p>
                                <pre style="white-space: pre-wrap; word-break: break-all; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($openrouter_response_data['raw_request']); ?></pre>
                            <?php endif; ?>
                            <?php if (isset($openrouter_response_data['raw_response'])): ?>
                                <p><strong>Raw Response:</strong></p>
                                <pre style="white-space: pre-wrap; word-break: break-all; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($openrouter_response_data['raw_response']); ?></pre>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($pagespeed_response_data): ?>
                        <div class="alert <?php echo $pagespeed_response_data['success'] ? 'alert-info' : 'alert-danger'; ?> mt-3" role="alert">
                            <p><strong>Google PageSpeed Insights API Status:</strong> <?php echo htmlspecialchars($pagespeed_response_data['message']); ?></p>
                            <?php if (isset($pagespeed_response_data['raw_request'])): ?>
                                <p><strong>Raw Request:</strong></p>
                                <pre style="white-space: pre-wrap; word-break: break-all; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($pagespeed_response_data['raw_request']); ?></pre>
                            <?php endif; ?>
                            <?php if (isset($pagespeed_response_data['raw_response'])): ?>
                                <p><strong>Raw Response:</strong></p>
                                <pre style="white-space: pre-wrap; word-break: break-all; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($pagespeed_response_data['raw_response']); ?></pre>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="post" action="<?php echo (isset($_GET['debug']) ? '?debug=' . urlencode($_GET['debug']) : ''); ?>" class="mb-4 seo-input-form" id="seo-form">
                    <div class="input-group input-group-lg mb-3">
                        <input type="text" class="form-control" name="domain" id="domain-input" placeholder="Enter domain name (e.g., google.com)" aria-label="Domain name" required value="<?php echo htmlspecialchars($last_domain_value); ?>" list="domain-suggestions" autocomplete="off">
                        <button class="btn btn-primary custom-submit-btn" type="submit" id="submit-btn">Analyze!</button>
                    </div>
                    <datalist id="domain-suggestions">
                        <?php
                        // Get recent domains from database for suggestions
                        $conn = get_db_connection();
                        if ($conn) {
                            $stmt = $conn->prepare("SELECT DISTINCT domain FROM data WHERE domain IS NOT NULL AND domain != '' ORDER BY dt_updated DESC LIMIT 10");
                            if ($stmt) {
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['domain']) . '">';
                                }
                                $stmt->close();
                            }
                            $conn->close();
                        }
                        ?>
                    </datalist>
                </form>

                <div class="overall-score-container mt-4 mb-3">
                    <h2>
                        <?php if (!empty($last_domain_value) && $layout_class === 'results-view'): ?>
                            <strong><?php echo htmlspecialchars($last_domain_value); ?></strong> - 
                        <?php endif; ?>
                        Overall score: 
                        <?php 
                        if (isset($overall_score_display_data['color_score'])) {
                            echo render_stars(
                                $overall_score_display_data['color_score'], 
                                true, 
                                $overall_score_display_data['achieved'], 
                                $overall_score_display_data['possible']
                            );
                        } else {
                            // Default display for initial view (no score calculated yet)
                            echo render_stars(null, true); 
                        }
                        ?>
                    </h2>
                </div>
                
                <!-- Progress Bar -->
                <div class="progress-container mt-3 mb-4" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">Analysis Progress</small>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted progress-text">0/17</small>
                            <button class="btn btn-sm btn-outline-danger cancel-analysis-btn" style="display: none;">
                                Cancel
                            </button>
                        </div>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" aria-valuenow="0" 
                             aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive result-container">
                    <table class="table table-bordered table-dark">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 5%;">Nr.</th> 
                                <th scope="col" style="width: 15%;">Indicator</th>
                                <th scope="col" style="width: 10%;">Score</th> 
                                <th scope="col" style="width: 25%;">Result</th>
                                <th scope="col" style="width: 45%;">Suggestion</th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider">
                            <?php
                            // FINAL URL analysis right before table generation
                            if (isset($_GET['debug']) && isset($_GET['test_url'])) {
                                $test_domain = $_GET['test_url'];
                                $test_final_url = "https://{$test_domain}";
                                
                                if (function_exists('analyzeUrlIndicator')) {
                                    $final_url_result = analyzeUrlIndicator($test_domain, $test_final_url);
                                    $indicators_results['url'] = $final_url_result;
                                    error_log("FINAL assignment right before table - URL result: " . json_encode($final_url_result));
                                }
                            }
                            ?>
                            <!-- DEBUG: URL result at table generation = <?php echo htmlspecialchars(json_encode($indicators_results['url'] ?? 'NOT_SET')); ?> -->
                            <?php error_log("Just before table generation - indicators_results[url]: " . json_encode($indicators_results['url'] ?? 'NOT_SET')); ?>
                            <?php $row_nr = 1; ?>
                            <?php foreach ($indicators_results as $key => $result): ?>
                                <?php if ($key === 'accessibility') continue; ?>
                                <?php if ($key === 'url'): ?>
                                    <?php error_log("Table generation - URL result: " . json_encode($result)); ?>
                                <?php endif; ?>
                                <!-- DEBUG: Processing key = <?php echo htmlspecialchars($key); ?> -->
                                <tr class="<?php echo get_score_row_class($result['score']); ?>" data-indicator="<?php echo htmlspecialchars($key); ?>">
                                    <td><?php echo $row_nr++; ?></td> 
                                    <td>
                                        <?php if ($key === 'title'): ?>
                                            <a href="/seo-guides/title-tag" target="_blank" title="Title Tag SEO Guide">
                                                <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                                            </a>
                                                                <?php elseif ($key === 'meta_description'): ?>
                            <a href="/seo-guides/meta-description" target="_blank" title="Meta Description SEO Guide">
                                <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                            </a>
                        <?php elseif ($key === 'url'): ?>
                            <a href="/seo-guides/url" target="_blank" title="URL SEO Guide">
                                URL
                            </a>
                            <!-- DEBUG: URL result = <?php echo htmlspecialchars(json_encode($result)); ?> -->
                                                <?php elseif ($key === 'robots'): ?>
                            <a href="/seo-guides/robots-txt" target="_blank" title="Robots.txt SEO Guide">
                                robots.txt
                            </a>
                        <?php elseif ($key === 'sitemap'): ?>
                            <a href="/seo-guides/sitemap-xml" target="_blank" title="Sitemap.xml SEO Guide">
                                sitemap.xml
                            </a>
                        <?php elseif ($key === 'h1'): ?>
                            <a href="/seo-guides/headings" target="_blank" title="Headings SEO Guide">
                                Headings
                            </a>
                        <?php elseif ($key === 'filenames'): ?>
                            <a href="/seo-guides/filenames" target="_blank" title="Filenames SEO Guide">
                                Filenames
                            </a>
                                                <?php elseif ($key === 'image_alt'): ?>
                            <a href="/seo-guides/image-alt" target="_blank" title="Image Alt SEO Guide">
                                Image Alt
                            </a>
                        <?php elseif ($key === 'links'): ?>
                            <a href="/seo-guides/links" target="_blank" title="Links SEO Guide">
                                Links
                            </a>
                        <?php elseif ($key === '404_page'): ?>
                            <a href="/seo-guides/404-page" target="_blank" title="404 Page SEO Guide">
                                404 Page
                            </a>
                        <?php elseif ($key === 'mobile_friendly'): ?>
                            <a href="/seo-guides/mobile-friendly" target="_blank" title="Mobile Friendly SEO Guide">
                                Mobile Friendly
                            </a>
                        <?php elseif ($key === 'language'): ?>
                            <a href="/seo-guides/language" target="_blank" title="Language SEO Guide">
                                Language
                            </a>
                        <?php elseif ($key === 'favicon'): ?>
                            <a href="/seo-guides/favicon" target="_blank" title="Favicon SEO Guide">
                                Favicon
                            </a>
                        <?php elseif ($key === 'canonical'): ?>
                            <a href="/seo-guides/canonical-tag" target="_blank" title="Canonical Tag SEO Guide">
                                Canonical Tag
                            </a>
                        <?php elseif ($key === 'social_meta_tags'): ?>
                            <a href="/seo-guides/social-media-meta-tags" target="_blank" title="Social Media Meta Tags SEO Guide">
                                Social Media Meta Tags
                            </a>
                        <?php elseif ($key === 'aria'): ?>
                            <a href="/seo-guides/aria" target="_blank" title="ARIA SEO Guide">
                                ARIA
                            </a>
                        <?php elseif ($key === 'page_speed'): ?>
                            <a href="/seo-guides/page-speed" target="_blank" title="Page Speed SEO Guide">
                                Page Speed
                            </a>
                                        <?php else: ?>
                                            <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                                        <?php endif; ?>

                                    </td>
                                    <td class="score-cell"><?php 
                                        if ($key === 'url' && isset($_GET['debug']) && isset($_GET['test_url'])) {
                                            // Direct URL analysis for debug mode
                                            echo "DIRECT_ANALYSIS_TRIGGERED";
                                            $direct_domain = $_GET['test_url'];
                                            $direct_final_url = "https://{$direct_domain}";
                                            $direct_result = analyzeUrlIndicator($direct_domain, $direct_final_url);
                                            echo render_stars($direct_result['score']);
                                        } elseif ($key === 'url') {
                                            echo 'DEBUG: ' . json_encode($result);
                                        } elseif (is_string($result['score']) && strtolower($result['score']) === 'mocked') {
                                            echo 'mocked';
                                        } else {
                                            echo ($result['score'] !== null) ? render_stars($result['score']) : '-';
                                        }
                                    ?></td>
                                    <td class="result-cell"><?php 
                                        if ($key === 'url' && isset($_GET['debug']) && isset($_GET['test_url'])) {
                                            // Direct URL analysis for debug mode
                                            $direct_domain = $_GET['test_url'];
                                            $direct_final_url = "https://{$direct_domain}";
                                            $direct_result = analyzeUrlIndicator($direct_domain, $direct_final_url);
                                            echo htmlspecialchars($direct_result['details']);
                                        } elseif (isset($result['details'])) {
                                            if ($key === 'title' || $key === 'meta_description' || $key === 'url' || $key === 'robots' || $key === 'sitemap' || $key === 'h1' || $key === 'filenames' || $key === 'image_alt' || $key === 'links' || $key === '404_page' || $key === 'mobile_friendly' || $key === 'language' || $key === 'favicon' || $key === 'canonical' || $key === 'social_meta_tags' || $key === 'aria' || $key === 'page_speed') {
                                                echo $result['details'];
                                            } else {
                                                echo htmlspecialchars($result['details']);
                                            }
                                        } else {
                                            echo '-';
                                        }
                                    ?></td>
                                    <td class="suggestion-cell"><?php 
                                        if ($key === 'url' && isset($_GET['debug']) && isset($_GET['test_url'])) {
                                            // Direct URL analysis for debug mode
                                            $direct_domain = $_GET['test_url'];
                                            $direct_final_url = "https://{$direct_domain}";
                                            $direct_result = analyzeUrlIndicator($direct_domain, $direct_final_url);
                                            echo $direct_result['suggestion'];
                                        } elseif (isset($result['suggestion'])) {
                                            if ($key === 'url' || $key === '404_page' || $key === 'mobile_friendly' || $key === 'language' || $key === 'favicon' || $key === 'canonical' || $key === 'social_meta_tags' || $key === 'aria' || $key === 'page_speed') {
                                                // For URL, 404 Page, Mobile Friendly, Language, Favicon, Canonical Tag, Social Media Meta Tags, ARIA, and Page Speed suggestions, allow HTML (specifically <br> tags)
                                                echo $result['suggestion'];
                                            } else {
                                                // For other suggestions, escape HTML
                                                echo htmlspecialchars($result['suggestion']);
                                            }
                                        } else {
                                            echo '-';
                                        }
                                    ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('seo-form');
            const loaderOverlay = document.getElementById('loader-overlay');
            const body = document.body;
            
            if (form) {
                // Let form submit normally to trigger PHP POST handling
                form.addEventListener('submit', function() {
                    // Show loading overlay during form submission
                    if (loaderOverlay) loaderOverlay.style.display = 'block';
                });
            }

            // Debug mode: Process URL indicator immediately if we have a domain
            const lastDomain = <?php echo json_encode($last_domain_value); ?>;
            const isDebugMode = <?php echo isset($_GET['debug']) ? 'true' : 'false'; ?>;
            
            console.log('JavaScript Debug - lastDomain:', lastDomain);
            console.log('JavaScript Debug - isDebugMode:', isDebugMode);
            console.log('JavaScript Debug - URL search:', window.location.search);
            
            if (isDebugMode && lastDomain) {
                console.log('Debug mode: Processing URL indicator immediately for domain:', lastDomain);
                
                // Process URL indicator directly
                const urlRow = document.querySelector('tr[data-indicator="url"]');
                if (urlRow) {
                    const scoreCell = urlRow.querySelector('.score-cell');
                    const resultCell = urlRow.querySelector('.result-cell');
                    const suggCell = urlRow.querySelector('.suggestion-cell');
                    
                    if (scoreCell) scoreCell.textContent = '★★★';
                    if (resultCell) resultCell.textContent = 'https://' + lastDomain;
                    if (suggCell) suggCell.textContent = 'Excellent URL structure.';
                    
                    console.log('URL indicator updated directly in debug mode');
                }
            }
            
            // If it's the results view, trigger animations after a short delay for page render
            if (body.classList.contains('results-view')) {
                // Hide loader if it was somehow still visible (e.g., browser back button)
                if (loaderOverlay) { loaderOverlay.style.display = 'none'; }
                // Add class to trigger animations
                setTimeout(function() {
                    body.classList.add('content-loaded');
                }, 100); // Small delay to ensure styles are applied

                // Progressive analysis orchestration
                const lastDomain = <?php echo json_encode($last_domain_value); ?>;
                console.log('Progressive analysis: lastDomain =', lastDomain);
                console.log('Debug mode active:', <?php echo isset($_GET['debug']) ? 'true' : 'false'; ?>);
                console.log('Layout class:', '<?php echo $layout_class; ?>');
                console.log('Body has results-view class:', body.classList.contains('results-view'));
                
                // In debug mode, process URL indicator immediately
                if (<?php echo isset($_GET['debug']) ? 'true' : 'false'; ?> && lastDomain) {
                    console.log('Debug mode: Processing URL indicator immediately for domain:', lastDomain);
                    
                    // Process URL indicator directly
                    const urlRow = document.querySelector('tr[data-indicator="url"]');
                    if (urlRow) {
                        const scoreCell = urlRow.querySelector('.score-cell');
                        const resultCell = urlRow.querySelector('.result-cell');
                        const suggCell = urlRow.querySelector('.suggestion-cell');
                        
                        if (scoreCell) scoreCell.textContent = '★★★';
                        if (resultCell) resultCell.textContent = 'https://' + lastDomain;
                        if (suggCell) suggCell.textContent = 'Excellent URL structure.';
                        
                        console.log('URL indicator updated directly in debug mode');
                    }
                } else if (lastDomain) {
                    console.log('Starting progressive analysis for domain:', lastDomain);
                    // Initialize/clear rows to mocked state
                    const rows = document.querySelectorAll('tr[data-indicator]');
                    rows.forEach(row => {
                        row.classList.remove('table-success', 'table-warning', 'table-danger');
                        const scoreCell = row.querySelector('.score-cell');
                        const resultCell = row.querySelector('.result-cell');
                        const suggCell = row.querySelector('.suggestion-cell');
                        
                        // Don't mock the URL indicator - keep it as "-"
                        const indicator = row.getAttribute('data-indicator');
                        if (indicator === 'url') {
                            if (scoreCell) scoreCell.textContent = '-';
                            if (resultCell) resultCell.textContent = '-';
                            if (suggCell) suggCell.textContent = '-';
                        } else {
                            if (scoreCell) scoreCell.textContent = 'mocked';
                            if (resultCell) resultCell.textContent = 'mocked';
                            if (suggCell) suggCell.textContent = 'mocked';
                        }
                    });
                    
                    // Start job
                    const startForm = new FormData();
                    startForm.append('domain', lastDomain);

                    const debugParam = window.location.search.includes('debug=1') ? '&debug=1' : '';
                    fetch('?api=start-analysis' + debugParam, { method: 'POST', body: startForm })
                        .then(r => r.json())
                        .then(startData => {
                            if (!startData || !startData.success) return;
                            const jobId = startData.job_id;
                            const indicators = startData.indicators || [];
                            
                            // Show progress bar
                            const progressContainer = document.querySelector('.progress-container');
                            const progressBar = document.querySelector('.progress-bar');
                            const progressText = document.querySelector('.progress-text');
                            const cancelBtn = document.querySelector('.cancel-analysis-btn');
                            if (progressContainer) {
                                progressContainer.style.setProperty('display', 'block', 'important');
                            }
                            if (cancelBtn) cancelBtn.style.display = 'inline-block';
                            
                            // Cancel functionality
                            if (cancelBtn) {
                                cancelBtn.onclick = function() {
                                    const cancelForm = new FormData();
                                    cancelForm.append('job_id', jobId);
                                    fetch('?api=cancel-job', { method: 'POST', body: cancelForm })
                                        .then(() => {
                                            if (progressContainer) progressContainer.style.display = 'none';
                                            clearInterval(poll);
                                        });
                                };
                            }

                            const whitelistHtml = new Set(['url','404_page','mobile_friendly','language','favicon','canonical','social_meta_tags','aria','page_speed']);

                            function renderStars(score) {
                                if (score === null || score === undefined) return '(N/A)';
                                if (typeof score === 'string' && score.toLowerCase() === 'mocked') return 'mocked';
                                const n = Number(score);
                                if (Number.isNaN(n)) return 'mocked';
                                const s = Math.max(0, Math.min(3, n));
                                const filled = '★'.repeat(s);
                                const empty = '☆'.repeat(3 - s);
                                return filled + empty + ' (' + s + '/3)';
                            }

                            function rowClassForScore(score) {
                                if (typeof score === 'string' && score.toLowerCase() === 'mocked') return '';
                                const n = Number(score);
                                if (!Number.isFinite(n)) return '';
                                if (n >= 3) return 'table-success';
                                if (n === 2) return 'table-warning';
                                return 'table-danger';
                            }

                            function updateRow(indicator, result) {
                                console.log('updateRow called for indicator:', indicator, 'result:', result);
                                const row = document.querySelector('tr[data-indicator="' + indicator + '"]');
                                if (!row) {
                                    console.log('Row not found for indicator:', indicator);
                                    return;
                                }
                                // Update class
                                row.classList.remove('table-success','table-warning','table-danger');
                                if (result && typeof result.score !== 'undefined' && result.score !== null) {
                                    row.classList.add(rowClassForScore(result.score));
                                }
                                // Score cell
                                const scoreCell = row.querySelector('.score-cell');
                                if (scoreCell) {
                                    const stars = renderStars(result.score);
                                    console.log('Updating score cell for', indicator, 'to:', stars);
                                    scoreCell.textContent = stars;
                                }
                                // Result cell
                                const resultCell = row.querySelector('.result-cell');
                                if (resultCell) {
                                    if (whitelistHtml.has(indicator)) {
                                        resultCell.innerHTML = result.details || '-';
                                    } else {
                                        resultCell.textContent = result.details || '-';
                                    }
                                    console.log('Updating result cell for', indicator, 'to:', result.details);
                                }
                                // Suggestion cell
                                const suggCell = row.querySelector('.suggestion-cell');
                                if (suggCell) {
                                    if (whitelistHtml.has(indicator)) {
                                        suggCell.innerHTML = result.suggestion || '-';
                                    } else {
                                        suggCell.textContent = result.suggestion || '-';
                                    }
                                }
                            }
                            
                            function retryIndicator(indicator) {
                                const fd = new FormData();
                                fd.append('job_id', jobId);
                                fd.append('indicator', indicator);
                                fetch('?api=run-indicator' + debugParam, { method: 'POST', body: fd })
                                    .then(r => r.json())
                                    .then(d => {
                                        if (d && d.success && d.result) {
                                            updateRow(indicator, d.result);
                                        }
                                    })
                                    .catch(() => {});
                            }

                            // Throttled queue
                            const maxConcurrent = 5;
                            let active = 0; let index = 0;
                            function runNext() {
                                while (active < maxConcurrent && index < indicators.length) {
                                    const ind = indicators[index++];
                                    active++;
                                    const fd = new FormData();
                                    fd.append('job_id', jobId);
                                    fd.append('indicator', ind);
                                    console.log('Processing indicator:', ind);
                                    fetch('?api=run-indicator' + debugParam, { method: 'POST', body: fd })
                                        .then(r => r.json())
                                        .then(d => {
                                            console.log('Response for indicator', ind, ':', d);
                                            if (d && d.success && d.result) {
                                                updateRow(ind, d.result);
                                            }
                                        })
                                        .catch((error) => {
                                            console.log('Error processing indicator', ind, ':', error);
                                        })
                                        .finally(() => { active--; runNext(); });
                                }
                            }
                            runNext();

                            // Poll overall progress and stars
                            const poll = setInterval(() => {
                                fetch('?api=job-progress&job_id=' + encodeURIComponent(jobId))
                                    .then(r => r.json())
                                    .then(p => {
                                        if (!p || !p.success) return;
                                        
                                        // Update progress bar
                                        if (p.progress && progressBar && progressText) {
                                            const percentage = Math.round((p.progress.done / p.progress.total) * 100);
                                            progressBar.style.width = percentage + '%';
                                            progressBar.setAttribute('aria-valuenow', percentage);
                                            progressText.textContent = p.progress.done + '/' + p.progress.total;
                                        }
                                        
                                        // Stop polling when done or cancelled
                                        if (p.cancelled || (p.progress && p.progress.done === p.progress.total)) {
                                            clearInterval(poll);
                                            // Hide progress bar when complete
                                            if (progressContainer) {
                                                setTimeout(() => {
                                                    progressContainer.style.display = 'none';
                                                }, 2000);
                                            }
                                        }
                                    })
                                    .catch(() => {});
                            }, 1500);
                        })
                        .catch(() => {});
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html> 