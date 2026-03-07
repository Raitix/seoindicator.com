# TASK-14-9: Implement HTML Analysis Checkers

## STATUS
`To Develop`

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

### [2026-03-07 09:45] Agent: ProjectManager
**Prioritization Analysis:**

| Metric | Value | Notes |
|--------|-------|-------|
| Priority | High | HTML checkers detect CRITICAL SEO issues (missing title/meta/H1) |
| Risk | Low | Pure PHP with symfony/dom-crawler, no external APIs |
| Dependency | TASK-14-8 (IssueDetector) | `To Write Tests` - progressing |

**TDD Pattern Applied:**
- PreTester CAN design tests with mocked `CheckerInterface` before TASK-14-8 completes
- Tests use mocked `CrawlResult` with sample HTML containing various issue scenarios
- Similar pattern to TASK-14-10 (Link Checker - Done)

**Detection Rules Summary:**
| Checker | Critical Issues | Warning Issues | Notice Issues |
|---------|----------------|----------------|---------------|
| TitleTagChecker | Missing/empty title | Title > 60 chars | Title < 30 chars |
| MetaDescriptionChecker | - | Missing/empty description | Description length issues |
| H1Checker | - | No H1, empty H1 | Multiple H1s |
| ViewportChecker | - | Missing viewport, wrong settings | - |

**Recommendation for PreTester:**
- Use TDD approach with sample HTML fixtures
- Test edge cases: malformed HTML, empty pages, special characters
- Each checker: 10+ tests covering all detection rules
- Use symfony/dom-crawler for HTML parsing (Laravel built-in)

**Status changed:** `To Prioritize` → `To Write Tests`

### [2026-03-07 10:25] Agent: PreTester
**Test Design Complete:**

| Test File | Tests | Coverage |
|-----------|-------|----------|
| `TitleTagCheckerTest.php` | 45 | Missing/empty title, length rules, special chars, edge cases |
| `MetaDescriptionCheckerTest.php` | 42 | Missing/empty description, length rules, case insensitivity |
| `H1CheckerTest.php` | 35 | Missing/empty H1, multiple H1s, nested elements |
| `ViewportCheckerTest.php` | 32 | Missing viewport, invalid settings, case insensitivity |
| `BaseCheckerTest.php` | 25 | Helper methods, extraction logic, finding creation |
| **Total** | **179** | All detection rules covered |

**TDD Pattern:**
- Tests use Mockery for CrawlResult with HTML property
- IssueFinding DTO assertions for severity, type, recommendation
- symfony/dom-crawler used for HTML parsing
- Unicode/emoji/special character tests included

**Status changed:** `To Write Tests` → `To Develop`
