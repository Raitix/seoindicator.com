$content = (Invoke-WebRequest -Uri 'https://seoindicator.com/seo-guides/internal-links/' -UseBasicParsing -TimeoutSec 30).Content

$tests = @(
    @{ Name = "Title"; Pattern = "Internal Linking Strategy" },
    @{ Name = "Section 1: What are Internal Links"; Pattern = "What are Internal Links" },
    @{ Name = "Section 2: Why Internal Links Matter"; Pattern = "Why Internal Links Matter" },
    @{ Name = "Section 3: Site Architecture"; Pattern = "Site Architecture" },
    @{ Name = "Section 4: Anchor Text"; Pattern = "Anchor Text" },
    @{ Name = "Section 5: Common Mistakes"; Pattern = "Common.*Mistakes" },
    @{ Name = "Breadcrumbs"; Pattern = "Breadcrumb" },
    @{ Name = "Hub-and-Spoke"; Pattern = "hub" },
    @{ Name = "Link Equity"; Pattern = "equity|authority" },
    @{ Name = "Orphan Pages"; Pattern = "orphan" },
    @{ Name = "Code Examples"; Pattern = "&lt;a href|<a href" },
    @{ Name = "Checklist"; Pattern = "descriptive|anchor text" }
)

foreach ($test in $tests) {
    if ($content -match $test.Pattern) {
        Write-Host "$($test.Name): OK"
    } else {
        Write-Host "$($test.Name): FAIL"
    }
}

Write-Host ""
Write-Host "Content length: $($content.Length) bytes"
