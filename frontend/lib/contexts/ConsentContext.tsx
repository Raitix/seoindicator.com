"use client";

import {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  type ReactNode,
} from "react";
import {
  getCookieConsent,
  setCookieConsent,
  clearCookieConsent,
  type CookieConsentState,
} from "@/lib/utils/cookieConsent";

/**
 * Consent context for managing cookie consent state
 * TASK-30-5: Cookie Consent Integration
 */

interface ConsentContextValue {
  /** Current consent state, null if no consent given */
  consent: CookieConsentState | null;
  /** Whether user has given any consent */
  hasConsent: boolean;
  /** Check if specific consent type is granted */
  hasConsentFor: (type: "analytics" | "marketing") => boolean;
  /** Update consent preferences */
  updateConsent: (analytics: boolean, marketing: boolean) => void;
  /** Clear all consent (withdraw consent) */
  clearConsent: () => void;
  /** Whether to show the consent banner */
  showBanner: boolean;
  /** Programmatically show the banner (e.g., from footer link) */
  setShowBanner: (show: boolean) => void;
  /** Called when user accepts all cookies */
  onAccept?: (consent: CookieConsentState) => void;
  /** Called when user declines optional cookies */
  onDecline?: (consent: CookieConsentState) => void;
}

const ConsentContext = createContext<ConsentContextValue | null>(null);

interface ConsentProviderProps {
  children: ReactNode;
  /** Callback when user accepts all cookies */
  onAccept?: (consent: CookieConsentState) => void;
  /** Callback when user declines optional cookies */
  onDecline?: (consent: CookieConsentState) => void;
}

/**
 * Provider for cookie consent state management
 *
 * @example
 * ```tsx
 * <ConsentProvider onAccept={handleAccept}>
 *   <App />
 * </ConsentProvider>
 * ```
 */
export function ConsentProvider({
  children,
  onAccept,
  onDecline,
}: ConsentProviderProps) {
  const [consent, setConsent] = useState<CookieConsentState | null>(null);
  const [showBanner, setShowBanner] = useState(false);

  // Load consent state on mount and listen for storage changes
  useEffect(() => {
    const loadConsent = () => {
      const stored = getCookieConsent();
      setConsent(stored);
      // Show banner if no consent exists
      if (!stored) {
        setShowBanner(true);
      }
    };

    // Initial load
    loadConsent();

    // Listen for storage changes (e.g., from other tabs)
    const handleStorageChange = (e: StorageEvent) => {
      if (e.key === "seoindicator_cookie_consent") {
        loadConsent();
      }
    };

    window.addEventListener("storage", handleStorageChange);

    return () => {
      window.removeEventListener("storage", handleStorageChange);
    };
  }, []);

  const hasConsent = consent !== null;

  const hasConsentFor = useCallback(
    (type: "analytics" | "marketing"): boolean => {
      if (!consent) return false;
      return consent[type] === true;
    },
    [consent]
  );

  const updateConsent = useCallback(
    (analytics: boolean, marketing: boolean) => {
      setCookieConsent({
        necessary: true,
        analytics,
        marketing,
      });

      const newConsent = getCookieConsent();
      setConsent(newConsent);
      setShowBanner(false);

      // Call appropriate callback
      if (analytics && marketing) {
        onAccept?.(newConsent!);
      } else {
        onDecline?.(newConsent!);
      }
    },
    [onAccept, onDecline]
  );

  const clearConsent = useCallback(() => {
    clearCookieConsent();
    setConsent(null);
    setShowBanner(true);
  }, []);

  const value: ConsentContextValue = {
    consent,
    hasConsent,
    hasConsentFor,
    updateConsent,
    clearConsent,
    showBanner,
    setShowBanner,
    onAccept,
    onDecline,
  };

  return (
    <ConsentContext.Provider value={value}>{children}</ConsentContext.Provider>
  );
}

/**
 * Hook for accessing consent context
 *
 * @throws Error if used outside ConsentProvider
 */
export function useConsentContext(): ConsentContextValue {
  const context = useContext(ConsentContext);

  if (!context) {
    throw new Error("useConsentContext must be used within a ConsentProvider");
  }

  return context;
}

export type { ConsentContextValue, ConsentProviderProps };
