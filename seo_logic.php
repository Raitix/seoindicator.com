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

// Function to call Google PageSpeed Insights API
function call_pagespeed_insights_api($url) {
    if (!defined('GOOGLE_PAGESPEED_API_KEY') || GOOGLE_PAGESPEED_API_KEY === '') {
        return ['success' => false, 'message' => 'Google PageSpeed API Key is not configured.', 'raw_request' => null, 'raw_response' => null];
    }

    $api_key = GOOGLE_PAGESPEED_API_KEY;
    $api_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    
    // Build query parameters properly
    $params = [
        'url' => $url,
        'key' => $api_key,
        'strategy' => 'DESKTOP',
        'category' => 'PERFORMANCE'
    ];
    
    $request_url = $api_url . '?' . http_build_query($params);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $request_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'SEOIndicator.com/1.0',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
        ]
    ]);

    $raw_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $trimmed_raw_response = trim($raw_response);

    if ($curl_error) {
        return ['success' => false, 'message' => "cURL Error: " . $curl_error, 'raw_request' => $request_url, 'raw_response' => $trimmed_raw_response];
    }

    if ($http_code == 200) {
        $response_data = json_decode($trimmed_raw_response, true);
        if ($response_data && isset($response_data['lighthouseResult'])) {
            return ['success' => true, 'message' => 'PageSpeed Insights API Success!', 'data' => $response_data, 'raw_request' => $request_url, 'raw_response' => $trimmed_raw_response];
        } else {
            return ['success' => false, 'message' => 'PageSpeed API: Unexpected response structure.', 'raw_request' => $request_url, 'raw_response' => $trimmed_raw_response];
        }
    } else {
        // Parse error response for more details
        $error_data = json_decode($trimmed_raw_response, true);
        $error_message = "PageSpeed API Error (HTTP {$http_code})";
        
        if ($error_data && isset($error_data['error'])) {
            $error_details = $error_data['error'];
            
            if (isset($error_details['message'])) {
                $error_message .= ": " . $error_details['message'];
            }
            
            // Check for specific 403 service disabled error
            if ($http_code == 403 && isset($error_details['status']) && $error_details['status'] === 'PERMISSION_DENIED') {
                if (strpos($error_details['message'], 'PageSpeed Insights API has not been used') !== false) {
                    $error_message = "PageSpeed Insights API is not enabled in your Google Cloud project. Please enable it in the Google Cloud Console and try again.";
                }
            }
            
            if (isset($error_details['code'])) {
                $error_message .= " (Code: " . $error_details['code'] . ")";
            }
        }
        
        return ['success' => false, 'message' => $error_message, 'raw_request' => $request_url, 'raw_response' => $trimmed_raw_response];
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

    // Visual stars based on the 0-3 $score parameter
    if (!is_numeric($score) || $score < 0 || $score > $max_stars_visual) {
         $stars_html = '<span class="text-secondary">' . str_repeat($empty_star, $max_stars_visual) . '</span>';
         if ($show_text_score) {
            // If detailed text scores are provided, use them, otherwise N/A
            if (isset($achieved_score_text) && isset($max_possible_score_text)) {
                $score_text_part = ' <span class="text-muted">(' . htmlspecialchars($achieved_score_text) . '/' . htmlspecialchars($max_possible_score_text) . ')</span>';
            } else {
                $score_text_part = ' <span class="text-muted">(N/A)</span>';
            }
         }
    } elseif ($score == 0) {
        // Score 0 - show grey stars with 0/3 text
        $stars_html = '<span class="text-secondary">' . str_repeat($empty_star, $max_stars_visual) . '</span>';
        if ($show_text_score) {
            $score_text_part = ' <span class="text-muted">(0/' . $max_stars_visual . ')</span>';
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
    if (!is_numeric($score) || $score < 0 || $score > 3) {
        return '';
    }
    switch ($score) {
        case 3:
            return 'table-success';
        case 2:
            return 'table-warning';
        case 1:
            return 'table-danger';
        case 0:
            return 'table-dark';
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
    'links' => 3,
    '404_page' => 3,
    'mobile_friendly' => 3,
    'language' => 2,
    'favicon' => 1,
    'canonical' => 2,
    'social_meta_tags' => 1,
    'aria' => 1,
    'page_speed' => 3,
];

// --- Active Indicators Configuration ---
// Only these indicators will be processed and included in scoring
$active_indicators = [
    'page_speed', // Only Page Speed indicator is active as per user requirement
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
    'links' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    '404_page' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'mobile_friendly' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'language' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'favicon' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'canonical' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'social_meta_tags' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'aria' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
    'page_speed' => ['score' => null, 'details' => '-', 'suggestion' => '-'],
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
                    $current_run_results['title']['score'] = 0;
                    $current_run_results['title']['details'] = htmlspecialchars($title_content);
                    $title_content_for_ai = $title_content; // Store for AI
                    // Suggestion will be filled by AI or default if AI fails
                    $current_run_results['title']['suggestion'] = 'Developed and paused'; 
                } else {
                    $current_run_results['title']['score'] = 0;
                    $current_run_results['title']['details'] = 'Title tag exists but is empty.';
                    $current_run_results['title']['suggestion'] = 'Developed and paused';
                }
            } else {
                $current_run_results['title']['score'] = 0;
                $current_run_results['title']['details'] = 'No title tag found.';
                $current_run_results['title']['suggestion'] = 'Developed and paused';
            }

            // Meta Description (REAL CHECK - NOT MOCKED)
            $meta_description_nodes = $xpath->query('//meta[@name="description"]');
            if ($meta_description_nodes->length > 0) {
                $meta_content = trim($meta_description_nodes->item(0)->getAttribute('content'));
                if (!empty($meta_content)) {
                    $current_run_results['meta_description']['score'] = 0;
                    $current_run_results['meta_description']['details'] = htmlspecialchars($meta_content);
                    $meta_description_content_for_ai = $meta_content; // Store for AI
                    // Suggestion will be filled by AI or default if AI fails
                    $current_run_results['meta_description']['suggestion'] = 'Developed and paused';
                } else {
                    $current_run_results['meta_description']['score'] = 0;
                    $current_run_results['meta_description']['details'] = 'Meta description tag exists but content is empty.';
                    $current_run_results['meta_description']['suggestion'] = 'Developed and paused';
                }
            } else {
                $current_run_results['meta_description']['score'] = 0;
                $current_run_results['meta_description']['details'] = 'No meta description tag found.';
                $current_run_results['meta_description']['suggestion'] = 'Developed and paused';
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
            
            // Scoring logic (will be overridden later for paused indicators)
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
            
            // Store the full URL and analysis in details (shown in Result column)
            $current_run_results['url']['details'] = htmlspecialchars($final_accessible_url) . "<br><br>" . $url_analysis_text;
            $current_run_results['url']['suggestion'] = 'Developed and paused';
            
            // Prepare content for AI analysis
            $url_content_for_ai = $final_accessible_url;
            
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
                $current_run_results['h1']['score'] = 0;
                if ($h1_count == 1) {
                    $current_run_results['h1']['details'] = "Found 1 H1 tag and {$total_headings} total headings (H2: {$h2_count}, H3: {$h3_count})";
                } else {
                    $current_run_results['h1']['details'] = "Found {$h1_count} H1 tags and {$total_headings} total headings (H2: {$h2_count}, H3: {$h3_count})";
                }
                $current_run_results['h1']['suggestion'] = 'Developed and paused';
            } else {
                $current_run_results['h1']['score'] = 0;
                $current_run_results['h1']['details'] = "No H1 tags found. Total headings: {$total_headings} (H2: {$h2_count}, H3: {$h3_count})";
                $current_run_results['h1']['suggestion'] = 'Developed and paused';
            }

            // Image Alt Analysis (Developed and Paused)
            $current_run_results['image_alt']['score'] = 0;
            $current_run_results['image_alt']['details'] = 'Image Alt analysis indicator';
            $current_run_results['image_alt']['suggestion'] = 'Developed and paused';

            // Links Analysis
            $link_nodes = $xpath->query('//a[@href] | //*[@onclick]');
            $links_found = [];
            $links_content_for_ai = null;
            
            if ($link_nodes->length > 0) {
                foreach ($link_nodes as $link_node) {
                    $link_info = [];
                    
                    // Handle regular anchor links
                    if ($link_node->nodeName === 'a' && $link_node->hasAttribute('href')) {
                        $href = trim($link_node->getAttribute('href'));
                        $anchor_text = trim($link_node->nodeValue);
                        $link_info = [
                            'type' => 'anchor',
                            'href' => $href,
                            'text' => $anchor_text,
                            'target' => $link_node->hasAttribute('target') ? $link_node->getAttribute('target') : '',
                            'rel' => $link_node->hasAttribute('rel') ? $link_node->getAttribute('rel') : ''
                        ];
                    }
                    // Handle onclick links
                    elseif ($link_node->hasAttribute('onclick')) {
                        $onclick = trim($link_node->getAttribute('onclick'));
                        $element_text = trim($link_node->nodeValue);
                        $link_info = [
                            'type' => 'onclick',
                            'onclick' => $onclick,
                            'text' => $element_text,
                            'element' => $link_node->nodeName
                        ];
                    }
                    
                    if (!empty($link_info)) {
                        $links_found[] = $link_info;
                    }
                }
                
                $links_count = count($links_found);
                
                // Analyze link quality
                $internal_links = 0;
                $external_links = 0;
                $empty_anchor_text = 0;
                $descriptive_anchor_text = 0;
                $onclick_links = 0;
                
                foreach ($links_found as $link) {
                    if ($link['type'] === 'onclick') {
                        $onclick_links++;
                    } elseif ($link['type'] === 'anchor') {
                        // Check if internal or external
                        $href = $link['href'];
                        if (strpos($href, 'http') === 0) {
                            $link_domain = parse_url($href, PHP_URL_HOST);
                            $current_domain = parse_url($final_accessible_url, PHP_URL_HOST);
                            if ($link_domain === $current_domain) {
                                $internal_links++;
                            } else {
                                $external_links++;
                            }
                        } else {
                            $internal_links++; // Relative links are internal
                        }
                        
                        // Check anchor text quality
                        $text = $link['text'];
                        if (empty($text) || strlen($text) < 2) {
                            $empty_anchor_text++;
                        } elseif (strlen($text) >= 3 && !in_array(strtolower($text), ['click here', 'read more', 'more', 'here', 'link'])) {
                            $descriptive_anchor_text++;
                        }
                    }
                }
                
                // Prepare content for AI
                $links_for_ai = array_slice($links_found, 0, 20); // Limit to first 20 links to avoid token limits
                $links_content_for_ai = json_encode($links_for_ai, JSON_UNESCAPED_UNICODE);
                
                // Score calculation
                $score = 1;
                if ($descriptive_anchor_text > $empty_anchor_text && $onclick_links < $links_count * 0.3) {
                    if ($internal_links > 0 && $external_links >= 0) {
                        $score = 3;
                    } else {
                        $score = 2;
                    }
                } elseif ($descriptive_anchor_text >= $empty_anchor_text) {
                    $score = 2;
                }
                
                $current_run_results['links']['score'] = 0;
                $current_run_results['links']['details'] = "Found {$links_count} links (Internal: {$internal_links}, External: {$external_links}, OnClick: {$onclick_links})";
                $current_run_results['links']['suggestion'] = 'Developed and paused';
            } else {
                $current_run_results['links']['score'] = 1;
                $current_run_results['links']['details'] = 'No links found on the page';
                $current_run_results['links']['suggestion'] = 'Add internal and external links to improve navigation and SEO value.';
            }

            // 404 Page Analysis with Static Checks
            $page_404_content_for_ai = null;
            $parsed_url_for_404 = parse_url($final_accessible_url);
            
            if (isset($parsed_url_for_404['scheme']) && isset($parsed_url_for_404['host'])) {
                $base_url_for_404 = $parsed_url_for_404['scheme'] . '://' . $parsed_url_for_404['host'];
                
                // Try multiple common 404 page URLs
                $possible_404_urls = [
                    $base_url_for_404 . '/404',
                    $base_url_for_404 . '/404.html',
                    $base_url_for_404 . '/404.php',
                    $base_url_for_404 . '/error',
                    $base_url_for_404 . '/page-not-found',
                    $base_url_for_404 . '/not-found'
                ];
                
                $page_404_found = false;
                $page_404_content = '';
                $page_404_url_found = '';
                $page_404_status_code = 0;
                
                // Static checks results
                $static_checks = [
                    'http_404_status' => false,
                    'has_logo' => false,
                    'has_404_message' => false,
                    'has_search_form' => false,
                    'has_contact_info' => false,
                    'has_home_link' => false,
                    'has_helpful_links' => false
                ];
                
                foreach ($possible_404_urls as $test_404_url) {
                    $context_404 = stream_context_create([
                        'http' => [
                            'method' => "GET",
                            'timeout' => 5,
                            'ignore_errors' => true,
                            'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
                        ]
                    ]);
                    
                    $content_404 = @file_get_contents($test_404_url, false, $context_404);
                    $headers_404 = @get_headers($test_404_url, false, $context_404);
                    
                    if ($content_404 !== false && strlen($content_404) > 100) {
                        // Check HTTP status code
                        if ($headers_404) {
                            foreach ($headers_404 as $header) {
                                if (stripos($header, '404') !== false) {
                                    $static_checks['http_404_status'] = true;
                                    break;
                                }
                            }
                        }
                        
                        // Parse the 404 page content
                        libxml_use_internal_errors(true);
                        $doc_404 = new DOMDocument();
                        @$doc_404->loadHTML($content_404);
                        libxml_clear_errors();
                        
                        $xpath_404 = new DOMXPath($doc_404);
                        
                        // Check for logo (image or text)
                        $logo_img = $xpath_404->query('//img[contains(@src, "logo") or contains(@alt, "logo") or contains(@class, "logo")]');
                        $logo_text = $xpath_404->query('//*[contains(text(), "logo") or contains(@class, "logo") or contains(@id, "logo")]');
                        if ($logo_img->length > 0 || $logo_text->length > 0) {
                            $static_checks['has_logo'] = true;
                        }
                        
                        // Check for 404 message
                        $content_text = strtolower($doc_404->textContent);
                        $error_phrases = ['404', 'not found', 'page not found', 'does not exist', 'cannot be found', 'error'];
                        foreach ($error_phrases as $phrase) {
                            if (strpos($content_text, $phrase) !== false) {
                                $static_checks['has_404_message'] = true;
                                break;
                            }
                        }
                        
                        // Check for search form
                        $search_forms = $xpath_404->query('//form[contains(@action, "search") or .//input[@type="search"]] | //input[@type="search"]');
                        if ($search_forms->length > 0) {
                            $static_checks['has_search_form'] = true;
                        }
                        
                        // Check for contact information
                        $contact_elements = $xpath_404->query('//a[contains(@href, "contact") or contains(text(), "contact")] | //*[contains(text(), "contact") or contains(text(), "email") or contains(text(), "support")]');
                        if ($contact_elements->length > 0) {
                            $static_checks['has_contact_info'] = true;
                        }
                        
                        // Check for home link
                        $home_links = $xpath_404->query('//a[contains(@href, "home") or contains(@href, "/") or contains(text(), "home") or contains(text(), "homepage")]');
                        if ($home_links->length > 0) {
                            $static_checks['has_home_link'] = true;
                        }
                        
                        // Check for helpful links (similar/popular/recent pages)
                        $helpful_links = $xpath_404->query('//a[contains(text(), "popular") or contains(text(), "recent") or contains(text(), "similar") or contains(@class, "nav") or contains(@class, "menu")]');
                        $nav_sections = $xpath_404->query('//nav | //ul[contains(@class, "nav")] | //div[contains(@class, "nav")]');
                        if ($helpful_links->length > 2 || $nav_sections->length > 0) {
                            $static_checks['has_helpful_links'] = true;
                        }
                        
                        $page_404_found = true;
                        $page_404_url_found = $test_404_url;
                        $page_404_content = $content_404;
                        break;
                    }
                }
                
                if (!$page_404_found) {
                    // Try accessing a definitely non-existent page to see default 404 response
                    $test_nonexistent_url = $base_url_for_404 . '/definitely-does-not-exist-' . time();
                    $context_test = stream_context_create([
                        'http' => [
                            'method' => "GET",
                            'timeout' => 5,
                            'ignore_errors' => true,
                            'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
                        ]
                    ]);
                    
                    $test_content = @file_get_contents($test_nonexistent_url, false, $context_test);
                    $test_headers = @get_headers($test_nonexistent_url, false, $context_test);
                    
                    if ($test_content !== false && strlen($test_content) > 50) {
                        // Check HTTP status code for default response
                        if ($test_headers) {
                            foreach ($test_headers as $header) {
                                if (stripos($header, '404') !== false) {
                                    $static_checks['http_404_status'] = true;
                                    break;
                                }
                            }
                        }
                        
                        // Check if default response has basic 404 message
                        $content_text = strtolower($test_content);
                        if (strpos($content_text, '404') !== false || strpos($content_text, 'not found') !== false) {
                            $static_checks['has_404_message'] = true;
                        }
                        
                        $page_404_content = $test_content;
                    }
                }
                
                // Calculate score based on static checks
                $passed_checks = array_sum($static_checks);
                if ($passed_checks >= 6) {
                    $score_404 = 3;
                } elseif ($passed_checks >= 4) {
                    $score_404 = 2;
                } else {
                    $score_404 = 1;
                }
                
                // Build suggestion with check results
                $check_results = [];
                $check_results[] = ($static_checks['http_404_status'] ? '✓' : '✗') . ' HTTP 404 Status';
                $check_results[] = ($static_checks['has_logo'] ? '✓' : '✗') . ' Logo Present';
                $check_results[] = ($static_checks['has_404_message'] ? '✓' : '✗') . ' Error Message';
                $check_results[] = ($static_checks['has_search_form'] ? '✓' : '✗') . ' Search Form';
                $check_results[] = ($static_checks['has_contact_info'] ? '✓' : '✗') . ' Contact Info';
                $check_results[] = ($static_checks['has_home_link'] ? '✓' : '✗') . ' Home Link';
                $check_results[] = ($static_checks['has_helpful_links'] ? '✓' : '✗') . ' Navigation Links';
                
                $current_run_results['404_page']['score'] = $score_404;
                
                if ($page_404_found) {
                    $current_run_results['404_page']['details'] = "Custom 404 page found at: " . str_replace($base_url_for_404, '', $page_404_url_found);
                } else {
                    $current_run_results['404_page']['details'] = 'Default server 404 response (no custom page found)';
                }
                
                $current_run_results['404_page']['suggestion'] = 'Developed and paused';
                
                // Store content for potential future AI analysis
                if ($page_404_content) {
                    $page_404_content_for_ai = substr(strip_tags($page_404_content), 0, 300);
                }
                
            } else {
                $current_run_results['404_page']['score'] = 1;
                $current_run_results['404_page']['details'] = 'Could not determine base URL to check for 404 page';
                $current_run_results['404_page']['suggestion'] = 'Developed and paused';
            }

            // Mobile Friendly Analysis with Static Checks
            if ($page_content !== false) {
                // Static checks for mobile friendliness
                $mobile_checks = [
                    'viewport_meta' => false,
                    'responsive_design' => false,
                    'readable_font_size' => false,
                    'touch_friendly_elements' => false,
                    'media_queries' => false,
                    'optimized_images' => false,
                    'no_outdated_tech' => true, // Default to true, set false if outdated technologies found
                    'fast_loading' => false,
                    'no_user_scalable_no' => true, // Default to true, set false if user-scalable=no found
                    'no_intrusive_popups' => true, // Default to true, set false if popups detected
                    'hamburger_menu' => false,
                    'content_parity' => false
                ];
                
                libxml_use_internal_errors(true);
                $doc_mobile = new DOMDocument();
                @$doc_mobile->loadHTML($page_content);
                libxml_clear_errors();
                $xpath_mobile = new DOMXPath($doc_mobile);
                
                // Check for viewport meta tag
                $viewport_nodes = $xpath_mobile->query('//meta[@name="viewport"]');
                if ($viewport_nodes->length > 0) {
                    $viewport_content = $viewport_nodes->item(0)->getAttribute('content');
                    if (strpos($viewport_content, 'width=device-width') !== false) {
                        $mobile_checks['viewport_meta'] = true;
                    }
                    // Check for user-scalable=no (accessibility violation)
                    if (strpos($viewport_content, 'user-scalable=no') !== false) {
                        $mobile_checks['no_user_scalable_no'] = false;
                    }
                }
                
                // Check for responsive design indicators in CSS
                if (preg_match('/@media\s*\([^)]*\)/', $page_content)) {
                    $mobile_checks['media_queries'] = true;
                }
                
                // Check for responsive design classes/patterns
                if (preg_match('/\b(responsive|mobile|tablet|desktop|col-xs|col-sm|col-md|col-lg|flex|grid)\b/i', $page_content)) {
                    $mobile_checks['responsive_design'] = true;
                }
                
                // Check font sizes (look for reasonable font-size declarations)
                if (preg_match('/font-size\s*:\s*([1-9][0-9]*px|[1-9](\.[0-9]+)?rem|[1-9](\.[0-9]+)?em)/i', $page_content)) {
                    $mobile_checks['readable_font_size'] = true;
                }
                
                // Check for touch-friendly elements (buttons, links with proper sizing)
                $buttons = $xpath_mobile->query('//button | //input[@type="submit"] | //input[@type="button"]');
                $links = $xpath_mobile->query('//a');
                if ($buttons->length > 0 || $links->length > 0) {
                    // Look for CSS that suggests touch-friendly sizing
                    if (preg_match('/(min-height|padding|margin)\s*:\s*([2-9][0-9]*px|[2-9](\.[0-9]+)?rem)/i', $page_content)) {
                        $mobile_checks['touch_friendly_elements'] = true;
                    }
                }
                
                // Check for optimized images (responsive images, srcset)
                $img_nodes = $xpath_mobile->query('//img[@srcset] | //picture | //img[contains(@class, "responsive")]');
                if ($img_nodes->length > 0) {
                    $mobile_checks['optimized_images'] = true;
                } else {
                    // Check for CSS max-width on images
                    if (preg_match('/img[^}]*max-width\s*:\s*100%/i', $page_content)) {
                        $mobile_checks['optimized_images'] = true;
                    }
                }
                
                // Check for outdated technologies (should not be present for mobile friendliness)
                $outdated_tech_found = false;
                
                // Check for Flash (more specific to avoid false positives like "flash-messages")
                if (preg_match('/(\.swf|application\/x-shockwave-flash|type=["\']application\/x-shockwave-flash|embed.*\.swf|object.*flash)/i', $page_content)) {
                    $outdated_tech_found = true;
                }
                
                // Check for Adobe Shockwave Player
                if (preg_match('/(shockwave.*player|application\/x-director|\.dcr)/i', $page_content)) {
                    $outdated_tech_found = true;
                }
                
                // Check for Microsoft Silverlight
                if (preg_match('/(silverlight|application\/x-silverlight|\.xap)/i', $page_content)) {
                    $outdated_tech_found = true;
                }
                
                // Check for Java Applets
                if (preg_match('/(java.*applet|application\/x-java-applet|<applet|\.jar.*applet)/i', $page_content)) {
                    $outdated_tech_found = true;
                }
                
                // Check for Frames (frameset, frame tags)
                if (preg_match('/(<frameset|<frame\s)/i', $page_content)) {
                    $outdated_tech_found = true;
                }
                
                if ($outdated_tech_found) {
                    $mobile_checks['no_outdated_tech'] = false;
                }
                
                // Check for fast loading indicators (compressed CSS, minified JS, optimized resources)
                $html_size_kb = strlen($page_content) / 1024;
                if ($html_size_kb < 100) {
                    $mobile_checks['fast_loading'] = true;
                }
                
                // Check for hamburger menu indicators
                if (preg_match('/(hamburger|burger|menu-toggle|navbar-toggler|mobile-menu|side-menu)/i', $page_content)) {
                    $mobile_checks['hamburger_menu'] = true;
                }
                
                // Check for intrusive popups (basic detection)
                if (preg_match('/(popup|modal|overlay).*(?:subscribe|newsletter|discount|offer)/i', $page_content) ||
                    preg_match('/position\s*:\s*fixed.*z-index\s*:\s*[1-9][0-9]{2,}/i', $page_content)) {
                    $mobile_checks['no_intrusive_popups'] = false;
                }
                
                // Check for content parity indicators (same meta descriptions, titles, etc.)
                $title_mobile = $xpath_mobile->query('//title');
                $meta_desc_mobile = $xpath_mobile->query('//meta[@name="description"]');
                if ($title_mobile->length > 0 && $meta_desc_mobile->length > 0) {
                    // Basic check - if both title and meta description exist, assume good content parity
                    $mobile_checks['content_parity'] = true;
                }
                
                // Calculate score based on checks
                $passed_mobile_checks = array_sum($mobile_checks);
                if ($passed_mobile_checks >= 10) {
                    $mobile_score = 3;
                } elseif ($passed_mobile_checks >= 7) {
                    $mobile_score = 2;
                } else {
                    $mobile_score = 1;
                }
                
                // Build suggestion with check results
                $mobile_check_results = [];
                $mobile_check_results[] = ($mobile_checks['viewport_meta'] ? '✓' : '✗') . ' Viewport Meta Tag';
                $mobile_check_results[] = ($mobile_checks['responsive_design'] ? '✓' : '✗') . ' Responsive Design';
                $mobile_check_results[] = ($mobile_checks['readable_font_size'] ? '✓' : '✗') . ' Readable Font Size';
                $mobile_check_results[] = ($mobile_checks['touch_friendly_elements'] ? '✓' : '✗') . ' Touch-Friendly Elements';
                $mobile_check_results[] = ($mobile_checks['media_queries'] ? '✓' : '✗') . ' Media Queries';
                $mobile_check_results[] = ($mobile_checks['optimized_images'] ? '✓' : '✗') . ' Optimized Images';
                $mobile_check_results[] = ($mobile_checks['no_outdated_tech'] ? '✓' : '✗') . ' No Outdated Technologies';
                $mobile_check_results[] = ($mobile_checks['fast_loading'] ? '✓' : '✗') . ' Fast Loading';
                $mobile_check_results[] = ($mobile_checks['no_user_scalable_no'] ? '✓' : '✗') . ' No user-scalable=no';
                $mobile_check_results[] = ($mobile_checks['no_intrusive_popups'] ? '✓' : '✗') . ' No Intrusive Pop-ups';
                $mobile_check_results[] = ($mobile_checks['hamburger_menu'] ? '✓' : '✗') . ' Hamburger Menu';
                $mobile_check_results[] = ($mobile_checks['content_parity'] ? '✓' : '✗') . ' Content Parity';
                
                $current_run_results['mobile_friendly']['score'] = $mobile_score;
                $current_run_results['mobile_friendly']['details'] = "Mobile analysis completed ({$passed_mobile_checks}/12 checks passed)";
                $current_run_results['mobile_friendly']['suggestion'] = 'Developed and paused';
                
            } else {
                $current_run_results['mobile_friendly']['score'] = 1;
                $current_run_results['mobile_friendly']['details'] = 'Could not analyze mobile friendliness - page content not available';
                $current_run_results['mobile_friendly']['suggestion'] = 'Developed and paused';
            }

            // Filenames Analysis (Developed and Paused)
            $current_run_results['filenames']['score'] = 0;
            $current_run_results['filenames']['details'] = 'Filenames analysis indicator';
            $current_run_results['filenames']['suggestion'] = 'Developed and paused';

            // Language Declaration Analysis (Developed and Paused)
            $current_run_results['language']['score'] = 0;
            $current_run_results['language']['details'] = 'Language declaration analysis indicator';
            $current_run_results['language']['suggestion'] = 'Developed and paused';



            // Favicon Analysis with Comprehensive Static Checks
            if ($page_content !== false) {
                // Static checks for favicon implementation
                $favicon_checks = [
                    'favicon_link_present' => false,
                    'ico_format_present' => false,
                    'png_format_present' => false,
                    'apple_touch_icon' => false,
                    'multiple_sizes' => false,
                    'valid_file_paths' => false,
                    'standard_location' => false,
                    'web_app_manifest_pwa' => false
                ];
                
                libxml_use_internal_errors(true);
                $doc_favicon = new DOMDocument();
                @$doc_favicon->loadHTML($page_content);
                libxml_clear_errors();
                $xpath_favicon = new DOMXPath($doc_favicon);
                
                // Check for various favicon link tags
                $favicon_links = [];
                $all_icon_links = $xpath_favicon->query('//link[contains(@rel, "icon") or contains(@rel, "shortcut") or contains(@rel, "apple-touch-icon")]');
                
                $found_ico = false;
                $found_png = false;
                $found_apple_touch = false;
                $size_count = 0;
                $valid_paths = 0;
                $total_links = $all_icon_links->length;
                
                if ($total_links > 0) {
                    $favicon_checks['favicon_link_present'] = true;
                    
                    foreach ($all_icon_links as $link) {
                        $rel = strtolower($link->getAttribute('rel'));
                        $href = $link->getAttribute('href');
                        $type = $link->getAttribute('type');
                        $sizes = $link->getAttribute('sizes');
                        
                        $favicon_links[] = [
                            'rel' => $rel,
                            'href' => $href,
                            'type' => $type,
                            'sizes' => $sizes
                        ];
                        
                        // Check for ICO format
                        if (strpos($href, '.ico') !== false || strpos($type, 'x-icon') !== false) {
                            $found_ico = true;
                        }
                        
                        // Check for PNG format
                        if (strpos($href, '.png') !== false || strpos($type, 'png') !== false) {
                            $found_png = true;
                        }
                        
                        // Check for Apple touch icon
                        if (strpos($rel, 'apple-touch-icon') !== false) {
                            $found_apple_touch = true;
                        }
                        
                        // Count different sizes
                        if (!empty($sizes) && $sizes !== 'any') {
                            $size_count++;
                        }
                        
                        // Check for valid file paths (basic validation)
                        if (!empty($href) && !strpos($href, 'data:') && (
                            strpos($href, '.ico') !== false || 
                            strpos($href, '.png') !== false || 
                            strpos($href, '.svg') !== false ||
                            strpos($href, '.jpg') !== false ||
                            strpos($href, '.gif') !== false
                        )) {
                            $valid_paths++;
                        }
                    }
                    
                    if ($found_ico) {
                        $favicon_checks['ico_format_present'] = true;
                    }
                    
                    if ($found_png) {
                        $favicon_checks['png_format_present'] = true;
                    }
                    
                    if ($found_apple_touch) {
                        $favicon_checks['apple_touch_icon'] = true;
                    }
                    
                    if ($size_count >= 2) {
                        $favicon_checks['multiple_sizes'] = true;
                    }
                    
                    if ($valid_paths > 0) {
                        $favicon_checks['valid_file_paths'] = true;
                    }
                }
                
                // Check for standard location (favicon.ico in root)
                // This would require an additional HTTP request, but we can infer from common patterns
                foreach ($favicon_links as $link) {
                    if (strpos($link['href'], '/favicon.ico') !== false || $link['href'] === 'favicon.ico') {
                        $favicon_checks['standard_location'] = true;
                        break;
                    }
                }
                
                // Check for Web Application Manifest (PWA)
                $manifest_nodes = $xpath_favicon->query('//link[@rel="manifest"]');
                if ($manifest_nodes->length > 0) {
                    $manifest_href = $manifest_nodes->item(0)->getAttribute('href');
                    if (!empty($manifest_href)) {
                        // Convert relative URL to absolute if needed
                        $manifest_url = $manifest_href;
                        if (strpos($manifest_href, 'http') !== 0) {
                            $base_url_parts = parse_url($final_accessible_url);
                            $base_url = $base_url_parts['scheme'] . '://' . $base_url_parts['host'];
                            if ($manifest_href[0] === '/') {
                                $manifest_url = $base_url . $manifest_href;
                            } else {
                                $manifest_url = $base_url . '/' . $manifest_href;
                            }
                        }
                        
                        // Try to fetch and parse the manifest
                        $context_manifest = stream_context_create([
                            'http' => [
                                'method' => "GET",
                                'timeout' => 5,
                                'ignore_errors' => true,
                                'header' => "User-Agent: SEOIndicatorBot/1.0\r\n"
                            ]
                        ]);
                        
                        $manifest_content = @file_get_contents($manifest_url, false, $context_manifest);
                        if ($manifest_content !== false) {
                            $manifest_data = @json_decode($manifest_content, true);
                            if ($manifest_data && isset($manifest_data['icons']) && is_array($manifest_data['icons'])) {
                                $found_192 = false;
                                $found_512 = false;
                                
                                foreach ($manifest_data['icons'] as $icon) {
                                    if (isset($icon['sizes'])) {
                                        $sizes = $icon['sizes'];
                                        if (strpos($sizes, '192x192') !== false || $sizes === '192x192') {
                                            $found_192 = true;
                                        }
                                        if (strpos($sizes, '512x512') !== false || $sizes === '512x512') {
                                            $found_512 = true;
                                        }
                                    }
                                }
                                
                                if ($found_192 && $found_512) {
                                    $favicon_checks['web_app_manifest_pwa'] = true;
                                }
                            }
                        }
                    }
                }
                
                // Calculate score based on checks
                $passed_favicon_checks = array_sum($favicon_checks);
                if ($passed_favicon_checks >= 6) {
                    $favicon_score = 3;
                } elseif ($passed_favicon_checks >= 4) {
                    $favicon_score = 2;
                } else {
                    $favicon_score = 1;
                }
                
                // Build suggestion with check results
                $favicon_check_results = [];
                $favicon_check_results[] = ($favicon_checks['favicon_link_present'] ? '✓' : '✗') . ' Favicon Link Tags Present';
                $favicon_check_results[] = ($favicon_checks['ico_format_present'] ? '✓' : '✗') . ' ICO Format Available';
                $favicon_check_results[] = ($favicon_checks['png_format_present'] ? '✓' : '✗') . ' PNG Format Available';
                $favicon_check_results[] = ($favicon_checks['apple_touch_icon'] ? '✓' : '✗') . ' Apple Touch Icon';
                $favicon_check_results[] = ($favicon_checks['multiple_sizes'] ? '✓' : '✗') . ' Multiple Icon Sizes';
                $favicon_check_results[] = ($favicon_checks['valid_file_paths'] ? '✓' : '✗') . ' Valid File Paths';
                $favicon_check_results[] = ($favicon_checks['standard_location'] ? '✓' : '✗') . ' Standard favicon.ico Location';
                $favicon_check_results[] = ($favicon_checks['web_app_manifest_pwa'] ? '✓' : '✗') . ' Web App Manifest PWA (192x192 + 512x512)';
                
                $current_run_results['favicon']['score'] = 0;
                if ($total_links > 0) {
                    $current_run_results['favicon']['details'] = "Found {$total_links} favicon-related link tag(s) ({$passed_favicon_checks}/8 checks passed)";
                } else {
                    $current_run_results['favicon']['details'] = "No favicon link tags found ({$passed_favicon_checks}/8 checks passed)";
                }
                $current_run_results['favicon']['suggestion'] = 'Developed and paused';
                
            } else {
                $current_run_results['favicon']['score'] = 0;
                $current_run_results['favicon']['details'] = 'Could not analyze favicon - page content not available';
                $current_run_results['favicon']['suggestion'] = 'Developed and paused';
            }

            // Canonical Tag Analysis with Comprehensive Static Checks
            if ($page_content !== false) {
                // Static checks for canonical tag implementation
                $canonical_checks = [
                    'canonical_tag_present' => false,
                    'single_canonical_tag' => false,
                    'valid_url_format' => false,
                    'absolute_url_used' => false,
                    'self_referencing' => false,
                    'proper_placement' => false,
                    'no_query_parameters' => false,
                    'https_canonical' => false
                ];
                
                libxml_use_internal_errors(true);
                $doc_canonical = new DOMDocument();
                @$doc_canonical->loadHTML($page_content);
                libxml_clear_errors();
                $xpath_canonical = new DOMXPath($doc_canonical);
                
                // Check for canonical link tags
                $canonical_nodes = $xpath_canonical->query('//link[@rel="canonical"]');
                $canonical_count = $canonical_nodes->length;
                $canonical_href = '';
                
                if ($canonical_count > 0) {
                    $canonical_checks['canonical_tag_present'] = true;
                    
                    // Check for single canonical tag (best practice)
                    if ($canonical_count === 1) {
                        $canonical_checks['single_canonical_tag'] = true;
                    }
                    
                    // Analyze the canonical URL
                    $canonical_href = trim($canonical_nodes->item(0)->getAttribute('href'));
                    
                    if (!empty($canonical_href)) {
                        // Check for valid URL format
                        if (filter_var($canonical_href, FILTER_VALIDATE_URL)) {
                            $canonical_checks['valid_url_format'] = true;
                            
                            // Parse canonical URL
                            $canonical_parsed = parse_url($canonical_href);
                            $current_parsed = parse_url($final_accessible_url);
                            
                            // Check for absolute URL
                            if (isset($canonical_parsed['scheme']) && isset($canonical_parsed['host'])) {
                                $canonical_checks['absolute_url_used'] = true;
                                
                                // Check for HTTPS canonical
                                if ($canonical_parsed['scheme'] === 'https') {
                                    $canonical_checks['https_canonical'] = true;
                                }
                                
                                // Check for self-referencing canonical
                                $canonical_domain = strtolower($canonical_parsed['host']);
                                $current_domain = strtolower($current_parsed['host'] ?? '');
                                
                                // Remove www. for comparison
                                $canonical_domain_clean = preg_replace('/^www\./', '', $canonical_domain);
                                $current_domain_clean = preg_replace('/^www\./', '', $current_domain);
                                
                                if ($canonical_domain_clean === $current_domain_clean) {
                                    $canonical_checks['self_referencing'] = true;
                                }
                                
                                // Check for no query parameters in canonical URL
                                if (!isset($canonical_parsed['query']) || empty($canonical_parsed['query'])) {
                                    $canonical_checks['no_query_parameters'] = true;
                                }
                            }
                        } elseif (strpos($canonical_href, '/') === 0) {
                            // Relative URL starting with / - not recommended but acceptable
                            $canonical_checks['valid_url_format'] = true;
                            
                            // Check for no query parameters
                            if (strpos($canonical_href, '?') === false) {
                                $canonical_checks['no_query_parameters'] = true;
                            }
                        }
                    }
                    
                    // Check for proper placement in <head> section
                    $head_canonical_nodes = $xpath_canonical->query('//head//link[@rel="canonical"]');
                    if ($head_canonical_nodes->length > 0) {
                        $canonical_checks['proper_placement'] = true;
                    }
                }
                
                // Calculate score based on checks
                $passed_canonical_checks = array_sum($canonical_checks);
                if ($passed_canonical_checks >= 6) {
                    $canonical_score = 3;
                } elseif ($passed_canonical_checks >= 4) {
                    $canonical_score = 2;
                } else {
                    $canonical_score = 1;
                }
                
                // Build suggestion with check results
                $canonical_check_results = [];
                $canonical_check_results[] = ($canonical_checks['canonical_tag_present'] ? '✓' : '✗') . ' Canonical Tag Present';
                $canonical_check_results[] = ($canonical_checks['single_canonical_tag'] ? '✓' : '✗') . ' Single Canonical Tag (no duplicates)';
                $canonical_check_results[] = ($canonical_checks['valid_url_format'] ? '✓' : '✗') . ' Valid URL Format';
                $canonical_check_results[] = ($canonical_checks['absolute_url_used'] ? '✓' : '✗') . ' Absolute URL Used';
                $canonical_check_results[] = ($canonical_checks['self_referencing'] ? '✓' : '✗') . ' Self-Referencing (same domain)';
                $canonical_check_results[] = ($canonical_checks['proper_placement'] ? '✓' : '✗') . ' Proper Placement in &lt;head&gt;';
                $canonical_check_results[] = ($canonical_checks['no_query_parameters'] ? '✓' : '✗') . ' No Query Parameters';
                $canonical_check_results[] = ($canonical_checks['https_canonical'] ? '✓' : '✗') . ' HTTPS Canonical URL';
                
                $current_run_results['canonical']['score'] = $canonical_score;
                if ($canonical_count > 0) {
                    $current_run_results['canonical']['details'] = "Found {$canonical_count} canonical tag(s) ({$passed_canonical_checks}/8 checks passed)<br>URL: " . htmlspecialchars($canonical_href);
                } else {
                    $current_run_results['canonical']['details'] = "No canonical tag found ({$passed_canonical_checks}/8 checks passed)";
                }
                $current_run_results['canonical']['suggestion'] = 'Developed and paused';
                
            } else {
                $current_run_results['canonical']['score'] = 1;
                $current_run_results['canonical']['details'] = 'Could not analyze canonical tag - page content not available';
                $current_run_results['canonical']['suggestion'] = 'Developed and paused';
            }

            // Social Media Meta Tags Analysis (OpenGraph + Twitter)
            $og_nodes = $xpath->query('//meta[starts-with(@property, "og:")]');
            $og_count = $og_nodes->length;
            
            $twitter_nodes = $xpath->query('//meta[starts-with(@name, "twitter:")]');
            $twitter_count = $twitter_nodes->length;
            
            // Initialize Social Media Meta Tags checks
            $social_checks = [
                'og_tags_present' => false,
                'og_title_present' => false,
                'og_description_present' => false,
                'og_image_present' => false,
                'og_url_present' => false,
                'og_type_present' => false,
                'twitter_card_present' => false,
                'twitter_site_present' => false,
                'proper_placement' => false,
                'valid_image_url' => false
            ];
            
            // Check OpenGraph tags
            if ($og_count > 0) {
                $social_checks['og_tags_present'] = true;
                
                // Check for specific OpenGraph tags
                $og_title_nodes = $xpath->query('//meta[@property="og:title"]');
                if ($og_title_nodes->length > 0) {
                    $social_checks['og_title_present'] = true;
                }
                
                $og_description_nodes = $xpath->query('//meta[@property="og:description"]');
                if ($og_description_nodes->length > 0) {
                    $social_checks['og_description_present'] = true;
                }
                
                $og_image_nodes = $xpath->query('//meta[@property="og:image"]');
                if ($og_image_nodes->length > 0) {
                    $social_checks['og_image_present'] = true;
                    // Check if image URL is valid (basic check)
                    foreach ($og_image_nodes as $img_node) {
                        $img_content = $img_node->getAttribute('content');
                        if (filter_var($img_content, FILTER_VALIDATE_URL) || strpos($img_content, '/') === 0) {
                            $social_checks['valid_image_url'] = true;
                            break;
                        }
                    }
                }
                
                $og_url_nodes = $xpath->query('//meta[@property="og:url"]');
                if ($og_url_nodes->length > 0) {
                    $social_checks['og_url_present'] = true;
                }
                
                $og_type_nodes = $xpath->query('//meta[@property="og:type"]');
                if ($og_type_nodes->length > 0) {
                    $social_checks['og_type_present'] = true;
                }
            }
            
            // Check Twitter tags
            if ($twitter_count > 0) {
                // Check for specific Twitter tags
                $twitter_card_nodes = $xpath->query('//meta[@name="twitter:card"]');
                if ($twitter_card_nodes->length > 0) {
                    $social_checks['twitter_card_present'] = true;
                }
                
                $twitter_site_nodes = $xpath->query('//meta[@name="twitter:site"]');
                if ($twitter_site_nodes->length > 0) {
                    $social_checks['twitter_site_present'] = true;
                }
            }
            
            // Check for proper placement in <head> section (both OpenGraph and Twitter)
            $head_social_nodes = $xpath->query('//head//meta[starts-with(@property, "og:") or starts-with(@name, "twitter:")]');
            if ($head_social_nodes->length > 0) {
                $social_checks['proper_placement'] = true;
            }
            
            // Calculate score based on checks (now 10 total checks)
            $passed_social_checks = array_sum($social_checks);
            if ($passed_social_checks >= 8) {
                $social_score = 3;
            } elseif ($passed_social_checks >= 5) {
                $social_score = 2;
            } else {
                $social_score = 1;
            }
            
            // Build suggestion with check results
            $social_check_results = [];
            $social_check_results[] = ($social_checks['og_tags_present'] ? '✓' : '✗') . ' OpenGraph Tags Present';
            $social_check_results[] = ($social_checks['og_title_present'] ? '✓' : '✗') . ' og:title Tag Present';
            $social_check_results[] = ($social_checks['og_description_present'] ? '✓' : '✗') . ' og:description Tag Present';
            $social_check_results[] = ($social_checks['og_image_present'] ? '✓' : '✗') . ' og:image Tag Present';
            $social_check_results[] = ($social_checks['og_url_present'] ? '✓' : '✗') . ' og:url Tag Present';
            $social_check_results[] = ($social_checks['og_type_present'] ? '✓' : '✗') . ' og:type Tag Present';
            $social_check_results[] = ($social_checks['twitter_card_present'] ? '✓' : '✗') . ' twitter:card Tag Present';
            $social_check_results[] = ($social_checks['twitter_site_present'] ? '✓' : '✗') . ' twitter:site Tag Present';
            $social_check_results[] = ($social_checks['proper_placement'] ? '✓' : '✗') . ' Proper Placement in &lt;head&gt;';
            $social_check_results[] = ($social_checks['valid_image_url'] ? '✓' : '✗') . ' Valid Image URL Format';
            
            // Social Media Meta Tags Analysis (Developed and Paused)
            $current_run_results['social_meta_tags']['score'] = 0;
            $current_run_results['social_meta_tags']['details'] = '-';
            $current_run_results['social_meta_tags']['suggestion'] = 'Developed and paused';

            // ARIA Accessibility Analysis (Developed and Paused)
            $current_run_results['aria']['score'] = 0;
            $current_run_results['aria']['details'] = '-';
            $current_run_results['aria']['suggestion'] = 'Developed and paused';

            // --- Page Speed Analysis with Google PageSpeed Insights API ---
            $pagespeed_response = call_pagespeed_insights_api($final_accessible_url);
            
            // Store the raw request and response from PageSpeed API for debugging
            /* Hide for production deployment
            $_SESSION['pagespeed_response'] = [
                'success' => $pagespeed_response['success'],
                'message' => $pagespeed_response['message'],
                'raw_request' => $pagespeed_response['raw_request'] ?? null,
                'raw_response' => $pagespeed_response['raw_response'] ?? null,
            ];
            */
            
            if ($pagespeed_response['success'] && isset($pagespeed_response['data'])) {
                $lighthouse_data = $pagespeed_response['data']['lighthouseResult'];
                $categories = $lighthouse_data['categories'];
                
                // Get Performance Score (0-100)
                $performance_score = round($categories['performance']['score'] * 100);
                
                // Get SEO Score (0-100) 
                $seo_score = round($categories['seo']['score'] * 100);
                
                // Get Core Web Vitals from audits
                $audits = $lighthouse_data['audits'];
                $cwv_results = [];
                
                // Largest Contentful Paint (LCP)
                if (isset($audits['largest-contentful-paint'])) {
                    $lcp_value = round($audits['largest-contentful-paint']['numericValue'] / 1000, 2);
                    $lcp_rating = $lcp_value <= 2.5 ? 'Good' : ($lcp_value <= 4.0 ? 'Needs Improvement' : 'Poor');
                    $cwv_results[] = "LCP: {$lcp_value}s ({$lcp_rating})";
                }
                
                // First Input Delay (FID) / Interaction to Next Paint (INP)
                if (isset($audits['interaction-to-next-paint'])) {
                    $inp_value = round($audits['interaction-to-next-paint']['numericValue']);
                    $inp_rating = $inp_value <= 200 ? 'Good' : ($inp_value <= 500 ? 'Needs Improvement' : 'Poor');
                    $cwv_results[] = "INP: {$inp_value}ms ({$inp_rating})";
                }
                
                // Cumulative Layout Shift (CLS)
                if (isset($audits['cumulative-layout-shift'])) {
                    $cls_value = round($audits['cumulative-layout-shift']['numericValue'], 3);
                    $cls_rating = $cls_value <= 0.1 ? 'Good' : ($cls_value <= 0.25 ? 'Needs Improvement' : 'Poor');
                    $cwv_results[] = "CLS: {$cls_value} ({$cls_rating})";
                }
                
                // Calculate overall score based on Performance score only
                if ($performance_score >= 80) {
                    $speed_score = 3; // Excellent
                } elseif ($performance_score >= 60) {
                    $speed_score = 2; // Good  
                } else {
                    $speed_score = 1; // Poor
                }
                
                $current_run_results['page_speed']['score'] = $speed_score;
                $current_run_results['page_speed']['details'] = "Performance {$performance_score}/100";
                
                // Build suggestions from audit recommendations
                $suggestions = [];
                $suggestions[] = "<strong>Performance Score:</strong> {$performance_score}/100";
                
                if (!empty($cwv_results)) {
                    $suggestions[] = "<strong>Core Web Vitals:</strong><br>" . implode('<br>', $cwv_results);
                }
                
                // Get top performance optimization opportunities
                $opportunities = [];
                $key_audits = [
                    'first-contentful-paint' => 'First Contentful Paint',
                    'speed-index' => 'Speed Index', 
                    'total-blocking-time' => 'Total Blocking Time',
                    'unused-css-rules' => 'Remove Unused CSS',
                    'unused-javascript' => 'Remove Unused JavaScript',
                    'render-blocking-resources' => 'Eliminate Render-blocking Resources',
                    'unminified-css' => 'Minify CSS',
                    'unminified-javascript' => 'Minify JavaScript',
                    'uses-text-compression' => 'Enable Text Compression',
                    'uses-optimized-images' => 'Serve Images in Next-gen Formats'
                ];
                
                foreach ($key_audits as $audit_id => $audit_name) {
                    if (isset($audits[$audit_id])) {
                        $audit = $audits[$audit_id];
                        if (isset($audit['score']) && $audit['score'] < 0.9) {
                            if (isset($audit['displayValue'])) {
                                $opportunities[] = "{$audit_name}: {$audit['displayValue']}";
                            } else {
                                $opportunities[] = $audit_name;
                            }
                        }
                    }
                }
                
                if (!empty($opportunities)) {
                    $suggestions[] = "<strong>Optimization Opportunities:</strong><br>" . implode('<br>', array_slice($opportunities, 0, 5));
                }
                
                // Get SEO audit issues
                $seo_issues = [];
                $seo_audits = [
                    'document-title' => 'Document has a title',
                    'meta-description' => 'Document has a meta description', 
                    'is-crawlable' => 'Page is crawlable',
                    'robots-txt' => 'robots.txt is valid',
                    'hreflang' => 'Document has valid hreflang',
                    'canonical' => 'Document has a valid canonical link'
                ];
                
                foreach ($seo_audits as $audit_id => $audit_name) {
                    if (isset($audits[$audit_id]) && isset($audits[$audit_id]['score']) && $audits[$audit_id]['score'] < 1) {
                        $seo_issues[] = $audit_name;
                    }
                }
                
                if (!empty($seo_issues)) {
                    $suggestions[] = "<strong>SEO Issues to Fix:</strong><br>" . implode('<br>', array_slice($seo_issues, 0, 5));
                }
                
                $current_run_results['page_speed']['suggestion'] = implode('<br><br>', $suggestions);
                
            } else {
                // Fallback if PageSpeed API fails
                $current_run_results['page_speed']['score'] = 1;
                $current_run_results['page_speed']['details'] = "PageSpeed API Error: " . $pagespeed_response['message'];
                $current_run_results['page_speed']['suggestion'] = "Could not analyze page speed. " . $pagespeed_response['message'];
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
                'favicon', 'canonical', 'social_meta_tags', 'aria', 'page_speed'
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
            $current_run_results['robots']['score'] = 0;
            $current_run_results['robots']['details'] = $robots_check_result['details'];
            
            // Fetch robots.txt content for AI analysis if accessible
            if ($robots_check_result['score'] == 3) {
                $robots_content = @file_get_contents($robots_url);
                if ($robots_content !== false) {
                    $robots_content_for_ai = trim($robots_content);
                    $content_length = strlen($robots_content_for_ai);
                    $line_count = substr_count($robots_content_for_ai, "\n") + 1;
                    $current_run_results['robots']['details'] = "Found robots.txt ({$content_length} chars, {$line_count} lines)";
                    $current_run_results['robots']['suggestion'] = 'Developed and paused';
                } else {
                    $current_run_results['robots']['suggestion'] = 'Developed and paused';
                }
            } else {
                $current_run_results['robots']['suggestion'] = 'Developed and paused';
            }

            $sitemap_content_for_ai = null; // Variable to store sitemap.xml content for AI
            
            // Sitemap.xml
            $sitemap_url = $base_url_for_files . '/sitemap.xml';
            $sitemap_check_result = check_resource_accessibility_helper($sitemap_url);
            $current_run_results['sitemap']['score'] = 0;
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
                    $current_run_results['sitemap']['suggestion'] = 'Developed and paused';
                } else {
                    $current_run_results['sitemap']['suggestion'] = 'Developed and paused';
                }
            } else {
                $current_run_results['sitemap']['suggestion'] = 'Developed and paused';
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

        // --- AI Suggestion for URL (PAUSED) ---
        /*
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
                    $current_run_results['url']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['url']['suggestion'] = "AI response JSON format error. Raw: " . $ai_output;
                }
            } else {
                $current_run_results['url']['suggestion'] = "AI suggestion failed: " . ($url_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif (isset($current_run_results['url']['score']) && $current_run_results['url']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['url']['suggestion'] = "URL found, but could not generate AI suggestion at this time.";
        }
        */
        // --- End AI Suggestion for URL (PAUSED) ---

        // --- AI Suggestion for Links (PAUSED) ---
        /*
        if ($links_content_for_ai) {
            $prompt = "Analyze the following website links for SEO best practices. The data is in JSON format containing all links found on the page. Provide your response in JSON format with EXACTLY 2 ELEMENTS: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Links data: {$links_content_for_ai}";
            
            $links_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $links_ai_response_full['success'], // Reflect success of this specific call
                'message' => $links_ai_response_full['message'], // Message of this specific call
                'raw_request' => $links_ai_response_full['raw_request'] ?? null,
                'raw_response' => $links_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($links_ai_response_full['success'] && isset($links_ai_response_full['data'])) {
                $ai_output = trim($links_ai_response_full['data']);
                
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
                        $current_run_results['links']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    $current_run_results['links']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['links']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $current_run_results['links']['suggestion'] = 'AI suggestion failed: ' . ($links_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif (isset($current_run_results['links']['score']) && $current_run_results['links']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['links']['suggestion'] = 'Links found, but could not generate AI suggestion at this time.';
        }
        */
        // --- End AI Suggestion for Links (PAUSED) ---

        // --- AI Suggestion for 404 Page (COMMENTED OUT FOR STATIC CHECKS) ---
        /*
        if ($page_404_content_for_ai) {
            $prompt = "Analyze the following 404 error page for SEO and user experience best practices. Provide your response in JSON format with exactly 2 elements: \"score\" (integer from 1 to 3, where 1=bad, 2=needs improvement, 3=good) and \"suggestion\" (1-2 sentences with improvement advice). Consider helpfulness, navigation options, search functionality, and user guidance. 404 page content: {$page_404_content_for_ai}";
            
            $page_404_ai_response_full = call_openrouter_api($prompt); // Get the full response
            
            // Store the raw request and response from THIS call for display
            $_SESSION['openrouter_response'] = [
                'success' => $page_404_ai_response_full['success'], // Reflect success of this specific call
                'message' => $page_404_ai_response_full['message'], // Message of this specific call
                'raw_request' => $page_404_ai_response_full['raw_request'] ?? null,
                'raw_response' => $page_404_ai_response_full['raw_response'] ?? null,
                // 'data' could also be stored if needed elsewhere, but raw is primary for the info alert
            ];

            if ($page_404_ai_response_full['success'] && isset($page_404_ai_response_full['data'])) {
                $ai_output = trim($page_404_ai_response_full['data']);
                
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
                        $current_run_results['404_page']['score'] = $ai_score;
                        // If AI gives a low score, the original 'details' is still relevant.
                        // The suggestion from AI is the key part here.
                    } else {
                        // If score is invalid, keep the original score and append a note about the AI's invalid score.
                        $ai_suggestion = "AI returned an invalid score ({$ai_score}). " . $ai_suggestion;
                    }
                    $current_run_results['404_page']['suggestion'] = $ai_suggestion;

                } else {
                    // Fallback if JSON parsing fails, use the whole AI output as suggestion, keep original score
                    $current_run_results['404_page']['suggestion'] = 'AI response JSON format error. Raw: ' . $ai_output;
                }
            } else {
                $current_run_results['404_page']['suggestion'] = 'AI suggestion failed: ' . ($page_404_ai_response_full['message'] ?? 'Unknown error');
            }
        } elseif (isset($current_run_results['404_page']['score']) && $current_run_results['404_page']['suggestion'] === 'Awaiting AI suggestion...') {
            // Generic fallback if AI was expected but didn't run for other reasons.
            $current_run_results['404_page']['suggestion'] = '404 page found, but could not generate AI suggestion at this time.';
        }
        */
        // --- End AI Suggestion for 404 Page (COMMENTED OUT FOR STATIC CHECKS) ---

    } else { 
        foreach (array_keys($current_run_results) as $key) {
            $current_run_results[$key]['score'] = null;
            $current_run_results[$key]['details'] = 'Please enter a domain name.';
            $current_run_results[$key]['suggestion'] = '-'; // Add default suggestion for empty domain
        }
    }

    // Set "Ready" indicators (developed but paused) to score 0 for display
    foreach ($current_run_results as $key => &$result_item) {
        if (isset($result_item['suggestion']) && $result_item['suggestion'] === 'Developed and paused') {
            $result_item['score'] = 0;
        }
    }
    unset($result_item); // Break the reference

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