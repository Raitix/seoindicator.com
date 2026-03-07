# TASK-34-3: AIQueryEngine Service

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create the `AIQueryEngine` service responsible for querying AI platforms via OpenRouter and parsing responses for brand mentions.

---

## Acceptance Criteria

- [ ] `AIQueryEngine` class created at `app/Services/AIVisibility/AIQueryEngine.php`
- [ ] Platform configuration defined
  - [ ] `chatgpt` with free model `google/gemini-2.0-flash-exp:free`
  - [ ] `perplexity` with free model `google/gemini-2.0-flash-exp:free`
  - [ ] `claude` disabled for MVP (future phase)

- [ ] Query templates defined
  - [ ] `comparison`: "What are the best {category} tools for {audience}?"
  - [ ] `recommendation`: "Can you recommend a good {category} tool?"
  - [ ] `feature`: "How does {brand} compare to {competitor}?"
  - [ ] `use_case`: "What tool should I use to {use_case}?"

- [ ] `query()` method implemented
  - [ ] Accepts: `platform`, `query`, `useFreeModel = true`
  - [ ] Returns `AIQueryResult` DTO with: response, model, tokens, latency
  - [ ] Uses OpenRouter client from FEAT-031
  - [ ] Handles API errors gracefully
  - [ ] Tracks response latency in milliseconds

- [ ] `parseMentions()` method implemented
  - [ ] Accepts: `response` (string), `brands` (array)
  - [ ] Returns array of mentions with: brand, position, context
  - [ ] Extracts 100 characters of context around each mention
  - [ ] Case-insensitive matching

- [ ] `analyzeSentiment()` method implemented
  - [ ] Accepts: `context` (string)
  - [ ] Returns: `positive`, `neutral`, `negative`, or `mixed`
  - [ ] Uses simple keyword-based sentiment (free approach)
  - [ ] Can be enhanced later with ML model

- [ ] Error handling
  - [ ] Catches API rate limit exceptions
  - [ ] Catches timeout exceptions
  - [ ] Logs errors with context
  - [ ] Returns structured error result

- [ ] Unit tests created
  - [ ] Test query method with mocked OpenRouter
  - [ ] Test mention parsing with sample responses
  - [ ] Test sentiment analysis
  - [ ] Test error handling

---

## Dependencies
- TASK-34-2 (Models)
- FEAT-031 (AI Infrastructure / OpenRouter Client)

---

## Effort Estimate
**4 hours**

---

## Technical Notes

- Reuse OpenRouter client from FEAT-031
- Consider creating `AIQueryResultDTO` for structured return types
- Sentiment analysis can start simple:
  - Positive words: excellent, great, best, recommended, top
  - Negative words: poor, bad, worst, avoid, limited
- Cache results using Laravel Cache with 7-day TTL

---

*Created by SpecSystemAnalyst on 2026-03-07*
