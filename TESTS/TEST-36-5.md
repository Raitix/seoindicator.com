# TEST-36-5: ContentRefreshPlannerService

## Task Reference
- **Task:** TASK-36-5
- **Specification:** SPEC-036: Content Decay Detection & Refresh Planner
- **Status:** Tests Coverage In Progress

---

## Overview

This document describes the comprehensive test suite for the `ContentRefreshPlannerService` - the recommendation engine that maps decay reason codes to concrete refresh actions and generates actionable task suggestions.

**Service Interface:**
```php
interface ContentRefreshPlannerServiceInterface
{
    public function generateRecommendations(ContentDecayCandidate $candidate): array;
}
```

**Return Structure:**
```php
[
    'primary_recommendation' => string,
    'supporting_recommendations' => array (max 3 strings),
    'task_suggestions' => array of ContentRefreshTask-like objects,
]
```

---

## Test Files

| File | Location | Tests | Description |
|------|----------|-------|-------------|
| `ContentRefreshPlannerServiceTest.php` | `tests/Unit/Services/ContentDecay/` | 150+ | Main service tests |
| **Total** | | **150+** | |

---

## Test Scenarios

### 1. Service Instantiation (SI-001 to SI-005)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SI-001 | Service can be instantiated | None | Instance created |
| SI-002 | Service implements interface | None | Implements ContentRefreshPlannerServiceInterface |
| SI-003 | Service has generateRecommendations method | None | Method exists |
| SI-004 | Service has recommendation templates | None | Templates array exists |
| SI-005 | Service has page type configurations | None | Page type configs exist |

---

### 2. Single-Trigger Recommendations (ST-001 to ST-080)

#### CTR Down Trigger (ST-001 to ST-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-001 | CTR down with stable impressions | `ctr_down` | Primary: "Rewrite title and meta description" |
| ST-002 | CTR down recommendation type | `ctr_down` | recommendation_type: 'meta_update' |
| ST-003 | CTR down due date | `ctr_down` | due_date: 7 days from now |
| ST-004 | CTR down priority | `ctr_down` | priority: 'high' |
| ST-005 | CTR down task suggestion title | `ctr_down` | title contains "title" or "meta" |
| ST-006 | CTR down task description | `ctr_down` (50% drop) | description contains "50%" |
| ST-007 | CTR down single task generated | `ctr_down` | Exactly 1 task in task_suggestions |
| ST-008 | CTR down with evidence | `ctr_down` + evidence | Evidence values included in description |
| ST-009 | CTR down supporting empty | `ctr_down` only | supporting_recommendations: [] |
| ST-010 | CTR down with severity critical | `ctr_down` + critical | Priority: 'critical' |

#### Audit Issues Present Trigger (ST-011 to ST-020)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-011 | Audit issues present | `audit_issues_present` | Primary: "Fix technical SEO issues" |
| ST-012 | Audit issues recommendation type | `audit_issues_present` | recommendation_type: 'technical_fix' |
| ST-013 | Audit issues due date | `audit_issues_present` | due_date: 3 days from now |
| ST-014 | Audit issues priority | `audit_issues_present` | priority: 'critical' |
| ST-015 | Audit issues task count | `audit_issues_present` (5 issues) | Task description mentions "5" |
| ST-016 | Audit issues blocking content | `audit_issues_present` | Supporting: "Fix issues before content refresh" |
| ST-017 | Audit issues high priority | `audit_issues_present` | Highest priority of all triggers |
| ST-018 | Audit issues with specific types | audit_issue_types | Mention issue types in description |
| ST-019 | Audit issues task suggestion | `audit_issues_present` | task_suggestions[0].source: 'audit' |
| ST-020 | Audit issues replaces meta focus | `audit_issues_present` + `ctr_down` | Audit prioritized |

#### Stale Content Trigger (ST-021 to ST-030)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-021 | Stale content detected | `stale_content` | Primary: "Update outdated content" |
| ST-022 | Stale content recommendation type | `stale_content` | recommendation_type: 'content_refresh' |
| ST-023 | Stale content due date | `stale_content` (180 days old) | due_date: 14 days from now |
| ST-024 | Stale content priority | `stale_content` | priority: 'high' |
| ST-025 | Stale content task suggestion | `stale_content` (365 days) | description mentions "365 days" |
| ST-026 | Stale content examples update | `stale_content` | Suggests: "Update examples, screenshots, stats" |
| ST-027 | Stale content date references | `stale_content` | Suggests: "Update date references" |
| ST-028 | Stale content supporting | `stale_content` | supporting_recommendations has items |
| ST-029 | Stale content very old | `stale_content` (730 days) | Priority elevated |
| ST-030 | Stale content recent update | `stale_content` (30 days) | Lower priority |

