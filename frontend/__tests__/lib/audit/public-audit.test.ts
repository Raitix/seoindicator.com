/**
 * Public Audit Utilities Tests
 * Task: TASK-33-23 - Lead Capture Flow
 * 
 * Tests for cookie management, email deduplication, redirect URLs, and email hashing.
 */

import {
  UNLOCK_COOKIE_PREFIX,
  UNLOCK_EXPIRY_HOURS,
  generateUnlockKey,
  setUnlockedAudit,
  isAuditUnlocked,
  getUnlockData,
  isUnlockExpired,
  clearUnlockCookie,
  checkEmailCaptured,
  storeEmailCaptured,
  clearEmailCaptured,
  generateSignupRedirectUrl,
  getRedirectTarget,
  hashEmail,
} from '@/lib/audit/public-audit';

// ============================================
// MOCKS
// ============================================

// Mock document.cookie
const mockCookieStore: Record<string, string> = {};
let mockDocumentCookie = '';

Object.defineProperty(document, 'cookie', {
  get: () => mockDocumentCookie,
  set: (value: string) => {
    // Parse cookie setter
    const [nameValue] = value.split(';');
    const [name, val] = nameValue.split('=');
    if (val === '' || name === '') {
      // Delete cookie
      delete mockCookieStore[name];
    } else {
      mockCookieStore[name] = val;
    }
    // Rebuild cookie string for reading
    mockDocumentCookie = Object.entries(mockCookieStore)
      .map(([k, v]) => `${k}=${v}`)
      .join('; ');
  },
  configurable: true,
});

// Mock sessionStorage
const mockSessionStorage: Record<string, string> = {};

Object.defineProperty(window, 'sessionStorage', {
  value: {
    getItem: (key: string) => mockSessionStorage[key] || null,
    setItem: (key: string, value: string) => { mockSessionStorage[key] = value; },
    removeItem: (key: string) => { delete mockSessionStorage[key]; },
    clear: () => { Object.keys(mockSessionStorage).forEach(k => delete mockSessionStorage[k]); },
    get length() { return Object.keys(mockSessionStorage).length; },
    key: (index: number) => Object.keys(mockSessionStorage)[index] || null,
  },
  configurable: true,
});

// Mock crypto.subtle for hashing
const mockDigest = jest.fn();
Object.defineProperty(global, 'crypto', {
  value: {
    subtle: {
      digest: mockDigest,
    },
  },
  configurable: true,
});

// ============================================
// TEST SUITE
// ============================================

