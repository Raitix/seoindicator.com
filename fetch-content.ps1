$content = (Invoke-WebRequest -Uri 'https://seoindicator.com/seo-guides/internal-links/' -UseBasicParsing -TimeoutSec 30).Content
$content | Out-File -FilePath "C:\Users\Raitis\.openclaw\logs\prod-content-check.html" -Encoding utf8
Write-Host "Content saved to prod-content-check.html"