#### Position Down Trigger (ST-031 to ST-040)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-031 | Average position down | `avg_position_down` | Primary: "Refresh core content sections" |
| ST-032 | Position down recommendation type | `avg_position_down` | recommendation_type: 'ranking_recovery' |
| ST-033 | Position down due date | `avg_position_down` | due_date: 10 days from now |
| ST-034 | Position down priority | `avg_position_down` | priority: 'medium' |
| ST-035 | Position down specific queries | `avg_position_down` + queries | Mention affected queries |
| ST-036 | Position down task suggestion | `avg_position_down` (5 positions lost) | description mentions "5 positions" |
| ST-037 | Position down significant | `avg_position_down` (10+ positions) | Priority: 'high' |
| ST-038 | Position down supporting | `avg_position_down` | Suggestions for query-specific updates |
| ST-039 | Position down with ranking loss | `avg_position_down` + tracked | Combined guidance |
| ST-040 | Position down keywords | `avg_position_down` | Keywords to optimize listed |

#### Tracked Rankings Down Trigger (ST-041 to ST-050)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-041 | Tracked rankings down | `tracked_rankings_down` | Primary: "Optimize for tracked keywords" |
| ST-042 | Tracked rankings recommendation type | `tracked_rankings_down` | recommendation_type: 'keyword_optimization' |
| ST-043 | Tracked rankings due date | `tracked_rankings_down` | due_date: 10 days from now |
| ST-044 | Tracked rankings priority | `tracked_rankings_down` | priority: 'medium' |
| ST-045 | Tracked rankings keyword list | `tracked_rankings_down` + keywords | Keywords listed in task |
| ST-046 | Tracked rankings count | `tracked_rankings_down` (8 keywords) | Mention "8 keywords" |
| ST-047 | Tracked rankings significant drop | `tracked_rankings_down` (many keywords) | Priority elevated |
| ST-048 | Tracked rankings supporting | `tracked_rankings_down` | Suggestions for content optimization |
| ST-049 | Tracked rankings with queries | `tracked_rankings_down` + GSC | Combined approach |
| ST-050 | Tracked rankings specific | `tracked_rankings_down` | Query-specific refresh guidance |

#### Impressions Down Trigger (ST-051 to ST-060)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-051 | Impressions down | `impressions_down` | Primary: "Investigate visibility loss" |
| ST-052 | Impressions recommendation type | `impressions_down` | recommendation_type: 'visibility_recovery' |
| ST-053 | Impressions due date | `impressions_down` | due_date: 14 days from now |
| ST-054 | Impressions priority | `impressions_down` | priority: 'medium' |
| ST-055 | Impressions task description | `impressions_down` (30% drop) | description mentions "30%" |
| ST-056 | Impressions with clicks stable | `impressions_down` + clicks stable | Different guidance than clicks_down |
| ST-057 | Impressions with clicks down | `impressions_down` + `clicks_down` | Combined in composite |
| ST-058 | Impressions supporting | `impressions_down` | Index and discovery suggestions |
| ST-059 | Impressions large drop | `impressions_down` (50%+) | Priority: 'high' |
| ST-060 | Impressions task suggestion | `impressions_down` | task_suggestions has visibility task |

#### AI Visibility Down Trigger (ST-061 to ST-070)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-061 | AI visibility down | `ai_visibility_down` | Primary: "Optimize for AI visibility" |
| ST-062 | AI visibility recommendation type | `ai_visibility_down` | recommendation_type: 'geo_optimization' |
| ST-063 | AI visibility due date | `ai_visibility_down` | due_date: 21 days from now |
| ST-064 | AI visibility priority | `ai_visibility_down` | priority: 'medium' |
| ST-065 | AI visibility suggestions | `ai_visibility_down` | Add definitions, FAQs, citations |
| ST-066 | AI visibility structured data | `ai_visibility_down` | Suggest structured summaries |
| ST-067 | AI visibility quotes | `ai_visibility_down` | Suggest adding expert quotes |
| ST-068 | AI visibility supporting | `ai_visibility_down` | 3 supporting recommendations |
| ST-069 | AI visibility significant drop | `ai_visibility_down` (large) | Priority elevated |
| ST-070 | AI visibility task suggestion | `ai_visibility_down` | GEO optimization task generated |

#### Competitor Gain Suspected Trigger (ST-071 to ST-080)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-071 | Competitor gain suspected | `competitor_gain_suspected` | Primary: "Add competitor comparison" |
| ST-072 | Competitor gain recommendation type | `competitor_gain_suspected` | recommendation_type: 'competitive_response' |
| ST-073 | Competitor gain due date | `competitor_gain_suspected` | due_date: 14 days from now |
| ST-074 | Competitor gain priority | `competitor_gain_suspected` | priority: 'medium' |
| ST-075 | Competitor gain with data | `competitor_gain_suspected` + competitor | Mention competitor name |
| ST-076 | Competitor gain alternative section | `competitor_gain_suspected` | Suggest alternatives section |
| ST-077 | Competitor gain comparison table | `competitor_gain_suspected` | Suggest comparison table |
| ST-078 | Competitor gain supporting | `competitor_gain_suspected` | Differentiation suggestions |
| ST-079 | Competitor gain with position loss | `competitor_gain_suspected` + position | Combined guidance |
| ST-080 | Competitor gain task suggestion | `competitor_gain_suspected` | Competitive response task |

