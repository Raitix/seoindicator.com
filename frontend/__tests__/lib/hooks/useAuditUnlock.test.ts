/**
 * useAuditUnlock Hook Tests
 * Task: TASK-33-23
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Tests for the custom hook that manages audit unlock state.
 *
 * The previous version of this file contained its own mock implementation
 * of the hook and never imported it, so it asserted only against itself.
 * These tests drive the real hook in `hooks/useAuditUnlock.ts`, which is
 * built on the cookie helpers in `lib/audit/public-audit.ts` and posts
 * the lead to `POST /api/v1/public-audit/{hash}/lead`.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, act, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';

import { useAuditUnlock } from '@/hooks/useAuditUnlock';
import {
  setUnlockedAudit,
  generateUnlockKey,
} from '@/lib/audit/public-audit';

const mockFetch = vi.fn();
global.fetch = mockFetch as unknown as typeof fetch;

const defaultHash = 'abc123def456';

/** Every cookie currently set on document.cookie. */
function allCookies(): string[] {
  return document.cookie.split(';').map((c) => c.trim()).filter(Boolean);
}

function clearAllCookies(): void {
  for (const cookie of allCookies()) {
    const name = cookie.split('=')[0];
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
  }
}

function okResponse(body: unknown = {}) {
  return {
    ok: true,
    status: 200,
    json: () => Promise.resolve(body),
  };
}

function errorResponse(status: number, body: unknown = {}) {
  return {
    ok: false,
    status,
    json: () => Promise.resolve(body),
  };
}

