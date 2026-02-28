# FEAT-021: AI Suggestions via OpenRouter/Vercel Gateway

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Description
Integrate AI suggestions for indicators (title, meta, robots, sitemap, headings, image alt, filenames, URL, links, 404) using OpenRouter or Vercel AI Gateway, with debug logging and fallbacks.

## User Story
As a user, I want AI‑powered suggestions tailored to my site so I can quickly fix issues.

## Acceptance Criteria
- [ ] Configurable AI provider (OpenRouter/Vercel).
- [ ] Per‑indicator prompts + JSON parsing.
- [ ] Debug mode to inspect requests/responses.
- [ ] Fallback static suggestions on failure.

## Notes
- Source: legacy AI integration (user request).

---
## Comments
_Agent log_

### [2026-02-28 10:42 GMT+2] Agent: Architect
Created SPEC-021 with full technical specification.

**Architecture**:
- AISuggestionService as orchestrator
- Provider interface for OpenRouter/Vercel/Static fallback
- 10 indicator prompts with JSON response schemas
- Caching layer (24h TTL) to reduce API calls
- Debug mode for request/response inspection

**Free Tools Compliance**: ✅ OpenRouter provides free models (Llama 3.1, Gemma 2) suitable for this feature.

**Risk Factors** (High):
- External API dependency
- AI response quality variability
- Rate limit considerations

**12 Tasks Defined** across 3 phases:
- Phase 1: Core infrastructure (service, providers, prompts)
- Phase 2: API endpoints, caching, logging
- Phase 3: Vercel provider, rate limiting, frontend integration

Status: `To Break Into Tasks` → SpecSystemAnalyst

---
*Created by Lead from individual user request (legacy migration).*