#### Internal Link Weakness Trigger (ST-081 to ST-090)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-081 | Internal link weakness | `internal_link_weakness` | Primary: "Strengthen internal linking" |
| ST-082 | Internal link recommendation type | `internal_link_weakness` | recommendation_type: 'internal_linking' |
| ST-083 | Internal link due date | `internal_link_weakness` | due_date: 21 days from now |
| ST-084 | Internal link priority | `internal_link_weakness` | priority: 'low' |
| ST-085 | Internal link suggestions | `internal_link_weakness` | Add links from stronger pages |
| ST-086 | Internal link with related | `internal_link_weakness` + related | Mention related page URLs |
| ST-087 | Internal link supporting | `internal_link_weakness` | Contextual link suggestions |
| ST-088 | Internal link anchor text | `internal_link_weakness` | Anchor text optimization |
| ST-089 | Internal link task suggestion | `internal_link_weakness` | Link building task |
| ST-090 | Internal link low priority | `internal_link_weakness` | Lowest priority of all triggers |

---

### 3. Composite Recommendations (CR-001 to CR-040)

| ID | Scenario | Input Triggers | Expected Output |
|----|----------|----------------|-----------------|
| CR-001 | CTR + Stale content | `ctr_down` + `stale_content` | Primary: "Comprehensive page refresh" |
| CR-002 | CTR + Stale supporting | `ctr_down` + `stale_content` | 2-3 supporting recommendations |
| CR-003 | CTR + Stale tasks | `ctr_down` + `stale_content` | Multiple tasks generated |
| CR-004 | Audit + CTR | `audit_issues_present` + `ctr_down` | Audit prioritized (critical first) |
| CR-005 | Audit + Stale | `audit_issues_present` + `stale_content` | Fix technical first |
| CR-006 | Audit + Position | `audit_issues_present` + `avg_position_down` | Technical blockers addressed first |
| CR-007 | Position + Tracked Rankings | `avg_position_down` + `tracked_rankings_down` | Combined ranking recovery |
| CR-008 | Position + Competitor | `avg_position_down` + `competitor_gain_suspected` | Competitive ranking response |
| CR-009 | Stale + AI Visibility | `stale_content` + `ai_visibility_down` | Content + GEO refresh |
| CR-010 | Stale + Competitor | `stale_content` + `competitor_gain_suspected` | Update + differentiate |
| CR-011 | All three traffic triggers | `clicks_down` + `impressions_down` + `ctr_down` | Comprehensive traffic recovery |
| CR-012 | Three triggers composite | `ctr_down` + `stale_content` + `competitor_gain` | Primary + 3 supporting |
| CR-013 | Four triggers composite | 4 triggers | Maximum 3 supporting recommendations |
| CR-014 | All triggers combined | All 11 triggers | Highest priority wins primary |
| CR-015 | Priority ordering critical | `audit_issues` + `ctr_down` + `stale` | Audit first in task list |
| CR-016 | Supporting limited to 3 | 5+ triggers | Only 3 supporting recommendations |
| CR-017 | Tasks not limited | 5+ triggers | All relevant tasks generated |
| CR-018 | Evidence aggregation | Multiple triggers with evidence | All evidence included |
| CR-019 | Severity-based primary | High severity trigger | That trigger becomes primary |
| CR-020 | Candidate severity used | Candidate severity: critical | All tasks elevated priority |
| CR-021 | Composite primary title | Multiple triggers | Comprehensive action title |
| CR-022 | Composite supporting unique | Multiple triggers | No duplicate supporting items |
| CR-023 | Composite task count | 3 triggers | 3-5 tasks generated |
| CR-024 | Composite task priorities | Multiple triggers | Varying priorities |
| CR-025 | Composite due dates | Multiple triggers | Different due dates per task |
| CR-026 | CTR + Impressions | `ctr_down` + `impressions_down` | Meta + visibility focus |
| CR-027 | CTR + Position | `ctr_down` + `avg_position_down` | Meta + content focus |
| CR-028 | Clicks + Position | `clicks_down` + `avg_position_down` | Traffic + ranking recovery |
| CR-029 | All ranking triggers | `avg_position_down` + `tracked_rankings_down` | Full ranking strategy |
| CR-030 | All content triggers | `stale_content` + `ai_visibility_down` | Content modernization |
| CR-031 | Competitive composite | `competitor_gain` + `avg_position_down` | Full competitive response |
| CR-032 | Technical + Content | `audit_issues` + `stale_content` | Sequential approach |
| CR-033 | Urgency escalation | Multiple high severity | Due dates shortened |
| CR-034 | Conflicting priorities | High + Low triggers | High priority tasks first |
| CR-035 | Composite recommendation types | Multiple triggers | Various recommendation_type values |
| CR-036 | Composite task metadata | Multiple triggers | Metadata includes all triggers |
| CR-037 | Annotation override | Annotation recently changed | Consider annotation context |
| CR-038 | Annotation explains | `annotation_recently_changed` | May deprioritize other triggers |
| CR-039 | Annotation ignored | Annotation + other triggers | Other triggers still generate tasks |
| CR-040 | Complex real-world scenario | Realistic 4-trigger mix | Actionable recommendations |

