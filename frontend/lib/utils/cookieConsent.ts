/**
 * Cookie Consent Utilities
 * TASK-30-4: Cookie Consent Component
 *
 * localStorage-based cookie consent state management.
 * GDPR/CCPA compliant - no pre-checked consent, explicit user action required.
 */

/**
 * Cookie consent state stored in localStorage
 */
export interface CookieConsentState {
  /** Always true - necessary for site functionality */
  necessary: boolean;
  /** User choice for analytics cookies */
  analytics: boolean;
  /** User choice for marketing cookies */
  marketing: boolean;
  /** ISO 8601 timestamp when consent was given */
  timestamp: string;
}

/** localStorage key for cookie consent */
const STORAGE_KEY = 'seoindicator_cookie_consent';

/**
 * Safely check if localStorage is available
 */
function isLocalStorageAvailable(): boolean {
  try {
    const testKey = '__storage_test__';
    window.localStorage.setItem(testKey, testKey);
    window.localStorage.removeItem(testKey);
    return true;
  } catch {
    return false;
  }
}

/**
 * Validates that a value is a valid CookieConsentState
 */
function isValidConsentState(value: unknown): value is CookieConsentState {
  if (!value || typeof value !== 'object') {
    return false;
  }

  const state = value as Record<string, unknown>;

  return (
    typeof state.necessary === 'boolean' &&
    typeof state.analytics === 'boolean' &&
    typeof state.marketing === 'boolean' &&
    typeof state.timestamp === 'string'
  );
}

/**
 * Get the current cookie consent state from localStorage
 * @returns CookieConsentState if valid consent exists, null otherwise
 */
export function getCookieConsent(): CookieConsentState | null {
  if (typeof window === 'undefined' || !isLocalStorageAvailable()) {
    return null;
  }

  try {
    const stored = localStorage.getItem(STORAGE_KEY);

    if (!stored || stored.trim() === '') {
      return null;
    }

    const parsed = JSON.parse(stored);

    if (!isValidConsentState(parsed)) {
      return null;
    }

    return parsed;
  } catch {
    return null;
  }
}

/**
 * Store cookie consent state in localStorage
 * Always sets necessary to true (required for site functionality)
 * Automatically adds timestamp if not provided
 *
 * @param consent - The consent choices (timestamp auto-generated if omitted)
 */
export function setCookieConsent(
  consent: Omit<CookieConsentState, 'timestamp'> & { timestamp?: string }
): void {
  if (typeof window === 'undefined' || !isLocalStorageAvailable()) {
    return;
  }

  try {
    const state: CookieConsentState = {
      necessary: true, // Always true - necessary cookies are required
      analytics: consent.analytics,
      marketing: consent.marketing,
      timestamp: consent.timestamp || new Date().toISOString(),
    };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  } catch {
    // Silently fail on quota errors or other localStorage issues
    console.warn('Failed to save cookie consent to localStorage');
  }
}

/**
 * Check if user has given any cookie consent
 * @returns true if valid consent exists, false otherwise
 */
export function hasCookieConsent(): boolean {
  return getCookieConsent() !== null;
}

/**
 * Clear cookie consent from localStorage
 * Used for testing or when user withdraws consent
 */
export function clearCookieConsent(): void {
  if (typeof window === 'undefined' || !isLocalStorageAvailable()) {
    return;
  }

  try {
    localStorage.removeItem(STORAGE_KEY);
  } catch {
    // Silently fail
    console.warn('Failed to clear cookie consent from localStorage');
  }
}
