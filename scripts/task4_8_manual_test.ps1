# PowerShell Manual Test Script for TASK-4-8 (Report Generation Endpoint)
# API: POST /api/v1/sites/:siteId/reports/generate

# Test Case 1: Valid Input (MVP Synchronous Generation)
$siteId = "testSite123"
$apiUrl = "http://localhost:8080/api/v1/sites/$siteId/reports/generate"

$validBody = @{
    name = "Test Report"
    type = "summary"
    format = "pdf"
    dateRange = "2026-01-01 to 2026-01-31"
    sections = @("overview", "details")
} | ConvertTo-Json -Depth 10

$response1 = Invoke-RestMethod -Method Post -Uri $apiUrl -Body $validBody -ContentType "application/json" -ErrorAction SilentlyContinue
$response1Code = $response1.StatusCode
Write-Output "Test Case 1 - HTTP Status Code: $response1Code"

# Test Case 2: Invalid Input (Missing Required Fields)
$invalidBody = @{
    type = "summary"
} | ConvertTo-Json -Depth 10

$response2 = Invoke-RestMethod -Method Post -Uri $apiUrl -Body $invalidBody -ContentType "application/json" -ErrorAction SilentlyContinue
$response2Code = $response2.StatusCode
Write-Output "Test Case 2 - HTTP Status Code: $response2Code"