---

### 4. Page-Type-Aware Recommendations (PT-001 to PT-040)

#### Blog Posts (PT-001 to PT-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-001 | Blog + stale content | page_type: blog + stale | Focus on content refresh |
| PT-002 | Blog + CTR down | page_type: blog + ctr_down | Title/meta focus |
| PT-003 | Blog + AI visibility | page_type: blog + ai_visibility | FAQ and citations emphasis |
| PT-004 | Blog + competitor | page_type: blog + competitor | Comparison section suggested |
| PT-005 | Blog task description | page_type: blog | Mentions "blog post" or "article" |
| PT-006 | Blog examples update | page_type: blog + stale | Update examples and statistics |
| PT-007 | Blog date references | page_type: blog | Update date references |
| PT-008 | Blog supporting topics | page_type: blog | Related topic suggestions |
| PT-009 | Blog multiple triggers | page_type: blog + 3 triggers | Blog-specific task priorities |
| PT-010 | Blog priority derivation | page_type: blog | Standard blog priority rules |

#### Product Pages (PT-011 to PT-020)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-011 | Product + competitor | page_type: product + competitor | Comparison/alternative focus |
| PT-012 | Product + CTR down | page_type: product + ctr_down | Meta for shopping emphasis |
| PT-013 | Product + stale | page_type: product + stale | Feature/specs update focus |
| PT-014 | Product task description | page_type: product | Mentions "product page" |
| PT-015 | Product price update | page_type: product + stale | Consider price/spec updates |
| PT-016 | Product competitor comparison | page_type: product | vs alternatives section |
| PT-017 | Product feature focus | page_type: product | Feature documentation emphasis |
| PT-018 | Product multiple triggers | page_type: product + 3 triggers | Product-specific task priorities |
| PT-019 | Product CTA focus | page_type: product | CTA optimization suggested |
| PT-020 | Product priority derivation | page_type: product | Product priority rules |

#### Landing Pages (PT-021 to PT-030)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-021 | Landing + CTR down | page_type: landing + ctr_down | CTA optimization emphasis |
| PT-022 | Landing + impressions | page_type: landing + impressions | Conversion path focus |
| PT-023 | Landing + stale | page_type: landing + stale | CTA and offer update |
| PT-024 | Landing task description | page_type: landing | Mentions "landing page" |
| PT-025 | Landing CTA focus | page_type: landing | CTA optimization primary |
| PT-026 | Landing conversion focus | page_type: landing | Conversion optimization |
| PT-027 | Landing A/B suggestion | page_type: landing | A/B testing suggestion |
| PT-028 | Landing multiple triggers | page_type: landing + 3 triggers | Landing-specific priorities |
| PT-029 | Landing offer update | page_type: landing | Offer/CTA refresh |
| PT-030 | Landing priority derivation | page_type: landing | Landing priority rules |

#### Tool Pages (PT-031 to PT-040)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-031 | Tool + stale | page_type: tool + stale | Feature documentation focus |
| PT-032 | Tool + CTR down | page_type: tool + ctr_down | Tool description optimization |
| PT-033 | Tool + AI visibility | page_type: tool + ai_visibility | How-to documentation |
| PT-034 | Tool task description | page_type: tool | Mentions "tool page" |
| PT-035 | Tool feature focus | page_type: tool | Feature documentation emphasis |
| PT-036 | Tool documentation | page_type: tool | Usage examples suggested |
| PT-037 | Tool screenshots | page_type: tool + stale | Update screenshots |
| PT-038 | Tool multiple triggers | page_type: tool + 3 triggers | Tool-specific priorities |
| PT-039 | Tool usage examples | page_type: tool | Usage guide suggestions |
| PT-040 | Tool priority derivation | page_type: tool | Tool priority rules |

---

