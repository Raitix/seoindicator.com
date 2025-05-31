<?php
session_start(); // Start the session at the very top
include 'seo_logic.php'; // Include the separated PHP logic

$layout_class = 'initial-view'; // Default layout
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
$indicators_results = $_SESSION['seo_results_data'] ?? array_fill_keys(array_keys($GLOBALS['default_indicator_keys_for_logic'] ?? ['title'=>'', 'meta_description'=>'', 'robots'=>'', 'sitemap'=>'', 'h1'=>'', 'image_alt'=>'', 'https'=>'', 'viewport'=>'', 'lang'=>'', 'favicon'=>'', 'canonical'=>'', 'opengraph'=>'', 'html_size'=>'', 'inline_css'=>'']), ['score' => null, 'details' => '-', 'suggestion' => '-']);

$overall_score_display_data = $_SESSION['overall_score_data'] ?? null;
$last_domain_value = $_SESSION['last_domain_for_view'] ?? '';
$rate_limit_error = $_SESSION['rate_limit_error'] ?? null;
if ($rate_limit_error) {
    unset($_SESSION['rate_limit_error']); // Clear it after reading
}
$site_not_accessible_error = $_SESSION['site_not_accessible_error'] ?? null;
if ($site_not_accessible_error) {
    unset($_SESSION['site_not_accessible_error']); // Clear it after reading
}
$openrouter_response_data = $_SESSION['openrouter_response'] ?? null;
if ($openrouter_response_data) {
    unset($_SESSION['openrouter_response']); // Clear after reading
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
    
    <div id="loader-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="container <?php if ($layout_class === 'initial-view') echo 'vh-100 d-flex flex-column justify-content-center'; ?>">
        <div class="row justify-content-center <?php if ($layout_class === 'initial-view') echo 'w-100'; ?>">
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
                        <?php /* Keep data field for title suggestion if needed, but primary display is raw now
                        <?php if (isset($openrouter_response_data['data'])): ?>
                            <br><strong>Parsed Data:</strong> <pre style="white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($openrouter_response_data['data']); ?></pre>
                        <?php endif; ?>
                        */ ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/" class="mb-4 seo-input-form" id="seo-form">
                    <div class="input-group input-group-lg mb-3">
                        <input type="text" class="form-control" name="domain" placeholder="Enter domain name (e.g., example.com)" aria-label="Domain name" required value="<?php echo htmlspecialchars($last_domain_value); ?>">
                        <button class="btn btn-primary custom-submit-btn" type="submit">Analyze!</button>
                    </div>
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
                
                <div class="table-responsive result-container">
                    <?php if ($layout_class === 'results-view'): ?>
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
                            <?php $row_nr = 1; ?>
                            <?php foreach ($indicators_results as $key => $result): ?>
                                <?php if ($key === 'accessibility') continue; ?>
                                <tr class="<?php echo get_score_row_class($result['score']); ?>">
                                    <td><?php echo $row_nr++; ?></td> 
                                    <td>
                                        <?php if ($key === 'title'): ?>
                                            <a href="/title-tag-seo-guide" target="_blank" title="Title Tag SEO Guide">
                                                <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                                            </a>
                                        <?php elseif ($key === 'meta_description'): ?>
                                            <a href="/meta-description-seo-guide" target="_blank" title="Meta Description SEO Guide">
                                                <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                                            </a>
                                                                <?php elseif ($key === 'robots'): ?>
                            <a href="/robots-txt-seo-guide" target="_blank" title="Robots.txt SEO Guide">
                                robots.txt
                            </a>
                        <?php elseif ($key === 'sitemap'): ?>
                            <a href="/sitemap-xml-seo-guide" target="_blank" title="Sitemap.xml SEO Guide">
                                sitemap.xml
                            </a>
                        <?php elseif ($key === 'h1'): ?>
                            <a href="/headings-seo-guide" target="_blank" title="Headings SEO Guide">
                                Headings
                            </a>
                        <?php else: ?>
                                            <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                                        <?php endif; ?>
                                        
                                                                <?php if (in_array($key, $active_indicators ?? ['h1'])): ?>
                            <span class="badge bg-success ms-1" title="Active indicator - contributes to overall score">ACTIVE</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-1" title="Inactive indicator - shown for development only">DEV</span>
                        <?php endif; ?>
                                    </td>
                                    <td><?php echo render_stars($result['score']); ?></td>
                                    <td><?php echo ($key === 'title' || $key === 'meta_description' || $key === 'robots' || $key === 'sitemap' || $key === 'h1') ? $result['details'] : htmlspecialchars($result['details'] ?? '-'); ?></td>
                                    <td class="suggestion-cell"><?php echo isset($result['suggestion']) ? htmlspecialchars($result['suggestion']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
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
                form.addEventListener('submit', function() {
                    if (loaderOverlay) {
                        loaderOverlay.classList.add('visible');
                    }
                });
            }

            // If it's the results view, trigger animations after a short delay for page render
            if (body.classList.contains('results-view')) {
                // Hide loader if it was somehow still visible (e.g., browser back button)
                if (loaderOverlay) {
                    loaderOverlay.classList.remove('visible');
                }
                // Add class to trigger animations
                setTimeout(function() {
                    body.classList.add('content-loaded');
                }, 100); // Small delay to ensure styles are applied
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html> 