# Production Auth Test Script for TASK-35-25
# Tests the SPA auth fallback contract

$ErrorActionPreference = "Continue"
$tempDir = "C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\temp"
$cookiesFile = Join-Path $tempDir "cookies-task3525.txt"
$regJsonFile = Join-Path $tempDir "reg-body.json"
$loginJsonFile = Join-Path $tempDir "login-body.json"
$resultsFile = Join-Path $tempDir "task3525-prod-validation-$(Get-Date -Format 'yyyyMMddTHHmmZ').json"

# Ensure temp directory exists
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

Write-Host "=== TASK-35-25 Production Validation ===" -ForegroundColor Cyan
Write-Host "Testing SPA Auth Fallback + Local SEO API Routing`n"

# Test results object
$results = @{
    timestamp = (Get-Date -Format "o")
    tests = @()
}

# 1. Get CSRF Cookie
Write-Host "1. GET /sanctum/csrf-cookie" -ForegroundColor Yellow
$csrfResponse = curl.exe -s -k -c $cookiesFile -X GET "https://seoindicator.com/sanctum/csrf-cookie" -w "%{http_code}" -o NUL 2>&1
Write-Host "   Status: $csrfResponse"
$results.tests += @{ name = "csrf-cookie"; status = $csrfResponse; passed = ($csrfResponse -eq "204") }

# Parse XSRF-TOKEN from cookies file
$xsrfToken = $null
if (Test-Path $cookiesFile) {
    $cookies = Get-Content $cookiesFile
    $xsrfLine = $cookies | Where-Object { $_ -match "XSRF-TOKEN" }
    if ($xsrfLine) {
        $xsrfToken = ($xsrfLine -split "`t")[-1]
        Write-Host "   XSRF-TOKEN obtained: $($xsrfToken.Substring(0, [Math]::Min(20, $xsrfToken.Length)))..."
    }
}

# Generate unique email
$regEmail = "task3525.prod.$(Get-Date -Format 'yyyyMMdd.HHmm')@example.com"

# Write JSON body to file (avoids PowerShell quote issues)
$regBody = @{
    name = "Task3525 Tester"
    email = $regEmail
    password = "TestPass123!"
    password_confirmation = "TestPass123!"
} | ConvertTo-Json -Compress
Set-Content -Path $regJsonFile -Value $regBody -Encoding UTF8

$loginBody = @{
    email = $regEmail
    password = "TestPass123!"
} | ConvertTo-Json -Compress
Set-Content -Path $loginJsonFile -Value $loginBody -Encoding UTF8

Write-Host "   Registration email: $regEmail"

# 2. Test Registration with Token Return
Write-Host "`n2. POST /api/v1/auth/register (expect token in response)" -ForegroundColor Yellow
Write-Host "   JSON body: $regBody"
$regResponse = curl.exe -s -k -b $cookiesFile -X POST "https://seoindicator.com/api/v1/auth/register" `
    -H "Content-Type: application/json" `
    -H "Accept: application/json" `
    -H "X-XSRF-TOKEN: $xsrfToken" `
    -d "@$regJsonFile" 2>&1

Write-Host "   Response: $regResponse"
$regData = $regResponse | ConvertFrom-Json -ErrorAction SilentlyContinue
$hasToken = $false
$token = $null
if ($regData.data.token) {
    $hasToken = $true
    $token = $regData.data.token
    Write-Host "   Token received: $($token.Substring(0, [Math]::Min(20, $token.Length)))..." -ForegroundColor Green
} elseif ($regData.success -eq $false) {
    Write-Host "   Registration failed: $($regData.message)" -ForegroundColor Red
    if ($regData.errors) {
        Write-Host "   Errors: $($regData.errors | ConvertTo-Json -Compress)" -ForegroundColor Red
    }
} else {
    Write-Host "   No token in response!" -ForegroundColor Red
}
$results.tests += @{ name = "register-token"; passed = $hasToken; response = $regResponse }

# 3. Test Login with Token Return
Write-Host "`n3. POST /api/v1/auth/login (expect token in response)" -ForegroundColor Yellow
$loginResponse = curl.exe -s -k -b $cookiesFile -X POST "https://seoindicator.com/api/v1/auth/login" `
    -H "Content-Type: application/json" `
    -H "Accept: application/json" `
    -H "X-XSRF-TOKEN: $xsrfToken" `
    -d "@$loginJsonFile" 2>&1

