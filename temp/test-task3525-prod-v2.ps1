# Production Auth Test Script for TASK-35-25
# Using PowerShell Invoke-RestMethod for reliable JSON handling

$ErrorActionPreference = "Continue"
$tempDir = "C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\temp"
$resultsFile = Join-Path $tempDir "task3525-prod-validation-$(Get-Date -Format 'yyyyMMddTHHmmZ').json"

Write-Host "=== TASK-35-25 Production Validation ===" -ForegroundColor Cyan
Write-Host "Testing SPA Auth Fallback + Local SEO API Routing`n"

$results = @{
    timestamp = (Get-Date -Format "o")
    tests = @()
}

try {
    # 1. Get CSRF Cookie using session
    Write-Host "1. GET /sanctum/csrf-cookie" -ForegroundColor Yellow
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $csrfUri = "https://seoindicator.com/sanctum/csrf-cookie"
    $csrfResponse = Invoke-WebRequest -Uri $csrfUri -Method GET -SessionVariable session -SkipCertificateCheck -UseBasicParsing
    Write-Host "   Status: $($csrfResponse.StatusCode)"
    $results.tests += @{ name = "csrf-cookie"; status = $csrfResponse.StatusCode; passed = ($csrfResponse.StatusCode -eq 204) }
    
    # Extract XSRF-TOKEN from cookies
    $xsrfToken = $null
    $xsrfCookie = $session.Cookies.GetCookies($csrfUri) | Where-Object { $_.Name -eq "XSRF-TOKEN" }
    if ($xsrfCookie) {
        $xsrfToken = $xsrfCookie.Value
        Write-Host "   XSRF-TOKEN obtained"
    }
    
    # Generate unique email
    $regEmail = "task3525.prod.$(Get-Date -Format 'yyyyMMdd.HHmm')@example.com"
    Write-Host "   Registration email: $regEmail"
    
    # 2. Test Registration with Token Return
    Write-Host "`n2. POST /api/v1/auth/register (expect token in response)" -ForegroundColor Yellow
    $regBody = @{
        name = "Task3525 Tester"
        email = $regEmail
        password = "TestPass123!"
        password_confirmation = "TestPass123!"
    }
    
    $headers = @{
        "Accept" = "application/json"
        "X-XSRF-TOKEN" = $xsrfToken
    }
    
    try {
        $regResponse = Invoke-RestMethod -Uri "https://seoindicator.com/api/v1/auth/register" `
            -Method POST `
            -Body $regBody `
            -Headers $headers `
            -WebSession $session `
            -SkipCertificateCheck
            
        Write-Host "   Response: $($regResponse | ConvertTo-Json -Compress)"
        
        $hasToken = $false
        $token = $null
        if ($regResponse.data.token) {
            $hasToken = $true
            $token = $regResponse.data.token
            Write-Host "   Token received!" -ForegroundColor Green
        } elseif ($regResponse.success -eq $false) {
            Write-Host "   Registration failed: $($regResponse.message)" -ForegroundColor Red
        }
        $results.tests += @{ name = "register-token"; passed = $hasToken; hasToken = $hasToken }
    } catch {
        $errorResponse = $_.ErrorDetails.Message
        Write-Host "   Error: $errorResponse" -ForegroundColor Red
        $results.tests += @{ name = "register-token"; passed = $false; error = $errorResponse }
    }
    
    # 3. Test Login with Token Return
    Write-Host "`n3. POST /api/v1/auth/login (expect token in response)" -ForegroundColor Yellow
    $loginBody = @{
        email = $regEmail
        password = "TestPass123!"
    }
    
    try {
        $loginResponse = Invoke-RestMethod -Uri "https://seoindicator.com/api/v1/auth/login" `
            -Method POST `
            -Body $loginBody `
            -Headers $headers `
            -WebSession $session `
            -SkipCertificateCheck
            
        Write-Host "   Response: $($loginResponse | ConvertTo-Json -Compress)"
        
        $loginHasToken = $false
        if ($loginResponse.data.token) {
            $loginHasToken = $true
            if (-not $token) { $token = $loginResponse.data.token }
            Write-Host "   Token received!" -ForegroundColor Green
        } elseif ($loginResponse.success -eq $false) {
            Write-Host "   Login failed: $($loginResponse.message)" -ForegroundColor Red
        }
        $results.tests += @{ name = "login-token"; passed = $loginHasToken; hasToken = $loginHasToken }
    } catch {
        $errorResponse = $_.ErrorDetails.Message
        Write-Host "   Error: $errorResponse" -ForegroundColor Red
        $results.tests += @{ name = "login-token"; passed = $false; error = $errorResponse }
    }
    
    # 4. Test Authenticated /auth/user with Bearer Token
    if ($token) {
        Write-Host "`n4. GET /api/v1/auth/user (with Bearer token)" -ForegroundColor Yellow
        $bearerHeaders = @{
            "Accept" = "application/json"
            "Authorization" = "Bearer $token"
        }
        
        try {
            $userResponse = Invoke-RestMethod -Uri "https://seoindicator.com/api/v1/auth/user" `
                -Method GET `
                -Headers $bearerHeaders `
                -SkipCertificateCheck
                
            Write-Host "   Response: $($userResponse | ConvertTo-Json -Compress)"
            
            $userAuthenticated = $false
            if ($userResponse.id -or $userResponse.data.id) {
                $userAuthenticated = $true
                Write-Host "   User authenticated!" -ForegroundColor Green
            }
            $results.tests += @{ name = "auth-user-bearer"; passed = $userAuthenticated }
        } catch {
            $errorResponse = $_.ErrorDetails.Message
            Write-Host "   Error: $errorResponse" -ForegroundColor Red
            $results.tests += @{ name = "auth-user-bearer"; passed = $false; error = $errorResponse }
        }
        
        # 5. Test /sites/{siteId}/locations with Bearer Token
        Write-Host "`n5. GET /api/v1/sites/1/locations (with Bearer token)" -ForegroundColor Yellow
        try {
            $locResponse = Invoke-RestMethod -Uri "https://seoindicator.com/api/v1/sites/1/locations" `
                -Method GET `
                -Headers $bearerHeaders `
                -SkipCertificateCheck
                
            Write-Host "   Response: $($locResponse | ConvertTo-Json -Compress)"
            Write-Host "   Locations endpoint accessible!" -ForegroundColor Green
            $results.tests += @{ name = "locations-bearer"; passed = $true }
        } catch {
            $errorResponse = $_.ErrorDetails.Message
            Write-Host "   Error: $errorResponse"
            # Check if it's a 403 (authorized but no permission) vs 401 (not authenticated)
            if ($errorResponse -match "403" -or $errorResponse -match "unauthorized|forbidden") {
                Write-Host "   Access denied (expected - user doesn't own site 1)" -ForegroundColor Yellow
                $results.tests += @{ name = "locations-bearer"; passed = $true }
            } else {
                Write-Host "   Unexpected error" -ForegroundColor Red
                $results.tests += @{ name = "locations-bearer"; passed = $false; error = $errorResponse }
            }
        }
    } else {
        Write-Host "`n4-5. SKIPPED - No token available" -ForegroundColor Red
        $results.tests += @{ name = "auth-user-bearer"; passed = $false; skipped = $true }
        $results.tests += @{ name = "locations-bearer"; passed = $false; skipped = $true }
    }
    
    # 6. Test Production Frontend Routes
    Write-Host "`n6. GET /login (frontend route)" -ForegroundColor Yellow
    try {
        $loginPage = Invoke-WebRequest -Uri "https://seoindicator.com/login/" -Method GET -SkipCertificateCheck -UseBasicParsing
        Write-Host "   Status: $($loginPage.StatusCode)"
        $results.tests += @{ name = "login-page"; passed = ($loginPage.StatusCode -eq 200) }
    } catch {
        Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
        $results.tests += @{ name = "login-page"; passed = $false }
    }
    
    Write-Host "`n7. GET /sites/1/local (protected frontend route)" -ForegroundColor Yellow
    try {
        $localPage = Invoke-WebRequest -Uri "https://seoindicator.com/sites/1/local/" -Method GET -SkipCertificateCheck -UseBasicParsing
        Write-Host "   Status: $($localPage.StatusCode) (should be 200 - SSR renders, client-side handles auth)"
        $results.tests += @{ name = "local-page"; passed = ($localPage.StatusCode -eq 200) }
    } catch {
        Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
        $results.tests += @{ name = "local-page"; passed = $false }
    }
    
} catch {
    Write-Host "Critical error: $($_.Exception.Message)" -ForegroundColor Red
}

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

if ($results.summary.success) {
    exit 0
} else {
    exit 1
}
