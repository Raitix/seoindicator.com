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

---

## Environment Issues

_(Document local environment quirks)_

---

Last updated: 2026-02-28
