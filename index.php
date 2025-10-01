<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEOIndicator.com - AI Powered SEO Analysis</title>
    <meta name="description" content="Free AI-powered SEO analysis tool. Get instant scores and suggestions for 17 SEO indicators including title, meta, mobile-friendly, and more.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.ico">
    
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-RWCSLW7EXF"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-RWCSLW7EXF');
    </script>
</head>

<body>
    <!-- Contact Email -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <a href="mailto:info@SEOIndicator.com" class="text-white text-decoration-none" 
           style="transition: color 0.3s ease;" title="Contact us"
           onmouseover="this.style.color='#0d6efd'" onmouseout="this.style.color='white'">
            info@SEOIndicator.com
        </a>
    </div>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12 text-center">
                <img src="Logo-SEOIndicator-com.png" alt="SEOIndicator.com Logo" style="max-width: 200px; margin-bottom: 20px;">
                <h1 class="mb-4 main-title">AI Powered SEO Analysis</h1>

                <!-- Error Messages -->
                <div id="error-messages"></div>

                <!-- Domain Input Form -->
                <form id="seo-form" class="mb-4 seo-input-form">
                    <div class="input-group input-group-lg mb-3">
                        <input type="text" class="form-control" id="domain-input" 
                               placeholder="Enter domain name (e.g., google.com)" 
                               aria-label="Domain name" required autocomplete="off">
                        <button class="btn btn-primary custom-submit-btn" type="submit">Analyze!</button>
                    </div>
                </form>

                <!-- Overall Score -->
                <div class="overall-score-container mt-4 mb-3">
                    <h2 id="overall-score">Overall score: ☆☆☆ (0/51)</h2>
                </div>
                
                <!-- Progress Bar -->
                <div class="progress-container mt-3 mb-4" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">Analysis Progress</small>
                        <small class="text-muted progress-text">0/17</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" aria-valuenow="0" 
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                
                <!-- Results Table -->
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
                        <tbody class="table-group-divider" id="results-tbody">
                            <!-- Results populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const INDICATORS = [
            {key: 'url', name: 'URL', guide: 'url'},
            {key: 'title', name: 'Title Tag', guide: 'title-tag'},
            {key: 'meta_description', name: 'Meta Description', guide: 'meta-description'},
            {key: 'robots', name: 'robots.txt', guide: 'robots-txt'},
            {key: 'sitemap', name: 'sitemap.xml', guide: 'sitemap-xml'},
            {key: 'h1', name: 'Headings', guide: 'headings'},
            {key: 'filenames', name: 'Filenames', guide: 'filenames'},
            {key: 'image_alt', name: 'Image Alt', guide: 'image-alt'},
            {key: 'links', name: 'Links', guide: 'links'},
            {key: '404_page', name: '404 Page', guide: '404-page'},
            {key: 'mobile_friendly', name: 'Mobile Friendly', guide: 'mobile-friendly'},
            {key: 'language', name: 'Language', guide: 'language'},
            {key: 'favicon', name: 'Favicon', guide: 'favicon'},
            {key: 'canonical', name: 'Canonical Tag', guide: 'canonical-tag'},
            {key: 'social_meta_tags', name: 'Social Media Meta Tags', guide: 'social-media-meta-tags'},
            {key: 'aria', name: 'ARIA', guide: 'aria'},
            {key: 'page_speed', name: 'Page Speed', guide: 'page-speed'}
        ];

        function renderStars(score) {
            if (score === null || score === undefined) return '☆☆☆';
            const s = Math.max(0, Math.min(3, Number(score)));
            return '★'.repeat(s) + '☆'.repeat(3 - s);
        }

        function getRowClass(score) {
            const s = Number(score);
            if (s >= 3) return 'table-success';
            if (s === 2) return 'table-warning';
            return 'table-danger';
        }

        function initTable() {
            const tbody = document.getElementById('results-tbody');
            tbody.innerHTML = '';
            INDICATORS.forEach((ind, idx) => {
                const row = document.createElement('tr');
                row.setAttribute('data-indicator', ind.key);
                row.innerHTML = `
                    <td>${idx + 1}</td>
                    <td><a href="/seo-guides/${ind.guide}" target="_blank" title="${ind.name} SEO Guide">${ind.name}</a></td>
                    <td class="score-cell">☆☆☆</td>
                    <td class="result-cell">-</td>
                    <td class="suggestion-cell">-</td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateRow(indicator, result) {
            const row = document.querySelector(`tr[data-indicator="${indicator}"]`);
            if (!row) return;
            
            row.className = result.score !== null ? getRowClass(result.score) : '';
            row.querySelector('.score-cell').textContent = renderStars(result.score);
            row.querySelector('.result-cell').textContent = result.details || '-';
            row.querySelector('.suggestion-cell').textContent = result.suggestion || '-';
        }

        function showError(message) {
            const errorDiv = document.getElementById('error-messages');
            errorDiv.innerHTML = `<div class="alert alert-danger" role="alert">${message}</div>`;
        }

        function startAnalysis(domain) {
            // Clear previous errors
            document.getElementById('error-messages').innerHTML = '';
            
            // Show progress
            const progressContainer = document.querySelector('.progress-container');
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.querySelector('.progress-text');
            progressContainer.style.display = 'block';
            
            // Initialize table
            initTable();
            
            // Start analysis
            const formData = new FormData();
            formData.append('domain', domain);
            
            fetch('api/start-analysis.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showError(data.message || 'Analysis failed');
                    progressContainer.style.display = 'none';
                    return;
                }
                
                const jobId = data.job_id;
                const analyzedDomain = data.final_url;
                
                // Update header with domain
                document.getElementById('overall-score').innerHTML = 
                    `<strong>${analyzedDomain}</strong> - Overall score: ☆☆☆ (0/51)`;
                
                // Poll for results
                const pollInterval = setInterval(() => {
                    fetch(`api/job-progress.php?job_id=${encodeURIComponent(jobId)}`)
                        .then(r => r.json())
                        .then(progress => {
                            if (!progress.success) {
                                clearInterval(pollInterval);
                                progressContainer.style.display = 'none';
                                return;
                            }
                            
                            // Update progress bar
                            const pct = Math.round((progress.progress.done / progress.progress.total) * 100);
                            progressBar.style.width = pct + '%';
                            progressBar.setAttribute('aria-valuenow', pct);
                            progressText.textContent = `${progress.progress.done}/${progress.progress.total}`;
                            
                            // Update rows with results
                            Object.entries(progress.results).forEach(([indicator, result]) => {
                                if (result.status === 'done') {
                                    updateRow(indicator, result);
                                }
                            });
                            
                            // Update overall score
                            if (progress.overall) {
                                const overallScore = Math.round(progress.overall.achieved / progress.overall.possible * 3);
                                const stars = renderStars(overallScore);
                                document.getElementById('overall-score').innerHTML = 
                                    `<strong>${analyzedDomain}</strong> - Overall score: ${stars} (${progress.overall.achieved}/${progress.overall.possible})`;
                            }
                            
                            // Stop when done
                            if (progress.progress.done === progress.progress.total) {
                                clearInterval(pollInterval);
                                setTimeout(() => {
                                    progressContainer.style.display = 'none';
                                }, 1000);
                            }
                        })
                        .catch(() => {
                            clearInterval(pollInterval);
                            progressContainer.style.display = 'none';
                        });
                }, 1000);
            })
            .catch(err => {
                showError('Network error. Please try again.');
                progressContainer.style.display = 'none';
            });
        }

        // Form submission
        document.getElementById('seo-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('domain-input').value.trim();
            if (domain) {
                startAnalysis(domain);
            }
        });

        // Initialize empty table
        initTable();
    </script>
</body>
</html>

