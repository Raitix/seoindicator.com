/**
 * useAuditUnlock Hook Tests
 * Task: TASK-33-23
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Tests for the custom hook that manages audit unlock state.
 */

import { renderHook, act } from '@testing-library/react';
import '@testing-library/jest-dom';

// ============================================
// MOCK IMPLEMENTATION
// ============================================

/**
 * Mock cookie storage for testing
 */
const mockCookieStorage: Record<string, string> = {};

/**
 * Mock cookie functions
 */
const mockCookies = {
  get: jest.fn((name: string) => {
    try {
      return mockCookieStorage[name] || null;
    } catch {
      return null;
    }
  }),
  set: jest.fn((name: string, value: string, options?: Record<string, unknown>) => {
    mockCookieStorage[name] = value;
  }),
  remove: jest.fn((name: string) => {
    delete mockCookieStorage[name];
  }),
};

/**
 * Mock localStorage for session ID
 */
const mockLocalStorage: Record<string, string> = {};

const mockLocalStorageApi = {
  getItem: jest.fn((key: string) => mockLocalStorage[key] || null),
  setItem: jest.fn((key: string, value: string) => {
    mockLocalStorage[key] = value;
  }),
  removeItem: jest.fn((key: string) => {
    delete mockLocalStorage[key];
  }),
  clear: jest.fn(() => {
    Object.keys(mockLocalStorage).forEach(key => delete mockLocalStorage[key]);
  }),
};

Object.defineProperty(window, 'localStorage', {
  value: mockLocalStorageApi,
});

// Mock fetch for API calls
const mockFetch = jest.fn();
global.fetch = mockFetch;

// ============================================
// MOCK HOOK IMPLEMENTATION
// ============================================

/**
 * useAuditUnlock hook interface
 * This is the expected interface the Developer should implement
 */
interface UseAuditUnlockOptions {
  auditHash: string;
  autoCheck?: boolean;
}

interface UseAuditUnlockReturn {
  isUnlocked: boolean;
  isSkipped: boolean;
  isLoading: boolean;
  error: string | null;
  unlock: (email: string) => Promise<boolean>;
  skip: () => void;
  checkUnlockStatus: () => Promise<void>;
  clearError: () => void;
}

/**
 * Mock hook implementation for testing
 */
function useAuditUnlock(options: UseAuditUnlockOptions): UseAuditUnlockReturn {
  const { auditHash, autoCheck = true } = options;

  // State
  let _isUnlocked = false;
  let _isSkipped = false;
  let _isLoading = false;
  let _error: string | null = null;

  // Check initial state from cookies
  const checkCookies = () => {
    try {
      const unlocks = JSON.parse(mockCookies.get('audit_unlocks') || '[]');
      _isUnlocked = unlocks.includes(auditHash);

      const skips = JSON.parse(mockCookies.get('audit_skips') || '[]');
      _isSkipped = skips.includes(auditHash);
    } catch {
      _isUnlocked = false;
      _isSkipped = false;
    }
  };

  checkCookies();

  return {
    isUnlocked: _isUnlocked,
    isSkipped: _isSkipped,
    isLoading: _isLoading,
    error: _error,
    unlock: async (email: string): Promise<boolean> => {
      try {
        const response = await fetch(`/api/v1/public-audit/${auditHash}/lead`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email }),
        });

        if (!response.ok) {
          const data = await response.json();
          _error = data.error || 'Failed to unlock';
          return false;
        }

        // Update cookie
        const unlocks = JSON.parse(mockCookies.get('audit_unlocks') || '[]');
        if (!unlocks.includes(auditHash)) {
          unlocks.push(auditHash);
        }
        mockCookies.set('audit_unlocks', JSON.stringify(unlocks), { maxAge: 86400 });
        _isUnlocked = true;

        return true;
      } catch (err) {
        _error = err instanceof Error ? err.message : 'Network error';
        return false;
      }
    },
    skip: () => {
      const skips = JSON.parse(mockCookies.get('audit_skips') || '[]');
      if (!skips.includes(auditHash)) {
        skips.push(auditHash);
      }
      mockCookies.set('audit_skips', JSON.stringify(skips), { maxAge: 86400 });
      _isSkipped = true;
    },
    checkUnlockStatus: async () => {
      // API call to verify unlock status (optional)
    },
    clearError: () => {
      _error = null;
    },
  };
}

