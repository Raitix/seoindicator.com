# Changelog

All notable changes to the SEOIndicator project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added - AI Suggestions Feature (FEAT-021)

#### Core Infrastructure
- AI Suggestion Service with provider abstraction
- OpenRouter API provider for AI-powered SEO suggestions
- Static Fallback Provider for rule-based suggestions
- Prompt Templates for all 10 SEO indicators
- AI Suggestion API endpoint (`POST /api/v1/ai/suggestions`)
- Caching layer for identical requests (24-hour TTL)
- Debug mode with detailed logging
- Database migrations for `ai_suggestion_logs` and `ai_suggestions_cache` tables

#### SEO Indicators Supported
- Title tag optimization
- Meta description analysis
- Robots.txt validation
- Sitemap structure analysis
- Heading hierarchy (H1-H6)
- Image alt text suggestions
- Filename optimization
- URL structure analysis
- Link quality assessment
- 404 error handling suggestions

#### Security Features
- API key protection (encrypted in environment)
- Input sanitization (HTML stripping, length limits)
- Rate limiting (per-user: 20/hour, per-IP: 10/hour)
- PII protection (no personal data sent to AI)
- Response validation before client delivery
- Error message sanitization

#### Performance
- Static fallback responds within 100ms
- AI provider timeout: 30 seconds
- Caching reduces duplicate API calls
- Memory-efficient request handling

### Configuration

#### Environment Variables
```env
AI_PROVIDER=openrouter
AI_DEBUG=false
OPENROUTER_API_KEY=sk-or-...
OPENROUTER_MODEL=meta-llama/llama-3.1-8b-instruct:free
VERCEL_AI_API_KEY=...
VERCEL_AI_MODEL=gpt-4o-mini
AI_FALLBACK_ENABLED=true
AI_CACHE_TTL_HOURS=24
AI_TIMEOUT_SECONDS=30
AI_MAX_RETRIES=2
```

#### Free AI Models Available
| Model | Max Tokens | Notes |
|-------|------------|-------|
| `meta-llama/llama-3.1-8b-instruct:free` | 128K | Default |
| `google/gemma-2-9b-it:free` | 8K | Alternative |
| `qwen/qwen-2-7b-instruct:free` | 32K | Alternative |

### Tests

#### Backend Tests (236+ tests)
- Integration: `AISuggestionFlowTest.php` (38 tests)
- Security: `AISuggestionSecurityTest.php` (43 tests)
- Performance: `AISuggestionPerformanceTest.php` (28 tests)
- Feature: `AISuggestionControllerTest.php` (55 tests)
- Unit: Multiple test files for services, providers, cache, rate limiter

#### Frontend Tests
- Component tests: `AISuggestionButton.test.tsx` (15+ tests)
- Component tests: `AISuggestionPanel.test.tsx`
- API client tests: `ai-suggestions.test.ts` (17+ tests)
- E2E tests: `ai-suggestions.spec.ts` (20+ tests)

#### Test Coverage
| Component | Target | 
|-----------|--------|
| AISuggestionService | 90% |
| AISuggestionController | 95% |
| StaticFallbackProvider | 95% |
| OpenRouterProvider | 85% |
| AISuggestionCache | 85% |
| **Overall** | **85%** |

### Documentation
- API documentation in DOCUMENTATION.md
- Environment variables documented in SPEC-021.md
- Test documentation in TESTS/TEST-21-12.md
- User guide for AI suggestion features
- Developer documentation for provider integration

### Tasks Completed
- TASK-21-1: AI Service Structure and Interfaces
- TASK-21-2: OpenRouter Provider Implementation
- TASK-21-3: Static Fallback Provider
- TASK-21-4: Prompt Templates for All Indicators
- TASK-21-5: AI Suggestion API Endpoint
- TASK-21-6: Caching Layer
- TASK-21-7: Debug Mode and Logging
- TASK-21-8: Database Migrations
- TASK-21-9: Vercel AI Provider
- TASK-21-10: Rate Limiting
- TASK-21-11: Frontend Integration with Tools
- TASK-21-12: Documentation and Tests

---

## Version History

| Version | Date | Description |
|---------|------|-------------|
| Unreleased | 2026-03 | AI Suggestions feature (FEAT-021) |

---

## Migration Guide

### Upgrading to Include AI Suggestions

1. **Run migrations:**
   ```bash
   php artisan migrate
   ```

2. **Add environment variables:**
   ```env
   AI_PROVIDER=openrouter
   OPENROUTER_API_KEY=sk-or-your-key
   AI_FALLBACK_ENABLED=true
   ```

3. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Verify installation:**
   ```bash
   php artisan test --group=ai
   ```

### Frontend Integration

1. **Import components:**
   ```tsx
   import { AISuggestionButton } from '@/components/tools/AISuggestionButton';
   import { AISuggestionPanel } from '@/components/tools/AISuggestionPanel';
   ```

2. **Use in tool components:**
   ```tsx
   <AISuggestionButton
     indicator="title"
     context={{ title: pageTitle }}
     onSuggestion={handleSuggestion}
   />
   ```

---

*Last updated: 2026-03-04*
