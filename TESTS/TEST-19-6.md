# TEST-19-6: Core Indicator Implementations

## Overview
Test suite for 4 core indicator processors: MetaTags, Headings, Images, Links.

---

## Test Files
- `tests/Unit/Processors/MetaTagsProcessorTest.php`
- `tests/Unit/Processors/HeadingsProcessorTest.php`
- `tests/Unit/Processors/ImagesProcessorTest.php`
- `tests/Unit/Processors/LinksProcessorTest.php`

---

## Test Scenarios

### 1. MetaTagsProcessor Tests

#### 1.1 Title Tag Checks
- [ ] Detects presence of title tag
- [ ] Scores title length 50-60 chars as optimal
- [ ] Scores title length < 50 as suboptimal
- [ ] Scores title length > 60 as suboptimal
- [ ] Handles missing title tag (score: 0)
- [ ] Handles empty title tag
- [ ] Returns title in data array

#### 1.2 Meta Description Checks
- [ ] Detects presence of meta description
- [ ] Scores description length 150-160 chars as optimal
- [ ] Scores description length < 150 as suboptimal
- [ ] Scores description length > 160 as suboptimal
- [ ] Handles missing meta description
- [ ] Returns description in data array

#### 1.3 Open Graph Tags
- [ ] Detects og:title
- [ ] Detects og:description
- [ ] Detects og:image
- [ ] Scores based on presence/absence
- [ ] Returns OG data in results

#### 1.4 Recommendations
- [ ] Recommends adding title if missing
- [ ] Recommends optimal title length
- [ ] Recommends adding description if missing
- [ ] Recommends optimal description length
- [ ] Recommends adding OG tags if missing

### 2. HeadingsProcessor Tests

#### 2.1 H1 Checks
- [ ] Detects presence of H1
- [ ] Flags multiple H1s as error
- [ ] Scores single H1 as optimal
- [ ] Handles missing H1
- [ ] Returns H1 content in data

#### 2.2 Heading Hierarchy
- [ ] Validates H1 → H2 → H3 structure
- [ ] Flags skipped levels (H1 → H3)
- [ ] Scores proper hierarchy
- [ ] Returns heading outline in data

#### 2.3 H2-H6 Analysis
- [ ] Counts H2 elements
- [ ] Counts H3-H6 elements
- [ ] Flags excessive nesting
- [ ] Returns counts in data

#### 2.4 Recommendations
- [ ] Recommends adding H1 if missing
- [ ] Recommends single H1 if multiple
- [ ] Recommends fixing hierarchy issues

### 3. ImagesProcessor Tests

#### 3.1 Image Count
- [ ] Counts total images on page
- [ ] Returns count in data
- [ ] Handles pages with no images

#### 3.2 Alt Text Checks
- [ ] Counts images with alt text
- [ ] Counts images without alt text
- [ ] Calculates percentage with alt text
- [ ] Scores based on alt text coverage

#### 3.3 Large Images
- [ ] Identifies images > 500KB
- [ ] Flags images without lazy loading
- [ ] Returns list of large images with URLs
- [ ] Provides recommendations for optimization

#### 3.4 Recommendations
- [ ] Recommends adding alt text
- [ ] Recommends lazy loading for large images
- [ ] Recommends image optimization

### 4. LinksProcessor Tests

#### 4.1 Link Counting
- [ ] Counts total links
- [ ] Counts internal links
- [ ] Counts external links
- [ ] Returns counts in data

#### 4.2 Broken Link Detection
- [ ] Identifies 4xx response links
- [ ] Identifies 5xx response links
- [ ] Returns list of broken links
- [ ] Scores based on broken link percentage

#### 4.3 External Link Analysis
- [ ] Checks for nofollow on external links
- [ ] Counts nofollow external links
- [ ] Counts dofollow external links
- [ ] Provides recommendations for external linking

#### 4.4 Performance Considerations
- [ ] Limits concurrent link checks
- [ ] Handles timeout for slow links
- [ ] Caches results for repeated URLs

### 5. Common Processor Tests

#### 5.1 Score Calculation
- [ ] All processors return score 0-100
- [ ] All processors return maxScore
- [ ] All processors calculate percentage

#### 5.2 Data Structure
- [ ] All processors return data array
- [ ] All processors return recommendations array
- [ ] All processors include processing time

#### 5.3 Error Handling
- [ ] Handle invalid URLs gracefully
- [ ] Return meaningful error messages
- [ ] Don't crash on malformed HTML

### 6. Integration Tests

#### 6.1 Real HTML Processing
- [ ] Process sample HTML files
- [ ] Verify accurate results
- [ ] Compare against expected output

#### 6.2 Performance
- [ ] Complete processing in reasonable time (<5s)
- [ ] Memory usage within limits

---

## Test Implementation Notes

- Create sample HTML fixtures for testing
- Mock HTTP responses for links processor
- Use real HTML for parsing tests
- Test edge cases thoroughly

---

## Dependencies
- TASK-19-5 (IndicatorProcessor base class)

---

## Coverage Target
- 80+ test methods per processor
- 85%+ code coverage

---

*Created by PreTester for TASK-19-6*