Write-Host "   Response: $loginResponse"
$loginData = $loginResponse | ConvertFrom-Json -ErrorAction SilentlyContinue
$loginHasToken = $false
if ($loginData.data.token) {
    $loginHasToken = $true
    if (-not $token) { $token = $loginData.data.token }
    Write-Host "   Token received: $($loginData.data.token.Substring(0, [Math]::Min(20, $loginData.data.token.Length)))..." -ForegroundColor Green
} elseif ($loginData.success -eq $false) {
    Write-Host "   Login failed: $($loginData.message)" -ForegroundColor Red
    if ($loginData.errors) {
        Write-Host "   Errors: $($loginData.errors | ConvertTo-Json -Compress)" -ForegroundColor Red
    }
}
$results.tests += @{ name = "login-token"; passed = $loginHasToken; response = $loginResponse }

# 4. Test Authenticated /auth/user with Bearer Token
if ($token) {
    Write-Host "`n4. GET /api/v1/auth/user (with Bearer token)" -ForegroundColor Yellow
    $userResponse = curl.exe -s -k -X GET "https://seoindicator.com/api/v1/auth/user" `
        -H "Accept: application/json" `
        -H "Authorization: Bearer $token" 2>&1
    
    Write-Host "   Response: $userResponse"
    $userData = $userResponse | ConvertFrom-Json -ErrorAction SilentlyContinue
    $userAuthenticated = $false
    if ($userData.id -or $userData.data.id) {
        $userAuthenticated = $true
        Write-Host "   User authenticated: $($userData.email)" -ForegroundColor Green
    } elseif ($userData.message -eq "Unauthenticated.") {
        Write-Host "   Bearer token did NOT authenticate!" -ForegroundColor Red
    }
    $results.tests += @{ name = "auth-user-bearer"; passed = $userAuthenticated; response = $userResponse }
    
    # 5. Test /sites/{siteId}/locations with Bearer Token
    Write-Host "`n5. GET /api/v1/sites/1/locations (with Bearer token)" -ForegroundColor Yellow
    $locResponse = curl.exe -s -k -X GET "https://seoindicator.com/api/v1/sites/1/locations" `
        -H "Accept: application/json" `
        -H "Authorization: Bearer $token" 2>&1
    
    Write-Host "   Response: $locResponse"
    $locData = $locResponse | ConvertFrom-Json -ErrorAction SilentlyContinue
    $locAccessible = $false
    if ($locData.message -eq "Unauthenticated.") {
        Write-Host "   Unauthenticated - token not working" -ForegroundColor Red
    } elseif ($locData.message -match "unauthorized|forbidden" -or $locResponse -match "403") {
        Write-Host "   Access denied (expected - user doesn't own site 1)" -ForegroundColor Yellow
        $locAccessible = $true  # 403 is valid - means auth worked, just no permission
    } elseif ($locData.data -ne $null) {
        Write-Host "   Locations data received" -ForegroundColor Green
        $locAccessible = $true
    } else {
        Write-Host "   Unexpected response" -ForegroundColor Yellow
    }
    $results.tests += @{ name = "locations-bearer"; passed = $locAccessible; response = $locResponse }
} else {
    Write-Host "`n4-5. SKIPPED - No token available" -ForegroundColor Red
    $results.tests += @{ name = "auth-user-bearer"; passed = $false; skipped = $true }
    $results.tests += @{ name = "locations-bearer"; passed = $false; skipped = $true }
}

# 6. Test Production Frontend Routes
Write-Host "`n6. GET /login (frontend route)" -ForegroundColor Yellow
$loginPage = curl.exe -s -k -X GET "https://seoindicator.com/login/" -w "`nHTTP Status: %{http_code}" 2>&1
$loginPageStatus = ($loginPage -split "`n" | Select-Object -Last 1) -replace "HTTP Status: ", ""
Write-Host "   Status: $loginPageStatus"
$results.tests += @{ name = "login-page"; passed = ($loginPageStatus -eq "200") }

Write-Host "`n7. GET /sites/1/local (protected frontend route)" -ForegroundColor Yellow
$localPage = curl.exe -s -k -X GET "https://seoindicator.com/sites/1/local/" -w "`nHTTP Status: %{http_code}" 2>&1
$localPageStatus = ($localPage -split "`n" | Select-Object -Last 1) -replace "HTTP Status: ", ""
Write-Host "   Status: $localPageStatus (should be 200 - SSR renders, client-side handles auth)"
$results.tests += @{ name = "local-page"; passed = ($localPageStatus -eq "200") }

# Summary
Write-Host "`n=== Summary ===" -ForegroundColor Cyan
$passed = ($results.tests | Where-Object { $_.passed }).Count
$total = $results.tests.Count
Write-Host "Passed: $passed / $total"

$results.summary = @{
    passed = $passed
    total = $total
    success = ($passed -eq $total)
}

# Save results
$results | ConvertTo-Json -Depth 5 | Set-Content $resultsFile
Write-Host "`nResults saved to: $resultsFile" -ForegroundColor Green

# Return exit code
if ($results.summary.success) {
    exit 0
} else {
    exit 1
}
