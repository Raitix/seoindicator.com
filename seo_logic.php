<?php
// Include Database Configuration
require_once 'db_config.php';
require_once 'config.php'; // Include the new config file

// Helper function to call OpenRouter API
function call_openrouter_api($prompt_message) {
    if (!defined('OPENROUTER_API_KEY') || OPENROUTER_API_KEY === '') {
        return ['success' => false, 'message' => 'OpenRouter API Key is not configured.', 'raw_request' => null, 'raw_response' => null];
    }

    $api_key = OPENROUTER_API_KEY;
    $your_site_url = defined('SITE_URL_FOR_OPENROUTER') ? SITE_URL_FOR_OPENROUTER : 'http://localhost';
    $your_site_name = defined('SITE_NAME_FOR_OPENROUTER') ? SITE_NAME_FOR_OPENROUTER : 'SEOIndicatorDev';

    $request_payload_array = [
        "model" => "deepseek/deepseek-chat-v3-0324:free",
        "messages" => [
            ["role" => "user", "content" => $prompt_message]
        ]
    ];
    $request_payload_json = json_encode($request_payload_array);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => "https://openrouter.ai/api/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $api_key,
            "Content-Type: application/json",
            "HTTP-Referer: " . $your_site_url, 
            "X-Title: " . $your_site_name      
        ],
        CURLOPT_POSTFIELDS => $request_payload_json,
        CURLOPT_TIMEOUT => 30 
    ]);

    $raw_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $trimmed_raw_response = trim($raw_response); // Trim the raw response

    if ($curl_error) {
        return ['success' => false, 'message' => "cURL Error: " . $curl_error, 'raw_request' => $request_payload_json, 'raw_response' => $trimmed_raw_response];
    }

    if ($http_code == 200) {
        $response_data = json_decode($trimmed_raw_response, true);
        if (isset($response_data['choices'][0]['message']['content'])) {
            return ['success' => true, 'message' => 'OpenRouter API Success!', 'data' => $response_data['choices'][0]['message']['content'], 'raw_request' => $request_payload_json, 'raw_response' => $trimmed_raw_response];
        } else {
            return ['success' => false, 'message' => 'OpenRouter API: Unexpected response structure.', 'raw_request' => $request_payload_json, 'raw_response' => $trimmed_raw_response];
        }
    } else {
        return ['success' => false, 'message' => "OpenRouter API Error (HTTP {$http_code})", 'raw_request' => $request_payload_json, 'raw_response' => $trimmed_raw_response];
    }
}

// Helper function to get a database connection
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // In a real app, you might log this error more gracefully
        // For now, we can die or return null and handle it
        // error_log("Connection failed: " . $conn->connect_error);
        return null; 
    }
    return $conn;
}

// Helper function to normalize domain name
function normalize_domain($url) {
    if (empty($url)) return null;

    // Add http:// if no scheme is present to help parse_url
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }

    $parsed_url = parse_url(strtolower(trim($url)));
    if (isset($parsed_url['host'])) {
        $host = $parsed_url['host'];
        // Remove www. prefix
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        // Basic attempt to get main domain and TLD for common cases
        // This might need a more robust library for all edge cases of TLDs (e.g. .co.uk)
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            // e.g., example.com -> parts = [example, com]
            // sub.example.com -> parts = [sub, example, com]
            // For simplicity, let's take the last two parts, unless it's a known ccSLD like .co.uk
            // This is a simplified approach. A full TLD list would be better for production.
            if (count($parts) > 2 && strlen($parts[count($parts)-2]) == 2 && strlen($parts[count($parts)-1]) == 2) {
                 // e.g. domain.co.uk
                 return implode('.', array_slice($parts, -3));
            }
            return implode('.', array_slice($parts, -2));
        }
        return $host; // Fallback to the full host if fewer than 2 parts (e.g. localhost)
    }
    return null;
}

// Helper function to render stars based on score
function render_stars($score, $show_text_score = false, $achieved_score_text = null, $max_possible_score_text = null) {
    $stars_html = '';
    $max_stars_visual = 3; // For the visual star display (1-3 stars)
    $filled_star = '★';
    $empty_star = '☆';
    $score_text_part = '';

    // Visual stars based on the 1-3 $score parameter
    if (!is_numeric($score) || $score < 1 || $score > $max_stars_visual) {
         $stars_html = '<span class="text-secondary">' . str_repeat($empty_star, $max_stars_visual) . '</span>';
         if ($show_text_score) {
            // If detailed text scores are provided, use them, otherwise N/A
            if (isset($achieved_score_text) && isset($max_possible_score_text)) {
                $score_text_part = ' <span class="text-muted">(' . htmlspecialchars($achieved_score_text) . '/' . htmlspecialchars($max_possible_score_text) . ')</span>';
            } else {
                $score_text_part = ' <span class="text-muted">(N/A)</span>';
            }
         }
    } else {
        $current_stars = '';
        for ($i = 1; $i <= $max_stars_visual; $i++) {
            $current_stars .= ($i <= $score) ? $filled_star : $empty_star;
        }
        if ($score == 3) {
            $stars_html = '<span class="text-success">' . $current_stars . '</span>';
        } elseif ($score == 2) {
            $stars_html = '<span class="text-warning">' . $current_stars . '</span>';
        } else { // Score == 1
            $stars_html = '<span class="text-danger">' . $current_stars . '</span>';
        }
        if ($show_text_score) {
            // If detailed text scores are provided, use them, otherwise the 1-3 score
            if (isset($achieved_score_text) && isset($max_possible_score_text)) {
                $score_text_part = ' <span class="text-muted">(' . htmlspecialchars($achieved_score_text) . '/' . htmlspecialchars($max_possible_score_text) . ')</span>';
            } else {
                 $score_text_part = ' <span class="text-muted">(' . (int)$score . '/' . $max_stars_visual . ')</span>';
            }
        }
    }
    return $stars_html . $score_text_part;
}

// Helper function to get table row class based on score
function get_score_row_class($score) {
    if (!is_numeric($score) || $score < 1 || $score > 3) {
        return '';
    }
    switch ($score) {
        case 3:
            return 'table-success';
        case 2:
            return 'table-warning';
        case 1:
            return 'table-danger';
        default:
            return '';
    }
}

// Helper function for resource accessibility (returns score now)
function check_resource_accessibility_helper($url) {
    $context_options = [
        'http' => [
            'method' => "GET",
            'timeout' => 5, 
            'ignore_errors' => true,
            'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
        ]
    ];
    $context = stream_context_create($context_options);
    $headers = @get_headers($url, false, $context);
    $score = 1;
    $details = 'Could not fetch headers or an error occurred.';

    if ($headers && !empty($headers)) {
        $found_200 = false;
        foreach ($headers as $header) {
            if (stripos($header, '200 OK') !== false) {
                $found_200 = true;
                break;
            }
        }
        if ($found_200) {
            $score = 3;
            $details = 'Responded with 200 OK.';
        } else {
            $score = 1;
            $details = 'Could not find 200 OK status. First header: ' . (isset($headers[0]) ? htmlspecialchars($headers[0]) : 'N/A');
        }
    } else {
         $score = 1;
         $details = 'Failed to connect or domain does not exist for the resource.';
    }
    return ['score' => $score, 'details' => $details];
}

// --- Indicator Weights (Velocity) ---
$indicator_weights = [
    'title' => 3,
    'meta_description' => 3,
    'url' => 3,
    'robots' => 3,
    'sitemap' => 2,
    'h1' => 3,
    'filenames' => 2,
    'image_alt' => 2,
    'viewport' => 2,
    'lang' => 2,
    'favicon' => 1,
    'canonical' => 2,
    'opengraph' => 1,
    'html_size' => 2,
    'inline_css' => 1,
];

// --- Active Indicators Configuration ---
// Only these indicators will be processed and included in scoring
$active_indicators = [
    'url', // Only URL indicator is active as per user requirement
];

// Default structure for indicators_results (used by POST logic for $current_run_results)
// This ensures $current_run_results has all keys even if $indicators_results in index.php changes.
$default_indicator_keys_for_logic = [
    'url' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'title' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'meta_description' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'robots' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'sitemap' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'h1' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'filenames' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'image_alt' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'viewport' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'lang' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'favicon' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'canonical' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'opengraph' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'html_size' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'inline_css' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
];


