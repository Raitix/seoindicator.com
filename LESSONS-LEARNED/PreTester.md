# PreTester.md — Project Lessons Learned

Knowledge base for PreTester agent working on SEOIndicator project.

---

## Test Patterns

### TypeScript Type Testing (TASK-7-2)
- Test type definitions with validation scenarios (valid/invalid values)
- Test interface required vs optional fields separately
- Test constant structure and content independently
- Include model class serialization/deserialization tests
- Mock model class in tests if actual implementation doesn't exist yet
- Use Chai's `expect().to.include()` for array membership tests

### SERP Feature Types
All 10 feature types:
1. `featured_snippet` - Answer box at top of results
2. `local_pack` - Map with 3 local business listings
3. `image_pack` - Horizontal row of images
4. `video` - Video thumbnail in results
5. `people_also_ask` - Expandable related questions
6. `knowledge_panel` - Entity information sidebar
7. `sitelinks` - Additional page links under result
8. `reviews` - Star ratings with result
9. `faq` - FAQ accordion in results
10. `product` - Product rich result

---

## Edge Cases

### SERP Feature Types (TASK-7-2)
- Types are case-sensitive (uppercase rejected)
- `isPresent` must be boolean (not "true" string or 1 number)
- URL max length is 2083 characters (HTTP spec)
- Position can be 0 (position zero = featured snippet)
- Unicode/emoji should be supported in title/snippet
- Newlines should be preserved in snippet text
- Extra properties on objects should be handled gracefully

---

## Test Documentation Format

Use `TESTS/SPEC-X-Y.md` with sections:
1. Task Reference (links)
2. Overview
3. Test Files table
4. Test Scenarios (tables with ID, Scenario, Input, Expected Output)
5. Mock Data samples
6. Coverage Targets
7. Test Execution commands
8. Dependencies
9. Notes for Developer
10. Comments (agent log)

---

Last updated: 2026-02-26
