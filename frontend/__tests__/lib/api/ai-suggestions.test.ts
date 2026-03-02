// Tests for AI Suggestions API client
// frontend/__tests__/lib/api/ai-suggestions.test.ts

import {
  getAISuggestion,
  getAISuggestionWithDebug,
  RateLimitError,
  NetworkError,
  APIError,
  isRateLimitError,
  isNetworkError,
  isAPIError,
  getIndicatorLabel,
} from '@/lib/api/ai-suggestions';

import type { SuggestionResult, DebugSuggestionResult } from '@/lib/types/ai-suggestions';

// Mock fetch globally
const mockFetch = jest.fn();
global.fetch = mockFetch;

describe('AI Suggestions API', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================
  // Success Cases
  // ============================================

  describe('getAISuggestion - Success Cases', () => {
    const mockSuggestionResult: SuggestionResult = {
      indicator: 'title',
      score: 75,
      issues: ['Title is too generic', 'Missing target keyword'],
      suggestions: [
        {
          type: 'rewrite',
          text: 'Best Widgets 2026 | Premium Quality Widgets',
          reason: 'Includes target keyword and value proposition',
        },
      ],
      best_practices: ['Keep title under 60 characters'],
      provider: 'openrouter',
      model: 'llama-3.1-8b-instruct',
      generated_at: '2026-03-02T10:00:00Z',
    };

    it('AC-001: returns valid SuggestionResult on successful response', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockSuggestionResult }),
      });

      const result = await getAISuggestion('title', { title: 'Test Page' });

      expect(mockFetch).toHaveBeenCalledWith('/api/v1/ai/suggestions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ indicator: 'title', context: { title: 'Test Page' } }),
      });

      expect(result).toEqual(mockSuggestionResult);
      expect(result.score).toBe(75);
      expect(result.issues).toHaveLength(2);
      expect(result.suggestions).toHaveLength(1);
    });

    it('AC-006: includes provider option in request body', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockSuggestionResult }),
      });

      await getAISuggestion('title', { title: 'Test' }, { provider: 'openrouter' });

      expect(JSON.parse(mockFetch.mock.calls[0][1].body)).toEqual({
        indicator: 'title',
        context: { title: 'Test' },
        provider: 'openrouter',
      });
    });

    it('AC-009: handles empty response object', async () => {
      const emptyResult: SuggestionResult = {
        indicator: 'title',
        score: 0,
        issues: [],
        suggestions: [],
        best_practices: [],
        provider: 'static_fallback',
        model: null,
        generated_at: '2026-03-02T10:00:00Z',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: emptyResult }),
      });

      const result = await getAISuggestion('title', {});

      expect(result.score).toBe(0);
      expect(result.issues).toEqual([]);
      expect(result.suggestions).toEqual([]);
    });

    it('AC-010: handles fallback response with fallback_reason', async () => {
      const fallbackResult: SuggestionResult = {
        indicator: 'title',
        score: 0,
        issues: [],
        suggestions: [],
        best_practices: ['Keep title under 60 characters'],
        provider: 'static_fallback',
        model: null,
        generated_at: '2026-03-02T10:00:00Z',
        fallback_reason: 'AI provider timeout',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: fallbackResult }),
      });

      const result = await getAISuggestion('title', {});

      expect(result.fallback_reason).toBe('AI provider timeout');
      expect(result.provider).toBe('static_fallback');
    });

    it('AC-014: handles large context (5000 chars)', async () => {
      const largeContext = {
        content: 'x'.repeat(5000),
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockSuggestionResult }),
      });

      const result = await getAISuggestion('title', largeContext);

      expect(result).toBeDefined();
      const body = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(body.context.content.length).toBe(5000);
    });

    it('AC-015: handles special characters and Unicode', async () => {
      const unicodeContext = {
        title: '测试标题 🎉 Test Title',
        content: '日本語コンテンツ ñ é ü',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockSuggestionResult }),
      });

      const result = await getAISuggestion('title', unicodeContext);

      expect(result).toBeDefined();
    });

    it('AC-016: handles null context values', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockSuggestionResult }),
      });

      await getAISuggestion('title', { title: null, content: 'test' });

      const body = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(body.context.title).toBeNull();
    });

    it('AC-017: strips undefined values from request body', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockSuggestionResult }),
      });

      await getAISuggestion('title', { title: 'Test', extra: undefined });

      const body = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(body.context).toEqual({ title: 'Test' });
      expect('extra' in body.context).toBe(false);
    });
  });

  // ============================================
  // Error Cases
  // ============================================

  describe('getAISuggestion - Error Cases', () => {
    it('AC-002: handles error response with message', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 400,
        json: () =>
          Promise.resolve({
            success: false,
            error: { code: 'VALIDATION_ERROR', message: 'Invalid indicator type' },
          }),
      });

      await expect(getAISuggestion('title', {})).rejects.toThrow('Invalid indicator type');
    });

    it('AC-003: handles 429 rate limit response', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        headers: new Headers({
          'Retry-After': '30',
        }),
        json: () =>
          Promise.resolve({
            success: false,
            error: {
              code: 'RATE_LIMIT_EXCEEDED',
              message: 'Rate limit exceeded. Please try again later.',
              retry_after: 30,
            },
          }),
      });

      try {
        await getAISuggestion('title', {});
        // Should have thrown
        expect(true).toBe(false);
      } catch (error) {
        expect(error).toBeInstanceOf(RateLimitError);
        expect((error as RateLimitError).retryAfter).toBe(30);
      }
    });

    it('AC-004: handles 500 server error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: () =>
          Promise.resolve({
            success: false,
            error: { code: 'INTERNAL_ERROR', message: 'Internal server error' },
          }),
      });

      await expect(getAISuggestion('title', {})).rejects.toThrow('Internal server error');
    });

    it('AC-007: handles network timeout', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      await expect(getAISuggestion('title', {})).rejects.toThrow();
    });

    it('AC-008: handles malformed JSON response', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.reject(new Error('Invalid JSON')),
      });

      await expect(getAISuggestion('title', {})).rejects.toThrow('Failed to parse API response');
    });
  });

  // ============================================
  // Debug Mode
  // ============================================

  describe('getAISuggestionWithDebug', () => {
    const mockDebugResult: DebugSuggestionResult = {
      indicator: 'title',
      score: 75,
      issues: ['Issue 1'],
      suggestions: [{ type: 'rewrite', text: 'New Title', reason: 'Better for SEO' }],
      best_practices: ['Best practice 1'],
      provider: 'openrouter',
      model: 'llama-3.1-8b-instruct',
      generated_at: '2026-03-02T10:00:00Z',
      debug: {
        prompt: 'Analyze this title...',
        raw_response: '{"score": 75, ...}',
        tokens_used: 245,
        latency_ms: 1250,
        provider: 'openrouter',
        model: 'llama-3.1-8b-instruct',
      },
    };

    it('AC-005: returns DebugSuggestionResult with debug info', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockDebugResult }),
      });

      const result = await getAISuggestionWithDebug('title', { title: 'Test' });

      expect(result.debug).toBeDefined();
      expect(result.debug.prompt).toBe('Analyze this title...');
      expect(result.debug.tokens_used).toBe(245);
      expect(result.debug.latency_ms).toBe(1250);
    });

    it('includes provider option in debug request', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockDebugResult }),
      });

      await getAISuggestionWithDebug('title', { title: 'Test' }, { provider: 'vercel' });

      const body = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(body.debug).toBe(true);
    });
  });

  // ============================================
  // Type Validation
  // ============================================

  describe('Type validation', () => {
    it('AC-011: SuggestionResult has all required fields', async () => {
      const mockResult: SuggestionResult = {
        indicator: 'title',
        score: 75,
        issues: [],
        suggestions: [],
        best_practices: [],
        provider: 'openrouter',
        model: 'llama-3.1',
        generated_at: '2026-03-02T10:00:00Z',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockResult }),
      });

      const result = await getAISuggestion('title', {});

      expect(result.indicator).toBeDefined();
      expect(result.score).toBeDefined();
      expect(result.issues).toBeDefined();
      expect(result.suggestions).toBeDefined();
      expect(result.best_practices).toBeDefined();
      expect(result.provider).toBeDefined();
      expect(result.generated_at).toBeDefined();
    });

    it('AC-012: Suggestion has type, text, and reason fields', async () => {
      const mockResult: SuggestionResult = {
        indicator: 'title',
        score: 75,
        issues: [],
        suggestions: [
          { type: 'rewrite', text: 'New Title', reason: 'Better' },
          { type: 'improve', text: 'Add keyword', reason: 'SEO benefit' },
        ],
        best_practices: [],
        provider: 'openrouter',
        model: null,
        generated_at: '2026-03-02T10:00:00Z',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockResult }),
      });

      const result = await getAISuggestion('title', {});

      expect(result.suggestions[0].type).toBe('rewrite');
      expect(result.suggestions[0].text).toBe('New Title');
      expect(result.suggestions[0].reason).toBe('Better');
    });

    it('AC-013: DebugInfo has all required fields', async () => {
      const mockDebugResult: DebugSuggestionResult = {
        indicator: 'title',
        score: 75,
        issues: [],
        suggestions: [],
        best_practices: [],
        provider: 'openrouter',
        model: null,
        generated_at: '2026-03-02T10:00:00Z',
        debug: {
          prompt: 'Test prompt',
          raw_response: '{"test": true}',
          tokens_used: 100,
          latency_ms: 500,
          provider: 'openrouter',
          model: null,
        },
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, data: mockDebugResult }),
      });

      const result = await getAISuggestionWithDebug('title', {});

      expect(result.debug.prompt).toBeDefined();
      expect(result.debug.raw_response).toBeDefined();
      expect(result.debug.tokens_used).toBeDefined();
      expect(result.debug.latency_ms).toBeDefined();
    });
  });

  // ============================================
  // Error Type Guards
  // ============================================

  describe('Error type guards', () => {
    it('isRateLimitError returns true for RateLimitError', () => {
      const error = new RateLimitError('Rate limited', 30);
      expect(isRateLimitError(error)).toBe(true);
      expect(isRateLimitError(new Error('test'))).toBe(false);
    });

    it('isNetworkError returns true for NetworkError', () => {
      const error = new NetworkError('Network failed');
      expect(isNetworkError(error)).toBe(true);
      expect(isNetworkError(new Error('test'))).toBe(false);
    });

    it('isAPIError returns true for APIError', () => {
      const error = new APIError('API error', 'TEST_CODE');
      expect(isAPIError(error)).toBe(true);
      expect(isAPIError(new Error('test'))).toBe(false);
    });
  });

  // ============================================
  // Helper Functions
  // ============================================

  describe('getIndicatorLabel', () => {
    it('returns correct label for title indicator', () => {
      expect(getIndicatorLabel('title')).toBe('Title Tag');
    });

    it('returns correct label for meta_description indicator', () => {
      expect(getIndicatorLabel('meta_description')).toBe('Meta Description');
    });

    it('returns correct label for all supported indicators', () => {
      const indicators = [
        'title',
        'meta_description',
        'robots',
        'sitemap',
        'headings',
        'image_alt',
        'filenames',
        'url',
        'links',
        '404',
      ] as const;

      indicators.forEach((indicator) => {
        expect(getIndicatorLabel(indicator)).toBeDefined();
        expect(typeof getIndicatorLabel(indicator)).toBe('string');
      });
    });
  });

  // ============================================
  // Edge Cases
  // ============================================

  describe('Edge cases', () => {
    it('handles response without success wrapper', async () => {
      const mockResult: SuggestionResult = {
        indicator: 'title',
        score: 75,
        issues: [],
        suggestions: [],
        best_practices: [],
        provider: 'openrouter',
        model: null,
        generated_at: '2026-03-02T10:00:00Z',
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResult), // No success wrapper
      });

      const result = await getAISuggestion('title', {});

      expect(result).toEqual(mockResult);
    });

    it('handles rate limit without Retry-After header', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        headers: new Headers(),
        json: () =>
          Promise.resolve({
            success: false,
            error: { code: 'RATE_LIMIT_EXCEEDED', message: 'Rate limited' },
          }),
      });

      try {
        await getAISuggestion('title', {});
      } catch (error) {
        expect((error as RateLimitError).retryAfter).toBe(60); // Default
      }
    });

    it('handles error response without error object', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ message: 'Server error' }),
      });

      await expect(getAISuggestion('title', {})).rejects.toThrow('Server error');
    });

    it('handles error response with null json', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: () => Promise.resolve(null),
      });

      await expect(getAISuggestion('title', {})).rejects.toThrow('HTTP error: 500');
    });
  });
});
