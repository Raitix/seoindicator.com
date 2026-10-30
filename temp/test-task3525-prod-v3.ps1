# Production Auth Test Script for TASK-35-25
# Compatible with PowerShell 5.1

$ErrorActionPreference = "Continue"
$tempDir = "C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\temp"
$resultsFile = Join-Path $tempDir "task3525-prod-validation-$(Get-Date -Format 'yyyyMMddTHHmmZ').json"

# Ignore SSL certificate errors for PowerShell 5.1
Add-Type @"
using System.Net;
using System.Security.Cryptography.X509Certificates;
public class TrustAllCertsPolicy : ICertificatePolicy {
    public bool CheckValidationResult(ServicePoint srvPoint, X509Certificate certificate, WebRequest request, int certificateProblem) {
        return true;
    }
}
"@
[System.Net.ServicePointManager]::CertificatePolicy = New-Object TrustAllCertsPolicy
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

Write-Host "=== TASK-35-25 Production Validation ===" -ForegroundColor Cyan
Write-Host "Testing SPA Auth Fallback + Local SEO API Routing`n"

$results = @{
    timestamp = (Get-Date -Format "o")
    tests = @()
}

try {
    # 1. Get CSRF Cookie using cookie container
    Write-Host "1. GET /sanctum/csrf-cookie" -ForegroundColor Yellow
    $cookieContainer = New-Object System.Net.CookieContainer
    $csrfUri = [System.Uri]"https://seoindicator.com/sanctum/csrf-cookie"
    
    $csrfRequest = [System.Net.WebRequest]::Create($csrfUri)
    $csrfRequest.Method = "GET"
    $csrfRequest.CookieContainer = $cookieContainer
    $csrfResponse = $csrfRequest.GetResponse()
    Write-Host "   Status: $([int]$csrfResponse.StatusCode)"
    $results.tests += @{ name = "csrf-cookie"; status = [int]$csrfResponse.StatusCode; passed = ([int]$csrfResponse.StatusCode -eq 204) }
    $csrfResponse.Close()
    
    # Extract XSRF-TOKEN from cookies
    $xsrfToken = $null
    $cookies = $cookieContainer.GetCookies($csrfUri)
    $xsrfCookie = $cookies | Where-Object { $_.Name -eq "XSRF-TOKEN" }
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
    } | ConvertTo-Json -Compress
    
    $regUri = [System.Uri]"https://seoindicator.com/api/v1/auth/register"
    $regRequest = [System.Net.WebRequest]::Create($regUri)
    $regRequest.Method = "POST"
    $regRequest.ContentType = "application/json"
    $regRequest.Accept = "application/json"
    $regRequest.CookieContainer = $cookieContainer
    $regRequest.Headers.Add("X-XSRF-TOKEN", $xsrfToken)
    
    $regBytes = [System.Text.Encoding]::UTF8.GetBytes($regBody)
    $regRequest.ContentLength = $regBytes.Length
    $regStream = $regRequest.GetRequestStream()
    $regStream.Write($regBytes, 0, $regBytes.Length)
    $regStream.Close()
    
    try {
        $regResponse = $regRequest.GetResponse()
        $regReader = New-Object System.IO.StreamReader($regResponse.GetResponseStream())
        $regContent = $regReader.ReadToEnd()
        $regReader.Close()
        $regResponse.Close()
        
        Write-Host "   Response: $regContent"
        
        $regData = $regContent | ConvertFrom-Json
        $hasToken = $false
        $token = $null
        if ($regData.data.token) {
            $hasToken = $true
            $token = $regData.data.token
            Write-Host "   Token received!" -ForegroundColor Green
        } elseif ($regData.success -eq $false) {
            Write-Host "   Registration failed: $($regData.message)" -ForegroundColor Red
        }
        $results.tests += @{ name = "register-token"; passed = $hasToken; hasToken = $hasToken }
    } catch [System.Net.WebException] {
        $errorResponse = $_.Exception.Response
        $errorReader = New-Object System.IO.StreamReader($errorResponse.GetResponseStream())
        $errorContent = $errorReader.ReadToEnd()
        $errorReader.Close()
        Write-Host "   Error: $errorContent" -ForegroundColor Red
        $results.tests += @{ name = "register-token"; passed = $false; error = $errorContent }
    }
    
    # 3. Test Login with Token Return
    Write-Host "`n3. POST /api/v1/auth/login (expect token in response)" -ForegroundColor Yellow
    $loginBody = @{
        email = $regEmail
        password = "TestPass123!"
    } | ConvertTo-Json -Compress
    
    $loginUri = [System.Uri]"https://seoindicator.com/api/v1/auth/login"
    $loginRequest = [System.Net.WebRequest]::Create($loginUri)
    $loginRequest.Method = "POST"
    $loginRequest.ContentType = "application/json"
    $loginRequest.Accept = "application/json"
    $loginRequest.CookieContainer = $cookieContainer
    $loginRequest.Headers.Add("X-XSRF-TOKEN", $xsrfToken)
    
    $loginBytes = [System.Text.Encoding]::UTF8.GetBytes($loginBody)
    $loginRequest.ContentLength = $loginBytes.Length
    $loginStream = $loginRequest.GetRequestStream()
    $loginStream.Write($loginBytes, 0, $loginBytes.Length)
    $loginStream.Close()
    
    try {
        $loginResponse = $loginRequest.GetResponse()
        $loginReader = New-Object System.IO.StreamReader($loginResponse.GetResponseStream())
        $loginContent = $loginReader.ReadToEnd()
        $loginReader.Close()
        $loginResponse.Close()
        
        Write-Host "   Response: $loginContent"
        
        $loginData = $loginContent | ConvertFrom-Json
        $loginHasToken = $false
        if ($loginData.data.token) {
            $loginHasToken = $true
            if (-not $token) { $token = $loginData.data.token }
            Write-Host "   Token received!" -ForegroundColor Green
        } elseif ($loginData.success -eq $false) {
            Write-Host "   Login failed: $($loginData.message)" -ForegroundColor Red
        }
        $results.tests += @{ name = "login-token"; passed = $loginHasToken; hasToken = $loginHasToken }
    } catch [System.Net.WebException] {
        $errorResponse = $_.Exception.Response
        $errorReader = New-Object System.IO.StreamReader($errorResponse.GetResponseStream())
        $errorContent = $errorReader.ReadToEnd()
        $errorReader.Close()
        Write-Host "   Error: $errorContent" -ForegroundColor Red
        $results.tests += @{ name = "login-token"; passed = $false; error = $errorContent }
    }
    
    # 4. Test Authenticated /auth/user with Bearer Token
    if ($token) {
        Write-Host "`n4. GET /api/v1/auth/user (with Bearer token)" -ForegroundColor Yellow
        
        $userUri = [System.Uri]"https://seoindicator.com/api/v1/auth/user"
        $userRequest = [System.Net.WebRequest]::Create($userUri)
        $userRequest.Method = "GET"
        $userRequest.Accept = "application/json"
        $userRequest.Headers.Add("Authorization", "Bearer $token")
        
        try {
            $userResponse = $userRequest.GetResponse()
            $userReader = New-Object System.IO.StreamReader($userResponse.GetResponseStream())
            $userContent = $userReader.ReadToEnd()
            $userReader.Close()
            $userResponse.Close()
            
            Write-Host "   Response: $userContent"
            
            $userData = $userContent | ConvertFrom-Json
            $userAuthenticated = $false
            if ($userData.id -or $userData.data.id) {
                $userAuthenticated = $true
                Write-Host "   User authenticated!" -ForegroundColor Green
            }
            $results.tests += @{ name = "auth-user-bearer"; passed = $userAuthenticated }
        } catch [System.Net.WebException] {
            $errorResponse = $_.Exception.Response
            $errorReader = New-Object System.IO.StreamReader($errorResponse.GetResponseStream())
            $errorContent = $errorReader.ReadToEnd()
            $errorReader.Close()
            Write-Host "   Error: $errorContent" -ForegroundColor Red
            $results.tests += @{ name = "auth-user-bearer"; passed = $false; error = $errorContent }
        }
        
        # 5. Test /sites/{siteId}/locations with Bearer Token
        Write-Host "`n5. GET /api/v1/sites/1/locations (with Bearer token)" -ForegroundColor Yellow
        
        $locUri = [System.Uri]"https://seoindicator.com/api/v1/sites/1/locations"
        $locRequest = [System.Net.WebRequest]::Create($locUri)
        $locRequest.Method = "GET"
        $locRequest.Accept = "application/json"
        $locRequest.Headers.Add("Authorization", "Bearer $token")
        
        try {
            $locResponse = $locRequest.GetResponse()
            $locReader = New-Object System.IO.StreamReader($locResponse.GetResponseStream())
            $locContent = $locReader.ReadToEnd()
            $locReader.Close()
            $locResponse.Close()
            
            Write-Host "   Response: $locContent"
            Write-Host "   Locations endpoint accessible!" -ForegroundColor Green
            $results.tests += @{ name = "locations-bearer"; passed = $true }
        } catch [System.Net.WebException] {
            $errorResponse = $_.Exception.Response
            $errorReader = New-Object System.IO.StreamReader($errorResponse.GetResponseStream())
            $errorContent = $errorReader.ReadToEnd()
            $errorReader.Close()
            Write-Host "   Error: $errorContent"
            # Check if it's a 403 (authorized but no permission) vs 401 (not authenticated)
            if ([int]$errorResponse.StatusCode -eq 403 -or $errorContent -match "unauthorized|forbidden") {
                Write-Host "   Access denied (expected - user doesn't own site 1)" -ForegroundColor Yellow
                $results.tests += @{ name = "locations-bearer"; passed = $true }
            } else {
                Write-Host "   Unexpected error" -ForegroundColor Red
                $results.tests += @{ name = "locations-bearer"; passed = $false; error = $errorContent }
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
        $loginPageRequest = [System.Net.WebRequest]::Create("https://seoindicator.com/login/")
        $loginPageRequest.Method = "GET"
        $loginPageResponse = $loginPageRequest.GetResponse()
        Write-Host "   Status: $([int]$loginPageResponse.StatusCode)"
        $results.tests += @{ name = "login-page"; passed = ([int]$loginPageResponse.StatusCode -eq 200) }
        $loginPageResponse.Close()
    } catch {
        Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
        $results.tests += @{ name = "login-page"; passed = $false }
    }
    
    Write-Host "`n7. GET /sites/1/local (protected frontend route)" -ForegroundColor Yellow
    try {
        $localPageRequest = [System.Net.WebRequest]::Create("https://seoindicator.com/sites/1/local/")
        $localPageRequest.Method = "GET"
        $localPageResponse = $localPageRequest.GetResponse()
        Write-Host "   Status: $([int]$localPageResponse.StatusCode) (should be 200 - SSR renders, client-side handles auth)"
        $results.tests += @{ name = "local-page"; passed = ([int]$localPageResponse.StatusCode -eq 200) }
        $localPageResponse.Close()
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
