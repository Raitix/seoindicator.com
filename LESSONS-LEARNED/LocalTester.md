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

---

## Environment Issues

_(Document local environment quirks)_

---

Last updated: 2026-02-28
