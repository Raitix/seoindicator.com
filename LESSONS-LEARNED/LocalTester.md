# LocalTester.md — Project Lessons Learned

Knowledge base for LocalTester agent working on SEOIndicator project.

---

## Common Test Failures

### Laravel Model Table Name Mismatch (2026-02-28)
**Issue:** Tests failing with "no such table: report_brandings" error.
**Root Cause:** Migration creates `report_branding` (singular) but Laravel's default naming convention expects `report_brandings` (plural).
**Fix:** Add explicit `$table` property to the model:
```php
class ReportBranding extends Model
{
    protected $table = 'report_branding';
    // ...
}
```
**Prevention:** Always check migration table names against model expectations. Laravel defaults to pluralized snake_case of the class name.

### Symfony DomCrawler Not Available (2026-02-28)
**Issue:** Tests failing with "Class 'Symfony\Component\DomCrawler\Crawler' not found" even though package files exist in vendor.
**Root Cause:** Package was not properly registered in composer's autoloader (likely manually copied to vendor without running composer require).
**Fix:** Use native PHP `DOMDocument` with `Symfony\Component\CssSelector\CssSelectorConverter` for CSS-to-XPath conversion instead:
```php
use DOMDocument;
use DOMXPath;
use Symfony\Component\CssSelector\CssSelectorConverter;

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();

$converter = new CssSelectorConverter();
$xpath = new DOMXPath($dom);
$nodes = $xpath->query($converter->toXPath('div.container p'));
```
**Prevention:** Check if classes are actually autoloaded with `class_exists()` before using. If package is missing, consider native alternatives.

### Log Facade Not Available in Unit Tests (2026-02-28)
**Issue:** Unit tests failing with "A facade root has not been set" when using `Log::info()`.
**Root Cause:** Laravel facades require the application container to be bootstrapped, which unit tests don't do by default.
**Fix:** Check if the Log facade is available before using:
```php
if (app()->bound('log')) {
    Log::info('Message', ['context' => 'data']);
}
```
**Prevention:** For classes that need to work in both integration and unit tests, make logging conditional or inject a logger dependency.

### Route-to-Controller Test Mismatch (2026-03-14)
**Issue:** Tests in `CrawlControllerTest` failing with mock errors for `/jobs/{id}/content` endpoint.
**Root Cause:** The content endpoint is routed to `ContentRetrievalController@content`, not `CrawlController`. Tests were mocking the wrong controller's service.
**Detection:** Run `php artisan route:list --path=crawler` to verify which controller handles each endpoint.
**Fix:** Either move content tests to `ContentRetrievalControllerTest` or mock both controllers' services.
**Prevention:** Always verify route-to-controller mappings before writing controller tests. Check `route:list` output.

### HTTP Status Code Test Expectations (2026-03-14)
**Issue:** Tests expecting 200 OK for DELETE endpoint, but controller returns 202 Accepted.
**Root Cause:** Test expectations didn't match REST API conventions for async operations.
**Fix:** Update test expectations to match SPEC documentation. DELETE operations that trigger async processing should return 202 Accepted, not 200 OK.
**Prevention:** Use SPEC documentation as the source of truth for HTTP status codes. REST conventions: 202 for async operations, 200 for synchronous completion.

---

## Environment Issues

_(Document local environment quirks)_

---

## Credit Model Testing (TASK-26-2) - 2026-03-14

### SQLite Constraint Limitations
**Issue:** Tests for DECIMAL overflow, VARCHAR length exceeded, and type enum constraints are skipped in SQLite.
**Root Cause:** SQLite is typeless and does not enforce DECIMAL precision or VARCHAR length constraints like MySQL does.
**Skipped Tests:**
- `balance overflow` - SQLite accepts any numeric value
- `long reference type truncated` - SQLite doesn't enforce VARCHAR lengths
- `description exceeds max` - SQLite doesn't enforce VARCHAR lengths
- `amount overflow` - SQLite accepts any numeric value
**Fix:** These tests pass in production MySQL. Mark as skipped with clear reason in test doc-comment.
**Prevention:** For constraint-heavy models, consider running integration tests against MySQL in CI/CD pipeline.

### CreditRepositoryInterface Optional
**Issue:** Test for interface binding skipped because `CreditRepositoryInterface` is not defined.
**Root Cause:** The task did not require an interface - only the concrete implementation was needed.
**Fix:** Mark test as skipped. Interface can be added later if needed for DI swapping.
**Prevention:** Check acceptance criteria before writing interface tests. If interface is not required, skip the test.

---

Last updated: 2026-03-14