### 5. Task Suggestion Structure (TS-001 to TS-030)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TS-001 | Task has title | Any trigger | task_suggestions[].title: string |
| TS-002 | Task has recommendation_type | Any trigger | task_suggestions[].recommendation_type: string |
| TS-003 | Task has description | Any trigger | task_suggestions[].description: string |
| TS-004 | Task has due_date | Any trigger | task_suggestions[].due_date: Carbon date |
| TS-005 | Task has priority | Any trigger | task_suggestions[].priority: string |
| TS-006 | Task title actionable | Any trigger | Title starts with action verb |
| TS-007 | Task description detailed | `ctr_down` (40% drop) | Description includes specifics |
| TS-008 | Task due_date appropriate | `audit_issues` | 3 days from now |
| TS-009 | Task due_date appropriate | `ctr_down` | 7 days from now |
| TS-010 | Task due_date appropriate | `stale_content` | 14 days from now |
| TS-011 | Task priority from severity | candidate severity: critical | task priority: critical |
| TS-012 | Task priority from severity | candidate severity: high | task priority: high |
| TS-013 | Task priority from severity | candidate severity: medium | task priority: medium |
| TS-014 | Task priority from severity | candidate severity: low | task priority: low |
| TS-015 | Task title under 100 chars | Any trigger | title.length < 100 |
| TS-016 | Task description under 500 chars | Any trigger | description.length < 500 |
| TS-017 | Task recommendation_type valid | Any trigger | Valid enum value |
| TS-018 | Task priority valid | Any trigger | Valid priority enum |
| TS-019 | Task due_date in future | Any trigger | due_date > now |
| TS-020 | Task source attribution | Any trigger | source field present |
| TS-021 | Task evidence reference | Any trigger with evidence | Evidence referenced in description |
| TS-022 | Task count matches triggers | 3 triggers | 3-5 tasks |
| TS-023 | Task no duplicates | Multiple triggers | Unique task titles |
| TS-024 | Task metadata included | Any trigger | metadata_json populated |
| TS-025 | Task can be serialized | Any trigger | JSON serializable |
| TS-026 | Task title unique per trigger | Different triggers | Different titles |
| TS-027 | Task recommendation type variety | Multiple triggers | Different recommendation_types |
| TS-028 | Task due_date calculation | `ctr_down` | Correct date calculation |
| TS-029 | Task priority escalation | Critical severity | Priority escalated |
| TS-030 | Task returned as array | Any trigger | Array of task objects |

---

### 6. Priority and Due Date Derivation (PD-001 to PD-030)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PD-001 | Audit issues priority | `audit_issues_present` | priority: 'critical' |
| PD-002 | CTR down priority | `ctr_down` | priority: 'high' |
| PD-003 | Stale content priority | `stale_content` | priority: 'high' |
| PD-004 | Position down priority | `avg_position_down` | priority: 'medium' |
| PD-005 | Tracked rankings priority | `tracked_rankings_down` | priority: 'medium' |
| PD-006 | Impressions down priority | `impressions_down` | priority: 'medium' |
| PD-007 | AI visibility priority | `ai_visibility_down` | priority: 'medium' |
| PD-008 | Competitor gain priority | `competitor_gain_suspected` | priority: 'medium' |
| PD-009 | Internal link priority | `internal_link_weakness` | priority: 'low' |
| PD-010 | Audit issues due date | `audit_issues_present` | due_date: +3 days |
| PD-011 | CTR down due date | `ctr_down` | due_date: +7 days |
| PD-012 | Position down due date | `avg_position_down` | due_date: +10 days |
| PD-013 | Stale content due date | `stale_content` | due_date: +14 days |
| PD-014 | AI visibility due date | `ai_visibility_down` | due_date: +21 days |
| PD-015 | Internal link due date | `internal_link_weakness` | due_date: +21 days |
| PD-016 | Critical severity escalation | severity: critical | All tasks elevated |
| PD-017 | High severity escalation | severity: high | Tasks elevated to high |
| PD-018 | Due date with weekend | trigger | Due date accounts for days |
| PD-019 | Priority from magnitude | 50% CTR drop | Priority elevated |
| PD-020 | Priority from count | 10 audit issues | Priority elevated |
| PD-021 | Due date shortened critical | critical severity | Due date reduced |
| PD-022 | Priority composite triggers | 3+ triggers | Highest priority wins |
| PD-023 | Due date composite triggers | 3+ triggers | Earliest due date used |
| PD-024 | Priority consistency | Same trigger | Consistent priority |
| PD-025 | Due date consistency | Same trigger | Consistent due date |
| PD-026 | Priority boundary | severity 70 | correct priority |
| PD-027 | Priority boundary | severity 69 | correct priority |
| PD-028 | Due date format | Any trigger | Y-m-d format |
| PD-029 | Priority string value | Any trigger | 'critical'|'high'|'medium'|'low' |
| PD-030 | Severity to priority mapping | All severities | Correct mapping |

---

