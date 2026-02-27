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

Last updated: 2026-02-27