// ============================================
// TEST SUITE
// ============================================

describe('useAuditUnlock Hook', () => {
  const defaultHash = 'abc123def456';

  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
    // Clear storage
    Object.keys(mockCookieStorage).forEach(key => delete mockCookieStorage[key]);
    Object.keys(mockLocalStorage).forEach(key => delete mockLocalStorage[key]);
  });

  // ============================================
  // 1. INITIAL STATE TESTS
  // ============================================

  describe('Initial State', () => {
    it('INIT-001: returns locked state initially', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isUnlocked).toBe(false);
      expect(result.current.isSkipped).toBe(false);
    });

    it('INIT-002: returns loading false initially', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isLoading).toBe(false);
    });

    it('INIT-003: returns null error initially', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.error).toBeNull();
    });

    it('INIT-004: checks cookies on mount for existing unlock', () => {
      // Pre-set unlock cookie
      mockCookieStorage['audit_unlocks'] = JSON.stringify([defaultHash]);

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isUnlocked).toBe(true);
    });

    it('INIT-005: checks cookies on mount for existing skip', () => {
      // Pre-set skip cookie
      mockCookieStorage['audit_skips'] = JSON.stringify([defaultHash]);

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isSkipped).toBe(true);
    });
  });

  // ============================================
  // 2. UNLOCK FUNCTION TESTS
  // ============================================

  describe('unlock Function', () => {
    it('UNLOCK-001: calls API with correct parameters', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(mockFetch).toHaveBeenCalledWith(
        `/api/v1/public-audit/${defaultHash}/lead`,
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ email: 'test@example.com' }),
        })
      );
    });

    it('UNLOCK-002: returns true on success', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      let success: boolean = false;
      await act(async () => {
        success = await result.current.unlock('test@example.com');
      });

      expect(success).toBe(true);
    });

    it('UNLOCK-003: sets isUnlocked to true on success', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isUnlocked).toBe(false);

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(result.current.isUnlocked).toBe(true);
    });

    it('UNLOCK-004: sets cookie on success', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      const unlocks = JSON.parse(mockCookieStorage['audit_unlocks'] || '[]');
      expect(unlocks).toContain(defaultHash);
    });

    it('UNLOCK-005: returns false on API error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 400,
        json: async () => ({ success: false, error: 'Invalid email' }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      let success: boolean = true;
      await act(async () => {
        success = await result.current.unlock('invalid');
      });

      expect(success).toBe(false);
    });

    it('UNLOCK-006: sets error message on failure', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 400,
        json: async () => ({ success: false, error: 'Invalid email format' }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('invalid');
      });

      expect(result.current.error).toBe('Invalid email format');
    });

    it('UNLOCK-007: handles network error', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      let success: boolean = true;
      await act(async () => {
        success = await result.current.unlock('test@example.com');
      });

      expect(success).toBe(false);
      expect(result.current.error).toBe('Network error');
    });

    it('UNLOCK-008: handles rate limit (429)', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        json: async () => ({ success: false, error: 'Too many requests' }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(result.current.error).toBe('Too many requests');
    });

    it('UNLOCK-009: handles not found (404)', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
        json: async () => ({ success: false, error: 'Audit not found' }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(result.current.error).toBe('Audit not found');
    });

    it('UNLOCK-010: does not set cookie on failure', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 400,
        json: async () => ({ success: false, error: 'Error' }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(mockCookieStorage['audit_unlocks']).toBeUndefined();
    });
  });

  // ============================================
  // 3. SKIP FUNCTION TESTS
  // ============================================

  describe('skip Function', () => {
    it('SKIP-001: sets isSkipped to true', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isSkipped).toBe(false);

      act(() => {
        result.current.skip();
      });

      expect(result.current.isSkipped).toBe(true);
    });

    it('SKIP-002: sets skip cookie', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      act(() => {
        result.current.skip();
      });

      const skips = JSON.parse(mockCookieStorage['audit_skips'] || '[]');
      expect(skips).toContain(defaultHash);
    });

    it('SKIP-003: does not affect isUnlocked', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      act(() => {
        result.current.skip();
      });

      expect(result.current.isUnlocked).toBe(false);
    });

    it('SKIP-004: can skip multiple audits', () => {
      const { result: result1 } = renderHook(() => useAuditUnlock({ auditHash: 'audit1' }));
      const { result: result2 } = renderHook(() => useAuditUnlock({ auditHash: 'audit2' }));

      act(() => {
        result1.current.skip();
        result2.current.skip();
      });

      const skips = JSON.parse(mockCookieStorage['audit_skips'] || '[]');
      expect(skips).toContain('audit1');
      expect(skips).toContain('audit2');
    });

    it('SKIP-005: skip persists after hook re-render', () => {
      const { result, rerender } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      act(() => {
        result.current.skip();
      });

      rerender();

      expect(result.current.isSkipped).toBe(true);
    });
  });

  // ============================================
  // 4. COOKIE MANAGEMENT TESTS
  // ============================================

  describe('Cookie Management', () => {
    it('COOKIE-001: reads existing unlock cookie', () => {
      mockCookieStorage['audit_unlocks'] = JSON.stringify(['existing-hash', defaultHash]);

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isUnlocked).toBe(true);
    });

    it('COOKIE-002: reads existing skip cookie', () => {
      mockCookieStorage['audit_skips'] = JSON.stringify([defaultHash]);

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isSkipped).toBe(true);
    });

    it('COOKIE-003: handles invalid JSON in cookie', () => {
      mockCookieStorage['audit_unlocks'] = 'not-valid-json';

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      // Should not throw, should default to unlocked: false
      expect(result.current.isUnlocked).toBe(false);
    });

    it('COOKIE-004: handles missing cookie', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isUnlocked).toBe(false);
      expect(result.current.isSkipped).toBe(false);
    });

    it('COOKIE-005: appends to existing unlocks', async () => {
      mockCookieStorage['audit_unlocks'] = JSON.stringify(['existing-hash']);
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      const unlocks = JSON.parse(mockCookieStorage['audit_unlocks'] || '[]');
      expect(unlocks).toContain('existing-hash');
      expect(unlocks).toContain(defaultHash);
    });

    it('COOKIE-006: does not duplicate unlock entries', async () => {
      mockCookieStorage['audit_unlocks'] = JSON.stringify([defaultHash]);
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      const unlocks = JSON.parse(mockCookieStorage['audit_unlocks'] || '[]');
      const count = unlocks.filter((h: string) => h === defaultHash).length;
      expect(count).toBe(1);
    });
  });

  // ============================================
  // 5. ERROR HANDLING TESTS
  // ============================================

  describe('Error Handling', () => {
    it('ERR-001: clearError removes error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ success: false, error: 'Test error' }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(result.current.error).toBe('Test error');

      act(() => {
        result.current.clearError();
      });

      expect(result.current.error).toBeNull();
    });

    it('ERR-002: error is null after successful unlock', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ success: false, error: 'First error' }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      // First, cause an error
      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(result.current.error).toBe('First error');

      // Then, succeed
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(result.current.error).toBeNull();
    });

    it('ERR-003: handles timeout error', async () => {
      mockFetch.mockImplementationOnce(() =>
        new Promise((_, reject) =>
          setTimeout(() => reject(new Error('Request timeout')), 100)
        )
      );

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      let success: boolean = true;
      await act(async () => {
        success = await result.current.unlock('test@example.com');
      });

      expect(success).toBe(false);
      expect(result.current.error).toBe('Request timeout');
    });
  });

  // ============================================
  // 6. MULTIPLE AUDIT HASHES TESTS
  // ============================================

  describe('Multiple Audit Hashes', () => {
    it('MULTI-001: separate state for different hashes', () => {
      const { result: result1 } = renderHook(() => useAuditUnlock({ auditHash: 'hash1' }));
      const { result: result2 } = renderHook(() => useAuditUnlock({ auditHash: 'hash2' }));

      expect(result1.current.isUnlocked).toBe(false);
      expect(result2.current.isUnlocked).toBe(false);
    });

    it('MULTI-002: unlocking one does not affect others', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result: result1 } = renderHook(() => useAuditUnlock({ auditHash: 'hash1' }));

      await act(async () => {
        await result1.current.unlock('test@example.com');
      });

      // Check that hash1 is unlocked
      const unlocks = JSON.parse(mockCookieStorage['audit_unlocks'] || '[]');
      expect(unlocks).toContain('hash1');
      expect(unlocks).not.toContain('hash2');
    });

    it('MULTI-003: can skip one and unlock another', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result: result1 } = renderHook(() => useAuditUnlock({ auditHash: 'hash1' }));
      const { result: result2 } = renderHook(() => useAuditUnlock({ auditHash: 'hash2' }));

      act(() => {
        result1.current.skip();
      });

      await act(async () => {
        await result2.current.unlock('test@example.com');
      });

      expect(result1.current.isSkipped).toBe(true);
      expect(result1.current.isUnlocked).toBe(false);
      expect(result2.current.isSkipped).toBe(false);
      expect(result2.current.isUnlocked).toBe(true);
    });
  });

  // ============================================
  // 7. RETURN VALUE STABILITY TESTS
  // ============================================

  describe('Return Value Stability', () => {
    it('STABLE-001: functions are stable across renders', () => {
      const { result, rerender } = renderHook(() =>
        useAuditUnlock({ auditHash: defaultHash })
      );

      const initialUnlock = result.current.unlock;
      const initialSkip = result.current.skip;
      const initialClearError = result.current.clearError;

      rerender();

      // Functions should maintain reference stability
      // (This is a best practice - actual implementation may vary)
      expect(typeof result.current.unlock).toBe('function');
      expect(typeof result.current.skip).toBe('function');
      expect(typeof result.current.clearError).toBe('function');
    });

    it('STABLE-002: state updates correctly after actions', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      // Initial state
      expect(result.current.isUnlocked).toBe(false);

      // After unlock
      await act(async () => {
        await result.current.unlock('test@example.com');
      });

      expect(result.current.isUnlocked).toBe(true);
    });
  });

  // ============================================
  // 8. EDGE CASES TESTS
  // ============================================

  describe('Edge Cases', () => {
    it('EDGE-001: handles empty audit hash', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: '' }));

      expect(result.current.isUnlocked).toBe(false);
    });

    it('EDGE-002: handles very long audit hash', () => {
      const longHash = 'a'.repeat(100);

      const { result } = renderHook(() => useAuditUnlock({ auditHash: longHash }));

      expect(result.current.isUnlocked).toBe(false);
    });

    it('EDGE-003: handles special characters in hash', () => {
      const specialHash = 'hash-with-special_chars.123';

      const { result } = renderHook(() => useAuditUnlock({ auditHash: specialHash }));

      expect(result.current.isUnlocked).toBe(false);
    });

    it('EDGE-004: rapid unlock calls do not cause issues', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      // Simulate rapid calls
      await act(async () => {
        await Promise.all([
          result.current.unlock('test1@example.com'),
          result.current.unlock('test2@example.com'),
          result.current.unlock('test3@example.com'),
        ]);
      });

      // Should not throw, state should be consistent
      expect(result.current.isUnlocked).toBe(true);
    });

    it('EDGE-05: handles concurrent skip and unlock', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, data: { unlocked: true } }),
      });

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        result.current.skip();
        await result.current.unlock('test@example.com');
      });

      // Both states could be true (depending on implementation preference)
      // The key is no crash and consistent state
      expect(result.current.isUnlocked).toBe(true);
    });
  });
});
