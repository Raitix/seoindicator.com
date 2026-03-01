# ProdTester.md — Project Lessons Learned

Knowledge base for ProdTester agent working on SEOIndicator project.

---

## Production Issues

### 2026-02-27: TASK-6-9 Rankings API Testing
- Health endpoint reliable for checking production status
- Route constraints (`where('id', '[0-9]+')`) block SQL injection at route level
- PowerShell requires `curl.exe` (not `curl` alias) for production API testing
- SSL cert revocation issues on Windows: use `-k` flag with curl.exe
- Authentication errors (401) take precedence over validation (422) - correct behavior

### 2026-02-27: TASK-16-11 Rate Limiting
- LiteSpeed web server strips response body on 429 errors
- CDN/proxy may modify response bodies
- Always test locally vs production to identify environment vs code issues
- SSRF protection verified via blocked URL tests

---

## Smoke Test Patterns

### Rate Limiting Verification
1. Make rapid requests to trigger 429
2. Verify headers: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset
3. Verify body includes error code and CAPTCHA info
4. Test SSRF protection with localhost, private IPs, file://

### SSRF Protection Tests
- `http://localhost` → should return error
- `http://127.0.0.1` → should return error
- `http://192.168.x.x` → should return error
- `file:///etc/passwd` → should return error
- `http://169.254.169.254` (AWS metadata) → should return error

---

## 2026-03-01: TASK-25-22 Feature Tests for API Endpoints
- Feature tests are development artifacts - not deployed to production
- Run tests locally via `php artisan test --filter="ControllerName"`
- Production smoke tests verify: health check, API authentication (401), security
- All protected API endpoints should return 401 for unauthenticated requests
- SQL injection attempts may return 000 (connection reset by WAF) or 401
- XSS in query params should return 401 (auth required) + sanitized
- Path traversal attempts should return 404 (route not found)
- Tests can be validated via CI/CD pipeline when available

---

## 2026-03-01: TASK-25-5 Email Verification
- Signed URL verification endpoints return 403 for invalid signatures (correct behavior)
- Route constraints (`where('id', '[0-9]+')`) block SQL injection at route level
- Resend verification endpoint requires authentication (401 for unauthenticated)
- Rate limiting on resend: 1 per minute per user with X-RateLimit headers
- Timing-safe hash comparison prevents timing attacks
- All verification tests can be run via `php artisan test --filter=EmailVerificationControllerTest`

---

Last updated: 2026-03-01

---

## 2026-02-28: TASK-3-7 Comparison Dashboard
- Frontend page load verification: check HTTP status + HTML content for UI elements
- Loading skeleton visible in SSR HTML confirms component deployment
- XSS test: check if `<script>` tags in query params are rendered in HTML (should NOT be)
- Path traversal test: try `../../../` in API paths - should return 404 or normalized path
- All comparison API endpoints require authentication (rankings/comparison, competitors, gaps)
- Navigation link presence confirms feature integration

---

## 2026-02-28: TASK-4-19 Report Delivery
- ReportDelivery service is auto-resolved via Laravel DI (no explicit binding needed)
- Email validation uses PHP's FILTER_VALIDATE_EMAIL - secure and reliable
- File size threshold for attachments: 10MB (constant in service)
- Delivery status tracking: 'pending', 'sent', 'partial', 'failed'
- Unsubscribe token uses hash('sha256', report_id + site_id + app.key)
- All report-related endpoints require authentication (401 for unauthenticated)

---

## 2026-02-27: TASK-7-8 SERP Feature Change Tracker
- Health endpoint reliable for verifying production status
- SSH access may be unavailable - use API testing as fallback
- DevOps deployment notes valuable for verification
- Run tests locally when production data unavailable
- SERP feature filtering API (`?serpFeature=...`) confirms integration

---

## 2026-02-28: TASK-1-19 E2E Tests
- Production validation can rely on CI/CD test results when GitHub Actions passes
- Smoke tests verify: health check, frontend accessibility, API authentication, security
- Playwright tests in CI/CD are equivalent to local test execution
- 404 on some frontend routes is expected (protected routes, static export routing)
