<?php
session_start(); // Start the session at the very top
define('SEO_LOGIC_DISABLE_POST', false);
include 'seo_logic.php'; // Include the separated PHP logic

$layout_class = 'initial-view'; // Default layout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $layout_class = 'results-view';
} elseif (isset($_SESSION['is_post_redirect_layout_active']) && $_SESSION['is_post_redirect_layout_active']) {
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
$indicators_results = $_SESSION['seo_results_data'] ?? array_fill_keys(array_keys($GLOBALS['default_indicator_keys_for_logic'] ?? ['url'=>'', 'title'=>'', 'meta_description'=>'', 'robots'=>'', 'sitemap'=>'', 'h1'=>'', 'filenames'=>'', 'image_alt'=>'', 'links'=>'', '404_page'=>'', 'mobile_friendly'=>'', 'language'=>'', 'favicon'=>'', 'canonical'=>'', 'social_meta_tags'=>'', 'aria'=>'', 'page_speed'=>'']), ['score' => null, 'details' => 'Pending...', 'suggestion' => 'Pending...']);

$overall_score_display_data = $_SESSION['overall_score_data'] ?? null;
$last_domain_value = $_SESSION['last_domain_for_view'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_domain_value = trim($_POST['domain'] ?? '');
}
$rate_limit_error = $_SESSION['rate_limit_error'] ?? null;
if ($rate_limit_error) {
    unset($_SESSION['rate_limit_error']); // Clear it after reading
}
$site_not_accessible_error = $_SESSION['site_not_accessible_error'] ?? null;
if ($site_not_accessible_error) {
    unset($_SESSION['site_not_accessible_error']); // Clear it after reading
}
/* Hide API debugging for production deployment
$openrouter_response_data = $_SESSION['openrouter_response'] ?? null;
if ($openrouter_response_data) {
    unset($_SESSION['openrouter_response']); // Clear after reading
}

$pagespeed_response_data = $_SESSION['pagespeed_response'] ?? null;
if ($pagespeed_response_data) {
    unset($_SESSION['pagespeed_response']); // Clear after reading
}
*/

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

                <?php /* Hide API debugging for production deployment
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
                */ ?>

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
                                    <td class="score-cell"><?php echo render_stars($result['score']); ?></td>
                                    <td class="result-cell"><?php echo ($key === 'title' || $key === 'meta_description' || $key === 'url' || $key === 'robots' || $key === 'sitemap' || $key === 'h1' || $key === 'filenames' || $key === 'image_alt' || $key === 'links' || $key === '404_page' || $key === 'mobile_friendly' || $key === 'language' || $key === 'favicon' || $key === 'canonical' || $key === 'social_meta_tags' || $key === 'aria' || $key === 'page_speed') ? $result['details'] : htmlspecialchars($result['details'] ?? '-'); ?></td>
                                    <td class="suggestion-cell"><?php 
                                        if (isset($result['suggestion'])) {
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
                // Disable legacy spinner behavior; let results table show immediately
                form.addEventListener('submit', function() {
                    if (loaderOverlay) loaderOverlay.style.display = 'none';
                });
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
                if (lastDomain) {
                    // Start job
                    const startForm = new FormData();
                    startForm.append('domain', lastDomain);

                    fetch('/api/start-analysis.php', { method: 'POST', body: startForm })
                        .then(r => r.json())
                        .then(startData => {
                            if (!startData || !startData.success) return;
                            const jobId = startData.job_id;
                            const indicators = startData.indicators || [];

                            const whitelistHtml = new Set(['url','404_page','mobile_friendly','language','favicon','canonical','social_meta_tags','aria','page_speed']);

                            function renderStars(score) {
                                if (score === null || score === undefined) return '(N/A)';
                                const s = Math.max(0, Math.min(3, Number(score)));
                                const filled = '★'.repeat(s);
                                const empty = '☆'.repeat(3 - s);
                                return filled + empty + ' (' + s + '/3)';
                            }

                            function rowClassForScore(score) {
                                const s = Number(score);
                                if (s >= 3) return 'table-success';
                                if (s === 2) return 'table-warning';
                                return 'table-danger';
                            }

                            function updateRow(indicator, result) {
                                const row = document.querySelector('tr[data-indicator="' + indicator + '"]');
                                if (!row) return;
                                // Update class
                                row.classList.remove('table-success','table-warning','table-danger');
                                if (result && typeof result.score !== 'undefined' && result.score !== null) {
                                    row.classList.add(rowClassForScore(result.score));
                                }
                                // Score cell
                                const scoreCell = row.querySelector('.score-cell');
                                if (scoreCell) scoreCell.textContent = renderStars(result.score);
                                // Result cell
                                const resultCell = row.querySelector('.result-cell');
                                if (resultCell) {
                                    if (whitelistHtml.has(indicator)) {
                                        resultCell.innerHTML = result.details || '-';
                                    } else {
                                        resultCell.textContent = result.details || '-';
                                    }
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
                                    fetch('/api/run-indicator.php', { method: 'POST', body: fd })
                                        .then(r => r.json())
                                        .then(d => {
                                            if (d && d.success && d.result) {
                                                updateRow(ind, d.result);
                                            }
                                        })
                                        .catch(() => {})
                                        .finally(() => { active--; runNext(); });
                                }
                            }
                            runNext();

                            // Poll overall progress and stars
                            const poll = setInterval(() => {
                                fetch('/api/job-progress.php?job_id=' + encodeURIComponent(jobId))
                                    .then(r => r.json())
                                    .then(p => {
                                        if (!p || !p.success) return;
                                        // Stop polling when done
                                        if (p.progress && p.progress.done === p.progress.total) {
                                            clearInterval(poll);
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