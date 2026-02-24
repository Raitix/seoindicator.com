#!/bin/bash

# Manual Test Script for TASK-4-8 (Report Generation Endpoint)
# API: POST /api/v1/sites/:siteId/reports/generate

# Test Case 1: Valid Input (MVP Synchronous Generation)
SITE_ID="testSite123"
API_URL="http://localhost:8080/api/v1/sites/$SITE_ID/reports/generate"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X POST -H "Content-Type: application/json" \
  -d '{
    "name": "Test Report",
    "type": "summary",
    "format": "pdf",
    "dateRange": "2026-01-01 to 2026-01-31",
    "sections": ["overview", "details"]
  }' \
  "$API_URL")

echo "Test Case 1 - HTTP Status Code: $RESPONSE"

# Test Case 2: Invalid Input (Missing Required Fields)
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X POST -H "Content-Type: application/json" \
  -d '{
    "type": "summary"
  }' \
  "$API_URL")

echo "Test Case 2 - HTTP Status Code: $RESPONSE"

# Additional cases can be appended here for comprehensive coverage
