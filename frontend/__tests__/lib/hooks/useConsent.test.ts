/**
 * @jest-environment jsdom
 */
import { renderHook, act, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';

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
    get store() {
      return store;
    },
  };
})();

Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// ============================================================================
// Mock useConsent Hook Implementation
// ============================================================================

const STORAGE_KEY = 'seoindicator_cookie_consent';

interface CookieConsentState {
  necessary: boolean;
  analytics: boolean;
  marketing: boolean;
  timestamp: string;
}

interface UseConsentReturn {
  consent: CookieConsentState | null;
  hasConsent: (type: 'analytics' | 'marketing') => boolean;
  updateConsent: (updates: Partial<Omit<CookieConsentState, 'timestamp'>>) => void;
  clearConsent: () => void;
  showBanner: boolean;
  setShowBanner: (show: boolean) => void;
}

function useConsent(): UseConsentReturn {
  // State for consent
  const [consent, setConsent] = React.useState<CookieConsentState | null>(() => {
    try {
      const stored = localStorageMock.getItem(STORAGE_KEY);
      if (!stored) return null;
      
      const parsed = JSON.parse(stored);
      // Validate structure
      if (
        typeof parsed.necessary === 'boolean' &&
        typeof parsed.analytics === 'boolean' &&
        typeof parsed.marketing === 'boolean' &&
        typeof parsed.timestamp === 'string'
      ) {
        return parsed;
      }
      return null;
    } catch {
      return null;
    }
  });

  // State for showing banner
  const [showBanner, setShowBannerState] = React.useState(() => consent === null);

  // Check consent for specific type
  const hasConsent = React.useCallback(
    (type: 'analytics' | 'marketing'): boolean => {
      return consent?.[type] ?? false;
    },
    [consent]
  );

  // Update consent
  const updateConsent = React.useCallback(
    (updates: Partial<Omit<CookieConsentState, 'timestamp'>>) => {
      const newConsent: CookieConsentState = {
        necessary: true, // Always true
        analytics: updates.analytics ?? consent?.analytics ?? false,
        marketing: updates.marketing ?? consent?.marketing ?? false,
        timestamp: new Date().toISOString(),
      };
      
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify(newConsent));
      setConsent(newConsent);
      setShowBannerState(false);
    },
    [consent]
  );

  // Clear consent
  const clearConsent = React.useCallback(() => {
    localStorageMock.removeItem(STORAGE_KEY);
    setConsent(null);
    setShowBannerState(true);
  }, []);

  // Set show banner
  const setShowBanner = React.useCallback((show: boolean) => {
    setShowBannerState(show);
  }, []);

  return {
    consent,
    hasConsent,
    updateConsent,
    clearConsent,
    showBanner,
    setShowBanner,
  };
}

// Import React for the hook
import React from 'react';

// ============================================================================
// Basic Hook Behavior Tests
// ============================================================================

describe('useConsent Hook - Basic Behavior', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('HB-001: Returns consent state object', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
    expect(result.current.hasConsent).toBeInstanceOf(Function);
    expect(result.current.updateConsent).toBeInstanceOf(Function);
    expect(result.current.clearConsent).toBeInstanceOf(Function);
  });

  it('HB-002: Returns null when no consent stored', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
    expect(result.current.showBanner).toBe(true);
  });

  it('HB-003: Returns stored values when consent exists', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toEqual(storedConsent);
    expect(result.current.showBanner).toBe(false);
  });

  it('HB-004: Returns false for hasConsent with invalid JSON', () => {
    localStorageMock.setItem(STORAGE_KEY, 'invalid-json');
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
    expect(result.current.hasConsent('analytics')).toBe(false);
  });

  it('HB-005: Handles malformed state structure', () => {
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({ foo: 'bar' }));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
  });

  it('HB-006: Returns hasConsent function', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.hasConsent).toBeInstanceOf(Function);
    expect(typeof result.current.hasConsent).toBe('function');
  });
});

// ============================================================================
// hasConsent Function Tests
// ============================================================================

describe('useConsent Hook - hasConsent Function', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('HC-001: hasConsent(analytics) returns true when analytics enabled', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.hasConsent('analytics')).toBe(true);
  });

  it('HC-002: hasConsent(analytics) returns false when disabled', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: false,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.hasConsent('analytics')).toBe(false);
  });

  it('HC-003: hasConsent(marketing) returns true when enabled', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: false,
      marketing: true,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.hasConsent('marketing')).toBe(true);
  });

  it('HC-004: hasConsent(marketing) returns false when disabled', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.hasConsent('marketing')).toBe(false);
  });

  it('HC-005: hasConsent returns false with no stored consent', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.hasConsent('analytics')).toBe(false);
    expect(result.current.hasConsent('marketing')).toBe(false);
  });

  it('HC-006: hasConsent reflects consent changes', async () => {
    const { result } = renderHook(() => useConsent());
    
    // Initially false
    expect(result.current.hasConsent('analytics')).toBe(false);
    
    // Update consent
    act(() => {
      result.current.updateConsent({ analytics: true, marketing: false });
    });
    
    // Now true
    expect(result.current.hasConsent('analytics')).toBe(true);
  });

  it('HC-008: Returns false for null consent', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
    expect(result.current.hasConsent('analytics')).toBe(false);
  });

  it('HC-009: Returns false for malformed consent', () => {
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({ necessary: 'not-a-boolean' }));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
    expect(result.current.hasConsent('analytics')).toBe(false);
  });

  it('HC-010: Memoized result - same reference for same consent', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result, rerender } = renderHook(() => useConsent());
    
    const firstRef = result.current.hasConsent;
    rerender();
    const secondRef = result.current.hasConsent;
    
    expect(firstRef).toBe(secondRef);
  });
});

