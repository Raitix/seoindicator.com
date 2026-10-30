"use client";

import { useState, useEffect, useCallback } from "react";
import {
  getCookieConsent,
  setCookieConsent,
  clearCookieConsent,
  type CookieConsentState,
} from "@/lib/utils/cookieConsent";

/**
 * Cookie consent management hook
 * TASK-30-5: Cookie Consent Integration
 *
 * Provides consent state management and banner visibility control
 * for GDPR/CCPA compliance.
 */

interface UseConsentReturn {
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
}

/**
 * Hook for managing cookie consent state
 *
 * @example
 * ```tsx
 * const { hasConsentFor, showBanner, setShowBanner } = useConsent();
 *
 * // Check consent before loading analytics
 * if (hasConsentFor('analytics')) {
 *   loadAnalytics();
 * }
 *
 * // Show banner from footer link
 * <button onClick={() => setShowBanner(true)}>Cookie Settings</button>
 * ```
 */
export function useConsent(): UseConsentReturn {
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
    },
    []
  );

  const clearConsent = useCallback(() => {
    clearCookieConsent();
    setConsent(null);
    setShowBanner(true);
  }, []);

  return {
    consent,
    hasConsent,
    hasConsentFor,
    updateConsent,
    clearConsent,
    showBanner,
    setShowBanner,
  };
}

export type { UseConsentReturn };
