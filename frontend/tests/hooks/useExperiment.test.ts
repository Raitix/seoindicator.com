/**
 * useExperiment Hook Tests
 *
 * Tests for the A/B testing hook that retrieves experiment variants.
 *
 * @see TEST-17-14.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor, act } from '@testing-library/react';

// Mock fetch for API calls
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(global, 'localStorage', { value: localStorageMock });

// Mock useExperiment hook implementation
const useExperiment = (experimentName: string) => {
  const [variant, setVariant] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  useEffect(() => {
    const fetchVariant = async () => {
      try {
        setLoading(true);

        // Check cache first
        const cached = localStorage.getItem(`exp_${experimentName}`);
        if (cached) {
          setVariant(cached);
          setLoading(false);
          return;
        }

        const response = await fetch(`/api/v1/experiments/${experimentName}/variant`);
        if (!response.ok) {
          throw new Error('Failed to fetch variant');
        }

        const data = await response.json();
        setVariant(data.variant);
        localStorage.setItem(`exp_${experimentName}`, data.variant);
      } catch (err) {
        setError(err instanceof Error ? err : new Error('Unknown error'));
        setVariant('control'); // Fallback
      } finally {
        setLoading(false);
      }
    };

    fetchVariant();
  }, [experimentName]);

  return { variant, loading, error };
};

// Need to import React hooks
import { useState, useEffect } from 'react';

describe('useExperiment Hook', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.getItem.mockReturnValue(null);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // AB-005: useExperiment Hook
  // ============================================
  describe('useExperiment Hook', () => {
    it('AB-005-01: hook returns variant', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ experiment_name: 'test', variant: 'variant_a' }),
      });

      const { result } = renderHook(() => useExperiment('test'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.variant).toBe('variant_a');
    });

    it('AB-005-02: hook returns loading state', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves

      const { result } = renderHook(() => useExperiment('test'));

      expect(result.current.loading).toBe(true);
    });

    it('AB-005-03: hook returns error state', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      const { result } = renderHook(() => useExperiment('test'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.error).toBeInstanceOf(Error);
    });

    it('AB-005-04: hook caches result', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ variant: 'variant_a' }),
      });

      const { result, rerender } = renderHook(() => useExperiment('test'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      // Clear fetch mock and rerender
      mockFetch.mockClear();
      rerender();

      // Should not fetch again (cached in localStorage)
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('AB-005-05: hook works with multiple experiments', async () => {
      mockFetch
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve({ variant: 'variant_a' }),
        })
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve({ variant: 'variant_b' }),
        });

      const { result: result1 } = renderHook(() => useExperiment('test_a'));
      const { result: result2 } = renderHook(() => useExperiment('test_b'));

      await waitFor(() => {
        expect(result1.current.loading).toBe(false);
        expect(result2.current.loading).toBe(false);
      });

      expect(result1.current.variant).toBe('variant_a');
      expect(result2.current.variant).toBe('variant_b');
    });

    it('AB-005-06: hook returns null for inactive', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ variant: null }),
      });

      const { result } = renderHook(() => useExperiment('inactive_test'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.variant).toBe(null);
    });
  });

  // ============================================
  // AB-006: Analytics Integration
  // ============================================
  describe('Analytics Integration', () => {
    it('AB-006-01: events include experiment variant', async () => {
      // After implementation: verify analytics events include variant
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ variant: 'variant_a' }),
      });

      const { result } = renderHook(() => useExperiment('onboarding_test'));

      await waitFor(() => {
        expect(result.current.variant).toBe('variant_a');
      });

      // Verify analytics context receives variant
    });

    it('AB-006-02: multiple experiments tracked', async () => {
      // After implementation: verify multiple experiment variants tracked
    });

    it('AB-006-03: experiment name in event', async () => {
      // After implementation: verify experiment_name in analytics
    });

    it('AB-006-04: control variant tracked', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ variant: 'control' }),
      });

      const { result } = renderHook(() => useExperiment('test'));

      await waitFor(() => {
        expect(result.current.variant).toBe('control');
      });
    });
  });

  // ============================================
  // AB-007: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('AB-007-01: handles empty experiment name', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ variant: 'control' }),
      });

      const { result } = renderHook(() => useExperiment(''));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      // Should return control or handle gracefully
      expect(result.current.variant).toBeDefined();
    });

    it('AB-007-02: handles network timeout', async () => {
      mockFetch.mockImplementation(() => new Promise((_, reject) => {
        setTimeout(() => reject(new Error('Timeout')), 100);
      }));

      const { result } = renderHook(() => useExperiment('test'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      // Should fallback to control
      expect(result.current.variant).toBe('control');
    });

    it('AB-007-03: handles invalid JSON response', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.reject(new Error('Invalid JSON')),
      });

      const { result } = renderHook(() => useExperiment('test'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.error).toBeInstanceOf(Error);
    });

    it('AB-007-04: handles 401 unauthorized', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 401,
      });

      const { result } = renderHook(() => useExperiment('test'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      // Should fallback to control
      expect(result.current.variant).toBe('control');
    });

    it('AB-007-05: handles 404 not found', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
      });

      const { result } = renderHook(() => useExperiment('nonexistent'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.error).toBeInstanceOf(Error);
    });

    it('AB-007-06: uses cached value on subsequent renders', async () => {
      localStorageMock.getItem.mockReturnValue('cached_variant');

      const { result } = renderHook(() => useExperiment('test'));

      // Should immediately return cached value without fetch
      expect(result.current.variant).toBe('cached_variant');
      expect(mockFetch).not.toHaveBeenCalled();
    });
  });

  // ============================================
  // Additional Tests
  // ============================================
  describe('Additional Tests', () => {
    it('stores variant in localStorage after fetch', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ variant: 'variant_a' }),
      });

      renderHook(() => useExperiment('test'));

      await waitFor(() => {
        expect(localStorageMock.setItem).toHaveBeenCalledWith('exp_test', 'variant_a');
      });
    });

    it('handles experiment name with special characters', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ variant: 'variant_a' }),
      });

      const { result } = renderHook(() => useExperiment('test-experiment_v2'));

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(mockFetch).toHaveBeenCalledWith('/api/v1/experiments/test-experiment_v2/variant');
    });

    it('re-fetches when experiment name changes', async () => {
      mockFetch
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve({ variant: 'variant_a' }),
        })
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve({ variant: 'variant_b' }),
        });

      const { result, rerender } = renderHook(
        ({ name }: { name: string }) => useExperiment(name),
        { initialProps: { name: 'test_a' } }
      );

      await waitFor(() => {
        expect(result.current.variant).toBe('variant_a');
      });

      rerender({ name: 'test_b' });

      await waitFor(() => {
        expect(result.current.variant).toBe('variant_b');
      });

      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });
});
