# FEAT-031: Unified AI Prompt + Context Management

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`High`

## Description
Refactor AI calls into a single consolidated request that returns JSON for all indicators plus an overall SEO summary. Add prompt‑size safeguards for model context windows.

## User Story
As a user, I want faster AI analysis and a clear overall SEO summary without errors from oversized prompts.

## Acceptance Criteria
- [ ] Single AI request for all indicators.
- [ ] Strict JSON response parsing.
- [ ] Context window checks + truncation strategy.
- [ ] Overall SEO summary displayed below results.

## Notes
- Source: user ToDo (unified prompt + context handling).

## Comments

### SpecSystemAnalyst (2026-03-05)
**Task Breakdown Complete:** 17 tasks created across 5 phases.

**Summary:**
- Phase 1 (Core Services): 4 tasks - ContextWindowManager, TruncationService, UnifiedPromptService, ResponseParser
- Phase 2 (AI Integration): 4 tasks - OpenRouterClient, AIAnalysisService, ModelSelectionService, RetryPolicy
- Phase 3 (Infrastructure): 3 tasks - Migrations, Model updates, Cache service
- Phase 4 (Integration): 3 tasks - AnalysisRunnerService, Model API, Frontend picker
- Phase 5 (Testing): 3 tasks - Unit tests, Integration tests, Benchmarks

**Total Estimated Effort:** ~48 hours

**Status:** SPEC-031 and FEAT-031 updated to "To Prioritize"

### Architect (2026-02-28)
**Specification Complete:** Created SPEC-031.md with full technical design.

**Key Design Decisions:**
1. **Unified Architecture**: Single AI call replaces 17+ individual calls (18-20x latency reduction)
2. **Context Window Management**: Intelligent truncation strategies with model fallback
3. **Strict JSON Validation**: Schema-validated responses with graceful error handling
4. **Free-Tier Models**: OpenRouter integration with multiple free models (Gemini 2.0 Flash, Llama 3, Mistral)
5. **Graceful Degradation**: Handles all failure modes (timeout, malformed response, rate limits)

**Risk Assessment: Confirmed HIGH** - AI integration complexity, response validation, error handling.

**Architecture Components:**
- `UnifiedPromptService` - Builds consolidated prompt with all 17 indicators
- `ContextWindowManager` - Tracks token counts and model limits
- `TruncationService` - Applies progressive truncation strategies
- `AIAnalysisService` - Executes unified AI request
- `ModelSelectionService` - Chooses optimal model based on content size

**Task Breakdown:** 17 tasks across 5 phases (see SPEC-031.md Section 12).

**Dependencies:**
- FEAT-018 (indicator structure and analysis flow)
- FEAT-026 (model selection and pricing display - optional)

**Handoff to SpecSystemAnalyst:** Ready for task breakdown.

---
*Created by Lead from individual user request.*