### 7. Edge Cases (EC-001 to EC-040)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty reason codes | reason_codes: [] | Default recommendation |
| EC-002 | Empty reason codes message | reason_codes: [] | "No decay detected" |
| EC-003 | Empty reason codes tasks | reason_codes: [] | Empty task_suggestions |
| EC-004 | Null reason codes | reason_codes: null | Default recommendation |
| EC-005 | Unknown reason code | reason_codes: ['unknown'] | Ignored gracefully |
| EC-006 | Unknown reason code only | reason_codes: ['unknown'] | Default recommendation |
| EC-007 | Invalid reason code type | reason_codes: "string" | Handled gracefully |
| EC-008 | Missing evidence | trigger without evidence | Task still generated |
| EC-009 | Malformed evidence | evidence: "invalid" | Evidence ignored |
| EC-010 | Very high decay score | decay_score: 99 | Appropriate recommendations |
| EC-011 | Very low decay score | decay_score: 1 | Low priority tasks |
| EC-012 | Zero decay score | decay_score: 0 | Minimal recommendations |
| EC-013 | Negative metric values | evidence with negatives | Absolute values used |
| EC-014 | Very large metrics | clicks: 10000000 | Formatted in description |
| EC-015 | Unicode in page title | title: "中文标题" | Handled correctly |
| EC-016 | Emoji in page title | title: "🎉 Sale" | Handled correctly |
| EC-017 | Very long page URL | URL: 500+ chars | Truncated in description |
| EC-018 | Missing page type | page_type: null | Default recommendations |
| EC-019 | Invalid page type | page_type: 'unknown' | Default recommendations |
| EC-020 | Missing severity | severity: null | Default priority |
| EC-021 | Invalid severity | severity: 'extreme' | Default priority |
| EC-022 | Very old content | content_age_days: 3650 | Appropriate staleness task |
| EC-023 | Very fresh content | content_age_days: 1 | Staleness trigger ignored |
| EC-024 | All metrics zero | All metrics = 0 | Handled gracefully |
| EC-025 | Conflicting signals | Opposite trends | Prioritization logic |
| EC-026 | Circular evidence | Self-referencing | Handled gracefully |
| EC-027 | Missing candidate fields | Partial candidate | Available fields used |
| EC-028 | Empty page URL | page_url: "" | Handled gracefully |
| EC-029 | Missing page title | page_title: null | URL used in description |
| EC-030 | Very long page title | title: 500+ chars | Truncated in output |
| EC-031 | Multiple tasks same type | Same trigger | Unique tasks |
| EC-032 | Supporting limit enforced | Many triggers | Max 3 supporting |
| EC-033 | Task limit not enforced | Many triggers | All tasks included |
| EC-034 | Empty signal sources | signal_sources: [] | Tasks still generated |
| EC-035 | Very low confidence | detection_confidence: 0.70 | Tasks still generated |
| EC-036 | Very high confidence | detection_confidence: 1.0 | Tasks generated |
| EC-037 | Missing decay score | decay_score: null | Default priority |
| EC-038 | Timestamp in future | future timestamp | Handled gracefully |
| EC-039 | Timestamp in past | old timestamp | Handled gracefully |
| EC-040 | All optional fields null | Minimal candidate | Core recommendations |

---

### 8. Return Structure Validation (RS-001 to RS-020)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RS-001 | Returns array | Any trigger | array type |
| RS-002 | Has primary_recommendation | Any trigger | Key exists |
| RS-003 | Primary is string | Any trigger | String type |
| RS-004 | Primary not empty | Any trigger | String length > 0 |
| RS-005 | Has supporting_recommendations | Any trigger | Key exists |
| RS-006 | Supporting is array | Any trigger | Array type |
| RS-007 | Supporting max 3 items | Many triggers | count <= 3 |
| RS-008 | Supporting items are strings | Any trigger | Each item is string |
| RS-009 | Has task_suggestions | Any trigger | Key exists |
| RS-010 | Tasks is array | Any trigger | Array type |
| RS-011 | Task items have title | Any trigger | title key exists |
| RS-012 | Task items have recommendation_type | Any trigger | recommendation_type key exists |
| RS-013 | Task items have description | Any trigger | description key exists |
| RS-014 | Task items have due_date | Any trigger | due_date key exists |
| RS-015 | Task items have priority | Any trigger | priority key exists |
| RS-016 | Primary recommendation actionable | Any trigger | Starts with verb |
| RS-017 | Supporting recommendations unique | Any trigger | No duplicates |
| RS-018 | Tasks have unique titles | Any trigger | No duplicate titles |
| RS-019 | Structure JSON serializable | Any trigger | json_encode succeeds |
| RS-020 | Structure recursively valid | Any trigger | All nested items valid |

---

### 9. Integration Scenarios (IS-001 to IS-015)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IS-001 | Service with config | config values | Config values used |
| IS-002 | Service with custom templates | custom templates | Custom templates used |
| IS-003 | Service caches recommendations | Same candidate | Consistent output |
| IS-004 | Service handles many candidates | Multiple calls | All handled correctly |
| IS-005 | Service with app container | Container resolution | Resolves correctly |
| IS-006 | Service mockable | Mock interface | Mock works |
| IS-007 | Service injectable | DI container | Injection works |
| IS-008 | Service with logger | Logging enabled | Logs generated |
| IS-009 | Service performance | 100 calls | Completes in reasonable time |
| IS-010 | Service memory usage | Large candidate | Memory efficient |
| IS-011 | Service with events | Events configured | Events dispatched |
| IS-012 | Service with validation | Invalid input | Validation error |
| IS-013 | Service with exception | Exception thrown | Graceful handling |
| IS-014 | Service with transaction | Database transaction | Transaction respected |
| IS-015 | Service idempotent | Same input twice | Same output |

---

## Mock Data Patterns

### Mock ContentDecayCandidate

```php
$mockCandidate = new ContentDecayCandidate([
    'id' => 1,
    'project_id' => 1,
    'page_url' => 'https://example.com/blog/seo-tips',
    'page_title' => '10 SEO Tips for 2024',
    'page_type' => 'blog',
    'decay_score' => 75.50,
    'detection_confidence' => 0.95,
    'severity' => 'high',
    'status' => 'backlog',
    'reason_codes' => ['ctr_down', 'stale_content'],
    'signal_sources' => ['gsc', 'audit'],
    'evidence_json' => [
        'ctr_down' => [
            'previous_value' => 5.2,
            'current_value' => 2.8,
            'change_percent' => -46.15,
        ],
        'stale_content' => [
            'content_age_days' => 365,
        ],
    ],
    'lost_clicks_estimate' => 450,
    'recovery_potential' => 1200,
]);
```

