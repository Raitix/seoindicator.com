# TESTS/SPEC-7-8: SERP Feature Change Tracker

## Overview
Unit tests for the SERP Feature Change Tracker service that detects when SERP features are gained or lost.

## Test File
`backend/tests/Unit/Services/SerpFeatureChangeTrackerTest.php`

---

## Test Scenarios

### 1. Feature Gained Detection
**Description:** Detect when a feature transitions from not present to present.

**Test Cases:**
- `it_detects_gained_feature_when_previously_absent`
- `it_detects_multiple_gained_features`
- `it_records_gained_change_in_history`
- `it_emits_feature_gained_event`

**Setup:**
```php
$currentFeatures = [
    ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
];
$previousFeatures = [
    ['type' => 'featured_snippet', 'is_present' => false]
];
// Expected: change with action 'gained'
```

---

### 2. Feature Lost Detection
**Description:** Detect when a feature transitions from present to not present.

**Test Cases:**
- `it_detects_lost_feature_when_previously_present`
- `it_detects_multiple_lost_features`
- `it_records_lost_change_in_history`
- `it_emits_feature_lost_event`

**Setup:**
```php
$currentFeatures = [
    ['type' => 'featured_snippet', 'is_present' => false]
];
$previousFeatures = [
    ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
];
// Expected: change with action 'lost'
```

---

### 3. First-Time Detection
**Description:** Handle scenario where there are no previous features (first check).

**Test Cases:**
- `it_returns_no_gained_events_for_first_check`
- `it_still_records_current_features_for_first_check`
- `it_distinguishes_first_check_from_subsequent_checks`

**Setup:**
```php
$currentFeatures = [
    ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
];
$previousFeatures = []; // Empty = first check
// Expected: no 'gained' events (first time, we don't know if it was there before)
```

---

### 4. No Change Detection
**Description:** Handle scenarios where features remain unchanged.

**Test Cases:**
- `it_returns_empty_array_when_no_changes`
- `it_handles_both_present_with_same_url`
- `it_handles_both_absent`

**Setup:**
```php
$currentFeatures = [
    ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
];
$previousFeatures = [
    ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
];
// Expected: no changes
```

---

### 5. URL Change Detection
**Description:** Track URL changes within the same feature (e.g., different page ranking).

**Test Cases:**
- `it_detects_url_change_for_gained_feature`
- `it_tracks_previous_url_for_lost_feature`
- `it_records_both_urls_in_history`

**Setup:**
```php
// Gained with URL change
$currentFeatures = [
    ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/new']
];
$previousFeatures = [
    ['type' => 'featured_snippet', 'is_present' => false, 'url' => 'https://example.com/old']
];
// Expected: gained with previous_url and current_url
```

---

### 6. Multiple Feature Types
**Description:** Handle tracking across all 10 feature types simultaneously.

**Test Cases:**
- `it_processes_all_feature_types`
- `it_detects_mixed_gained_and_lost`
- `it_handles_partial_feature_sets`

**Feature Types:**
- featured_snippet
- local_pack
- image_pack
- video
- people_also_ask
- knowledge_panel
- sitelinks
- reviews
- faq
- product

---

### 7. Edge Cases & Boundary Conditions

**Test Cases:**
- `it_handles_null_url_values`
- `it_handles_missing_feature_in_current_but_present_in_previous`
- `it_handles_missing_feature_in_previous_but_present_in_current`
- `it_handles_empty_feature_arrays`
- `it_handles_malformed_feature_data`
- `it_validates_feature_type_enum`

---

### 8. History Persistence
**Description:** Verify changes are persisted to serp_feature_history table.

**Test Cases:**
- `it_persists_gained_change_to_database`
- `it_persists_lost_change_to_database`
- `it_records_correct_timestamp`
- `it_records_correct_keyword_id`

---

### 9. Event Emission
**Description:** Verify events are emitted for alert system integration.

**Test Cases:**
- `it_dispatches_serp_feature_gained_event`
- `it_dispatches_serp_feature_lost_event`
- `it_includes_all_change_data_in_event`
- `it_dispatches_events_for_each_change`

---

### 10. Error Handling

**Test Cases:**
- `it_throws_exception_for_invalid_keyword_id`
- `it_handles_database_write_failure_gracefully`
- `it_logs_errors_appropriately`
- `it_continues_processing_on_partial_failure`

---

## Expected Service Interface

```php
interface SERPFeatureChangeTrackerInterface
{
    /**
     * Track feature changes between current and previous states.
     *
     * @param int $keywordId
     * @param array $currentFeatures Array of SERPFeature data
     * @param array $previousFeatures Array of SERPFeature data (empty for first check)
     * @return array Array of SERPFeatureChange objects
     */
    public function trackChanges(
        int $keywordId,
        array $currentFeatures,
        array $previousFeatures
    ): array;
}

interface SERPFeatureChange
{
    public string $type;        // feature_type
    public string $action;      // 'gained' | 'lost'
    public int $keywordId;
    public ?string $previousUrl;
    public ?string $currentUrl;
    public DateTime $detectedAt;
}
```

---

## Mock Data Examples

### Gained Featured Snippet
```php
$change = [
    'type' => 'featured_snippet',
    'action' => 'gained',
    'keyword_id' => 1,
    'previous_url' => null,
    'current_url' => 'https://example.com/seo-tools',
    'detected_at' => '2026-02-26T10:00:00Z'
];
```

### Lost Local Pack
```php
$change = [
    'type' => 'local_pack',
    'action' => 'lost',
    'keyword_id' => 1,
    'previous_url' => 'https://example.com/location',
    'current_url' => null,
    'detected_at' => '2026-02-26T10:00:00Z'
];
```

---

## Dependencies
- `SerpFeatureRepository` (TASK-7-3)
- `SerpFeatureHistoryRepository` (TASK-7-9)
- `SerpFeature` model
- Laravel Events system

---

## Coverage Target
- Minimum 90% code coverage for change detection logic
- All edge cases must have dedicated tests
- All 10 feature types must be tested

---

## Comments

### [2026-02-26 18:05] Agent: PreTester
Created test documentation for SERP Feature Change Tracker.
Focus on TDD approach - tests define expected behavior before implementation.
Key scenarios: gained/lost detection, first-time handling, URL tracking, event emission.
