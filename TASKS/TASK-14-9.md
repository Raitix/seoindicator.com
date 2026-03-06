# TASK-14-9: Implement HTML Analysis Checkers

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Description
Implement the HTML analysis checkers that detect on-page SEO issues like missing title tags, meta descriptions, H1 tags, and viewport settings.

## Dependencies
- TASK-14-8: IssueDetector Service

## Technical Requirements

### Checkers to Implement

#### 1. TitleTagChecker
Location: `backend/app/Services/Audit/Checkers/TitleTagChecker.php`

```php
class TitleTagChecker implements CheckerInterface
{
    public function check(CrawlResult $result): ?IssueFinding
    {
        // Check: Title exists
        // Check: Title length (optimal: 50-60 chars)
        // Return issue or null
    }
}
```

**Detection Rules:**
- Missing `<title>` tag → Critical
- Empty `<title>` tag → Critical
- Title > 60 chars → Warning (truncated in SERPs)
- Title < 30 chars → Notice (too short)

#### 2. MetaDescriptionChecker
Location: `backend/app/Services/Audit/Checkers/MetaDescriptionChecker.php`

```php
class MetaDescriptionChecker implements CheckerInterface
{
    public function check(CrawlResult $result): ?IssueFinding
    {
        // Check: Meta description exists
        // Check: Meta description length (optimal: 150-160 chars)
        // Return issue or null
    }
}
```

**Detection Rules:**
- Missing `<meta name="description">` → Warning
- Empty content attribute → Warning
- Description > 160 chars → Notice (truncated)
- Description < 50 chars → Notice (too short)

#### 3. H1Checker
Location: `backend/app/Services/Audit/Checkers/H1Checker.php`

```php
class H1Checker implements CheckerInterface
{
    public function check(CrawlResult $result): ?IssueFinding
    {
        // Check: H1 exists
        // Check: Only one H1
        // Return issue or null
    }
}
```

**Detection Rules:**
- No `<h1>` tag → Warning
- Multiple `<h1>` tags → Notice
- Empty `<h1>` tag → Warning

#### 4. ViewportChecker
Location: `backend/app/Services/Audit/Checkers/ViewportChecker.php`

```php
class ViewportChecker implements CheckerInterface
{
    public function check(CrawlResult $result): ?IssueFinding
    {
        // Check: Viewport meta tag exists
        // Check: Proper viewport settings
        // Return issue or null
    }
}
```

**Detection Rules:**
- Missing viewport meta → Warning
- Viewport without width=device-width → Warning

### HTML Parsing
Use `symfony/dom-crawler` (already in Laravel) for parsing:

```php
use Symfony\Component\DomCrawler\Crawler;

$crawler = new Crawler($html);
$title = $crawler->filter('title')->first()->text();
$description = $crawler->filter('meta[name="description"]')->attr('content');
$h1Count = $crawler->filter('h1')->count();
```

### Base Checker Class
```php
abstract class BaseChecker implements CheckerInterface
{
    protected function extractTitle(Crawler $crawler): ?string;
    protected function extractMetaDescription(Crawler $crawler): ?string;
    protected function extractH1s(Crawler $crawler): array;
    protected function hasViewport(Crawler $crawler): bool;
    
    protected function createFinding(
        string $issueType,
        string $severity,
        string $description,
        string $recommendation
    ): IssueFinding;
}
```

## Acceptance Criteria
- [ ] TitleTagChecker implemented with all rules
- [ ] MetaDescriptionChecker implemented with all rules
- [ ] H1Checker implemented with all rules
- [ ] ViewportChecker implemented with all rules
- [ ] BaseChecker abstract class created
- [ ] All checkers registered with IssueDetector
- [ ] Unit tests for each checker (10+ tests per checker)
- [ ] Edge cases handled (malformed HTML, empty pages)

## Effort Estimate
5 hours

---

## Comments
_Agent communication log_
