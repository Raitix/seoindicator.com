"use client";

import { useEffect, useRef, useCallback, useState } from "react";
import Link from "next/link";
import {
  getCookieConsent,
  setCookieConsent,
  hasCookieConsent,
  type CookieConsentState,
} from "@/lib/utils/cookieConsent";
import { cn } from "@/lib/utils";
import { useConsentContext } from "@/lib/contexts/ConsentContext";

export interface CookieConsentProps {
  /** Additional CSS classes for the banner */
  className?: string;
  /** Callback when user accepts all cookies */
  onAccept?: (consent: CookieConsentState) => void;
  /** Callback when user declines optional cookies */
  onDecline?: (consent: CookieConsentState) => void;
  /** Show customize button for granular cookie preferences */
  showCustomize?: boolean;
  /** Control banner visibility externally (overrides internal state) */
  visible?: boolean;
  /** Callback when banner visibility changes */
  onVisibleChange?: (visible: boolean) => void;
}

/**
 * GDPR/CCPA compliant cookie consent banner
 *
 * Features:
 * - Fixed position at bottom of viewport
 * - Appears on first visit (no localStorage record)
 * - Disappears after user action
 * - Can be shown again via visible prop (for footer link)
 * - No close button - user must make explicit choice
 * - Full keyboard navigation and screen reader support
 */
