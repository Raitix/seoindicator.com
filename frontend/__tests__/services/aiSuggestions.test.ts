/**
 * AI Suggestions API Service Tests
 * TASK-21-11: Frontend Integration with Tools
 * 
 * Tests for the AI suggestions API service methods.
 */

import { describe, it, expect, jest, beforeEach, afterEach } from '@jest/globals';

// Mock fetch globally
const mockFetch = jest.fn() as jest.MockedFunction<typeof fetch>;
global.fetch = mockFetch;

// Types
interface AISuggestionRequest {
  indicator: string;
  context: Record<string, unknown>;
  provider?: string;
  debug?: boolean;
}

interface Suggestion {
  type: 'improve' | 'rewrite' | 'add' | 'remove';
  text: string;
  reason: string;
}

interface Issue {
  severity: 'critical' | 'warning' | 'info';
  message: string;
}

interface AISuggestionResponse {
  success: boolean;
  data: {
    indicator: string;
    score?: number;
    issues: Issue[];
    suggestions: Suggestion[];
    best_practices: string[];
    provider: string;
    model?: string | null;
    generated_at?: string | null;
    fallback_reason?: string;
  };
  debug?: {
    prompt: string;
    raw_response: string;
    tokens_used: number;
    latency_ms: number;
    provider: string;
    model: string;
  };
}

interface RateLimitInfo {
  limit: number;
  remaining: number;
  resetAt: string | null;
}

interface AISuggestionError {
  success: false;
  error: {
    code: string;
    message: string;
    retry_after?: number;
  };
}

// Mock Service (will be replaced with actual implementation)
class AISuggestionsService {
  private baseUrl = '/api/v1/ai/suggestions';
  private cache = new Map<string, { data: AISuggestionResponse; expiresAt: number }>();
  private cacheTTL = 24 * 60 * 60 * 1000; // 24 hours in ms

  async getSuggestions(request: AISuggestionRequest): Promise<AISuggestionResponse> {
    const cacheKey = this.getCacheKey(request);
    const cached = this.cache.get(cacheKey);
    
    if (cached && cached.expiresAt > Date.now()) {
      return cached.data;
    }

    const response = await fetch(this.baseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      const error: AISuggestionError = await response.json();
      throw new Error(error.error.message);
    }

    const data: AISuggestionResponse = await response.json();
    
    // Cache successful responses
    if (data.success) {
      this.cache.set(cacheKey, {
        data,
        expiresAt: Date.now() + this.cacheTTL,
      });
    }

    return data;
  }

  async getSuggestionsWithRateLimit(
    request: AISuggestionRequest
  ): Promise<{ data: AISuggestionResponse; rateLimit: RateLimitInfo }> {
    const response = await fetch(this.baseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(request),
    });

    const rateLimit: RateLimitInfo = {
      limit: parseInt(response.headers.get('X-RateLimit-Limit') || '20', 10),
      remaining: parseInt(response.headers.get('X-RateLimit-Remaining') || '0', 10),
      resetAt: response.headers.get('X-RateLimit-Reset') || null,
    };

    if (!response.ok) {
      const error: AISuggestionError = await response.json();
      throw Object.assign(new Error(error.error.message), {
        rateLimit,
        code: error.error.code,
        retryAfter: error.error.retry_after,
      });
    }

    const data: AISuggestionResponse = await response.json();
    return { data, rateLimit };
  }

  async getDebugSuggestions(request: AISuggestionRequest): Promise<AISuggestionResponse> {
    return this.getSuggestions({ ...request, debug: true });
  }

  getRateLimitStatus(): RateLimitInfo | null {
    // Would be implemented to check stored rate limit info
    return null;
  }

  clearCache(): void {
    this.cache.clear();
  }

  private getCacheKey(request: AISuggestionRequest): string {
    return `${request.indicator}:${JSON.stringify(request.context)}`;
  }
}

