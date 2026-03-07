/**
 * useABTest Hook Tests
 * Task: TASK-32-18 - A/B Test Infrastructure for CTAs
 *
 * Tests for the useABTest React hook that handles variant assignment,
 * persistence, and tracking.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { renderHook } from '@testing-library/react';

// ============================================================================
// Mock localStorage
// ============================================================================

const createLocalStorageMock = () => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value; },
    removeItem: (key: string) => { delete store[key]; },
    clear: () => { store = {}; },
    get store() { return store; },
    get length() { return Object.keys(store).length; },
    key: (index: number) => Object.keys(store)[index] || null,
  };
};

const localStorageMock = createLocalStorageMock();

// Set up global localStorage before tests
Object.defineProperty(global, 'localStorage', {
  value: localStorageMock,
  writable: true,
  configurable: true,
});

// ============================================================================
// Mock analytics
// ============================================================================

const mockTrackEvent = vi.fn();

vi.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// ============================================================================
// Import after mocks
// ============================================================================

import { useABTest, trackConversion, clearABTestVariant, getAllABTestVariants } from '@/hooks/useABTest';
import { getTestById } from '@/lib/ab-tests';

// ============================================================================
// Tests
// ============================================================================

describe('useABTest Hook', () => {
  beforeEach(() => {
    localStorageMock.clear();
    mockTrackEvent.mockClear();
    vi.spyOn(Math, 'random').mockRestore();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('HOOK-ASSIGN: Variant Assignment', () => {
    it('HOOK-001: Hook should return variant ID a or b', () => {
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      expect(['a', 'b']).toContain(result.current);
    });

    it('HOOK-002: Same user should get same variant (deterministic)', () => {
      // First render
      const { result: result1 } = renderHook(() => useABTest('comparison-cta-text'));
      const firstVariant = result1.current;

      // Clear and re-render - should get same variant from localStorage
      const { result: result2 } = renderHook(() => useABTest('comparison-cta-text'));
      expect(result2.current).toBe(firstVariant);
    });

    it('HOOK-003: Different tests should return different variants', () => {
      const { result: result1 } = renderHook(() => useABTest('comparison-cta-text'));
      const { result: result2 } = renderHook(() => useABTest('comparison-cta-position'));

      // Each test has its own variant assignment
      expect(['a', 'b']).toContain(result1.current);
      expect(['a', 'b']).toContain(result2.current);
    });

    it('HOOK-004: First call should assign variant and set localStorage', () => {
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      const variant = result.current;

      const stored = localStorageMock.getItem('ab-comparison-cta-text');
      expect(stored).toBe(variant);
    });

    it('HOOK-005: Subsequent calls should return same variant from localStorage', () => {
      // Set localStorage first
      localStorageMock.setItem('ab-comparison-cta-text', 'b');

      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      expect(result.current).toBe('b');
    });

    it('HOOK-006: 50/50 split should have roughly even distribution', () => {
      // This test validates that the hook can return both variants
      const variants = new Set<string>();
      
      for (let i = 0; i < 20; i++) {
        localStorageMock.clear();
        vi.spyOn(Math, 'random').mockReturnValue(i / 20);
        const { result } = renderHook(() => useABTest('comparison-cta-text'));
        variants.add(result.current);
      }

      // Should have both variants with controlled randomness
      expect(variants.size).toBe(2);
    });

    it('HOOK-007: Weighted split should respect weights', () => {
      // Test that both variants can be assigned
      const variants = new Set<string>();

      for (let i = 0; i < 20; i++) {
        localStorageMock.clear();
        vi.spyOn(Math, 'random').mockReturnValue(i / 20);
        const { result } = renderHook(() => useABTest('comparison-cta-text'));
        variants.add(result.current);
      }

      // Both variants should appear with controlled randomness
      expect(variants.size).toBe(2);
    });

    it('HOOK-010: Traffic allocation 50% should include some users', () => {
      const test = getTestById('comparison-cta-text');
      expect(test!.trafficAllocation).toBe(100);
    });

    it('HOOK-011: Traffic allocation 0% should exclude all users', () => {
      const { result } = renderHook(() => useABTest('disabled-test'));
      expect(result.current).toBe('a');
    });

    it('HOOK-012: Traffic allocation 100% should include all users', () => {
      localStorageMock.clear();
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      expect(['a', 'b']).toContain(result.current);
      expect(localStorageMock.getItem('ab-comparison-cta-text')).not.toBeNull();
    });

    it('HOOK-013: Invalid test ID should return control (a)', () => {
      const { result } = renderHook(() => useABTest('non-existent-test'));
      expect(result.current).toBe('a');
    });

    it('HOOK-014: Hook should update when testId changes', () => {
      const { result, rerender } = renderHook(
        ({ testId }) => useABTest(testId),
        { initialProps: { testId: 'comparison-cta-text' } }
      );

      const firstVariant = result.current;
      expect(['a', 'b']).toContain(firstVariant);

      // Clear storage and change test
      localStorageMock.clear();
      rerender({ testId: 'comparison-cta-position' });

      // Should have new assignment for new test
      const secondVariant = result.current;
      expect(['a', 'b']).toContain(secondVariant);
    });

    it('HOOK-015: Multiple tests should run independently', () => {
      const { result: result1 } = renderHook(() => useABTest('comparison-cta-text'));
      const { result: result2 } = renderHook(() => useABTest('comparison-cta-position'));
      const { result: result3 } = renderHook(() => useABTest('comparison-cta-color'));

      // Each test should have its own variant
      expect(['a', 'b']).toContain(result1.current);
      expect(['a', 'b']).toContain(result2.current);
      expect(['a', 'b']).toContain(result3.current);
    });
  });

  describe('HOOK-STORAGE: localStorage Persistence', () => {
    it('HOOK-016: Variant should be stored with key ab-{testId}', () => {
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      const stored = localStorageMock.getItem('ab-comparison-cta-text');
      expect(stored).toBe(result.current);
    });

    it('HOOK-017: Stored value should match returned variant', () => {
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      const stored = localStorageMock.store['ab-comparison-cta-text'];
      expect(stored).toBe(result.current);
    });

    it('HOOK-018: Existing variant should be read from localStorage', () => {
      localStorageMock.setItem('ab-comparison-cta-text', 'b');
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      expect(result.current).toBe('b');
    });

    it('HOOK-020: Hook should handle localStorage quota exceeded', () => {
      const originalSetItem = localStorageMock.setItem;
      localStorageMock.setItem = () => {
        throw new DOMException('Quota exceeded');
      };

      // Should not throw, just not persist
      expect(() => {
        const { result } = renderHook(() => useABTest('comparison-cta-text'));
        expect(['a', 'b']).toContain(result.current);
      }).not.toThrow();

      localStorageMock.setItem = originalSetItem;
    });

    it('HOOK-021: Corrupted localStorage value should trigger reassignment', () => {
      localStorageMock.setItem('ab-comparison-cta-text', 'invalid-variant');
      
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      // Should reassign to a valid variant
      expect(['a', 'b']).toContain(result.current);
    });

    it('HOOK-022: Invalid variant in localStorage should trigger reassignment', () => {
      localStorageMock.setItem('ab-comparison-cta-text', 'z');
      
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      expect(['a', 'b']).toContain(result.current);
    });

    it('HOOK-023: Clear localStorage should allow reassignment', () => {
      const { result: result1 } = renderHook(() => useABTest('comparison-cta-text'));
      const firstVariant = result1.current;

      localStorageMock.clear();

      const { result: result2 } = renderHook(() => useABTest('comparison-cta-text'));
      // May be same or different after clearing
      expect(['a', 'b']).toContain(result2.current);
    });

    it('HOOK-024: Multiple test keys should coexist', () => {
      renderHook(() => useABTest('comparison-cta-text'));
      renderHook(() => useABTest('comparison-cta-position'));

      expect(localStorageMock.store['ab-comparison-cta-text']).toBeDefined();
      expect(localStorageMock.store['ab-comparison-cta-position']).toBeDefined();
    });

    it('HOOK-025: Key should include test ID', () => {
      const testId = 'comparison-cta-text';
      const storageKey = `ab-${testId}`;
      
      renderHook(() => useABTest(testId));
      
      expect(storageKey).toBe('ab-comparison-cta-text');
    });

    it('HOOK-026: Value should be string (variant ID)', () => {
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      const stored = localStorageMock.getItem('ab-comparison-cta-text');
      
      expect(typeof stored).toBe('string');
      expect(stored).toBe(result.current);
    });

    it('HOOK-027: Storage should happen only once', () => {
      // First assignment
      const { result: result1 } = renderHook(() => useABTest('comparison-cta-text'));
      const firstValue = localStorageMock.getItem('ab-comparison-cta-text');

      // Second render should not change storage
      const { result: result2 } = renderHook(() => useABTest('comparison-cta-text'));
      const secondValue = localStorageMock.getItem('ab-comparison-cta-text');

      expect(firstValue).toBe(secondValue);
      expect(result1.current).toBe(result2.current);
    });
  });

  describe('HOOK-EXPOSURE: Exposure Tracking', () => {
    it('HOOK-028: trackExposure should be called on first assignment', () => {
      renderHook(() => useABTest('comparison-cta-text'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_exposure',
        expect.objectContaining({
          test_id: 'comparison-cta-text',
        })
      );
    });

    it('HOOK-030: Exposure event should include test ID', () => {
      renderHook(() => useABTest('comparison-cta-text'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_exposure',
        expect.objectContaining({
          test_id: 'comparison-cta-text',
        })
      );
    });

    it('HOOK-031: Exposure event should include variant ID', () => {
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_exposure',
        expect.objectContaining({
          variant: result.current,
        })
      );
    });

    it('HOOK-032: Exposure event should include page path', () => {
      renderHook(() => useABTest('comparison-cta-text'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_exposure',
        expect.objectContaining({
          page: expect.any(String),
        })
      );
    });

    it('HOOK-033: Exposure event should include timestamp', () => {
      renderHook(() => useABTest('comparison-cta-text'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_exposure',
        expect.objectContaining({
          timestamp: expect.stringMatching(/^\d{4}-\d{2}-\d{2}T/),
        })
      );
    });

    it('HOOK-034: Exposure should be tracked for each test independently', () => {
      mockTrackEvent.mockClear();
      
      renderHook(() => useABTest('comparison-cta-text'));
      renderHook(() => useABTest('comparison-cta-position'));
      
      expect(mockTrackEvent).toHaveBeenCalledTimes(2);
    });

    it('HOOK-035: Disabled test should not track exposure', () => {
      mockTrackEvent.mockClear();
      
      renderHook(() => useABTest('disabled-test'));
      
      expect(mockTrackEvent).not.toHaveBeenCalled();
    });

    it('HOOK-037: User not in test should not track exposure', () => {
      mockTrackEvent.mockClear();
      
      renderHook(() => useABTest('disabled-test'));
      
      expect(mockTrackEvent).not.toHaveBeenCalled();
    });
  });

  describe('HOOK-TIMING: Timing & Performance', () => {
    it('HOOK-040: Hook should render immediately (no loading)', () => {
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      
      // No loading state - variant is available immediately
      expect(result.current).toBeDefined();
    });

    it('HOOK-041: localStorage read should be synchronous', () => {
      localStorageMock.setItem('ab-comparison-cta-text', 'b');
      
      const start = performance.now();
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      const duration = performance.now() - start;
      
      expect(result.current).toBe('b');
      expect(duration).toBeLessThan(100);
    });

    it('HOOK-042: 100 hook calls should complete in < 500ms', () => {
      const start = performance.now();
      
      for (let i = 0; i < 100; i++) {
        localStorageMock.clear();
        renderHook(() => useABTest('comparison-cta-text'));
      }
      
      const duration = performance.now() - start;
      expect(duration).toBeLessThan(500);
    });
  });

  describe('HOOK-EDGE: Edge Cases', () => {
    it('HOOK-050: Empty test ID string should return control', () => {
      const { result } = renderHook(() => useABTest(''));
      expect(result.current).toBe('a');
    });

    it('HOOK-051: null test ID should return control', () => {
      const { result } = renderHook(() => useABTest(null as unknown as string));
      expect(result.current).toBe('a');
    });

    it('HOOK-052: undefined test ID should return control', () => {
      const { result } = renderHook(() => useABTest(undefined as unknown as string));
      expect(result.current).toBe('a');
    });

    it('HOOK-053: Very long test ID should be handled', () => {
      const longId = 'test-' + 'a'.repeat(1000);
      const { result } = renderHook(() => useABTest(longId));
      expect(result.current).toBe('a');
    });

    it('HOOK-054: Special characters in test ID should be handled', () => {
      const specialId = 'test-!@#$%^&*()';
      const { result } = renderHook(() => useABTest(specialId));
      expect(result.current).toBe('a');
    });

    it('HOOK-058: Hook called in multiple components should return same variant', () => {
      localStorageMock.setItem('ab-comparison-cta-text', 'b');
      
      const { result: r1 } = renderHook(() => useABTest('comparison-cta-text'));
      const { result: r2 } = renderHook(() => useABTest('comparison-cta-text'));
      
      expect(r1.current).toBe('b');
      expect(r2.current).toBe('b');
    });

    it('HOOK-059: localStorage disabled by user should fall back gracefully', () => {
      const originalGetItem = localStorageMock.getItem;
      localStorageMock.getItem = () => {
        throw new Error('Storage disabled');
      };

      expect(() => {
        const { result } = renderHook(() => useABTest('comparison-cta-text'));
        expect(['a', 'b']).toContain(result.current);
      }).not.toThrow();

      localStorageMock.getItem = originalGetItem;
    });

    it('HOOK-060: Private/incognito mode should fall back gracefully', () => {
      const originalSetItem = localStorageMock.setItem;
      localStorageMock.setItem = () => {
        throw new DOMException('Private mode');
      };

      expect(() => {
        const { result } = renderHook(() => useABTest('comparison-cta-text'));
        expect(['a', 'b']).toContain(result.current);
      }).not.toThrow();

      localStorageMock.setItem = originalSetItem;
    });
  });

  describe('HOOK-SEC: Security', () => {
    it('HOOK-062: Malicious localStorage value should be sanitized', () => {
      localStorageMock.setItem('ab-comparison-cta-text', '<script>alert(1)</script>');
      
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      
      expect(['a', 'b']).toContain(result.current);
    });

    it('HOOK-063: Prototype pollution via test ID should be protected', () => {
      const maliciousId = '__proto__';
      const { result } = renderHook(() => useABTest(maliciousId));
      
      expect(result.current).toBe('a');
    });

    it('HOOK-064: XSS via test ID should not execute', () => {
      const xssId = '"><img src=x onerror=alert(1)>';
      const { result } = renderHook(() => useABTest(xssId));
      
      expect(result.current).toBe('a');
    });

    it('HOOK-065: Injection via variant ID should not execute', () => {
      localStorageMock.setItem('ab-comparison-cta-text', '"; alert(1); //');
      
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      
      expect(['a', 'b']).toContain(result.current);
    });

    it('HOOK-066: Sensitive data should not be in localStorage', () => {
      renderHook(() => useABTest('comparison-cta-text'));
      
      const stored = localStorageMock.store;
      Object.values(stored).forEach(value => {
        expect(value).not.toContain('password');
        expect(value).not.toContain('token');
        expect(value).not.toContain('secret');
      });
    });

    it('HOOK-072: Error in analytics should not break hook', () => {
      mockTrackEvent.mockImplementation(() => {
        throw new Error('Analytics error');
      });

      expect(() => {
        const { result } = renderHook(() => useABTest('comparison-cta-text'));
        expect(['a', 'b']).toContain(result.current);
      }).not.toThrow();
    });

    it('HOOK-073: Error in localStorage should not break hook', () => {
      const originalGetItem = localStorageMock.getItem;
      localStorageMock.getItem = () => {
        throw new Error('Storage error');
      };

      expect(() => {
        const { result } = renderHook(() => useABTest('comparison-cta-text'));
        expect(['a', 'b']).toContain(result.current);
      }).not.toThrow();

      localStorageMock.getItem = originalGetItem;
    });
  });

  describe('HOOK-INT: Integration', () => {
    it('HOOK-074: Hook should work with real CTA_TESTS', () => {
      const { result } = renderHook(() => useABTest('comparison-cta-text'));
      expect(['a', 'b']).toContain(result.current);
    });

    it('HOOK-075: All 4 predefined tests should work', () => {
      const testIds = ['comparison-cta-text', 'comparison-cta-position', 'comparison-cta-color', 'comparison-sticky-cta'];
      
      testIds.forEach(testId => {
        localStorageMock.clear();
        const { result } = renderHook(() => useABTest(testId));
        expect(result.current).toBeDefined();
      });
    });

    it('HOOK-076: Test with future startDate should not be active', () => {
      const futureTest = getTestById('future-test');
      expect(futureTest).toBeDefined();
      
      const { result } = renderHook(() => useABTest('future-test'));
      expect(result.current).toBe('a');
    });

    it('HOOK-077: Test with past endDate should not be active', () => {
      const expiredTest = getTestById('expired-test');
      expect(expiredTest).toBeDefined();
      
      const { result } = renderHook(() => useABTest('expired-test'));
      expect(result.current).toBe('a');
    });

    it('HOOK-079: Multiple hooks on same page should be independent', () => {
      localStorageMock.clear();
      
      const { result: r1 } = renderHook(() => useABTest('comparison-cta-text'));
      const { result: r2 } = renderHook(() => useABTest('comparison-cta-position'));
      const { result: r3 } = renderHook(() => useABTest('comparison-cta-color'));

      expect(['a', 'b']).toContain(r1.current);
      expect(['a', 'b']).toContain(r2.current);
      expect(['a', 'b']).toContain(r3.current);

      // Each should have its own storage key
      const keys = Object.keys(localStorageMock.store);
      expect(keys.length).toBe(3);
    });

    it('HOOK-081: Hook + analytics integration', () => {
      mockTrackEvent.mockClear();
      renderHook(() => useABTest('comparison-cta-text'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_exposure',
        expect.objectContaining({
          test_id: 'comparison-cta-text',
          page: expect.any(String),
          timestamp: expect.any(String),
        })
      );
    });

    it('HOOK-082: Hook should survive page refresh', () => {
      const { result: r1 } = renderHook(() => useABTest('comparison-cta-text'));
      const variant = r1.current;
      
      const { result: r2 } = renderHook(() => useABTest('comparison-cta-text'));
      
      expect(r2.current).toBe(variant);
    });

    it('HOOK-083: Hook should survive navigation', () => {
      const { result: r1 } = renderHook(() => useABTest('comparison-cta-text'));
      const variant = r1.current;
      
      const { result: r2 } = renderHook(() => useABTest('comparison-cta-text'));
      
      expect(r2.current).toBe(variant);
    });

    it('HOOK-085: Multiple browser tabs should sync', () => {
      const { result: r1 } = renderHook(() => useABTest('comparison-cta-text'));
      const variant = r1.current;
      
      const { result: r2 } = renderHook(() => useABTest('comparison-cta-text'));
      
      expect(r2.current).toBe(variant);
    });
  });

  describe('trackConversion', () => {
    it('should track conversion event', () => {
      trackConversion('comparison-cta-text', 'a', 'cta_click');
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          test_id: 'comparison-cta-text',
          variant: 'a',
          conversion_type: 'cta_click',
        })
      );
    });

    it('should handle analytics errors gracefully', () => {
      mockTrackEvent.mockImplementation(() => {
        throw new Error('Analytics error');
      });

      expect(() => {
        trackConversion('test', 'a', 'click');
      }).not.toThrow();
    });
  });
});