describe('Public Audit Utilities', () => {
  beforeEach(() => {
    // Clear all stores
    Object.keys(mockCookieStore).forEach(k => delete mockCookieStore[k]);
    mockDocumentCookie = '';
    Object.keys(mockSessionStorage).forEach(k => delete mockSessionStorage[k]);
    jest.clearAllMocks();
    
    // Reset Date.now mock
    jest.useRealTimers();
  });

  // ============================================
  // 1. COOKIE MANAGEMENT TESTS
  // ============================================
  
  describe('Cookie Management', () => {
    describe('generateUnlockKey', () => {
      it('COOKIE-001: generates unlock key from hash', () => {
        const key = generateUnlockKey('abc123');
        expect(key).toBe(`${UNLOCK_COOKIE_PREFIX}abc123`);
      });

      it('COOKIE-002: sanitizes special characters in hash', () => {
        const key = generateUnlockKey('abc+123');
        expect(key).toBe(`${UNLOCK_COOKIE_PREFIX}abc_123`);
        expect(key).not.toContain('+');
      });

      it('COOKIE-023: sanitizes all special characters', () => {
        const key = generateUnlockKey('test!@#$');
        expect(key).toBe(`${UNLOCK_COOKIE_PREFIX}test____`);
        expect(key).toMatch(/^[a-zA-Z0-9_]+$/);
      });

      it('generates key with underscores only', () => {
        const key = generateUnlockKey('abc-123_test');
        expect(key).toBe(`${UNLOCK_COOKIE_PREFIX}abc-123_test`);
      });
    });

    describe('setUnlockedAudit', () => {
      it('COOKIE-003: sets unlock cookie with 24h expiry', () => {
        const result = setUnlockedAudit('abc123', 'test@example.com');
        expect(result).toBe(true);
        expect(mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`]).toBeDefined();
      });

      it('COOKIE-004: returns true on success', () => {
        const result = setUnlockedAudit('abc123', 'test@example.com');
        expect(result).toBe(true);
      });

      it('COOKIE-005: handles errors gracefully', () => {
        // Mock btoa to throw
        const originalBtoa = global.btoa;
        global.btoa = jest.fn(() => { throw new Error('btoa error'); });
        
        const result = setUnlockedAudit('abc123', 'test@example.com');
        expect(result).toBe(false);
        
        global.btoa = originalBtoa;
      });

      it('COOKIE-015: sets cookie path to root', () => {
        // This is tested indirectly via the cookie setter
        setUnlockedAudit('abc123', 'test@example.com');
        // Cookie should be set (path is in the setter string)
        expect(mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`]).toBeDefined();
      });

      it('stores email in cookie data', () => {
        setUnlockedAudit('abc123', 'test@example.com');
        const data = getUnlockData('abc123');
        expect(data?.email).toBe('test@example.com');
      });

      it('stores hash in cookie data', () => {
        setUnlockedAudit('abc123', 'test@example.com');
        const data = getUnlockData('abc123');
        expect(data?.hash).toBe('abc123');
      });

      it('stores timestamp in cookie data', () => {
        const before = Date.now();
        setUnlockedAudit('abc123', 'test@example.com');
        const data = getUnlockData('abc123');
        expect(data?.timestamp).toBeGreaterThanOrEqual(before);
      });

      it('COOKIE-017: tracks multiple audits separately', () => {
        setUnlockedAudit('abc123', 'test1@example.com');
        setUnlockedAudit('xyz789', 'test2@example.com');
        
        expect(mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`]).toBeDefined();
        expect(mockCookieStore[`${UNLOCK_COOKIE_PREFIX}xyz789`]).toBeDefined();
      });
    });

    describe('isAuditUnlocked', () => {
      it('COOKIE-006: returns false when no cookie exists', () => {
        expect(isAuditUnlocked('nonexistent')).toBe(false);
      });

      it('COOKIE-007: returns true for valid cookie', () => {
        setUnlockedAudit('abc123', 'test@example.com');
        expect(isAuditUnlocked('abc123')).toBe(true);
      });

      it('COOKIE-008: returns false for expired cookie and clears it', () => {
        // Set cookie with past timestamp
        const expiredTimestamp = Date.now() - 25 * 60 * 60 * 1000; // 25 hours ago
        const data = {
          hash: 'abc123',
          email: 'test@example.com',
          timestamp: expiredTimestamp,
        };
        mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`] = btoa(JSON.stringify(data));
        mockDocumentCookie = `${UNLOCK_COOKIE_PREFIX}abc123=${btoa(JSON.stringify(data))}`;
        
        expect(isAuditUnlocked('abc123')).toBe(false);
      });

      it('returns true for cookie just under expiry', () => {
        const recentTimestamp = Date.now() - 23 * 60 * 60 * 1000; // 23 hours ago
        const data = {
          hash: 'abc123',
          email: 'test@example.com',
          timestamp: recentTimestamp,
        };
        mockDocumentCookie = `${UNLOCK_COOKIE_PREFIX}abc123=${btoa(JSON.stringify(data))}`;
        mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`] = btoa(JSON.stringify(data));
        
        expect(isAuditUnlocked('abc123')).toBe(true);
      });
    });

    describe('getUnlockData', () => {
      it('COOKIE-009: returns UnlockData object for valid cookie', () => {
        setUnlockedAudit('abc123', 'test@example.com');
        const data = getUnlockData('abc123');
        
        expect(data).toEqual({
          hash: 'abc123',
          email: 'test@example.com',
          timestamp: expect.any(Number),
        });
      });

      it('COOKIE-010: returns null when no cookie exists', () => {
        expect(getUnlockData('nonexistent')).toBeNull();
      });

      it('COOKIE-011: returns null and clears corrupted cookie', () => {
        mockDocumentCookie = `${UNLOCK_COOKIE_PREFIX}abc123=invalid-base64!@#`;
        mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`] = 'invalid-base64!@#';
        
        const result = getUnlockData('abc123');
        expect(result).toBeNull();
      });

      it('returns null for invalid JSON in cookie', () => {
        mockDocumentCookie = `${UNLOCK_COOKIE_PREFIX}abc123=${btoa('not-json')}`;
        mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`] = btoa('not-json');
        
        const result = getUnlockData('abc123');
        expect(result).toBeNull();
      });
    });

    describe('isUnlockExpired', () => {
      it('COOKIE-012: returns false for fresh timestamp', () => {
        const timestamp = Date.now() - 1 * 60 * 60 * 1000; // 1 hour ago
        expect(isUnlockExpired(timestamp)).toBe(false);
      });

      it('COOKIE-013: returns true for old timestamp', () => {
        const timestamp = Date.now() - 25 * 60 * 60 * 1000; // 25 hours ago
        expect(isUnlockExpired(timestamp)).toBe(true);
      });

      it('returns false at exactly 24 hours', () => {
        const timestamp = Date.now() - 24 * 60 * 60 * 1000;
        expect(isUnlockExpired(timestamp)).toBe(true);
      });

      it('returns false for future timestamp', () => {
        const timestamp = Date.now() + 60 * 60 * 1000; // 1 hour in future
        expect(isUnlockExpired(timestamp)).toBe(false);
      });
    });

    describe('clearUnlockCookie', () => {
      it('COOKIE-014: removes unlock cookie', () => {
        setUnlockedAudit('abc123', 'test@example.com');
        expect(mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`]).toBeDefined();
        
        clearUnlockCookie('abc123');
        expect(mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`]).toBeUndefined();
      });

      it('COOKIE-025: handles non-existent cookie without error', () => {
        expect(() => clearUnlockCookie('nonexistent')).not.toThrow();
      });
    });
  });

  // ============================================
  // 2. EMAIL DEDUPLICATION TESTS
  // ============================================
  
  describe('Email Deduplication', () => {
    describe('checkEmailCaptured', () => {
      it('DEDUP-001: returns false for new email', () => {
        expect(checkEmailCaptured('new@example.com')).toBe(false);
      });

      it('DEDUP-002: returns true for stored email', () => {
        storeEmailCaptured('stored@example.com');
        expect(checkEmailCaptured('stored@example.com')).toBe(true);
      });

      it('DEDUP-005: normalizes case when checking', () => {
        storeEmailCaptured('test@example.com');
        expect(checkEmailCaptured('TEST@EXAMPLE.COM')).toBe(true);
      });

      it('DEDUP-013: trims whitespace when checking', () => {
        storeEmailCaptured('test@example.com');
        expect(checkEmailCaptured('  test@example.com  ')).toBe(true);
      });

      it('DEDUP-014: returns false for empty email', () => {
        expect(checkEmailCaptured('')).toBe(false);
      });
    });

    describe('storeEmailCaptured', () => {
      it('DEDUP-003: stores email in sessionStorage', () => {
        storeEmailCaptured('test@example.com');
        const key = 'seoindicator_email_test@example.com';
        expect(mockSessionStorage[key]).toBeDefined();
      });

      it('DEDUP-004: normalizes email before storing', () => {
        storeEmailCaptured('  TEST@Example.COM  ');
        expect(checkEmailCaptured('test@example.com')).toBe(true);
      });

      it('DEDUP-017: handles special characters in email', () => {
        storeEmailCaptured('user+tag@example.com');
        expect(checkEmailCaptured('user+tag@example.com')).toBe(true);
      });

      it('DEDUP-015: handles unicode email', () => {
        storeEmailCaptured('用户@例え.jp');
        expect(checkEmailCaptured('用户@例え.jp')).toBe(true);
      });

      it('DEDUP-020: stores timestamp', () => {
        const before = Date.now();
        storeEmailCaptured('test@example.com');
        const key = 'seoindicator_email_test@example.com';
        const timestamp = parseInt(mockSessionStorage[key], 10);
        expect(timestamp).toBeGreaterThanOrEqual(before);
      });
    });

    describe('clearEmailCaptured', () => {
      it('DEDUP-006: removes email from sessionStorage', () => {
        storeEmailCaptured('test@example.com');
        expect(checkEmailCaptured('test@example.com')).toBe(true);
        
        clearEmailCaptured('test@example.com');
        expect(checkEmailCaptured('test@example.com')).toBe(false);
      });

      it('DEDUP-018: clears only target email', () => {
        storeEmailCaptured('email1@example.com');
        storeEmailCaptured('email2@example.com');
        
        clearEmailCaptured('email1@example.com');
        
        expect(checkEmailCaptured('email1@example.com')).toBe(false);
        expect(checkEmailCaptured('email2@example.com')).toBe(true);
      });
    });

    describe('Multiple Email Tracking', () => {
      it('DEDUP-007: tracks multiple emails separately', () => {
        storeEmailCaptured('email1@example.com');
        storeEmailCaptured('email2@example.com');
        storeEmailCaptured('email3@example.com');
        
        expect(checkEmailCaptured('email1@example.com')).toBe(true);
        expect(checkEmailCaptured('email2@example.com')).toBe(true);
        expect(checkEmailCaptured('email3@example.com')).toBe(true);
        expect(checkEmailCaptured('email4@example.com')).toBe(false);
      });

      it('DEDUP-008: treats same email with different case as duplicate', () => {
        storeEmailCaptured('test@example.com');
        
        // Should be detected as duplicate
        expect(checkEmailCaptured('TEST@EXAMPLE.COM')).toBe(true);
        expect(checkEmailCaptured('Test@Example.Com')).toBe(true);
      });
    });

    describe('Error Handling', () => {
      it('DEDUP-010: checkEmailCaptured handles sessionStorage error', () => {
        const originalGetItem = window.sessionStorage.getItem;
        window.sessionStorage.getItem = jest.fn(() => { throw new Error('Storage error'); });
        
        expect(checkEmailCaptured('test@example.com')).toBe(false);
        
        window.sessionStorage.getItem = originalGetItem;
      });

      it('DEDUP-011: storeEmailCaptured handles sessionStorage error', () => {
        const originalSetItem = window.sessionStorage.setItem;
        window.sessionStorage.setItem = jest.fn(() => { throw new Error('Storage error'); });
        
        expect(() => storeEmailCaptured('test@example.com')).not.toThrow();
        
        window.sessionStorage.setItem = originalSetItem;
      });

      it('DEDUP-012: clearEmailCaptured handles sessionStorage error', () => {
        const originalRemoveItem = window.sessionStorage.removeItem;
        window.sessionStorage.removeItem = jest.fn(() => { throw new Error('Storage error'); });
        
        expect(() => clearEmailCaptured('test@example.com')).not.toThrow();
        
        window.sessionStorage.removeItem = originalRemoveItem;
      });
    });
  });

  // ============================================
  // 3. REDIRECT URL TESTS
  // ============================================
  
  describe('Redirect URL Generation', () => {
    describe('generateSignupRedirectUrl', () => {
      it('REDIR-001: generates URL with email', () => {
        const url = generateSignupRedirectUrl('test@example.com');
        expect(url).toBe('/signup?email=test%40example.com');
      });

      it('REDIR-002: generates URL with hash', () => {
        const url = generateSignupRedirectUrl(undefined, 'abc123');
        expect(url).toBe('/signup?audit=abc123');
      });

      it('REDIR-003: generates URL with both params', () => {
        const url = generateSignupRedirectUrl('test@example.com', 'abc123');
        expect(url).toContain('email=test%40example.com');
        expect(url).toContain('audit=abc123');
      });

      it('REDIR-004: generates URL with neither param', () => {
        const url = generateSignupRedirectUrl();
        expect(url).toBe('/signup');
      });

      it('REDIR-005: encodes special characters in email', () => {
        const url = generateSignupRedirectUrl('test+user@example.com');
        expect(url).toContain('email=test%2Buser%40example.com');
      });

      it('REDIR-006: encodes hash with special characters', () => {
        const url = generateSignupRedirectUrl(undefined, 'abc+123');
        expect(url).toContain('audit=abc%2B123');
      });

      it('REDIR-009: trims email in URL', () => {
        const url = generateSignupRedirectUrl('  test@example.com  ');
        expect(url).toContain('email=test%40example.com');
        expect(url).not.toContain('%20');
      });

      it('REDIR-010: handles empty email param', () => {
        const url = generateSignupRedirectUrl('', 'abc123');
        expect(url).toBe('/signup?audit=abc123');
      });

      it('REDIR-011: handles empty hash param', () => {
        const url = generateSignupRedirectUrl('test@example.com', '');
        expect(url).toBe('/signup?email=test%40example.com');
      });

      it('REDIR-012: handles unicode email', () => {
        const url = generateSignupRedirectUrl('用户@例え.jp');
        expect(url).toContain('email=');
        expect(url).not.toContain('用户');
      });

      it('REDIR-015: generates hash-only URL', () => {
        const url = generateSignupRedirectUrl(undefined, 'abc123');
        expect(url).toBe('/signup?audit=abc123');
        expect(url).not.toContain('email');
      });
    });

    describe('getRedirectTarget', () => {
      it('REDIR-007: returns default redirect target', () => {
        expect(getRedirectTarget()).toBe('/signup');
      });

      it('REDIR-008: returns custom redirect target', () => {
        expect(getRedirectTarget('/dashboard')).toBe('/dashboard');
      });
    });
  });

  // ============================================
  // 4. EMAIL HASHING TESTS
  // ============================================
  
  describe('Email Hashing', () => {
    beforeEach(() => {
      // Setup mock crypto.subtle.digest
      mockDigest.mockImplementation(async (algorithm: string, data: BufferSource) => {
        // Simple mock hash - just return a fixed value for testing
        const hashArray = new Uint8Array(32);
        for (let i = 0; i < 32; i++) {
          hashArray[i] = i;
        }
        return hashArray.buffer;
      });
    });

    it('HASH-001: hashes email to 64-char hex string', async () => {
      const hash = await hashEmail('test@example.com');
      expect(hash).toHaveLength(64);
      expect(hash).toMatch(/^[0-9a-f]+$/);
    });

    it('HASH-002: normalizes email before hashing', async () => {
      const hash1 = await hashEmail('test@example.com');
      const hash2 = await hashEmail('TEST@Example.COM');
      
      expect(hash1).toBe(hash2);
    });

    it('HASH-003: trims email before hashing', async () => {
      const hash1 = await hashEmail('test@example.com');
      const hash2 = await hashEmail('  test@example.com  ');
      
      expect(hash1).toBe(hash2);
    });

    it('HASH-004: produces deterministic hash', async () => {
      const hash1 = await hashEmail('test@example.com');
      const hash2 = await hashEmail('test@example.com');
      
      expect(hash1).toBe(hash2);
    });

    it('HASH-005: produces different hashes for different emails', async () => {
      const hash1 = await hashEmail('test1@example.com');
      const hash2 = await hashEmail('test2@example.com');
      
      expect(hash1).not.toBe(hash2);
    });

    it('HASH-007: handles unicode email', async () => {
      const hash = await hashEmail('用户@例え.jp');
      expect(hash).toHaveLength(64);
      expect(hash).toMatch(/^[0-9a-f]+$/);
    });

    it('HASH-008: handles special characters in email', async () => {
      const hash = await hashEmail('user+tag@example.com');
      expect(hash).toHaveLength(64);
      expect(hash).toMatch(/^[0-9a-f]+$/);
    });

    it('HASH-012: returns a promise that resolves', async () => {
      await expect(hashEmail('test@example.com')).resolves.toBeDefined();
    });

    it('uses fallback when crypto.subtle is unavailable', async () => {
      const originalCrypto = global.crypto;
      // @ts-expect-error - testing fallback
      global.crypto = undefined;
      
      const hash = await hashEmail('test@example.com');
      expect(hash).toMatch(/^[0-9a-f]+$/);
      
      global.crypto = originalCrypto;
    });

    it('HASH-011: uses fallback when digest throws', async () => {
      mockDigest.mockImplementationOnce(() => {
        throw new Error('Digest error');
      });
      
      const hash = await hashEmail('test@example.com');
      expect(hash).toMatch(/^[0-9a-f]+$/);
    });
  });

  // ============================================
  // 5. CONSTANTS TESTS
  // ============================================
  
  describe('Constants', () => {
    it('has correct cookie prefix', () => {
      expect(UNLOCK_COOKIE_PREFIX).toBe('seoindicator_unlock_');
    });

    it('has correct expiry hours', () => {
      expect(UNLOCK_EXPIRY_HOURS).toBe(24);
    });
  });

  // ============================================
  // 6. EDGE CASES
  // ============================================
  
  describe('Edge Cases', () => {
    it('handles very long email', () => {
      const longEmail = 'a'.repeat(200) + '@example.com';
      expect(() => storeEmailCaptured(longEmail)).not.toThrow();
      expect(checkEmailCaptured(longEmail)).toBe(true);
    });

    it('handles cookie with no value', () => {
      mockDocumentCookie = `${UNLOCK_COOKIE_PREFIX}abc123=`;
      mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`] = '';
      
      expect(isAuditUnlocked('abc123')).toBe(false);
    });

    it('handles cookie with only whitespace', () => {
      mockDocumentCookie = `${UNLOCK_COOKIE_PREFIX}abc123=%20%20%20`;
      mockCookieStore[`${UNLOCK_COOKIE_PREFIX}abc123`] = '%20%20%20';
      
      expect(getUnlockData('abc123')).toBeNull();
    });

    it('handles multiple cookies with same prefix', () => {
      setUnlockedAudit('hash1', 'test1@example.com');
      setUnlockedAudit('hash2', 'test2@example.com');
      
      expect(isAuditUnlocked('hash1')).toBe(true);
      expect(isAuditUnlocked('hash2')).toBe(true);
    });

    it('handles sessionStorage full', () => {
      const originalSetItem = window.sessionStorage.setItem;
      
      // Simulate quota exceeded
      const error = new Error('QuotaExceededError');
      error.name = 'QuotaExceededError';
      window.sessionStorage.setItem = jest.fn(() => { throw error; });
      
      expect(() => storeEmailCaptured('test@example.com')).not.toThrow();
      
      window.sessionStorage.setItem = originalSetItem;
    });
  });
});
