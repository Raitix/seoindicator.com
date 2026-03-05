/**
 * Cookie Consent Utility Tests
 * TASK-30-4: Cookie Consent Component
 * 
 * Tests for localStorage-based cookie consent state management.
 * Covers GDPR/CCPA compliance requirements.
 */

import {
  getCookieConsent,
  setCookieConsent,
  hasCookieConsent,
  clearCookieConsent,
  type CookieConsentState,
} from '@/lib/utils/cookieConsent';

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: jest.fn((key: string) => store[key] || null),
    setItem: jest.fn((key: string, value: string) => {
      store[key] = value;
    }),
    removeItem: jest.fn((key: string) => {
      delete store[key];
    }),
    clear: jest.fn(() => {
      store = {};
    }),
  };
})();

Object.defineProperty(global, 'localStorage', {
  value: localStorageMock,
});

describe('Cookie Consent Utilities', () => {
  const STORAGE_KEY = 'seoindicator_cookie_consent';

  beforeEach(() => {
    localStorageMock.clear();
    jest.clearAllMocks();
  });

  // ============================================
  // getCookieConsent Tests
  // ============================================
  describe('getCookieConsent', () => {
    it('should return null when no consent stored', () => {
      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should return parsed consent state when stored', () => {
      const mockState: CookieConsentState = {
        necessary: true,
        analytics: true,
        marketing: false,
        timestamp: '2026-03-05T10:00:00Z',
      };
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify(mockState));

      const result = getCookieConsent();
      expect(result).toEqual(mockState);
    });

    it('should return null for invalid JSON', () => {
      localStorageMock.setItem(STORAGE_KEY, 'invalid-json');

      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should return null for malformed state object', () => {
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify({ foo: 'bar' }));

      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should handle missing timestamp gracefully', () => {
      const incompleteState = {
        necessary: true,
        analytics: true,
        marketing: false,
      };
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify(incompleteState));

      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should handle missing consent fields gracefully', () => {
      const incompleteState = {
        necessary: true,
        timestamp: '2026-03-05T10:00:00Z',
      };
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify(incompleteState));

      const result = getCookieConsent();
      expect(result).toBeNull();
    });
  });

  // ============================================
  // setCookieConsent Tests
  // ============================================
  describe('setCookieConsent', () => {
    it('should store consent state with timestamp', () => {
      const state: Omit<CookieConsentState, 'timestamp'> = {
        necessary: true,
        analytics: true,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.necessary).toBe(true);
      expect(stored.analytics).toBe(true);
      expect(stored.marketing).toBe(false);
      expect(stored.timestamp).toBeDefined();
      expect(new Date(stored.timestamp).toISOString()).toBe(stored.timestamp);
    });

    it('should always set necessary to true', () => {
      const state = {
        necessary: false,
        analytics: false,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.necessary).toBe(true);
    });

    it('should generate valid ISO 8601 timestamp', () => {
      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      const timestamp = new Date(stored.timestamp);
      expect(timestamp.toISOString()).toBe(stored.timestamp);
    });

    it('should overwrite existing consent', () => {
      const firstState = {
        necessary: true,
        analytics: false,
        marketing: false,
      };
      const secondState = {
        necessary: true,
        analytics: true,
        marketing: true,
      };

      setCookieConsent(firstState);
      setCookieConsent(secondState);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.analytics).toBe(true);
      expect(stored.marketing).toBe(true);
    });

    it('should use provided timestamp if passed', () => {
      const customTimestamp = '2026-01-15T08:30:00Z';
      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
        timestamp: customTimestamp,
      };

      setCookieConsent(state as CookieConsentState);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.timestamp).toBe(customTimestamp);
    });
  });

  // ============================================
  // hasCookieConsent Tests
  // ============================================
  describe('hasCookieConsent', () => {
    it('should return false when no consent stored', () => {
      expect(hasCookieConsent()).toBe(false);
    });

    it('should return true when consent is stored', () => {
      const state: CookieConsentState = {
        necessary: true,
        analytics: false,
        marketing: false,
        timestamp: '2026-03-05T10:00:00Z',
      };
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify(state));

      expect(hasCookieConsent()).toBe(true);
    });

    it('should return false for invalid stored value', () => {
      localStorageMock.setItem(STORAGE_KEY, 'invalid');

      expect(hasCookieConsent()).toBe(false);
    });
  });

  // ============================================
  // clearCookieConsent Tests
  // ============================================
  describe('clearCookieConsent', () => {
    it('should remove consent from localStorage', () => {
      const state: CookieConsentState = {
        necessary: true,
        analytics: true,
        marketing: false,
        timestamp: '2026-03-05T10:00:00Z',
      };
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify(state));

      clearCookieConsent();

      expect(localStorageMock.getItem(STORAGE_KEY)).toBeNull();
    });

    it('should not throw when no consent exists', () => {
      expect(() => clearCookieConsent()).not.toThrow();
    });
  });

  // ============================================
  // GDPR Compliance Tests
  // ============================================
  describe('GDPR Compliance', () => {
    it('should not have pre-checked consent on first visit', () => {
      // Simulating first visit - no stored consent
      const consent = getCookieConsent();
      expect(consent).toBeNull();
    });

    it('should require explicit user action to set consent', () => {
      // Consent should not be set automatically
      expect(hasCookieConsent()).toBe(false);
    });

    it('should store user choices separately for each cookie category', () => {
      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.analytics).toBe(true);
      expect(stored.marketing).toBe(false);
    });

    it('should allow users to decline all optional cookies', () => {
      const state = {
        necessary: true,
        analytics: false,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.necessary).toBe(true);
      expect(stored.analytics).toBe(false);
      expect(stored.marketing).toBe(false);
    });

    it('should allow users to accept all cookies', () => {
      const state = {
        necessary: true,
        analytics: true,
        marketing: true,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.necessary).toBe(true);
      expect(stored.analytics).toBe(true);
      expect(stored.marketing).toBe(true);
    });

    it('should allow users to withdraw consent', () => {
      const state: CookieConsentState = {
        necessary: true,
        analytics: true,
        marketing: true,
        timestamp: '2026-03-05T10:00:00Z',
      };
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify(state));

      clearCookieConsent();

      expect(hasCookieConsent()).toBe(false);
    });
  });

  // ============================================
  // Cookie Category Tests
  // ============================================
  describe('Cookie Categories', () => {
    it('should always have necessary cookies enabled', () => {
      const state = {
        necessary: false,
        analytics: false,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.necessary).toBe(true);
    });

    it('should allow independent control of analytics cookies', () => {
      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.analytics).toBe(true);
      expect(stored.marketing).toBe(false);
    });

    it('should allow independent control of marketing cookies', () => {
      const state = {
        necessary: true,
        analytics: false,
        marketing: true,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.analytics).toBe(false);
      expect(stored.marketing).toBe(true);
    });
  });

  // ============================================
  // Timestamp Tests
  // ============================================
  describe('Timestamp Handling', () => {
    it('should include timestamp in stored consent', () => {
      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      expect(stored.timestamp).toBeDefined();
    });

    it('should generate current timestamp', () => {
      const beforeTime = new Date().toISOString();

      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
      };
      setCookieConsent(state);

      const afterTime = new Date().toISOString();
      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');

      expect(stored.timestamp >= beforeTime).toBe(true);
      expect(stored.timestamp <= afterTime).toBe(true);
    });

    it('should store timestamp in ISO 8601 format', () => {
      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
      };

      setCookieConsent(state);

      const stored = JSON.parse(localStorageMock.getItem(STORAGE_KEY) || '{}');
      const timestamp = new Date(stored.timestamp);
      expect(timestamp.toISOString()).toBe(stored.timestamp);
    });
  });

  // ============================================
  // Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('should handle localStorage not available', () => {
      const originalLocalStorage = global.localStorage;
      // @ts-expect-error - Testing without localStorage
      delete global.localStorage;

      // Should not throw
      expect(() => getCookieConsent()).not.toThrow();

      global.localStorage = originalLocalStorage;
    });

    it('should handle localStorage quota exceeded', () => {
      const originalSetItem = localStorageMock.setItem;
      localStorageMock.setItem = jest.fn(() => {
        throw new DOMException('Quota exceeded', 'QuotaExceededError');
      });

      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
      };

      // Should not throw
      expect(() => setCookieConsent(state)).not.toThrow();

      localStorageMock.setItem = originalSetItem;
    });

    it('should handle corrupted consent data', () => {
      localStorageMock.setItem(STORAGE_KEY, '{"necessary":"invalid","analytics":true,"marketing":false,"timestamp":"2026-03-05T10:00:00Z"}');

      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should handle empty localStorage value', () => {
      localStorageMock.setItem(STORAGE_KEY, '');

      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should handle whitespace-only localStorage value', () => {
      localStorageMock.setItem(STORAGE_KEY, '   ');

      const result = getCookieConsent();
      expect(result).toBeNull();
    });
  });

  // ============================================
  // Type Validation Tests
  // ============================================
  describe('Type Validation', () => {
    it('should validate necessary is boolean', () => {
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
        necessary: 'true',
        analytics: true,
        marketing: false,
        timestamp: '2026-03-05T10:00:00Z',
      }));

      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should validate analytics is boolean', () => {
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
        necessary: true,
        analytics: 'true',
        marketing: false,
        timestamp: '2026-03-05T10:00:00Z',
      }));

      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should validate marketing is boolean', () => {
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
        necessary: true,
        analytics: true,
        marketing: 'false',
        timestamp: '2026-03-05T10:00:00Z',
      }));

      const result = getCookieConsent();
      expect(result).toBeNull();
    });

    it('should validate timestamp is string', () => {
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
        necessary: true,
        analytics: true,
        marketing: false,
        timestamp: 1234567890,
      }));

      const result = getCookieConsent();
      expect(result).toBeNull();
    });
  });

  // ============================================
  // Storage Key Tests
  // ============================================
  describe('Storage Key', () => {
    it('should use correct storage key', () => {
      const state = {
        necessary: true,
        analytics: true,
        marketing: false,
      };

      setCookieConsent(state);

      expect(localStorageMock.setItem).toHaveBeenCalledWith(
        STORAGE_KEY,
        expect.any(String)
      );
    });

    it('should read from correct storage key', () => {
      getCookieConsent();

      expect(localStorageMock.getItem).toHaveBeenCalledWith(STORAGE_KEY);
    });

    it('should remove from correct storage key', () => {
      clearCookieConsent();

      expect(localStorageMock.removeItem).toHaveBeenCalledWith(STORAGE_KEY);
    });
  });
});