export default function CookieConsent({
  className,
  onAccept,
  onDecline,
  showCustomize = false,
  visible,
  onVisibleChange,
}: CookieConsentProps) {
  // Try to use context, fall back to internal state if not available
  let contextShowBanner: boolean | undefined;
  let contextSetShowBanner: ((show: boolean) => void) | undefined;

  try {
    const context = useConsentContext();
    contextShowBanner = context.showBanner;
    contextSetShowBanner = context.setShowBanner;
  } catch {
    // Context not available, use internal state
  }

  // Use external control if provided, otherwise context, otherwise internal state
  const [internalVisible, setInternalVisible] = useState(false);
  const isVisible = visible !== undefined ? visible : contextShowBanner !== undefined ? contextShowBanner : internalVisible;

  const [isExiting, setIsExiting] = useState(false);
  const [showPreferences, setShowPreferences] = useState(false);
  const [preferences, setPreferences] = useState({
    analytics: true,
    marketing: true,
  });
  const [isProcessing, setIsProcessing] = useState(false);
  const acceptButtonRef = useRef<HTMLButtonElement>(null);
  const previousFocusRef = useRef<HTMLElement | null>(null);

  // Set visibility based on consent state
  const setShowBanner = useCallback((show: boolean) => {
    if (onVisibleChange) {
      onVisibleChange(show);
    } else if (contextSetShowBanner) {
      contextSetShowBanner(show);
    } else {
      setInternalVisible(show);
    }
  }, [onVisibleChange, contextSetShowBanner]);

  // Check if consent already exists on mount or when visibility changes
  useEffect(() => {
    if (visible === undefined && contextShowBanner === undefined) {
      // Internal mode: check localStorage on mount
      if (!hasCookieConsent()) {
        previousFocusRef.current = document.activeElement as HTMLElement;
        setInternalVisible(true);
      }
    }
  }, [visible, contextShowBanner]);

  // Focus the accept button when banner appears
  useEffect(() => {
    if (isVisible && acceptButtonRef.current) {
      previousFocusRef.current = document.activeElement as HTMLElement;
      acceptButtonRef.current.focus();
    }
  }, [isVisible]);

  const handleDismiss = useCallback(() => {
    setIsExiting(true);

    // Store the element to focus before we start unmounting
    const elementToFocus =
      previousFocusRef.current &&
      document.body.contains(previousFocusRef.current)
        ? previousFocusRef.current
        : null;

    // Wait for exit animation before hiding
    setTimeout(() => {
      setShowBanner(false);
      setIsExiting(false);
      setIsProcessing(false);
      setShowPreferences(false);

      // Restore focus to previous element or body
      // Use RAF to ensure DOM has updated
      requestAnimationFrame(() => {
        if (elementToFocus && document.body.contains(elementToFocus)) {
          elementToFocus.focus();
        } else {
          document.body.focus();
        }
      });
    }, 300);
  }, [setShowBanner]);

  const handleAcceptAll = useCallback(() => {
    if (isProcessing) return;
    setIsProcessing(true);

    const consent = {
      necessary: true,
      analytics: true,
      marketing: true,
    };

    try {
      setCookieConsent(consent);
    } catch {
      // Handle localStorage errors gracefully
    }
    // Call callback with the consent (tests expect this format without timestamp)
    onAccept?.(consent as CookieConsentState);
    handleDismiss();
  }, [onAccept, handleDismiss, isProcessing]);

  const handleNecessaryOnly = useCallback(() => {
    if (isProcessing) return;
    setIsProcessing(true);

    const consent = {
      necessary: true,
      analytics: false,
      marketing: false,
    };

    try {
      setCookieConsent(consent);
    } catch {
      // Handle localStorage errors gracefully
    }
    // Call callback with the consent (tests expect this format without timestamp)
    onDecline?.(consent as CookieConsentState);
    handleDismiss();
  }, [onDecline, handleDismiss, isProcessing]);

  const handleSavePreferences = useCallback(() => {
    if (isProcessing) return;
    setIsProcessing(true);

    const consent = {
      necessary: true,
      analytics: preferences.analytics,
      marketing: preferences.marketing,
    };

    try {
      setCookieConsent(consent);
    } catch {
      // Handle localStorage errors gracefully
    }

    if (preferences.analytics && preferences.marketing) {
      onAccept?.(consent as CookieConsentState);
    } else {
      onDecline?.(consent as CookieConsentState);
    }

    handleDismiss();
  }, [preferences, onAccept, onDecline, handleDismiss, isProcessing]);

  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent) => {
      // Escape should NOT close the banner (GDPR requirement - user must make a choice)
      // But we still handle it to prevent bubbling
      if (e.key === "Escape") {
        e.preventDefault();
        e.stopPropagation();
        // Do nothing - user must make explicit choice
      }
    },
    []
  );

  // Don't render if not visible
  if (!isVisible) {
    return null;
  }

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="cookie-consent-title"
      aria-describedby="cookie-consent-description"
      className={cn(
        "fixed bottom-0 left-0 right-0 z-50",
        "bg-slate-900 border-t border-slate-700",
        "transform transition-transform duration-300 ease-out",
        isExiting ? "translate-y-full" : "translate-y-0",
        "p-4 md:p-6",
        className
      )}
      onKeyDown={handleKeyDown}
      data-testid="cookie-consent-banner"
    >
      {/* Screen reader announcement for consent changes */}
      <div className="sr-only" aria-live="polite" aria-atomic="true">
        {isVisible &&
          "Consent dialog appeared. Please accept or decline to continue."}
      </div>

      <div className="container mx-auto max-w-4xl">
        <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
          {/* Content */}
          <div className="flex-1">
            <h2
              id="cookie-consent-title"
              className="text-lg font-semibold text-white mb-2"
            >
              Cookies & Privacy
            </h2>
            <p
              id="cookie-consent-description"
              className="text-sm text-slate-300 mb-4"
            >
              We use cookies to enhance your experience. Necessary cookies are
              essential for the site to function. You can choose to accept all
              cookies or only necessary ones.{" "}
              <a
                href="/terms"
                className="text-blue-400 hover:text-blue-300 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 rounded"
                aria-label="Learn more about our privacy policy"
              >
                Learn more
              </a>
            </p>

            {/* Preferences Panel (optional) */}
            {showCustomize && showPreferences && (
              <div
                className="bg-slate-800 rounded-lg p-4 mb-4"
                role="group"
                aria-label="Cookie preferences"
              >
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <label className="text-sm font-medium text-white">
                        Necessary Cookies
                      </label>
                      <p className="text-xs text-slate-400">
                        Essential for site functionality
                      </p>
                    </div>
                    <span className="text-xs text-slate-500 bg-slate-700 px-2 py-1 rounded">
                      Always On
                    </span>
                  </div>

                  <div className="flex items-center justify-between">
                    <div>
                      <label
                        htmlFor="analytics-cookies"
                        className="text-sm font-medium text-white"
                      >
                        Analytics Cookies
                      </label>
                      <p className="text-xs text-slate-400">
                        Help us improve our service
                      </p>
                    </div>
                    <button
                      id="analytics-cookies"
                      type="button"
                      role="switch"
                      aria-checked={preferences.analytics}
                      onClick={() =>
                        setPreferences((p) => ({ ...p, analytics: !p.analytics }))
                      }
                      className={cn(
                        "relative inline-flex h-6 w-11 items-center rounded-full transition-colors",
                        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-800",
                        preferences.analytics ? "bg-blue-600" : "bg-slate-600"
                      )}
                    >
                      <span
                        className={cn(
                          "inline-block h-4 w-4 transform rounded-full bg-white transition-transform",
                          preferences.analytics
                            ? "translate-x-6"
                            : "translate-x-1"
                        )}
                      />
                    </button>
                  </div>

                  <div className="flex items-center justify-between">
                    <div>
                      <label
                        htmlFor="marketing-cookies"
                        className="text-sm font-medium text-white"
                      >
                        Marketing Cookies
                      </label>
                      <p className="text-xs text-slate-400">
                        Personalized advertising
                      </p>
                    </div>
                    <button
                      id="marketing-cookies"
                      type="button"
                      role="switch"
                      aria-checked={preferences.marketing}
                      onClick={() =>
                        setPreferences((p) => ({
                          ...p,
                          marketing: !p.marketing,
                        }))
                      }
                      className={cn(
                        "relative inline-flex h-6 w-11 items-center rounded-full transition-colors",
                        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-800",
                        preferences.marketing ? "bg-blue-600" : "bg-slate-600"
                      )}
                    >
                      <span
                        className={cn(
                          "inline-block h-4 w-4 transform rounded-full bg-white transition-transform",
                          preferences.marketing
                            ? "translate-x-6"
                            : "translate-x-1"
                        )}
                      />
                    </button>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handleSavePreferences}
                  className="mt-4 w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-800"
                >
                  Save Preferences
                </button>
              </div>
            )}
          </div>

          {/* Buttons */}
          <div className="flex flex-col sm:flex-row gap-2 sm:gap-3 md:flex-col lg:flex-row shrink-0">
            <button
              ref={acceptButtonRef}
              type="button"
              onClick={handleAcceptAll}
              aria-label="Accept all"
              className={cn(
                "px-6 py-2.5 text-sm font-medium rounded-lg transition-colors",
                "bg-blue-600 text-white hover:bg-blue-700",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900",
                "min-w-[140px]"
              )}
            >
              Accept All
            </button>

            <button
              type="button"
              onClick={handleNecessaryOnly}
              aria-label="Necessary only"
              className={cn(
                "px-6 py-2.5 text-sm font-medium rounded-lg transition-colors",
                "bg-slate-700 text-white hover:bg-slate-600",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900",
                "min-w-[140px]"
              )}
            >
              Necessary Only
            </button>

            {showCustomize && !showPreferences && (
              <button
                type="button"
                onClick={() => setShowPreferences(true)}
                aria-label="Customize cookie preferences"
                className={cn(
                  "px-6 py-2.5 text-sm font-medium rounded-lg transition-colors",
                  "bg-transparent text-slate-300 hover:text-white hover:bg-slate-800 border border-slate-600",
                  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900",
                  "min-w-[140px]"
                )}
              >
                Customize
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Status message for screen readers after action */}
      <div className="sr-only" role="status" aria-live="polite">
        {isExiting && "Cookie consent saved. Dialog closing."}
      </div>
    </div>
  );
}