### Mock Signal Data

```php
$mockSignals = [
    'reason_codes' => ['ctr_down', 'stale_content', 'audit_issues_present'],
    'evidence' => [
        'ctr_down' => [
            'metric' => 'ctr',
            'previous_value' => 5.2,
            'current_value' => 2.8,
            'change_percent' => -46.15,
            'change_absolute' => -2.4,
        ],
        'stale_content' => [
            'metric' => 'content_age',
            'previous_value' => null,
            'current_value' => 365,
            'change_percent' => null,
            'change_absolute' => 365,
        ],
        'audit_issues_present' => [
            'metric' => 'audit_issues',
            'count' => 5,
            'types' => ['missing_meta_description', 'h1_missing'],
        ],
    ],
    'severity' => 'high',
    'page_type' => 'blog',
];
```

### Expected Recommendation Output

```php
$expectedOutput = [
    'primary_recommendation' => 'Fix technical SEO issues before content refresh',
    'supporting_recommendations' => [
        'Rewrite title and meta description to improve CTR',
        'Update outdated examples and statistics',
        'Address 5 technical audit issues',
    ],
    'task_suggestions' => [
        [
            'title' => 'Fix technical SEO issues',
            'recommendation_type' => 'technical_fix',
            'description' => 'This page has 5 unresolved audit issues. Address technical problems before investing in content updates.',
            'due_date' => '2026-03-14', // +3 days
            'priority' => 'critical',
            'source' => 'audit',
            'metadata_json' => [
                'issue_count' => 5,
                'issue_types' => ['missing_meta_description', 'h1_missing'],
            ],
        ],
        [
            'title' => 'Rewrite title and meta description',
            'recommendation_type' => 'meta_update',
            'description' => 'Your CTR has dropped by 46.2%. Update your title tag to be more compelling and ensure your meta description accurately reflects the page content.',
            'due_date' => '2026-03-18', // +7 days
            'priority' => 'high',
            'source' => 'gsc',
            'metadata_json' => [
                'ctr_drop_percent' => 46.15,
                'previous_ctr' => 5.2,
                'current_ctr' => 2.8,
            ],
        ],
        [
            'title' => 'Refresh outdated content',
            'recommendation_type' => 'content_refresh',
            'description' => 'This content is 365 days old. Update examples, screenshots, statistics, and date references to maintain relevance.',
            'due_date' => '2026-03-25', // +14 days
            'priority' => 'high',
            'source' => 'content_audit',
            'metadata_json' => [
                'content_age_days' => 365,
            ],
        ],
    ],
];
```

---

## Recommendation Templates

### Default Templates

```php
$recommendationTemplates = [
    'audit_issues_present' => [
        'title' => 'Fix technical SEO issues',
        'description' => 'This page has {count} unresolved audit issues. Address technical problems before investing in content updates.',
        'due_days' => 3,
        'priority' => 'critical',
        'recommendation_type' => 'technical_fix',
    ],
    'clicks_down' => [
        'title' => 'Investigate click loss',
        'description' => 'Clicks have dropped by {percent}%. Analyze the cause and implement recovery strategies.',
        'due_days' => 7,
        'priority' => 'high',
        'recommendation_type' => 'traffic_recovery',
    ],
    'ctr_down' => [
        'title' => 'Rewrite title and meta description',
        'description' => 'Your CTR has dropped by {percent}%. Update your title tag to be more compelling and ensure your meta description accurately reflects the page content.',
        'due_days' => 7,
        'priority' => 'high',
        'recommendation_type' => 'meta_update',
    ],
    'avg_position_down' => [
        'title' => 'Refresh core content sections',
        'description' => 'Average position has declined by {positions} positions. Refresh content sections targeting affected queries.',
        'due_days' => 10,
        'priority' => 'medium',
        'recommendation_type' => 'ranking_recovery',
    ],
    'tracked_rankings_down' => [
        'title' => 'Optimize for tracked keywords',
        'description' => 'Visibility has declined for {count} tracked keywords. Optimize content for these specific terms.',
        'due_days' => 10,
        'priority' => 'medium',
        'recommendation_type' => 'keyword_optimization',
    ],
    'impressions_down' => [
        'title' => 'Investigate visibility loss',
        'description' => 'Impressions have dropped by {percent}%. Check for indexing issues or SERP feature changes.',
        'due_days' => 14,
        'priority' => 'medium',
        'recommendation_type' => 'visibility_recovery',
    ],
    'stale_content' => [
        'title' => 'Refresh outdated content',
        'description' => 'This content is {age_days} days old. Update examples, screenshots, statistics, and date references to maintain relevance.',
        'due_days' => 14,
        'priority' => 'high',
        'recommendation_type' => 'content_refresh',
    ],
    'ai_visibility_down' => [
        'title' => 'Optimize for AI visibility',
        'description' => 'AI visibility has declined. Add definitions, FAQs, citations, quotes, and structured summaries to improve GEO.',
        'due_days' => 21,
        'priority' => 'medium',
        'recommendation_type' => 'geo_optimization',
    ],
    'competitor_gain_suspected' => [
        'title' => 'Add competitor comparison',
        'description' => 'Competitor may be gaining ground. Add comparison sections, alternatives, and differentiation content.',
        'due_days' => 14,
        'priority' => 'medium',
        'recommendation_type' => 'competitive_response',
    ],
    'internal_link_weakness' => [
        'title' => 'Strengthen internal linking',
        'description' => 'This page needs more internal link support. Add links from related stronger pages.',
        'due_days' => 21,
        'priority' => 'low',
        'recommendation_type' => 'internal_linking',
    ],
];
```

