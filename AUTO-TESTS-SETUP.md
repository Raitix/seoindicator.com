# AUTO-TESTS-SETUP.md

## Testing Philosophy
- All new features require automated tests
- Tests written alongside or before implementation (TDD encouraged)
- Minimum 80% code coverage target

---

## Test Types

| Type | Location | Purpose |
|------|----------|---------|
| Unit Tests | `tests/unit/` | Test individual functions/components |
| Integration Tests | `tests/integration/` | Test component interactions |
| E2E Tests | `tests/e2e/` | Test full user flows |
| API Tests | `tests/api/` | Test API endpoints |

---

## Test File Naming
- Unit: `[filename].test.js` or `[filename].spec.js`
- E2E: `[feature].e2e.js`
- API: `[endpoint].api.test.js`

---

## Running Tests

```bash
# Run all tests
npm test

# Run specific test type
npm run test:unit
npm run test:integration
npm run test:e2e

# Run with coverage
npm run test:coverage

# Run specific test file
npm test -- path/to/test.js
```

---

## CI/CD Integration
- Tests run automatically on PR creation
- All tests must pass before merge
- Coverage report generated on each run

---

## Test Documentation
- Each test task has corresponding `TESTS/SPEC-X-Y.md`
- Document test scenarios, edge cases, expected results
