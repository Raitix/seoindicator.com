# TASK-21-1: Create AI Service Structure and Interfaces

## STATUS
`To Prioritize`

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

(No entries yet)

---
*Created by SpecSystemAnalyst on 2026-03-01*