describe('useAuditUnlock Hook', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockReset();
    global.fetch = mockFetch as unknown as typeof fetch;
    mockFetch.mockResolvedValue(okResponse());
    clearAllCookies();
    sessionStorage.clear();
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // 1. INITIAL STATE TESTS
  // ============================================
  describe('Initial State', () => {
    it('INIT-001: returns locked state initially', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isUnlocked).toBe(false);
    });

    it('INIT-002: returns loading false initially', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isLoading).toBe(false);
    });

    it('INIT-003: returns null error initially', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.error).toBeNull();
    });

    it('INIT-004: checks cookies on mount for existing unlock', async () => {
      setUnlockedAudit(defaultHash, 'user@example.com');

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await waitFor(() => {
        expect(result.current.isUnlocked).toBe(true);
      });
    });

    it('INIT-005: checks cookies on mount for existing skip', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      act(() => {
        result.current.skip();
      });

      const { result: second } = renderHook(() =>
        useAuditUnlock({ auditHash: defaultHash })
      );
      await waitFor(() => {
        expect(second.current.isSkipped).toBe(true);
      });
    });
  });

  // ============================================
  // 2. UNLOCK TESTS
  // ============================================
  describe('unlock Function', () => {
    it('UNLOCK-001: calls API with correct parameters', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(mockFetch).toHaveBeenCalledWith(
        `/api/v1/public-audit/${defaultHash}/lead`,
        expect.objectContaining({
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: 'user@example.com' }),
        })
      );
    });

    it('UNLOCK-002: returns true on success', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      let returned: boolean | undefined;
      await act(async () => {
        returned = await result.current.unlock('user@example.com');
      });

      expect(returned).toBe(true);
    });

    it('UNLOCK-003: sets isUnlocked to true on success', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.isUnlocked).toBe(true);
    });

    it('UNLOCK-004: sets cookie on success', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(document.cookie).toContain(generateUnlockKey(defaultHash));
    });

    it('UNLOCK-005: returns false on API error', async () => {
      mockFetch.mockResolvedValue(errorResponse(500, { message: 'Server error' }));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      let returned: boolean | undefined;
      await act(async () => {
        returned = await result.current.unlock('user@example.com');
      });

      expect(returned).toBe(false);
    });

    it('UNLOCK-006: sets error message on failure', async () => {
      mockFetch.mockResolvedValue(errorResponse(500, { message: 'Server error' }));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.error).toBe('Server error');
    });

    it('UNLOCK-007: handles network error', async () => {
      mockFetch.mockRejectedValue(new Error('Network failure'));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.error).toBe('Network failure');
      expect(result.current.isUnlocked).toBe(false);
    });

    it('UNLOCK-008: handles rate limit (429)', async () => {
      mockFetch.mockResolvedValue(errorResponse(429));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.error).toMatch(/too many requests/i);
    });

    it('UNLOCK-009: handles not found (404)', async () => {
      mockFetch.mockResolvedValue(errorResponse(404));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.error).toMatch(/not found/i);
    });

    it('UNLOCK-010: does not set cookie on failure', async () => {
      mockFetch.mockResolvedValue(errorResponse(500, { message: 'Server error' }));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(document.cookie).not.toContain(generateUnlockKey(defaultHash));
      expect(result.current.isUnlocked).toBe(false);
    });
  });

  // ============================================
  // 3. SKIP TESTS
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

      expect(sessionStorage.getItem(`audit_skipped_${defaultHash}`)).not.toBeNull();
    });

    it('SKIP-003: does not affect isUnlocked', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      act(() => {
        result.current.skip();
      });

      expect(result.current.isUnlocked).toBe(false);
    });

    it('SKIP-004: can skip multiple audits', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: 'hashone' }));
      const { result: result2 } = renderHook(() => useAuditUnlock({ auditHash: 'hashtwo' }));

      act(() => {
        result.current.skip();
        result2.current.skip();
      });

      expect(result.current.isSkipped).toBe(true);
      expect(result2.current.isSkipped).toBe(true);
      expect(sessionStorage.getItem('audit_skipped_hashone')).not.toBeNull();
      expect(sessionStorage.getItem('audit_skipped_hashtwo')).not.toBeNull();
    });

    it('SKIP-005: skip persists after hook re-render', async () => {
      const { result, rerender } = renderHook(() =>
        useAuditUnlock({ auditHash: defaultHash })
      );

      act(() => {
        result.current.skip();
      });

      rerender();
      await waitFor(() => {
        expect(result.current.isSkipped).toBe(true);
      });
    });
  });

  // ============================================
  // 4. COOKIE TESTS
  // ============================================
  describe('Cookie Handling', () => {
    it('COOKIE-001: reads existing unlock cookie', async () => {
      setUnlockedAudit(defaultHash, 'user@example.com');

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await waitFor(() => {
        expect(result.current.isUnlocked).toBe(true);
      });
    });

    it('COOKIE-002: reads existing skip cookie', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      act(() => {
        result.current.skip();
      });

      const { result: second } = renderHook(() =>
        useAuditUnlock({ auditHash: defaultHash })
      );
      await waitFor(() => {
        expect(second.current.isSkipped).toBe(true);
      });
    });

    it('COOKIE-003: handles invalid JSON in cookie', async () => {
      // A corrupt unlock cookie must not throw and must not unlock.
      document.cookie = `${generateUnlockKey(defaultHash)}=not-json; path=/`;

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await waitFor(() => {
        expect(result.current.isUnlocked).toBe(false);
      });
    });

    it('COOKIE-004: handles missing cookie', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: 'nosuchhash' }));

      await waitFor(() => {
        expect(result.current.isUnlocked).toBe(false);
      });
    });

    it('COOKIE-005: appends to existing unlocks', async () => {
      setUnlockedAudit('otherhash', 'other@example.com');

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      // The other audit's unlock is untouched.
      expect(document.cookie).toContain(generateUnlockKey('otherhash'));
      expect(document.cookie).toContain(generateUnlockKey(defaultHash));
    });

    it('COOKIE-006: does not duplicate unlock entries', async () => {
      setUnlockedAudit(defaultHash, 'user@example.com');

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      const occurrences = allCookies().filter((c) =>
        c.startsWith(generateUnlockKey(defaultHash))
      ).length;
      expect(occurrences).toBe(1);
    });
  });

  // ============================================
  // 5. ERROR TESTS
  // ============================================
  describe('Error Handling', () => {
    it('ERR-001: clearError removes error', async () => {
      mockFetch.mockResolvedValue(errorResponse(500, { message: 'Server error' }));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });
      expect(result.current.error).not.toBeNull();

      act(() => {
        result.current.clearError();
      });
      expect(result.current.error).toBeNull();
    });

    it('ERR-002: error is null after successful unlock', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.error).toBeNull();
    });

    it('ERR-003: handles timeout error', async () => {
      mockFetch.mockRejectedValue(new Error('The operation timed out'));

      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.error).toMatch(/timed out/i);
      expect(result.current.isLoading).toBe(false);
    });
  });

  // ============================================
  // 6. MULTIPLE AUDIT TESTS
  // ============================================
  describe('Multiple Audit Hashes', () => {
    it('MULTI-001: separate state for different hashes', () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: 'hashone' }));
      const { result: result2 } = renderHook(() => useAuditUnlock({ auditHash: 'hashtwo' }));

      act(() => {
        result.current.skip();
      });

      expect(result.current.isSkipped).toBe(true);
      expect(result2.current.isSkipped).toBe(false);
    });

    it('MULTI-002: unlocking one does not affect others', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: 'hashone' }));
      const { result: result2 } = renderHook(() => useAuditUnlock({ auditHash: 'hashtwo' }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.isUnlocked).toBe(true);
      expect(result2.current.isUnlocked).toBe(false);
    });

    it('MULTI-003: can skip one and unlock another', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: 'hashone' }));
      const { result: result2 } = renderHook(() => useAuditUnlock({ auditHash: 'hashtwo' }));

      act(() => {
        result.current.skip();
      });
      await act(async () => {
        await result2.current.unlock('user@example.com');
      });

      expect(result.current.isSkipped).toBe(true);
      expect(result.current.isUnlocked).toBe(false);
      expect(result2.current.isUnlocked).toBe(true);
    });
  });

  // ============================================
  // 7. STABILITY TESTS
  // ============================================
  describe('Return Value Stability', () => {
    it('STABLE-001: functions are stable across renders', () => {
      const { result, rerender } = renderHook(() =>
        useAuditUnlock({ auditHash: defaultHash })
      );

      const unlock = result.current.unlock;
      const skip = result.current.skip;
      const clearError = result.current.clearError;

      rerender();

      expect(result.current.unlock).toBe(unlock);
      expect(result.current.skip).toBe(skip);
      expect(result.current.clearError).toBe(clearError);
    });

    it('STABLE-002: state updates correctly after actions', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      expect(result.current.isLoading).toBe(false);

      // The request is held open so the loading state can be observed
      // while it is in flight.
      let resolveFetch: (value: unknown) => void = () => {};
      mockFetch.mockReturnValue(
        new Promise((resolve) => {
          resolveFetch = resolve;
        })
      );

      let promise: Promise<boolean> | undefined;
      act(() => {
        promise = result.current.unlock('user@example.com');
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(true);
      });

      await act(async () => {
        resolveFetch(okResponse());
        await promise;
      });

      expect(result.current.isLoading).toBe(false);
      expect(result.current.isUnlocked).toBe(true);
    });
  });

  // ============================================
  // 8. EDGE CASE TESTS
  // ============================================
  describe('Edge Cases', () => {
    it('EDGE-001: handles empty audit hash', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: '' }));

      expect(result.current.isUnlocked).toBe(false);

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(result.current.error).not.toBeNull();
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('EDGE-002: handles very long audit hash', async () => {
      const longHash = 'a'.repeat(500);

      const { result } = renderHook(() => useAuditUnlock({ auditHash: longHash }));

      expect(result.current.isUnlocked).toBe(false);

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(mockFetch).toHaveBeenCalled();
    });

    it('EDGE-003: handles special characters in hash', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: 'hash-with-dashes_123' }));

      await act(async () => {
        await result.current.unlock('user@example.com');
      });

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/public-audit/hash-with-dashes_123/lead',
        expect.anything()
      );
    });

    it('EDGE-004: rapid unlock calls do not cause issues', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        await Promise.all([
          result.current.unlock('a@example.com'),
          result.current.unlock('b@example.com'),
        ]);
      });

      expect(result.current.isUnlocked).toBe(true);
      expect(result.current.error).toBeNull();
    });

    it('EDGE-05: handles concurrent skip and unlock', async () => {
      const { result } = renderHook(() => useAuditUnlock({ auditHash: defaultHash }));

      await act(async () => {
        result.current.skip();
        await result.current.unlock('user@example.com');
      });

      // Unlocking wins: the report is unlocked and no longer skipped.
      expect(result.current.isUnlocked).toBe(true);
      expect(result.current.isSkipped).toBe(false);
    });
  });
});