// --- FORM PROCESSING LOGIC (POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dev_mock_all_but_title = true; // Development flag for mocking

    // Clear previous OpenRouter response from session
    unset($_SESSION['openrouter_response']);

    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    // --- Rate Limiting (1 request per minute per IP) ---
    $conn_rate_limit = get_db_connection();
    if ($conn_rate_limit && $user_ip !== 'UNKNOWN' && $user_ip !== '::1') {
        $current_time = time();
        $one_minute_ago = $current_time - 60;

        // Check last request time for this IP
        $stmt_check_ip = $conn_rate_limit->prepare("SELECT last_request_time FROM ip_requests_log WHERE ip_address = ?");
        if ($stmt_check_ip) {
            $stmt_check_ip->bind_param("s", $user_ip);
            $stmt_check_ip->execute();
            $result_check_ip = $stmt_check_ip->get_result();

            if ($result_check_ip->num_rows > 0) {
                $row_ip = $result_check_ip->fetch_assoc();
                $last_request_timestamp = strtotime($row_ip['last_request_time']);
                if ($last_request_timestamp > $one_minute_ago) {
                    // Too soon, store error and redirect
                    $_SESSION['rate_limit_error'] = '1 request per minute allowed!';
                    $_SESSION['is_post_redirect_layout_active'] = false; // Ensure initial layout
                    $_SESSION['last_domain_for_view'] = trim($_POST['domain'] ?? '');
                    header('Location: /');
                    exit;
                } else {
                    // Update last request time
                    $stmt_update_ip = $conn_rate_limit->prepare("UPDATE ip_requests_log SET last_request_time = CURRENT_TIMESTAMP WHERE ip_address = ?");
                    if ($stmt_update_ip) {
                        $stmt_update_ip->bind_param("s", $user_ip);
                        $stmt_update_ip->execute();
                        $stmt_update_ip->close();
                    }
                }
            } else {
                // First request from this IP, insert it
                $stmt_insert_ip = $conn_rate_limit->prepare("INSERT INTO ip_requests_log (ip_address, last_request_time) VALUES (?, CURRENT_TIMESTAMP)");
                if ($stmt_insert_ip) {
                    $stmt_insert_ip->bind_param("s", $user_ip);
                    $stmt_insert_ip->execute();
                    $stmt_insert_ip->close();
                }
            }
            $stmt_check_ip->close();
        }
        $conn_rate_limit->close();
    } elseif ($user_ip === 'UNKNOWN') {
        // Optionally handle cases where IP is unknown (e.g., block or allow based on policy)
        // For now, we'll let it pass if IP is unknown, but this could be a security consideration.
    }
    // --- End Rate Limiting ---

    $domain_input = trim($_POST['domain'] ?? '');
    $normalized_domain_for_db = normalize_domain($domain_input);
    $final_accessible_url = null;
    $checked_domain_display = htmlspecialchars($domain_input);

    $current_run_results = array_map(function($item) {
        return ['score' => null, 'details' => 'Processing...', 'suggestion' => '-'];
    }, $default_indicator_keys_for_logic);

    $site_is_accessible = false;
    $accessibility_check_details_internal = 'Site accessibility not yet checked.';

    if (!empty($domain_input)) {
        $url_schemes_to_try = [];
        if (!preg_match("~^(?:f|ht)tps?://~i", $domain_input)) {
            $url_schemes_to_try[] = "https://{$domain_input}";
            $url_schemes_to_try[] = "http://{$domain_input}";
        } else {
            $url_schemes_to_try[] = $domain_input;
        }

        foreach ($url_schemes_to_try as $current_url_to_check) {
            $context_options = [
                'http' => [
                    'method' => "GET", 'timeout' => 7, 'ignore_errors' => true, 'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
                ]
            ];
            $context = stream_context_create($context_options);
            $headers = @get_headers($current_url_to_check, false, $context);
            if ($headers && !empty($headers)) {
                foreach ($headers as $header) {
                    if (stripos($header, '200 OK') !== false) {
                        $final_accessible_url = $current_url_to_check;
                        $site_is_accessible = true;
                        $accessibility_check_details_internal = 'Site responded with 200 OK from: ' . htmlspecialchars($final_accessible_url);
                        goto core_accessibility_done_logic;
                    }
                }
                if (!$final_accessible_url && $current_url_to_check === $url_schemes_to_try[0] && isset($headers[0])) {
                    $accessibility_check_details_internal = 'Attempted ' . htmlspecialchars($current_url_to_check) . '. First header: ' . htmlspecialchars($headers[0]);
                }
            }
        }
        core_accessibility_done_logic:;

        if (!$site_is_accessible) { // If site is NOT accessible
            $_SESSION['site_not_accessible_error'] = 'The website "' . htmlspecialchars($domain_input) . '" could not be reached or returned an error. Last status: ' . $accessibility_check_details_internal;
            $_SESSION['last_domain_for_view'] = $domain_input; // Keep domain in input
            $_SESSION['is_post_redirect_layout_active'] = false; // Ensure initial view layout
            
            unset($_SESSION['seo_results_data']); // Clear any stale results
            unset($_SESSION['overall_score_data']); // Clear any stale score
            unset($_SESSION['rate_limit_error']); // Clear rate limit message as well
            unset($_SESSION['openrouter_response']); // Clear any stale OpenRouter response

            header('Location: /');
            exit;
        }

        // If we reach here, site IS accessible ($site_is_accessible is true and $final_accessible_url is set).
        // Proceed with further checks.
        
        // General OpenRouter API Call Test is REMOVED.
        // $_SESSION['openrouter_response'] will now be populated by the title-specific call below.

        $parsed_final_url = parse_url($final_accessible_url);

        $context_options_content = [
            'http' => [
                'method' => "GET", 'timeout' => 10, 'header' => "User-Agent: SEOIndicatorBot/1.0\r\nAccept: text/html\r\n"
            ]
        ];
        $context_content = stream_context_create($context_options_content);
        $page_content = @file_get_contents($final_accessible_url, false, $context_content);

        $title_content_for_ai = null; // Variable to store title for AI
        $meta_description_content_for_ai = null; // Variable to store meta description for AI
        $url_content_for_ai = null; // Variable to store URL structure for AI
        $headings_content_for_ai = null; // Variable to store headings structure for AI
        $filenames_content_for_ai = null; // Variable to store filenames for AI
        $image_alt_content_for_ai = null; // Variable to store image alt attributes for AI
        $url_analysis_text = 'URL analyzed'; // Variable to store URL analysis for suggestion

        if ($page_content !== false) {
            // HTML Size
            if ($dev_mock_all_but_title) {
                $current_run_results['html_size'] = ['score' => 2, 'details' => 'HTML Size - Mocked (e.g. 150KB)', 'suggestion' => 'Mocked suggestion.'];
            } else {
                $html_size_bytes = strlen($page_content);
                $html_size_kb = round($html_size_bytes / 1024, 2);
                if ($html_size_kb < 100) {
                    $current_run_results['html_size']['score'] = 3;
                } elseif ($html_size_kb < 300) {
                    $current_run_results['html_size']['score'] = 2;
                } else {
                    $current_run_results['html_size']['score'] = 1;
                }
                $current_run_results['html_size']['details'] = $html_size_kb . ' KB (' . $html_size_bytes . ' bytes)';
                if ($current_run_results['html_size']['score'] == 1) {
                    $current_run_results['html_size']['suggestion'] = 'Page size is large. Aim for <100-150KB for faster loading.';
                } elseif ($current_run_results['html_size']['score'] == 2) {
                    $current_run_results['html_size']['suggestion'] = 'Page size is reasonable. Further optimization might be possible.';
                } else {
                    $current_run_results['html_size']['suggestion'] = 'Page size is excellent!';
                }
            }

            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            @$doc->loadHTML($page_content);
            libxml_clear_errors();
            $xpath = new DOMXPath($doc);

            // Title Tag (REAL CHECK - NOT MOCKED)
            $title_nodes = $xpath->query('//title');
            if ($title_nodes->length > 0) {
                $title_content = trim($title_nodes->item(0)->nodeValue);
                if (!empty($title_content)) {
                    $current_run_results['title']['score'] = 3;
                    $current_run_results['title']['details'] = htmlspecialchars($title_content);
                    $title_content_for_ai = $title_content; // Store for AI
                    // Suggestion will be filled by AI or default if AI fails
                    $current_run_results['title']['suggestion'] = 'Awaiting AI suggestion...'; 
                } else {
                    $current_run_results['title']['score'] = 2;
                    $current_run_results['title']['details'] = 'Title tag exists but is empty.';
                    $current_run_results['title']['suggestion'] = 'Provide a descriptive title for better SEO and user understanding.';
                }
            } else {
                $current_run_results['title']['score'] = 1;
                $current_run_results['title']['details'] = 'No title tag found.';
                $current_run_results['title']['suggestion'] = 'Add a compelling title tag to your page.';
            }

            // Meta Description (REAL CHECK - NOT MOCKED)
            $meta_description_nodes = $xpath->query('//meta[@name="description"]');
            if ($meta_description_nodes->length > 0) {
                $meta_content = trim($meta_description_nodes->item(0)->getAttribute('content'));
                if (!empty($meta_content)) {
                    $current_run_results['meta_description']['score'] = 3;
                    $current_run_results['meta_description']['details'] = htmlspecialchars($meta_content);
                    $meta_description_content_for_ai = $meta_content; // Store for AI
                    // Suggestion will be filled by AI or default if AI fails
                    $current_run_results['meta_description']['suggestion'] = 'Awaiting AI suggestion...';
                } else {
                    $current_run_results['meta_description']['score'] = 2;
                    $current_run_results['meta_description']['details'] = 'Meta description tag exists but content is empty.';
                    $current_run_results['meta_description']['suggestion'] = 'Fill in your meta description to improve click-through rates from search results.';
                }
            } else {
                $current_run_results['meta_description']['score'] = 1;
                $current_run_results['meta_description']['details'] = 'No meta description tag found.';
                $current_run_results['meta_description']['suggestion'] = 'Add a meta description to summarize the page content for search engines.';
            }
            
            // URL Structure Analysis
            $parsed_url_components = parse_url($final_accessible_url);
            $url_issues = [];
            $url_positives = [];
            
            // Check HTTPS
            $is_https = isset($parsed_url_components['scheme']) && strtolower($parsed_url_components['scheme']) === 'https';
            if ($is_https) {
                $url_positives[] = 'HTTPS';
            } else {
                $url_issues[] = 'Not HTTPS';
            }
            
            // Analyze domain
            $domain = isset($parsed_url_components['host']) ? strtolower($parsed_url_components['host']) : '';
            $domain_clean = str_replace('www.', '', $domain);
            
            // Check domain SEO friendliness
            $domain_seo_friendly = true;
            if (preg_match('/[^a-z0-9\-\.]/', $domain_clean)) {
                $url_issues[] = 'Domain contains special characters';
                $domain_seo_friendly = false;
            }
            if (strlen($domain_clean) > 63) {
                $url_issues[] = 'Domain too long';
                $domain_seo_friendly = false;
            }
            if (preg_match('/\d{3,}/', $domain_clean)) {
                $url_issues[] = 'Domain contains long numbers';
                $domain_seo_friendly = false;
            }
            if ($domain_seo_friendly) {
                $url_positives[] = 'SEO-friendly domain';
            }
            
            // Check domain extension popularity
            $popular_tlds = ['.com', '.org', '.net', '.edu', '.gov', '.co.uk', '.de', '.fr', '.it', '.es', '.ca', '.au', '.jp', '.br', '.in', '.ru', '.cn'];
            $tld_found = false;
            foreach ($popular_tlds as $tld) {
                if (substr($domain_clean, -strlen($tld)) === $tld) {
                    $url_positives[] = 'Popular domain extension';
                    $tld_found = true;
                    break;
                }
            }
            if (!$tld_found && strpos($domain_clean, '.') !== false) {
                $url_issues[] = 'Less common domain extension';
            }
            
            // Analyze URL path
            $url_path = isset($parsed_url_components['path']) ? $parsed_url_components['path'] : '/';
            $path_segments = array_filter(explode('/', trim($url_path, '/')));
            
            // Check path SEO friendliness
            $path_seo_friendly = true;
            foreach ($path_segments as $segment) {
                if (preg_match('/[A-Z]/', $segment)) {
                    $url_issues[] = 'URL contains uppercase';
                    $path_seo_friendly = false;
                    break;
                }
                if (preg_match('/\s/', $segment)) {
                    $url_issues[] = 'URL contains spaces';
                    $path_seo_friendly = false;
                    break;
                }
                if (preg_match('/[^a-z0-9\-\._]/', $segment)) {
                    $url_issues[] = 'URL contains special characters';
                    $path_seo_friendly = false;
                    break;
                }
            }
            
            // Check for query parameters
            if (isset($parsed_url_components['query']) && !empty($parsed_url_components['query'])) {
                $url_issues[] = 'Contains query parameters';
            } else {
                $url_positives[] = 'Clean URL without parameters';
            }
            
            // Check URL length
            $url_length = strlen($final_accessible_url);
            if ($url_length > 100) {
                $url_issues[] = 'URL too long';
            } elseif ($url_length < 50) {
                $url_positives[] = 'Optimal URL length';
            } else {
                $url_positives[] = 'Good URL length';
            }
            
            // Check for descriptive path
            if (!empty($path_segments)) {
                $has_descriptive_path = false;
                foreach ($path_segments as $segment) {
                    if (strlen($segment) >= 3 && !is_numeric($segment)) {
                        $has_descriptive_path = true;
                        break;
                    }
                }
                if ($has_descriptive_path) {
                    $url_positives[] = 'Descriptive URL path';
                } else {
                    $url_issues[] = 'Non-descriptive URL path';
                }
            }
            
            // Scoring logic
            $total_checks = count($url_issues) + count($url_positives);
            $positive_ratio = $total_checks > 0 ? count($url_positives) / $total_checks : 0;
            
            if ($positive_ratio >= 0.8) {
                $current_run_results['url']['score'] = 3;
            } elseif ($positive_ratio >= 0.5) {
                $current_run_results['url']['score'] = 2;
            } else {
                $current_run_results['url']['score'] = 1;
            }
            
            // Build analysis for suggestion (to be prepended to AI response)
            $analysis_parts = [];
            if (!empty($url_positives)) {
                $analysis_parts[] = 'Good: ' . implode(', ', $url_positives);
            }
            if (!empty($url_issues)) {
                $analysis_parts[] = 'Issues: ' . implode(', ', $url_issues);
            }
            $url_analysis_text = !empty($analysis_parts) ? implode('; ', $analysis_parts) : 'URL analyzed';
            
            // Store the full URL in details (shown in Result column)
            $current_run_results['url']['details'] = htmlspecialchars($final_accessible_url);
            $current_run_results['url']['suggestion'] = 'Awaiting AI suggestion...';
            
            // Prepare content for AI analysis
            $url_content_for_ai = "URL: {$final_accessible_url}";
            
            // Headings Structure Analysis
            $h1_nodes = $xpath->query('//h1');
            $h2_nodes = $xpath->query('//h2');
            $h3_nodes = $xpath->query('//h3');
            $h4_nodes = $xpath->query('//h4');
            $h5_nodes = $xpath->query('//h5');
            $h6_nodes = $xpath->query('//h6');
            
            $h1_count = $h1_nodes->length;
            $h2_count = $h2_nodes->length;
            $h3_count = $h3_nodes->length;
            $total_headings = $h1_count + $h2_count + $h3_count + $h4_nodes->length + $h5_nodes->length + $h6_nodes->length;
            
            // Build heading structure for AI analysis
            $heading_structure = [];
            if ($h1_count > 0) {
                foreach ($h1_nodes as $h1) {
                    $heading_structure[] = 'H1: ' . trim($h1->nodeValue);
                }
            }
            if ($h2_count > 0) {
                foreach ($h2_nodes as $h2) {
                    $heading_structure[] = 'H2: ' . trim($h2->nodeValue);
                }
            }
            if ($h3_count > 0) {
                foreach ($h3_nodes as $h3) {
                    $heading_structure[] = 'H3: ' . trim($h3->nodeValue);
                }
            }
            
            if (!empty($heading_structure)) {
                $headings_content_for_ai = implode('; ', array_slice($heading_structure, 0, 10)); // Limit to first 10 headings
            }
            
            if ($h1_count > 0) {
                $current_run_results['h1']['score'] = 3;
                if ($h1_count == 1) {
                    $current_run_results['h1']['details'] = "Found 1 H1 tag and {$total_headings} total headings (H2: {$h2_count}, H3: {$h3_count})";
                } else {
                    $current_run_results['h1']['details'] = "Found {$h1_count} H1 tags and {$total_headings} total headings (H2: {$h2_count}, H3: {$h3_count})";
                }
                $current_run_results['h1']['suggestion'] = 'Awaiting AI suggestion...';
            } else {
                $current_run_results['h1']['score'] = 1;
                $current_run_results['h1']['details'] = "No H1 tags found. Total headings: {$total_headings} (H2: {$h2_count}, H3: {$h3_count})";
                $current_run_results['h1']['suggestion'] = 'Add an H1 tag to define the main heading of your page.';
            }

            // Image Alt Attributes Analysis
            $img_nodes = $xpath->query('//img');
            $img_count = $img_nodes->length;
            $img_missing_alt = 0;
            $img_with_alt = [];
            $img_sources = [];
            
            if ($img_count > 0) {
                foreach ($img_nodes as $img_node) {
                    $alt_text = $img_node->hasAttribute('alt') ? trim($img_node->getAttribute('alt')) : '';
                    $src = $img_node->hasAttribute('src') ? $img_node->getAttribute('src') : 'unknown';
                    $img_sources[] = basename($src);
                    
                    if (empty($alt_text)) {
                        $img_missing_alt++;
                    } else {
                        $img_with_alt[] = $alt_text;
                    }
                }
                
                // Build image alt data for AI analysis
                if (!empty($img_with_alt)) {
                    $image_alt_content_for_ai = implode('; ', array_slice($img_with_alt, 0, 10)); // Limit to first 10 alt texts
                }
                
                if ($img_missing_alt === 0) {
                    $current_run_results['image_alt']['score'] = 3;
                    $current_run_results['image_alt']['details'] = "Found {$img_count} images, all have alt attributes";
                    $current_run_results['image_alt']['suggestion'] = 'Awaiting AI suggestion...';
                } else {
                    $current_run_results['image_alt']['score'] = 2;
                    $img_with_alt_count = $img_count - $img_missing_alt;
                    $current_run_results['image_alt']['details'] = "Found {$img_count} images: {$img_with_alt_count} with alt text, {$img_missing_alt} missing alt text";
                    $current_run_results['image_alt']['suggestion'] = 'Awaiting AI suggestion...';
                }
            } else {
                $current_run_results['image_alt']['score'] = 1; 
                $current_run_results['image_alt']['details'] = 'No images found on the page';
                $current_run_results['image_alt']['suggestion'] = 'Add relevant images with descriptive alt text to enhance accessibility and SEO.';
            }

            // Filenames Analysis (Images, Documents, Files)
            $all_filenames = [];
            $problematic_filenames = [];
            $good_filenames = [];
            $has_inline_encoded_images = false;
            
            // Analyze image filenames
            $img_nodes = $xpath->query('//img[@src]');
            foreach ($img_nodes as $img_node) {
                $src = $img_node->getAttribute('src');
                // Skip inline encoded images (data: URLs)
                if (strpos($src, 'data:') === 0) {
                    $has_inline_encoded_images = true;
                    continue;
                }
                $filename = basename(parse_url($src, PHP_URL_PATH));
                if (!empty($filename) && $filename !== '/') {
                    $all_filenames[] = ['type' => 'image', 'filename' => $filename, 'source' => $src];
                }
            }
            
            // Analyze document and file links
            $link_nodes = $xpath->query('//a[@href]');
            foreach ($link_nodes as $link_node) {
                $href = $link_node->getAttribute('href');
                // Check for common file extensions
                if (preg_match('/\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar|mp3|mp4|avi|mov|jpg|jpeg|png|gif|svg|css|js|xml|txt|csv)$/i', $href)) {
                    $filename = basename(parse_url($href, PHP_URL_PATH));
                    if (!empty($filename)) {
                        // Determine file type
                        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $file_types = [
                            'pdf' => 'document', 'doc' => 'document', 'docx' => 'document',
                            'xls' => 'spreadsheet', 'xlsx' => 'spreadsheet', 'csv' => 'spreadsheet',
                            'ppt' => 'presentation', 'pptx' => 'presentation',
                            'zip' => 'archive', 'rar' => 'archive',
                            'mp3' => 'audio', 'mp4' => 'video', 'avi' => 'video', 'mov' => 'video',
                            'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'svg' => 'image',
                            'css' => 'stylesheet', 'js' => 'script', 'xml' => 'data', 'txt' => 'text'
                        ];
                        $file_type = $file_types[$extension] ?? 'file';
                        $all_filenames[] = ['type' => $file_type, 'filename' => $filename, 'source' => $href];
                    }
                }
            }
            
            // Analyze CSS background images and other resources
            $style_nodes = $xpath->query('//style | //*[@style]');
            foreach ($style_nodes as $style_node) {
                $style_content = $style_node->nodeValue ?? $style_node->getAttribute('style');
                if (preg_match_all('/url\(["\']?([^"\')\s]+)["\']?\)/i', $style_content, $matches)) {
                    foreach ($matches[1] as $url) {
                        $filename = basename(parse_url($url, PHP_URL_PATH));
                        if (!empty($filename) && preg_match('/\.(jpg|jpeg|png|gif|svg|css|js|woff|woff2|ttf|eot)$/i', $filename)) {
                            $all_filenames[] = ['type' => 'resource', 'filename' => $filename, 'source' => $url];
                        }
                    }
                }
            }
            
            // Remove duplicates
            $unique_filenames = [];
            $seen_filenames = [];
            foreach ($all_filenames as $file_data) {
                if (!in_array($file_data['filename'], $seen_filenames)) {
                    $unique_filenames[] = $file_data;
                    $seen_filenames[] = $file_data['filename'];
                }
            }
            
            // Analyze filename quality
            foreach ($unique_filenames as $file_data) {
                $filename = $file_data['filename'];
                $name_without_extension = pathinfo($filename, PATHINFO_FILENAME);
                
                // Check for SEO-unfriendly patterns in filename
                $is_problematic = false;
                $issues = [];
                
                if (preg_match('/[A-Z]/', $name_without_extension)) {
                    $is_problematic = true;
                    $issues[] = 'uppercase';
                }
                if (preg_match('/\s/', $name_without_extension)) {
                    $is_problematic = true;
                    $issues[] = 'spaces';
                }
                if (preg_match('/[^a-z0-9\-_]/', $name_without_extension)) {
                    $is_problematic = true;
                    $issues[] = 'special_chars';
                }
                if (preg_match('/^\d+$/', $name_without_extension)) {
                    $is_problematic = true;
                    $issues[] = 'only_numbers';
                }
                if (strlen($name_without_extension) < 3) {
                    $is_problematic = true;
                    $issues[] = 'too_short';
                }
                if (strlen($name_without_extension) > 50) {
                    $is_problematic = true;
                    $issues[] = 'too_long';
                }
                if (preg_match('/^(img|image|file|doc|document|pic|picture)\d*$/i', $name_without_extension)) {
                    $is_problematic = true;
                    $issues[] = 'generic';
                }
                
                if ($is_problematic) {
                    $problematic_filenames[] = $filename;
                } else {
                    $good_filenames[] = $filename;
                }
            }
            
            // Prepare results
            $total_filenames = count($unique_filenames);
            $problematic_count = count($problematic_filenames);
            $good_count = count($good_filenames);
            
            if ($total_filenames > 0) {
                // Group filenames by type for better analysis
                $filename_types = [];
                foreach ($unique_filenames as $file_data) {
                    $filename_types[$file_data['type']][] = $file_data['filename'];
                }
                
                $sample_data = [];
                foreach ($filename_types as $type => $filenames) {
                    $sample_data[] = ucfirst($type) . ' files: ' . implode(', ', array_slice($filenames, 0, 5));
                }
                $filenames_content_for_ai = "Found {$total_filenames} files on page. " . implode('; ', array_slice($sample_data, 0, 4));
                
                if ($problematic_count === 0) {
                    $current_run_results['filenames']['score'] = 3;
                    $current_run_results['filenames']['details'] = "Analyzed {$total_filenames} files: all have SEO-friendly names";
                } elseif ($problematic_count < $total_filenames / 2) {
                    $current_run_results['filenames']['score'] = 2;
                    $current_run_results['filenames']['details'] = "Analyzed {$total_filenames} files: {$good_count} SEO-friendly names, {$problematic_count} need improvement";
                } else {
                    $current_run_results['filenames']['score'] = 1;
                    $current_run_results['filenames']['details'] = "Analyzed {$total_filenames} files: {$good_count} SEO-friendly names, {$problematic_count} need improvement";
                }
                $current_run_results['filenames']['suggestion'] = 'Awaiting AI suggestion...';
            } else {
                $current_run_results['filenames']['score'] = 2;
                $current_run_results['filenames']['details'] = 'No files found for filename analysis';
                $filenames_content_for_ai = "No files (images, documents, etc.) found on the page for filename analysis.";
                $current_run_results['filenames']['suggestion'] = 'Awaiting AI suggestion...';
            }

            // Viewport Meta Tag
            if ($dev_mock_all_but_title) {
                $current_run_results['viewport'] = ['score' => 2, 'details' => 'Viewport Meta Tag - Mocked', 'suggestion' => 'Mocked suggestion.'];
            } else {
                $viewport_nodes = $xpath->query('//meta[@name="viewport"]');
                if ($viewport_nodes->length > 0) {
                    $current_run_results['viewport']['score'] = 3;
                    $current_run_results['viewport']['details'] = 'Viewport meta tag is present.';
                    $current_run_results['viewport']['suggestion'] = 'Good! Viewport tag helps with mobile responsiveness.';
                } else {
                    $current_run_results['viewport']['score'] = 1;
                    $current_run_results['viewport']['details'] = 'Viewport meta tag is missing.';
                    $current_run_results['viewport']['suggestion'] = 'Add a viewport meta tag for proper scaling on mobile devices.';
                }
            }

            // Language Declaration
            if ($dev_mock_all_but_title) {
                $current_run_results['lang'] = ['score' => 2, 'details' => 'Language Declaration - Mocked', 'suggestion' => 'Mocked suggestion.'];
            } else {
                $html_node = $xpath->query('/html')->item(0);
                if ($html_node && $html_node->hasAttribute('lang') && trim($html_node->getAttribute('lang')) !== '') {
                    $lang_value = htmlspecialchars($html_node->getAttribute('lang'));
                    $current_run_results['lang']['score'] = 3;
                    $current_run_results['lang']['details'] = 'HTML lang attribute found: ' . $lang_value;
                    $current_run_results['lang']['suggestion'] = 'Correct language declaration helps accessibility and search engines.';
                } else {
                    $current_run_results['lang']['score'] = 1;
                    $current_run_results['lang']['details'] = 'HTML lang attribute is missing or empty.';
                    $current_run_results['lang']['suggestion'] = 'Declare the language of your HTML document using the lang attribute on the <html> tag.';
                }
            }

            // Favicon Presence
            if ($dev_mock_all_but_title) {
                $current_run_results['favicon'] = ['score' => 2, 'details' => 'Favicon Presence - Mocked', 'suggestion' => 'Mocked suggestion.'];
            } else {
                $favicon_nodes = $xpath->query('//link[contains(@rel, "icon")]');
                if ($favicon_nodes->length > 0) {
                    $current_run_results['favicon']['score'] = 3;
                    $current_run_results['favicon']['details'] = 'Favicon link tag found.';
                    $current_run_results['favicon']['suggestion'] = 'Good, favicon improves brand recognition in browser tabs.';
                } else {
                    $current_run_results['favicon']['score'] = 1;
                    $current_run_results['favicon']['details'] = 'No link tag with rel containing "icon" found.';
                    $current_run_results['favicon']['suggestion'] = 'Add a favicon to your site.';
                }
            }

            // Canonical Link Tag
            if ($dev_mock_all_but_title) {
                $current_run_results['canonical'] = ['score' => 2, 'details' => 'Canonical Link - Mocked', 'suggestion' => 'Mocked suggestion.'];
            } else {
                $canonical_nodes = $xpath->query('//link[@rel="canonical"]');
                if ($canonical_nodes->length > 0) {
                    $href = $canonical_nodes->item(0)->getAttribute('href');
                    $current_run_results['canonical']['score'] = 3;
                    $current_run_results['canonical']['details'] = 'Canonical link found: ' . htmlspecialchars($href);
                    $current_run_results['canonical']['suggestion'] = 'Good use of canonical tag to prevent duplicate content issues.';
                } else {
                    $current_run_results['canonical']['score'] = 1;
                    $current_run_results['canonical']['details'] = 'No canonical link tag found.';
                    $current_run_results['canonical']['suggestion'] = 'Consider adding a canonical link tag if this page can be accessed via multiple URLs.';
                }
            }

            // Open Graph Tags (Basic Check)
            if ($dev_mock_all_but_title) {
                $current_run_results['opengraph'] = ['score' => 2, 'details' => 'Open Graph Tags - Mocked', 'suggestion' => 'Mocked suggestion.'];
            } else {
                $og_nodes = $xpath->query('//meta[starts-with(@property, "og:")]');
                if ($og_nodes->length > 0) {
                    $current_run_results['opengraph']['score'] = 3;
                    $current_run_results['opengraph']['details'] = 'Found ' . $og_nodes->length . ' Open Graph meta tag(s).';
                    $current_run_results['opengraph']['suggestion'] = 'Open Graph tags enhance content sharing on social media.';
                } else {
                    $current_run_results['opengraph']['score'] = 1;
                    $current_run_results['opengraph']['details'] = 'No Open Graph (og:) meta tags found.';
                    $current_run_results['opengraph']['suggestion'] = 'Add Open Graph tags (og:title, og:description, og:image, etc.) for better social media sharing.';
                }
            }

            // Inline Styles Check
            if ($dev_mock_all_but_title) {
                $current_run_results['inline_css'] = ['score' => 2, 'details' => 'Inline Styles - Mocked', 'suggestion' => 'Mocked suggestion.'];
            } else {
                $inline_style_nodes = $xpath->query('//*[@style]');
                $style_tag_nodes = $xpath->query('//body//style');
                $inline_count = $inline_style_nodes->length;
                $style_tag_count = $style_tag_nodes->length;
                if ($inline_count > 0 || $style_tag_count > 0) {
                    $current_run_results['inline_css']['score'] = 2;
                    $details_css = [];
                    if ($inline_count > 0) $details_css[] = $inline_count . ' element(s) with style attribute';
                    if ($style_tag_count > 0) $details_css[] = $style_tag_count . ' <style> tag(s) in body';
                    $current_run_results['inline_css']['details'] = implode(', ', $details_css) . '.';
                    $current_run_results['inline_css']['suggestion'] = 'Consider moving inline styles and <style> tags in body to external CSS files for better performance and maintainability.';
                } else {
                    $current_run_results['inline_css']['score'] = 3;
                    $current_run_results['inline_css']['details'] = 'No inline styles or style tags found in body.';
                    $current_run_results['inline_css']['suggestion'] = 'Excellent! No inline styles in body content.';
                }
            }

        } else { 
            $content_error_msg = 'Could not fetch page content for detailed checks. (' . $accessibility_check_details_internal . ')';
            $error_score = 1;
            $error_suggestion = 'Unable to provide suggestion as content could not be fetched.';
            
            // Title still gets the error message because it's not mocked and depends on content
            $current_run_results['title']['score'] = $error_score;
            $current_run_results['title']['details'] = $content_error_msg;
            $current_run_results['title']['suggestion'] = $error_suggestion;

            // Other content-dependent checks
            $checks_needing_content_mocked_on_fail = [
                'meta_description', 'url', 'h1', 'filenames', 'image_alt', 'viewport', 'lang', 
                'favicon', 'canonical', 'opengraph', 'html_size', 'inline_css'
            ];

            foreach($checks_needing_content_mocked_on_fail as $key) {
                 if ($dev_mock_all_but_title) {
                     $current_run_results[$key] = ['score' => 1, 'details' => ucfirst(str_replace('_', ' ', $key)) . ' - Mocked (content fetch failed)', 'suggestion' => 'Mocked suggestion (content fetch failed).'];
                 } else {
                     $current_run_results[$key]['score'] = $error_score;
                     $current_run_results[$key]['details'] = $content_error_msg;
                     $current_run_results[$key]['suggestion'] = $error_suggestion;
                 }
            }
        }

        // --- AI Suggestion for Title (PAUSED) ---
        // Temporarily paused to develop Meta Description functionality
        /*
        if ($title_content_for_ai) {
            $prompt = "Analyze the following website title for SEO. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Title: \"{$title_content_for_ai}\"";
            $title_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $title_ai_response_full['success'], // Reflect success of this specific call
                'message' => $title_ai_response_full['message'], // Message of this specific call
                'raw_request' => $title_ai_response_full['raw_request'] ?? null,
                'raw_response' => $title_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($title_ai_response_full['success'] && isset($title_ai_response_full['data'])) {
                $ai_output = trim($title_ai_response_full['data']);
                
                // Extract JSON from markdown code blocks if present
                if (preg_match('/```json\s*(.*?)\s*```/s', $ai_output, $matches)) {
                    $json_content = trim($matches[1]);
                } else {
                    // If no markdown blocks, use the full content
                    $json_content = $ai_output;
                }
                
                // Attempt to parse JSON response
                $json_data = json_decode($json_content, true);
                
                if ($json_data && isset($json_data['score']) && isset($json_data['suggestion'])) {
                    $ai_score = (int)$json_data['score'];
                    $ai_suggestion = trim($json_data['suggestion']);

                    if ($ai_score >= 1 && $ai_score <= 3) {
                        $current_run_results['title']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' (the title itself) is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score (likely 3 if title existed, or 1/2 if empty/missing)
                        // and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    $current_run_results['title']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['title']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $current_run_results['title']['suggestion'] = 'AI suggestion failed: ' . ($title_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif ($page_content !== false && isset($current_run_results['title']['score']) && $current_run_results['title']['score'] == 3 && $current_run_results['title']['suggestion'] === 'Awaiting AI suggestion...') {
             // If title was found, but for some reason $title_content_for_ai was not set (should not happen if score is 3 and details are present)
             // or if it was empty and the suggestion is still "Awaiting AI suggestion..."
             if (isset($current_run_results['title']['details']) && $current_run_results['title']['details'] === 'Title tag exists but is empty.') {
                // Suggestion already set for this specific case by the non-AI logic, so do nothing here.
             } else {
                // Generic fallback if AI was expected but didn't run for other reasons.
                $current_run_results['title']['suggestion'] = 'Title found, but could not generate AI suggestion at this time.';
             }
        }
        */
        // --- End AI Suggestion for Title (PAUSED) ---

        // --- AI Suggestion for Meta Description (PAUSED) ---
        /*
        if ($meta_description_content_for_ai) {
            $prompt = "Analyze the following website meta description for SEO. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Meta Description: \"{$meta_description_content_for_ai}\"";
            $meta_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $meta_ai_response_full['success'], // Reflect success of this specific call
                'message' => $meta_ai_response_full['message'], // Message of this specific call
                'raw_request' => $meta_ai_response_full['raw_request'] ?? null,
                'raw_response' => $meta_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($meta_ai_response_full['success'] && isset($meta_ai_response_full['data'])) {
                $ai_output = trim($meta_ai_response_full['data']);
                
                // Extract JSON from markdown code blocks if present
                if (preg_match('/```json\s*(.*?)\s*```/s', $ai_output, $matches)) {
                    $json_content = trim($matches[1]);
                } else {
                    // If no markdown blocks, use the full content
                    $json_content = $ai_output;
                }
                
                // Attempt to parse JSON response
                $json_data = json_decode($json_content, true);
                
                if ($json_data && isset($json_data['score']) && isset($json_data['suggestion'])) {
                    $ai_score = (int)$json_data['score'];
                    $ai_suggestion = trim($json_data['suggestion']);

                    if ($ai_score >= 1 && $ai_score <= 3) {
                        $current_run_results['meta_description']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' (the meta description itself) is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score (likely 3 if meta description existed, or 1/2 if empty/missing)
                        // and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    $current_run_results['meta_description']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['meta_description']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $current_run_results['meta_description']['suggestion'] = 'AI suggestion failed: ' . ($meta_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif ($page_content !== false && isset($current_run_results['meta_description']['score']) && $current_run_results['meta_description']['score'] == 3 && $current_run_results['meta_description']['suggestion'] === 'Awaiting AI suggestion...') {
             // If meta description was found, but for some reason $meta_description_content_for_ai was not set (should not happen if score is 3 and details are present)
             // or if it was empty and the suggestion is still "Awaiting AI suggestion..."
             if (isset($current_run_results['meta_description']['details']) && $current_run_results['meta_description']['details'] === 'Meta description tag exists but content is empty.') {
                // Suggestion already set for this specific case by the non-AI logic, so do nothing here.
             } else {
                // Generic fallback if AI was expected but didn't run for other reasons.
                $current_run_results['meta_description']['suggestion'] = 'Meta description found, but could not generate AI suggestion at this time.';
             }
        }
        */
        // --- End AI Suggestion for Meta Description (PAUSED) ---

        $parsed_url_for_files = parse_url($final_accessible_url);
        if (isset($parsed_url_for_files['scheme']) && isset($parsed_url_for_files['host'])) {
            $base_url_for_files = $parsed_url_for_files['scheme'] . '://' . $parsed_url_for_files['host'];
            
            $robots_content_for_ai = null; // Variable to store robots.txt content for AI
            
            // Robots.txt
            // Skip mocking for robots.txt to show real results and AI suggestions
            $robots_url = $base_url_for_files . '/robots.txt';
            $robots_check_result = check_resource_accessibility_helper($robots_url);
            $current_run_results['robots']['score'] = $robots_check_result['score'];
            $current_run_results['robots']['details'] = $robots_check_result['details'];
            
            // Fetch robots.txt content for AI analysis if accessible
            if ($robots_check_result['score'] == 3) {
                $robots_content = @file_get_contents($robots_url);
                if ($robots_content !== false) {
                    $robots_content_for_ai = trim($robots_content);
                    $content_length = strlen($robots_content_for_ai);
                    $line_count = substr_count($robots_content_for_ai, "\n") + 1;
                    $current_run_results['robots']['details'] = "Found robots.txt ({$content_length} chars, {$line_count} lines)";
                    $current_run_results['robots']['suggestion'] = 'Awaiting AI suggestion...';
                } else {
                    $current_run_results['robots']['suggestion'] = 'robots.txt is accessible but content could not be fetched for analysis.';
                }
            } else {
                $current_run_results['robots']['suggestion'] = 'Check robots.txt accessibility and content.';
            }

            $sitemap_content_for_ai = null; // Variable to store sitemap.xml content for AI
            
            // Sitemap.xml
            $sitemap_url = $base_url_for_files . '/sitemap.xml';
            $sitemap_check_result = check_resource_accessibility_helper($sitemap_url);
            $current_run_results['sitemap']['score'] = $sitemap_check_result['score'];
            $current_run_results['sitemap']['details'] = $sitemap_check_result['details'];
            
            // Fetch sitemap.xml content for AI analysis if accessible
            if ($sitemap_check_result['score'] == 3) {
                $sitemap_content = @file_get_contents($sitemap_url);
                if ($sitemap_content !== false) {
                    $sitemap_content_for_ai = trim($sitemap_content);
                    $content_length = strlen($sitemap_content_for_ai);
                    $line_count = substr_count($sitemap_content_for_ai, "\n") + 1;
                    // Count URL entries
                    $url_count = substr_count($sitemap_content_for_ai, '<url>') + substr_count($sitemap_content_for_ai, '<url ');
                    if ($url_count == 0) {
                        // Check for sitemap index entries
                        $sitemap_count = substr_count($sitemap_content_for_ai, '<sitemap>') + substr_count($sitemap_content_for_ai, '<sitemap ');
                        if ($sitemap_count > 0) {
                            $current_run_results['sitemap']['details'] = "Found sitemap index ({$content_length} chars, {$line_count} lines, {$sitemap_count} sitemaps)";
                        } else {
                            $current_run_results['sitemap']['details'] = "Found sitemap.xml ({$content_length} chars, {$line_count} lines, 0 URLs)";
                        }
                    } else {
                        $current_run_results['sitemap']['details'] = "Found sitemap.xml ({$content_length} chars, {$line_count} lines, {$url_count} URLs)";
                    }
                    $current_run_results['sitemap']['suggestion'] = 'Awaiting AI suggestion...';
                } else {
                    $current_run_results['sitemap']['suggestion'] = 'sitemap.xml is accessible but content could not be fetched for analysis.';
                }
            } else {
                $current_run_results['sitemap']['suggestion'] = 'Check sitemap.xml accessibility and content.';
            }
        } else {
            $url_parse_error_msg = 'Could not parse URL to check for robots.txt/sitemap.xml.';
            $error_score = 1;
            $error_suggestion_file = 'Unable to provide suggestion as base URL could not be determined.';
            $current_run_results['robots'] = ['score' => $error_score, 'details' => $url_parse_error_msg, 'suggestion' => $error_suggestion_file];
            $current_run_results['sitemap'] = ['score' => $error_score, 'details' => $url_parse_error_msg, 'suggestion' => $error_suggestion_file];
        }

        // --- AI Suggestion for Robots.txt (PAUSED) ---
        /*
        if ($robots_content_for_ai) {
            $prompt = "Analyze the following robots.txt file for SEO. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Robots.txt content: \"{$robots_content_for_ai}\"";
            $robots_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $robots_ai_response_full['success'], // Reflect success of this specific call
                'message' => $robots_ai_response_full['message'], // Message of this specific call
                'raw_request' => $robots_ai_response_full['raw_request'] ?? null,
                'raw_response' => $robots_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($robots_ai_response_full['success'] && isset($robots_ai_response_full['data'])) {
                $ai_output = trim($robots_ai_response_full['data']);
                
                // Extract JSON from markdown code blocks if present
                if (preg_match('/```json\s*(.*?)\s*```/s', $ai_output, $matches)) {
                    $json_content = trim($matches[1]);
                } else {
                    // If no markdown blocks, use the full content
                    $json_content = $ai_output;
                }
                
                // Attempt to parse JSON response
                $json_data = json_decode($json_content, true);
                
                if ($json_data && isset($json_data['score']) && isset($json_data['suggestion'])) {
                    $ai_score = (int)$json_data['score'];
                    $ai_suggestion = trim($json_data['suggestion']);

                    if ($ai_score >= 1 && $ai_score <= 3) {
                        $current_run_results['robots']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    $current_run_results['robots']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['robots']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $current_run_results['robots']['suggestion'] = 'AI suggestion failed: ' . ($robots_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif (isset($current_run_results['robots']['score']) && $current_run_results['robots']['score'] == 3 && $current_run_results['robots']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['robots']['suggestion'] = 'robots.txt found, but could not generate AI suggestion at this time.';
        }
        */
        // --- End AI Suggestion for Robots.txt (PAUSED) ---

        // --- AI Suggestion for Sitemap.xml (PAUSED) ---
        /*
        if ($sitemap_content_for_ai) {
            $prompt = "Analyze the following sitemap.xml file for SEO. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Sitemap.xml content: \"{$sitemap_content_for_ai}\"";
            $sitemap_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $sitemap_ai_response_full['success'], // Reflect success of this specific call
                'message' => $sitemap_ai_response_full['message'], // Message of this specific call
                'raw_request' => $sitemap_ai_response_full['raw_request'] ?? null,
                'raw_response' => $sitemap_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($sitemap_ai_response_full['success'] && isset($sitemap_ai_response_full['data'])) {
                $ai_output = trim($sitemap_ai_response_full['data']);
                
                // Extract JSON from markdown code blocks if present
                if (preg_match('/```json\s*(.*?)\s*```/s', $ai_output, $matches)) {
                    $json_content = trim($matches[1]);
                } else {
                    // If no markdown blocks, use the full content
                    $json_content = $ai_output;
                }
                
                // Attempt to parse JSON response
                $json_data = json_decode($json_content, true);
                
                if ($json_data && isset($json_data['score']) && isset($json_data['suggestion'])) {
                    $ai_score = (int)$json_data['score'];
                    $ai_suggestion = trim($json_data['suggestion']);

                    if ($ai_score >= 1 && $ai_score <= 3) {
                        $current_run_results['sitemap']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    $current_run_results['sitemap']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['sitemap']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $current_run_results['sitemap']['suggestion'] = 'AI suggestion failed: ' . ($sitemap_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif (isset($current_run_results['sitemap']['score']) && $current_run_results['sitemap']['score'] == 3 && $current_run_results['sitemap']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['sitemap']['suggestion'] = 'sitemap.xml found, but could not generate AI suggestion at this time.';
        }
        */
        // --- End AI Suggestion for Sitemap.xml (PAUSED) ---

        // --- AI Suggestion for Headings (PAUSED) ---
        /*
        if ($headings_content_for_ai) {
            $prompt = "Analyze the following website heading structure for SEO. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Heading structure: \"{$headings_content_for_ai}\"";
            $headings_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $headings_ai_response_full['success'], // Reflect success of this specific call
                'message' => $headings_ai_response_full['message'], // Message of this specific call
                'raw_request' => $headings_ai_response_full['raw_request'] ?? null,
                'raw_response' => $headings_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($headings_ai_response_full['success'] && isset($headings_ai_response_full['data'])) {
                $ai_output = trim($headings_ai_response_full['data']);
                
                // Extract JSON from markdown code blocks if present
                if (preg_match('/```json\s*(.*?)\s*```/s', $ai_output, $matches)) {
                    $json_content = trim($matches[1]);
                } else {
                    // If no markdown blocks, use the full content
                    $json_content = $ai_output;
                }
                
                // Attempt to parse JSON response
                $json_data = json_decode($json_content, true);
                
                if ($json_data && isset($json_data['score']) && isset($json_data['suggestion'])) {
                    $ai_score = (int)$json_data['score'];
                    $ai_suggestion = trim($json_data['suggestion']);

                    if ($ai_score >= 1 && $ai_score <= 3) {
                        $current_run_results['h1']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    $current_run_results['h1']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['h1']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $current_run_results['h1']['suggestion'] = 'AI suggestion failed: ' . ($headings_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif (isset($current_run_results['h1']['score']) && $current_run_results['h1']['score'] == 3 && $current_run_results['h1']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['h1']['suggestion'] = 'Headings found, but could not generate AI suggestion at this time.';
        }
        */
        // --- End AI Suggestion for Headings (PAUSED) ---

        // --- AI Suggestion for Image Alt (PAUSED) ---
        /*
        // Check if we should run AI analysis (only when images exist AND have alt text to analyze)
        if (isset($img_count) && $img_count > 0) {
            if ($image_alt_content_for_ai) {
                // Images with alt text found - analyze quality with AI
                $prompt = "Analyze the following website image alt attributes for SEO and accessibility. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Image alt texts: \"{$image_alt_content_for_ai}\"";
                
                $image_alt_ai_response_full = call_openrouter_api($prompt); // Get the full response
                
                // Store the raw request and response from THIS call for display
                $_SESSION['openrouter_response'] = [
                    'success' => $image_alt_ai_response_full['success'], // Reflect success of this specific call
                    'message' => $image_alt_ai_response_full['message'], // Message of this specific call
                    'raw_request' => $image_alt_ai_response_full['raw_request'] ?? null,
                    'raw_response' => $image_alt_ai_response_full['raw_response'] ?? null,
                    // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
                ];

                if ($image_alt_ai_response_full['success'] && isset($image_alt_ai_response_full['data'])) {
                    $ai_output = trim($image_alt_ai_response_full['data']);
                    
                    // Extract JSON from markdown code blocks if present
                    if (preg_match('/```json\s*(.*?)\s*```/s', $ai_output, $matches)) {
                        $json_content = trim($matches[1]);
                    } else {
                        // If no markdown blocks, use the full content
                        $json_content = $ai_output;
                    }
                    
                    // Attempt to parse JSON response
                    $json_data = json_decode($json_content, true);
                    
                    if ($json_data && isset($json_data['score']) && isset($json_data['suggestion'])) {
                        $ai_score = (int)$json_data['score'];
                        $ai_suggestion = trim($json_data['suggestion']);

                        if ($ai_score >= 1 && $ai_score <= 3) {
                            $current_run_results['image_alt']['score'] = $ai_score;
                            // If AI gives a low score, the original 'details' is still relevant.
                            // The suggestion from AI is the key part here.
                        } else {
                            // If score is invalid, keep the original score and append a note about the AI's invalid score.
                            $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                        }
                        $current_run_results['image_alt']['suggestion'] = $ai_suggestion;

                    } else {
                        // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                        $current_run_results['image_alt']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                    }
                } else {
                    $current_run_results['image_alt']['suggestion'] = 'AI suggestion failed: ' . ($image_alt_ai_response_full['message'] ?? 'Unknown error');
                }
            } else {
                // Images found but NO alt text - provide static guidance without OpenRouter call
                $current_run_results['image_alt']['score'] = 1;
                $current_run_results['image_alt']['suggestion'] = 'Adding descriptive alt text to all images is essential for both SEO and accessibility, as it helps search engines understand the content and ensures screen readers can convey information to visually impaired users.';
            }
        } elseif (isset($current_run_results['image_alt']['score']) && $current_run_results['image_alt']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['image_alt']['suggestion'] = 'Images found, but could not generate AI suggestion at this time.';
        }
        */
        // --- End AI Suggestion for Image Alt (PAUSED) ---

        // Provide fallback suggestions for image alt since AI is paused
        if (isset($img_count) && $img_count > 0) {
            if ($image_alt_content_for_ai) {
                // Images with alt text found - provide static guidance
                $current_run_results['image_alt']['suggestion'] = 'Images found, but could not generate AI suggestion at this time.';
            } else {
                // Images found but NO alt text - provide static guidance without OpenRouter call
                $current_run_results['image_alt']['score'] = 1;
                $current_run_results['image_alt']['suggestion'] = 'Adding descriptive alt text to all images is essential for both SEO and accessibility, as it helps search engines understand the content and ensures screen readers can convey information to visually impaired users.';
            }
        } elseif (isset($current_run_results['image_alt']['score']) && $current_run_results['image_alt']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['image_alt']['suggestion'] = 'Images found, but could not generate AI suggestion at this time.';
        }

                // --- AI Suggestion for Filenames (PAUSED) ---
        /*
        if ($filenames_content_for_ai) {
            $prompt = "Analyze the following website file names (images, documents, etc.) for SEO best practices. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Provide answer only including the 2 JSON elements for whole filename batch, not for each file. Files found: \"{$filenames_content_for_ai}\"";
            
            $filenames_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $filenames_ai_response_full['success'], // Reflect success of this specific call
                'message' => $filenames_ai_response_full['message'], // Message of this specific call
                'raw_request' => $filenames_ai_response_full['raw_request'] ?? null,
                'raw_response' => $filenames_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($filenames_ai_response_full['success'] && isset($filenames_ai_response_full['data'])) {
                $ai_output = trim($filenames_ai_response_full['data']);
                
                // Extract JSON from markdown code blocks if present
                if (preg_match('/```json\s*(.*?)\s*```/s', $ai_output, $matches)) {
                    $json_content = trim($matches[1]);
                } else {
                    // If no markdown blocks, use the full content
                    $json_content = $ai_output;
                }
                
                // Attempt to parse JSON response
                $json_data = json_decode($json_content, true);
                
                if ($json_data && isset($json_data['score']) && isset($json_data['suggestion'])) {
                    $ai_score = (int)$json_data['score'];
                    $ai_suggestion = trim($json_data['suggestion']);

                    if ($ai_score >= 1 && $ai_score <= 3) {
                        $current_run_results['filenames']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    
                    // Append inline image warning if detected
                    if ($has_inline_encoded_images) {
                        $ai_suggestion .= " Avoid inline base64 images for static assets and use descriptive filenames instead.";
                    }
                    
                    $current_run_results['filenames']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['filenames']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $ai_suggestion = 'AI suggestion failed: ' . ($filenames_ai_response_full['message'] ?? 'Unknown error');
                
                // Append inline image warning if detected
                if ($has_inline_encoded_images) {
                    $ai_suggestion .= " Avoid inline base64 images for static assets and use descriptive filenames instead.";
                }
                
                $current_run_results['filenames']['suggestion'] = $ai_suggestion;
            }
        } elseif (isset($current_run_results['filenames']['score']) && $current_run_results['filenames']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $ai_suggestion = 'Filenames found, but could not generate AI suggestion at this time.';
            
            // Append inline image warning if detected
            if ($has_inline_encoded_images) {
                $ai_suggestion .= " Avoid inline base64 images for static assets and use descriptive filenames instead.";
            }
            
            $current_run_results['filenames']['suggestion'] = $ai_suggestion;
        }
        */
        // --- End AI Suggestion for Filenames (PAUSED) ---

        // Provide fallback suggestions for filenames since AI is paused
        if (isset($filenames_content_for_ai) && !empty($filenames_content_for_ai)) {
            $ai_suggestion = 'Filenames found, but could not generate AI suggestion at this time.';
            
            // Append inline image warning if detected
            if ($has_inline_encoded_images) {
                $ai_suggestion .= " Avoid inline base64 images for static assets and use descriptive filenames instead.";
            }
            
            $current_run_results['filenames']['suggestion'] = $ai_suggestion;
        } elseif (isset($current_run_results['filenames']['score']) && $current_run_results['filenames']['suggestion'] === 'Awaiting AI suggestion...') {
            $current_run_results['filenames']['suggestion'] = 'Filenames found, but could not generate AI suggestion at this time.';
        }

        // --- AI Suggestion for URL ---
        if ($url_content_for_ai) {
            // Determine if URL contains only domain name or has additional parts
            $parsed_for_prompt = parse_url($final_accessible_url);
            $has_path_beyond_domain = false;
            
            // Check if there's a meaningful path (not just "/" or empty)
            if (isset($parsed_for_prompt['path']) && $parsed_for_prompt['path'] !== '/' && !empty(trim($parsed_for_prompt['path'], '/'))) {
                $has_path_beyond_domain = true;
            }
            
            // Check if there are query parameters
            if (isset($parsed_for_prompt['query']) && !empty($parsed_for_prompt['query'])) {
                $has_path_beyond_domain = true;
            }
            
            // Check if there's a fragment
            if (isset($parsed_for_prompt['fragment']) && !empty($parsed_for_prompt['fragment'])) {
                $has_path_beyond_domain = true;
            }
            
            // Use different prompts based on URL structure
            if ($has_path_beyond_domain) {
                $prompt = "Analyze the following URL from SEO perspective. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). URL: \"{$url_content_for_ai}\"";
            } else {
                $prompt = "Analyze the following domain name from SEO perspective. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Domain name: \"{$url_content_for_ai}\"";
            }
            
            $url_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $url_ai_response_full['success'], // Reflect success of this specific call
                'message' => $url_ai_response_full['message'], // Message of this specific call
                'raw_request' => $url_ai_response_full['raw_request'] ?? null,
                'raw_response' => $url_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($url_ai_response_full['success'] && isset($url_ai_response_full['data'])) {
                $ai_output = trim($url_ai_response_full['data']);
                
                // Extract JSON from markdown code blocks if present
                if (preg_match('/```json\s*(.*?)\s*```/s', $ai_output, $matches)) {
                    $json_content = trim($matches[1]);
                } else {
                    // If no markdown blocks, use the full content
                    $json_content = $ai_output;
                }
                
                // Attempt to parse JSON response
                $json_data = json_decode($json_content, true);
                
                if ($json_data && isset($json_data['score']) && isset($json_data['suggestion'])) {
                    $ai_score = (int)$json_data['score'];
                    $ai_suggestion = trim($json_data['suggestion']);

                    if ($ai_score >= 1 && $ai_score <= 3) {
                        $current_run_results['url']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    $current_run_results['url']['suggestion'] = $url_analysis_text . '. ' . $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['url']['suggestion'] = $url_analysis_text . '. AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $current_run_results['url']['suggestion'] = $url_analysis_text . '. AI suggestion failed: ' . ($url_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif (isset($current_run_results['url']['score']) && $current_run_results['url']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['url']['suggestion'] = $url_analysis_text . '. URL found, but could not generate AI suggestion at this time.';
        }
        // --- End AI Suggestion for URL ---

    } else { 
        foreach (array_keys($current_run_results) as $key) {
            $current_run_results[$key]['score'] = null;
            $current_run_results[$key]['details'] = 'Please enter a domain name.';
            $current_run_results[$key]['suggestion'] = '-'; // Add default suggestion for empty domain
        }
    }

    $total_weighted_score = 0;
    $total_weight = 0;
    $final_overall_score = null; // The 1-3 star rating for color
    $total_achieved_stars_numeric = 0; // Sum of actual stars for all indicators
    $total_possible_stars_numeric = 0; // Sum of max possible stars (3 per indicator)

    if ($site_is_accessible) {
        foreach ($current_run_results as $key => $result_item) { // Renamed $result to $result_item to avoid conflict
            if (isset($indicator_weights[$key]) && is_numeric($result_item['score']) && in_array($key, $active_indicators)) {
                $weight = $indicator_weights[$key];
                $score_val = (int)$result_item['score']; 
                
                $total_weighted_score += ($score_val * $weight);
                $total_weight += $weight;

                $total_achieved_stars_numeric += $score_val;
                $total_possible_stars_numeric += 3; 
            }
        }
        if ($total_weight > 0) {
            $average_score = $total_weighted_score / $total_weight;
            if ($average_score >= 2.5) {
                $final_overall_score = 3;
            } elseif ($average_score >= 1.5) {
                $final_overall_score = 2;
            } else {
                $final_overall_score = 1;
            }
        } else {
             $final_overall_score = 1;
             // If no weights applied but site accessible, it implies no indicators were scorable
             // (e.g. content fetch failed but site itself was up). 
             // In this case, achieved and possible stars would be 0.
             $total_achieved_stars_numeric = 0;
             $total_possible_stars_numeric = 0;
        }
    } else {
        $final_overall_score = 1; // Site not accessible, overall color score is 1 star
        // All indicators would have failed, so set stars to 0 / N, where N is total number of active indicators * 3
        $total_achieved_stars_numeric = 0;
        $total_possible_stars_numeric = count($active_indicators) * 3;
    }

    // --- Database Interaction ---
    if ($normalized_domain_for_db && $site_is_accessible) { // Only save if domain is valid and site was accessible
        $conn = get_db_connection();
        if ($conn) {
            // Check if domain exists
            $stmt_select = $conn->prepare("SELECT id FROM data WHERE domain = ?");
            if ($stmt_select) {
                $stmt_select->bind_param("s", $normalized_domain_for_db);
                $stmt_select->execute();
                $result_select_data = $stmt_select->get_result();
                
                if ($result_select_data->num_rows > 0) {
                    // Domain exists, UPDATE it
                    $row = $result_select_data->fetch_assoc();
                    $existing_id = $row['id'];
                    $stmt_update = $conn->prepare("UPDATE data SET ip = ?, score_has = ?, score_max = ?, dt_updated = CURRENT_TIMESTAMP WHERE id = ?");
                    if ($stmt_update) {
                        $stmt_update->bind_param("siii", $user_ip, $total_achieved_stars_numeric, $total_possible_stars_numeric, $existing_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                    } else {
                        // error_log("DB Update Prepare Error: " . $conn->error);
                    }
                } else {
                    // Domain does not exist, INSERT new record
                    $stmt_insert = $conn->prepare("INSERT INTO data (ip, domain, score_has, score_max) VALUES (?, ?, ?, ?)");
                    if ($stmt_insert) {
                        $stmt_insert->bind_param("ssii", $user_ip, $normalized_domain_for_db, $total_achieved_stars_numeric, $total_possible_stars_numeric);
                        $stmt_insert->execute();
                        $stmt_insert->close();
                    } else {
                        // error_log("DB Insert Prepare Error: " . $conn->error);
                    }
                }
                $stmt_select->close();
            } else {
                 // error_log("DB Select Prepare Error: " . $conn->error);
            }
            $conn->close();
        } else {
            // error_log("DB Connection Error for domain: " . $normalized_domain_for_db);
        }
    }

    $_SESSION['seo_results_data'] = $current_run_results;
    $_SESSION['overall_score_data'] = [
        'color_score' => $final_overall_score, 
        'achieved' => $total_achieved_stars_numeric,
        'possible' => $total_possible_stars_numeric
    ];
    $_SESSION['last_domain_for_view'] = $domain_input; 
    $_SESSION['is_post_redirect_layout_active'] = true;

    // Make sure to clear any previous rate limit error if processing proceeds
    unset($_SESSION['rate_limit_error']);

    header('Location: /');
    exit;
}
?> 