---

## Priority Mapping

| Trigger | Default Priority | Due Days | Escalation Condition |
|---------|------------------|----------|---------------------|
| audit_issues_present | critical | 3 | Always critical |
| clicks_down | high | 7 | 30%+ drop → critical |
| ctr_down | high | 7 | 40%+ drop → critical |
| avg_position_down | medium | 10 | 10+ positions → high |
| tracked_rankings_down | medium | 10 | 5+ keywords → high |
| impressions_down | medium | 14 | 50%+ drop → high |
| stale_content | high | 14 | 365+ days → critical |
| ai_visibility_down | medium | 21 | 50%+ drop → high |
| competitor_gain_suspected | medium | 14 | Position loss → high |
| internal_link_weakness | low | 21 | Multiple pages → medium |

---

## Test Execution

```bash
# Run all ContentRefreshPlannerService tests
cd backend
php artisan test --filter ContentRefreshPlannerService

# Run specific test file
php artisan test tests/Unit/Services/ContentDecay/ContentRefreshPlannerServiceTest.php

# Run with coverage
php artisan test --coverage --filter ContentRefreshPlannerService

# Run specific test method
php artisan test --filter test_single_trigger_ctr_down
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-36-2 (Eloquent Models) | To Develop | ContentDecayCandidate model needed |
| TASK-36-4 (Reason Service) | To Write Tests | Reason codes structure defined |

**TDD Note:** Tests can use mocked `ContentDecayCandidate` objects. The service operates on the candidate's properties (reason_codes, evidence_json, severity, page_type, etc.) which can be mocked without the actual model implementation.

---

## Notes for Developer

### Service Implementation Checklist

**ContentRefreshPlannerService:**
- [ ] Implements ContentRefreshPlannerServiceInterface
- [ ] generateRecommendations(ContentDecayCandidate $candidate): array
- [ ] Recommendation templates array (configurable)
- [ ] Page-type configuration array
- [ ] Priority derivation logic
- [ ] Due date calculation logic
- [ ] Composite recommendation builder
- [ ] Supporting recommendation limiter (max 3)
- [ ] Task suggestion generator

### Key Implementation Notes

1. **Template Interpolation:**
   - Use {count}, {percent}, {age_days}, {positions} placeholders
   - Replace with evidence values from candidate

2. **Priority Escalation:**
   - Candidate severity can escalate task priorities
   - Critical severity: all tasks → critical
   - High severity: medium tasks → high

3. **Due Date Calculation:**
   - Use Carbon for date arithmetic
   - Add due_days to current date
   - Consider business days if required

4. **Page Type Variations:**
   - Store page-type-specific adjustments
   - Override descriptions and suggestions
   - Maintain core task structure

5. **Composite Logic:**
   - Merge multiple triggers
   - Select highest priority as primary
   - Limit supporting to 3 (most important)
   - Generate all relevant tasks

6. **Configuration:**
   - Templates should be configurable
   - Allow overriding via config file
   - Support custom due day formulas

### Expected File Structure

```
backend/
├── app/
│   ├── Services/
│   │   └── ContentDecay/
│   │       ├── ContentRefreshPlannerService.php
│   │       └── ContentRefreshPlannerServiceInterface.php
│   └── Config/
│       └── content-decay.php (optional)
└── tests/
    └── Unit/
        └── Services/
            └── ContentDecay/
                └── ContentRefreshPlannerServiceTest.php
```

---

## Comments

### PreTester (2026-03-11)
**Test Suite Design Started**

Creating comprehensive test suite for ContentRefreshPlannerService.

**Test Categories:**
1. Service Instantiation (5 tests)
2. Single-Trigger Recommendations (80 tests)
3. Composite Recommendations (40 tests)
4. Page-Type-Aware Recommendations (40 tests)
5. Task Suggestion Structure (30 tests)
6. Priority and Due Date Derivation (30 tests)
7. Edge Cases (40 tests)
8. Return Structure Validation (20 tests)
9. Integration Scenarios (15 tests)

**Total Expected Tests:** 300+

**Status:** Tests Coverage In Progress

---

*Created by PreTester agent on 2026-03-11*
