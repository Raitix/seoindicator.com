# TASK-21-1: Create AI Service Structure and Interfaces

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Create the core AI suggestion service architecture with provider interface, response DTOs, and configuration scaffolding. This is the foundation for all AI-powered suggestion functionality.

---

## Acceptance Criteria

### AC-1: AIProviderInterface Created
- [ ] Interface file exists at `backend/app/Services/AI/Contracts/AIProviderInterface.php`
- [ ] Defines `generate(string $prompt, array $options = []): AIResponse` method
- [ ] Defines `isAvailable(): bool` method
- [ ] Defines `getName(): string` method
- [ ] Proper PHPDoc documentation

### AC-2: AIResponse DTO Created
- [ ] File exists at `backend/app/Services/AI/DTOs/AIResponse.php`
- [ ] Contains properties: `success`, `content`, `tokensUsed`, `latencyMs`, `errorMessage`
- [ ] Immutable (readonly properties)
- [ ] Factory methods for success/error states

### AC-3: SuggestionResult DTO Created
- [ ] File exists at `backend/app/Services/AI/DTOs/SuggestionResult.php`
- [ ] Contains: `indicator`, `score`, `issues`, `suggestions`, `bestPractices`, `provider`, `model`, `generatedAt`
- [ ] JSON serialization support
- [ ] Validation for required fields

### AC-4: AISuggestionService Core Created
- [ ] File exists at `backend/app/Services/AI/AISuggestionService.php`
- [ ] Constructor accepts provider via interface
- [ ] Method `getSuggestions(string $indicator, array $context): SuggestionResult` stub
- [ ] Method `setProvider(string $provider): void` stub
- [ ] Method `isDebugMode(): bool` implemented
- [ ] Laravel service provider registration

### AC-5: Configuration File Created
- [ ] File exists at `backend/config/ai.php`
- [ ] Contains provider configurations (openrouter, vercel, fallback)
- [ ] Contains cache settings
- [ ] Contains debug flag
- [ ] Contains timeout settings

### AC-6: Unit Tests
- [ ] Test file exists at `backend/tests/Unit/Services/AI/AIProviderInterfaceTest.php`
- [ ] Test file exists at `backend/tests/Unit/Services/AI/DTOs/AIResponseTest.php`
- [ ] Test file exists at `backend/tests/Unit/Services/AI/DTOs/SuggestionResultTest.php`
- [ ] All tests passing

---

## Dependencies
- None (foundation task)

---

## Effort Estimate
**Medium** (4-6 hours)

- Interface and DTO creation: 2 hours
- Service skeleton: 1 hour
- Configuration: 1 hour
- Tests: 2 hours

---

## Technical Notes

### Directory Structure
```
backend/app/Services/AI/
├── Contracts/
│   └── AIProviderInterface.php
├── DTOs/
│   ├── AIResponse.php
│   └── SuggestionResult.php
├── Providers/
│   └── (TASK-21-2, TASK-21-3)
├── Prompts/
│   └── (TASK-21-4)
└── AISuggestionService.php
```

### AIProviderInterface
```php
interface AIProviderInterface
{
    public function generate(string $prompt, array $options = []): AIResponse;
    public function isAvailable(): bool;
    public function getName(): string;
}
```

---

## Comments
_Agent log_

### [2026-03-01 18:25 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: High** ✅
**Risk Confirmed: Medium** ✅

**Rationale:**
- Foundation task - creates interfaces and DTOs that all other SPEC-021 tasks depend on
- No external dependencies (no network calls, no database)
- Enables TASK-21-2, 21-3, 21-4, 21-5 to proceed
- Standard PHP interface/DTO creation - well-understood patterns

**Dependency Analysis:**
- No dependencies - can start immediately
- Blocks: TASK-21-2 (OpenRouter Provider), TASK-21-3 (Static Fallback), TASK-21-4 (Prompt Templates), TASK-21-5 (API Endpoint)
- All Phase 1 tasks can be developed in parallel after this task completes

**Execution Order within SPEC-021:**
1. TASK-21-1 (this task) → Foundation
2. TASK-21-2, 21-3, 21-4 → Can run in parallel after TASK-21-1
3. TASK-21-5 → After all providers are ready
4. TASK-21-6, 21-7, 21-8 → API enhancements (can parallelize)
5. TASK-21-9 → Alternative provider (optional)
6. TASK-21-10 → Security layer
7. TASK-21-11 → Frontend integration
8. TASK-21-12 → Final documentation and tests

Status: `To Prioritize` → `To Write Tests`

**Handoff to PreTester:** Ready for test design. Focus on:
- Interface contract testing (AIProviderInterface)
- DTO validation tests (AIResponse, SuggestionResult)
- Configuration validation tests
- Service instantiation tests

---
*Created by SpecSystemAnalyst on 2026-03-01*

### [2026-03-01 18:35 GMT+2] PreTester - Test Suite Created
**Test Design Complete** ✅

**Test Files Created:**
1. `TESTS/TEST-21-1.md` - Test documentation with 125 test scenarios
2. `backend/tests/Unit/Services/AI/AIProviderInterfaceTest.php` - 15 tests (interface contract)
3. `backend/tests/Unit/Services/AI/DTOs/AIResponseTest.php` - 20 tests (response DTO)
4. `backend/tests/Unit/Services/AI/DTOs/SuggestionResultTest.php` - 30 tests (result DTO)
5. `backend/tests/Unit/Services/AI/AISuggestionServiceTest.php` - 20 tests (service core)
6. `backend/tests/Unit/Services/AI/ConfigurationTest.php` - 17 tests (config validation)

**Total Tests:** 102 tests

**Acceptance Criteria Test Coverage:**
| AC | Tests | Status |
|----|-------|--------|
| AC-1: AIProviderInterface | 15 | ✅ |
| AC-2: AIResponse DTO | 20 | ✅ |
| AC-3: SuggestionResult DTO | 30 | ✅ |
| AC-4: AISuggestionService | 20 | ✅ |
| AC-5: Configuration | 17 | ✅ |

**Test Categories:**
- Interface contract (IFC-*): Method signatures, return types, PHPDoc
- DTO validation (RES-*, SUG-*): Required fields, immutability, JSON handling
- Service tests (SVC-*): Constructor injection, method stubs, debug mode
- Configuration tests (CFG-*): File existence, required keys, valid values

**TDD Note:** Tests will fail until implementation is complete. This is expected behavior.

**Handoff to Developer:** All tests are ready. Implementation should make tests pass.

Status: `Tests Coverage In Progress` → `To Develop`
