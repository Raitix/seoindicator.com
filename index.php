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
$indicators_results = $_SESSION['seo_results_data'] ?? array_fill_keys(array_keys($GLOBALS['default_indicator_keys_for_logic'] ?? ['title'=>'', 'meta_description'=>'', 'robots'=>'', 'sitemap'=>'', 'h1'=>'', 'image_alt'=>'', 'https'=>'', 'viewport'=>'', 'lang'=>'', 'favicon'=>'', 'canonical'=>'', 'opengraph'=>'', 'html_size'=>'', 'inline_css'=>'']), ['score' => null, 'details' => '-']);

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

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEOIndicator.com - Analyze your SEO parameters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> 
    <style>
      body {
        transition: padding-top 0.3s ease-in-out;
      }
      body.initial-view {
        padding-top: 0;
      }
      body.results-view {
        padding-top: 5rem;
      }

      /* Loader Styles */
      #loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        display: flex; /* Use flexbox for centering */
        justify-content: center;
        align-items: center;
        z-index: 1050; /* Ensure it's above other content */
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out, visibility 0s linear 0.3s;
      }
      #loader-overlay.visible {
        opacity: 1;
        visibility: visible;
        transition-delay: 0s;
      }
      .spinner-border {
         width: 3rem;
         height: 3rem;
      }

      /* Results Container Animation */
      .overall-score-container, 
      .result-container {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease-out, transform 0.5s ease-out;
      }

      body.initial-view .overall-score-container,
      body.initial-view .result-container {
        display: none !important; 
      }

      /* When results view is active AND content is loaded/animated in */
      body.results-view.content-loaded .overall-score-container,
      body.results-view.content-loaded .result-container {
        opacity: 1;
        transform: translateY(0);
        display: block; /* Ensure it takes space for animation */
      }
      /* To initially hide them in results-view before JS adds content-loaded */
      body.results-view .overall-score-container,
      body.results-view .result-container {
        display: none; 
      }

      /* Existing styles */
      td, th { vertical-align: middle; }
      td:nth-child(3) { word-break: break-word; max-width: 400px; }
      .overall-score-container h2 { font-weight: 300; }
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
            <div class="col-lg-8 col-md-10 text-center"> 
                <img src="Logo-SEOIndicator-com.png" alt="SEOIndicator.com Logo" style="max-width: 200px; margin-bottom: 20px;"> 
                <h1 class="mb-4 main-title">Check your SEO indicators</h1>

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
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 5%;">Nr.</th> 
                                <th scope="col" style="width: 25%;">Indicator</th>
                                <th scope="col" style="width: 20%;">Score</th> 
                                <th scope="col">Details</th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider">
                            <?php $row_nr = 1; ?>
                            <?php foreach ($indicators_results as $key => $result): ?>
                                <?php if ($key === 'accessibility') continue; ?>
                                <tr class="<?php echo get_score_row_class($result['score']); ?>">
                                    <td><?php echo $row_nr++; ?></td> 
                                    <td><?php echo ucwords(str_replace('_', ' ', $key)); ?></td>
                                    <td><?php echo render_stars($result['score']); // Individual indicators still use 1-3 stars without text ?></td>
                                    <td><?php echo ($key === 'title' || $key === 'meta_description') ? $result['details'] : htmlspecialchars($result['details']); ?></td>
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