// ============================================================================
// Consent Update Tests
// ============================================================================

describe('useConsent Hook - Consent Update', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('CU-001: updateConsent function is available', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.updateConsent).toBeInstanceOf(Function);
  });

  it('CU-002: updateConsent writes to localStorage', () => {
    const { result } = renderHook(() => useConsent());
    
    act(() => {
      result.current.updateConsent({ analytics: true, marketing: false });
    });
    
    expect(localStorageMock.setItem).toHaveBeenCalledWith(
      STORAGE_KEY,
      expect.stringContaining('"analytics":true')
    );
  });

  it('CU-003: Triggers re-render with new consent', async () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
    
    act(() => {
      result.current.updateConsent({ analytics: true, marketing: false });
    });
    
    expect(result.current.consent).not.toBeNull();
    expect(result.current.consent?.analytics).toBe(true);
  });

  it('CU-004: Updates timestamp on change', () => {
    const { result } = renderHook(() => useConsent());
    
    act(() => {
      result.current.updateConsent({ analytics: true, marketing: false });
    });
    
    expect(result.current.consent?.timestamp).toBeDefined();
    expect(new Date(result.current.consent!.timestamp).toISOString()).toBe(
      result.current.consent!.timestamp
    );
  });

  it('CU-005: Preserves other values on partial update', () => {
    // Start with consent
    const initialConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: true,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(initialConsent));
    
    const { result } = renderHook(() => useConsent());
    
    // Update only marketing
    act(() => {
      result.current.updateConsent({ marketing: false });
    });
    
    // Analytics should remain true
    expect(result.current.consent?.analytics).toBe(true);
    expect(result.current.consent?.marketing).toBe(false);
  });

  it('CU-006: Hides banner after consent update', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.showBanner).toBe(true);
    
    act(() => {
      result.current.updateConsent({ analytics: true, marketing: false });
    });
    
    expect(result.current.showBanner).toBe(false);
  });
});

// ============================================================================
// Clear Consent Tests
// ============================================================================

describe('useConsent Hook - Clear Consent', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('clearConsent removes from localStorage', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: true,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    act(() => {
      result.current.clearConsent();
    });
    
    expect(localStorageMock.removeItem).toHaveBeenCalledWith(STORAGE_KEY);
    expect(result.current.consent).toBeNull();
  });

  it('clearConsent shows banner again', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: true,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.showBanner).toBe(false);
    
    act(() => {
      result.current.clearConsent();
    });
    
    expect(result.current.showBanner).toBe(true);
  });
});

// ============================================================================
// Show Banner Tests
// ============================================================================

describe('useConsent Hook - Show Banner', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('showBanner is true when no consent', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.showBanner).toBe(true);
  });

  it('showBanner is false when consent exists', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.showBanner).toBe(false);
  });

  it('setShowBanner can manually show banner', () => {
    const storedConsent: CookieConsentState = {
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    };
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify(storedConsent));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.showBanner).toBe(false);
    
    act(() => {
      result.current.setShowBanner(true);
    });
    
    expect(result.current.showBanner).toBe(true);
  });

  it('setShowBanner can hide banner', () => {
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.showBanner).toBe(true);
    
    act(() => {
      result.current.setShowBanner(false);
    });
    
    expect(result.current.showBanner).toBe(false);
  });
});

// ============================================================================
// Edge Cases Tests
// ============================================================================

describe('useConsent Hook - Edge Cases', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('EC-001: Handles empty localStorage value', () => {
    localStorageMock.setItem(STORAGE_KEY, '');
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
  });

  it('EC-002: Handles whitespace-only value', () => {
    localStorageMock.setItem(STORAGE_KEY, '   ');
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
  });

  it('EC-003: Handles missing timestamp field', () => {
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: false,
      // missing timestamp
    }));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
  });

  it('EC-004: Handles missing consent fields', () => {
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      // missing analytics, marketing, timestamp
    }));
    
    const { result } = renderHook(() => useConsent());
    
    expect(result.current.consent).toBeNull();
  });

  it('EC-005: Necessary is always true in updateConsent', () => {
    const { result } = renderHook(() => useConsent());
    
    act(() => {
      // @ts-expect-error - testing invalid input
      result.current.updateConsent({ necessary: false, analytics: true });
    });
    
    // necessary should still be true
    expect(result.current.consent?.necessary).toBe(true);
  });
});