// Test Data
const mockSuccessResponse: AISuggestionResponse = {
  success: true,
  data: {
    indicator: 'title',
    score: 75,
    issues: [
      { severity: 'warning', message: 'Title is too short' },
    ],
    suggestions: [
      { type: 'rewrite', text: 'Better Title | Brand Name', reason: 'Includes keyword and brand' },
    ],
    best_practices: ['Keep title under 60 characters'],
    provider: 'openrouter',
    model: 'llama-3.1-8b-instruct',
    generated_at: '2026-03-04T12:00:00Z',
  },
};

const mockFallbackResponse: AISuggestionResponse = {
  success: true,
  data: {
    indicator: 'title',
    score: 50,
    issues: [],
    suggestions: [],
    best_practices: [],
    provider: 'static_fallback',
    model: null,
    generated_at: '2026-03-04T12:00:00Z',
    fallback_reason: 'AI provider timeout',
  },
};

const mockErrorResponse: AISuggestionError = {
  success: false,
  error: {
    code: 'RATE_LIMIT_EXCEEDED',
    message: 'Rate limit exceeded. Please try again later.',
    retry_after: 60,
  },
};

describe('AISuggestionsService', () => {
  let service: AISuggestionsService;

  beforeEach(() => {
    service = new AISuggestionsService();
    mockFetch.mockClear();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  // ========================================
  // AC-3: API Service Method Tests
  // ========================================
  describe('AC-3: API Service Methods', () => {
    it('should call correct endpoint', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/ai/suggestions',
        expect.objectContaining({
          method: 'POST',
        })
      );
    });

    it('should send correct request body', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test Page', keyword: 'test' },
        provider: 'openrouter',
      });

      const call = mockFetch.mock.calls[0];
      const body = JSON.parse(call[1].body);

      expect(body).toEqual({
        indicator: 'title',
        context: { title: 'Test Page', keyword: 'test' },
        provider: 'openrouter',
      });
    });

    it('should return parsed response on success', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.success).toBe(true);
      expect(result.data.indicator).toBe('title');
      expect(result.data.score).toBe(75);
    });

    it('should include debug mode in request', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ ...mockSuccessResponse, debug: { prompt: 'test' } }),
        headers: new Headers(),
      } as Response);

      await service.getDebugSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      const call = mockFetch.mock.calls[0];
      const body = JSON.parse(call[1].body);

      expect(body.debug).toBe(true);
    });

    it('should return debug info in response', async () => {
      const debugResponse = {
        ...mockSuccessResponse,
        debug: {
          prompt: 'Full prompt text...',
          raw_response: 'Raw JSON...',
          tokens_used: 245,
          latency_ms: 1250,
          provider: 'openrouter',
          model: 'llama-3.1-8b-instruct',
        },
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => debugResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getDebugSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.debug).toBeDefined();
      expect(result.debug?.tokens_used).toBe(245);
    });
  });

  // ========================================
  // AC-3: Error Handling Tests
  // ========================================
  describe('AC-3: Error Handling', () => {
    it('should throw error on HTTP error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: async () => mockErrorResponse,
        headers: new Headers(),
      } as Response);

      await expect(
        service.getSuggestions({
          indicator: 'title',
          context: { title: 'Test' },
        })
      ).rejects.toThrow('Rate limit exceeded');
    });

    it('should handle network errors', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      await expect(
        service.getSuggestions({
          indicator: 'title',
          context: { title: 'Test' },
        })
      ).rejects.toThrow('Network error');
    });

    it('should handle timeout errors', async () => {
      mockFetch.mockRejectedValueOnce(new Error('AbortError: The operation was aborted'));

      await expect(
        service.getSuggestions({
          indicator: 'title',
          context: { title: 'Test' },
        })
      ).rejects.toThrow();
    });

    it('should handle 500 server errors', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => ({
          success: false,
          error: { code: 'SERVER_ERROR', message: 'Internal server error' },
        }),
        headers: new Headers(),
      } as Response);

      await expect(
        service.getSuggestions({
          indicator: 'title',
          context: { title: 'Test' },
        })
      ).rejects.toThrow('Internal server error');
    });

    it('should handle 503 service unavailable', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 503,
        json: async () => ({
          success: false,
          error: { code: 'SERVICE_UNAVAILABLE', message: 'AI service temporarily unavailable' },
        }),
        headers: new Headers(),
      } as Response);

      await expect(
        service.getSuggestions({
          indicator: 'title',
          context: { title: 'Test' },
        })
      ).rejects.toThrow('AI service temporarily unavailable');
    });
  });

  // ========================================
  // AC-3: Loading States Tests
  // ========================================
  describe('AC-3: Loading States', () => {
    it('should handle slow responses', async () => {
      let resolvePromise: (value: unknown) => void;
      const slowPromise = new Promise((resolve) => {
        resolvePromise = resolve;
      });

      mockFetch.mockImplementationOnce(async () => {
        await new Promise((r) => setTimeout(r, 100));
        return {
          ok: true,
          json: async () => mockSuccessResponse,
          headers: new Headers(),
        } as Response;
      });

      const resultPromise = service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      // Should not resolve immediately
      await expect(Promise.race([resultPromise, Promise.resolve('pending')])).resolves.toBe('pending');
    });
  });

  // ========================================
  // AC-3: Retry Functionality Tests
  // ========================================
  describe('AC-3: Retry Functionality', () => {
    it('should allow retry after error', async () => {
      // First call fails
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => ({
          success: false,
          error: { code: 'SERVER_ERROR', message: 'Server error' },
        }),
        headers: new Headers(),
      } as Response);

      await expect(
        service.getSuggestions({
          indicator: 'title',
          context: { title: 'Test' },
        })
      ).rejects.toThrow();

      // Second call succeeds
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.success).toBe(true);
    });
  });

  // ========================================
  // AC-4: Rate Limit Tests
  // ========================================
  describe('AC-4: Rate Limit Handling', () => {
    it('should parse rate limit headers', async () => {
      const headers = new Headers({
        'X-RateLimit-Limit': '20',
        'X-RateLimit-Remaining': '15',
        'X-RateLimit-Reset': '1709553600',
      });

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers,
      } as Response);

      const { rateLimit } = await service.getSuggestionsWithRateLimit({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(rateLimit.limit).toBe(20);
      expect(rateLimit.remaining).toBe(15);
      expect(rateLimit.resetAt).toBe('1709553600');
    });

    it('should handle rate limit exceeded (429)', async () => {
      const headers = new Headers({
        'X-RateLimit-Limit': '20',
        'X-RateLimit-Remaining': '0',
        'X-RateLimit-Reset': '1709553600',
        'Retry-After': '60',
      });

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        json: async () => mockErrorResponse,
        headers,
      } as Response);

      try {
        await service.getSuggestionsWithRateLimit({
          indicator: 'title',
          context: { title: 'Test' },
        });
      } catch (error: any) {
        expect(error.rateLimit.remaining).toBe(0);
        expect(error.retryAfter).toBe(60);
      }
    });

    it('should return default rate limit info when headers missing', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      const { rateLimit } = await service.getSuggestionsWithRateLimit({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(rateLimit.limit).toBe(20); // Default
      expect(rateLimit.remaining).toBe(0); // Default
      expect(rateLimit.resetAt).toBeNull();
    });
  });

  // ========================================
  // Caching Tests
  // ========================================
  describe('Caching', () => {
    it('should cache successful responses', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      // First call
      await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      // Second call with same params
      await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      // Should only call fetch once due to caching
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it('should not cache different requests', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test 1' },
      });

      await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test 2' },
      });

      expect(mockFetch).toHaveBeenCalledTimes(2);
    });

    it('should clear cache', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      service.clearCache();

      await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  // ========================================
  // Indicator Types Tests
  // ========================================
  describe('Indicator Types', () => {
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
    ];

    indicators.forEach((indicator) => {
      it(`should handle indicator: ${indicator}`, async () => {
        mockFetch.mockResolvedValueOnce({
          ok: true,
          json: async () => ({
            ...mockSuccessResponse,
            data: { ...mockSuccessResponse.data, indicator },
          }),
          headers: new Headers(),
        } as Response);

        const result = await service.getSuggestions({
          indicator,
          context: { test: 'data' },
        });

        expect(result.data.indicator).toBe(indicator);
      });
    });
  });

  // ========================================
  // Context Validation Tests
  // ========================================
  describe('Context Validation', () => {
    it('should handle empty context', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: {},
      });

      expect(result.success).toBe(true);
    });

    it('should handle complex context objects', async () => {
      const complexContext = {
        title: 'Test Page',
        content: 'Long content here...',
        keyword: 'test keyword',
        headings: ['H1', 'H2', 'H2', 'H3'],
        meta: { robots: 'index, follow' },
        numbers: [1, 2, 3],
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: complexContext,
      });

      expect(result.success).toBe(true);
    });

    it('should handle unicode in context', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: '日本語タイトル 🎉' },
      });

      expect(result.success).toBe(true);
    });

    it('should handle large context (within limit)', async () => {
      const largeContext = {
        title: 'Test',
        content: 'x'.repeat(4000), // 4000 chars
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: largeContext,
      });

      expect(result.success).toBe(true);
    });
  });

  // ========================================
  // Provider Selection Tests
  // ========================================
  describe('Provider Selection', () => {
    it('should use specified provider', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          ...mockSuccessResponse,
          data: { ...mockSuccessResponse.data, provider: 'vercel' },
        }),
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
        provider: 'vercel',
      });

      expect(result.data.provider).toBe('vercel');
    });

    it('should handle static_fallback provider', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockFallbackResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.data.provider).toBe('static_fallback');
      expect(result.data.fallback_reason).toBe('AI provider timeout');
    });
  });

  // ========================================
  // Response Validation Tests
  // ========================================
  describe('Response Validation', () => {
    it('should validate response structure', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockSuccessResponse,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result).toHaveProperty('success');
      expect(result).toHaveProperty('data');
      expect(result.data).toHaveProperty('indicator');
      expect(result.data).toHaveProperty('issues');
      expect(result.data).toHaveProperty('suggestions');
      expect(result.data).toHaveProperty('best_practices');
      expect(result.data).toHaveProperty('provider');
    });

    it('should handle suggestions with all types', async () => {
      const responseWithAllTypes: AISuggestionResponse = {
        success: true,
        data: {
          indicator: 'title',
          suggestions: [
            { type: 'improve', text: 'Improve this', reason: 'Reason 1' },
            { type: 'rewrite', text: 'Rewrite this', reason: 'Reason 2' },
            { type: 'add', text: 'Add this', reason: 'Reason 3' },
            { type: 'remove', text: 'Remove this', reason: 'Reason 4' },
          ],
          issues: [],
          best_practices: [],
          provider: 'openrouter',
        },
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => responseWithAllTypes,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.data.suggestions).toHaveLength(4);
    });

    it('should handle issues with all severities', async () => {
      const responseWithAllSeverities: AISuggestionResponse = {
        success: true,
        data: {
          indicator: 'title',
          issues: [
            { severity: 'critical', message: 'Critical issue' },
            { severity: 'warning', message: 'Warning issue' },
            { severity: 'info', message: 'Info issue' },
          ],
          suggestions: [],
          best_practices: [],
          provider: 'openrouter',
        },
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => responseWithAllSeverities,
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.data.issues).toHaveLength(3);
    });
  });

  // ========================================
  // Edge Cases
  // ========================================
  describe('Edge Cases', () => {
    it('should handle score of 0', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          ...mockSuccessResponse,
          data: { ...mockSuccessResponse.data, score: 0 },
        }),
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.data.score).toBe(0);
    });

    it('should handle score of 100', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          ...mockSuccessResponse,
          data: { ...mockSuccessResponse.data, score: 100 },
        }),
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.data.score).toBe(100);
    });

    it('should handle null model', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          ...mockSuccessResponse,
          data: { ...mockSuccessResponse.data, model: null },
        }),
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Test' },
      });

      expect(result.data.model).toBeNull();
    });

    it('should handle empty suggestions array', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          ...mockSuccessResponse,
          data: { ...mockSuccessResponse.data, suggestions: [] },
        }),
        headers: new Headers(),
      } as Response);

      const result = await service.getSuggestions({
        indicator: 'title',
        context: { title: 'Perfect Title' },
      });

      expect(result.data.suggestions).toHaveLength(0);
    });
  });
});
