# TEST-19-5: IndicatorProcessor Base Class

## Overview
Test suite for abstract IndicatorProcessor base class with common functionality for all indicator checkers.

---

## Test Results

**Status:** ✅ PASSED

**Date:** 2026-02-28
**Tests Run:** 67
**Assertions:** 137
**Duration:** 0.31s

---

## Test Files Created

1. `tests/Unit/Services/Analysis/Indicators/IndicatorProcessorTest.php` - 41 tests
2. `tests/Unit/Services/Analysis/Indicators/IndicatorResultTest.php` - 26 tests

---

## Test Scenarios Covered

### 1. Abstract Method Tests ✅

#### 1.1 slug() Method
- [x] Returns unique identifier string
- [x] Uses snake_case format (lowercase letters, numbers, hyphens)

#### 1.2 name() Method
- [x] Returns human-readable name
- [x] Returns string type

#### 1.3 maxScore() Method
- [x] Returns float value
- [x] Default: 100.0

#### 1.4 process() Method
- [x] Accepts URL string
- [x] Returns IndicatorResult DTO

### 2. Helper Method Tests ✅

#### 2.1 fetchUrl() Method
- [x] Fetches HTML content from URL
- [x] Handles HTTP 404 errors
- [x] Handles HTTP 500 errors
- [x] Applies SSRF protection (blocks invalid URLs)
- [x] Handles connection timeout
- [x] Uses normalized URL from validator

#### 2.2 parseHtml() Method
- [x] Returns DOMDocument instance
- [x] Handles valid HTML
- [x] Handles malformed HTML gracefully
- [x] Throws on empty HTML
- [x] Throws on whitespace-only input

#### 2.3 CSS Selector Support
- [x] Element selector (h1, p, div)
- [x] Class selector (.classname)
- [x] ID selector (#id)
- [x] Nested selectors (div.container p.text)

### 3. SSRF Protection Tests ✅

- [x] URL validation called before fetch
- [x] Blocks invalid URLs via URLValidationService
- [x] Returns meaningful error message

### 4. IndicatorResult DTO Tests ✅

#### 4.1 Structure Tests
- [x] Has slug property (string)
- [x] Has name property (string)
- [x] Has score property (float)
- [x] Has maxScore property (float)
- [x] Has percentage property (int 0-100)
- [x] Has data property (array)
- [x] Has recommendations property (array)
- [x] Has processingTimeMs property (int)
- [x] Has error property (string|null)

#### 4.2 Factory Methods
- [x] success() creates result with calculated percentage
- [x] failed() creates result with error message
- [x] failed() adds error recommendation

#### 4.3 Immutability
- [x] Readonly properties (throws on modification)

#### 4.4 Serialization
- [x] toArray() uses snake_case keys
- [x] toArray() rounds score to 2 decimals
- [x] toArray() includes all fields

### 5. Configuration Tests ✅

#### 5.1 Timeout Configuration
- [x] Default timeout 30 seconds
- [x] setTimeout() changes value

#### 5.2 Max Response Size
- [x] Default 10MB (10485760 bytes)
- [x] setMaxResponseSize() changes value

### 6. Error Handling Tests ✅

#### 6.1 Network Errors
- [x] Handles connection timeout
- [x] Returns IndicatorResult with error on failure

#### 6.2 HTTP Errors
- [x] Handles 404 Not Found
- [x] Handles 500 Internal Server Error

### 7. Edge Cases ✅

- [x] Empty data array allowed
- [x] Large data array (100 items)
- [x] Nested data arrays
- [x] Zero score valid
- [x] Max score (100%) valid
- [x] Multiple recommendations
- [x] Zero processing time
- [x] Large processing time (30 seconds)
- [x] Special characters in error messages
- [x] Unicode support (Japanese, emojis, accented characters)
- [x] HTML with special characters
- [x] HTML with unicode
- [x] Data attributes extraction
- [x] Multiple attribute extraction

### 8. Processing Time Tests ✅

- [x] calculateProcessingTime() returns integer
- [x] Returns milliseconds

### 9. Recommendation Helper Tests ✅

- [x] Creates recommendation array with priority, message, details
- [x] Handles null details

---

## Test Implementation Notes

### Implementation Changes Made:
1. Changed from `symfony/dom-crawler` to native PHP `DOMDocument`
2. Added `symfony/css-selector` for CSS to XPath conversion
3. Added conditional logging (checks if Log facade is available)
4. All helper methods use DOMDocument instead of DomCrawler

### Concrete Test Implementation:
- Created `TestableIndicatorProcessor` class for testing
- Exposes protected methods for unit testing
- Accepts mock HTTP client in constructor

---

## Coverage Summary

| Category | Tests | Status |
|----------|-------|--------|
| Abstract Methods | 4 | ✅ |
| Fetch URL | 6 | ✅ |
| Parse HTML | 5 | ✅ |
| Extract Text | 2 | ✅ |
| Extract Attribute | 2 | ✅ |
| Extract All Text | 2 | ✅ |
| Extract All With Attributes | 2 | ✅ |
| Count Elements | 2 | ✅ |
| Recommendation Helper | 2 | ✅ |
| Configuration | 4 | ✅ |
| Processing Time | 1 | ✅ |
| CSS Selectors | 3 | ✅ |
| Edge Cases | 16 | ✅ |
| **IndicatorResult** | **26** | ✅ |
| **TOTAL** | **67** | ✅ |

---

## Dependencies
- TASK-16-1 (Tools infrastructure - SSRF protection via URLValidationService)
- symfony/css-selector (for CSS to XPath conversion)

---

*Tested by LocalTester for TASK-19-5*
*Date: 2026-02-28*
