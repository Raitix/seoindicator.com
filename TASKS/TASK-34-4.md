# TASK-34-4: MentionParser Service

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create the `MentionParser` service for extracting brand mentions from AI responses, determining mention types, and extracting context.

---

## Acceptance Criteria

- [ ] `MentionParser` class created at `app/Services/AIVisibility/MentionParser.php`

- [ ] `extractMentions()` method implemented
  - [ ] Accepts: `response` (string), `targetBrands` (array)
  - [ ] Returns array of: `{ brand, position, context, type }`
  - [ ] Uses regex with word boundaries for exact matching
  - [ ] Case-insensitive matching
  - [ ] Handles multiple mentions of same brand

- [ ] `extractContext()` method implemented
  - [ ] Accepts: `text`, `position`, `radius` (default: 100)
  - [ ] Returns surrounding text around mention
  - [ ] Handles edge cases (start/end of string)
  - [ ] Preserves original text, doesn't truncate words

- [ ] `detectMentionType()` method implemented
  - [ ] Accepts: `context` (string)
  - [ ] Returns: `list`, `comparison`, `recommendation`, `neutral`
  - [ ] Pattern detection:
    - "top 10", "best", "including" → list
    - "vs", "compared to", "better than" → comparison
    - "I recommend", "you should use" → recommendation
    - Default → neutral

- [ ] `extractCompetitorMentions()` method implemented
  - [ ] Accepts: `response`, `competitors` (array)
  - [ ] Returns array of competitor mentions with position and context
  - [ ] Same logic as brand mention extraction

- [ ] Edge cases handled
  - [ ] Empty response text
  - [ ] No mentions found
  - [ ] Brand name with special characters
  - [ ] Unicode text support
  - [ ] Very long responses (>10KB)

- [ ] Unit tests created
  - [ ] Test brand mention extraction
  - [ ] Test context extraction at boundaries
  - [ ] Test mention type detection
  - [ ] Test competitor mention extraction
  - [ ] Test edge cases

---

## Dependencies
- TASK-34-3 (AIQueryEngine - for integration)

---

## Effort Estimate
**3 hours**

---

## Technical Notes

- Use `preg_match_all()` with `PREG_OFFSET_CAPTURE` for position tracking
- Escape brand names with `preg_quote()` before regex
- Consider performance for large texts (streaming vs loading all)
- Can be made more sophisticated later with NLP libraries

---

*Created by SpecSystemAnalyst on 2026-03-07*
