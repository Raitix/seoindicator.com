/**
 * useABTest Hook
 * Task: TASK-32-18 - A/B Test Infrastructure for CTAs
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * React hook for A/B test variant assignment with localStorage persistence
 * and analytics tracking.
 */

'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import {
  CTA_TESTS,
  getTestById,
  isTestActive,
  assignVariant,
  shouldIncludeInTest,
  type ABTest,
} from '@/lib/ab-tests';
import { trackEvent } from '@/lib/analytics';

// ============================================================================
// Storage Helpers
// ============================================================================

/**
 * Safely get item from localStorage
 * Returns null if localStorage is unavailable or throws an error
 */
function safeGetItem(key: string): string | null {
  try {
    if (typeof window === 'undefined' || !window.localStorage) {
      return null;
    }
    return localStorage.getItem(key);
  } catch {
    // localStorage may be disabled or throw in private mode
    return null;
  }
}

/**
 * Safely set item in localStorage
 * Returns false if localStorage is unavailable or throws an error
 */
function safeSetItem(key: string, value: string): boolean {
  try {
    if (typeof window === 'undefined' || !window.localStorage) {
      return false;
    }
    localStorage.setItem(key, value);
    return true;
  } catch {
    // localStorage may be disabled, full, or throw in private mode
    return false;
  }
}

/**
 * Get the storage key for a test
 */
function getStorageKey(testId: string): string {
  return `ab-${testId}`;
}

// ============================================================================
// Main Hook
// ============================================================================

/**
 * useABTest Hook
 *
 * Returns the variant ID for the given A/B test, with the following behavior:
 * 1. Check if test exists and is active
 * 2. Check if user is included in test (traffic allocation)
 * 3. Check localStorage for existing variant assignment
 * 4. If no existing assignment, assign variant based on weights
 * 5. Track exposure event on first assignment
 * 6. Persist variant to localStorage
 *
 * @param testId - The A/B test identifier
 * @returns The variant ID (default: 'a' for control)
 *
 * @example
 * ```tsx
 * function MyComponent() {
 *   const variant = useABTest('comparison-cta-text');
 *   const config = getTestConfig('comparison-cta-text', variant);
 *
 *   return <button>{config?.text || 'Default Text'}</button>;
 * }
 * ```
 */
export function useABTest(testId: string): string {
  // Always start with control variant
  const [variant, setVariant] = useState<string>('a');
  const exposureTrackedRef = useRef(false);

  useEffect(() => {
    // Validate testId
    if (!testId || typeof testId !== 'string' || testId.trim() === '') {
      setVariant('a');
      return;
    }

    // Get test configuration
    const test = getTestById(testId);
    if (!test) {
      // Test not found - return control
      setVariant('a');
      return;
    }

    // Check if test is active
    if (!isTestActive(test)) {
      // Test not active - return control
      setVariant('a');
      return;
    }

    // Check if user is included in test based on traffic allocation
    if (!shouldIncludeInTest(test.trafficAllocation)) {
      // User not in test - return control
      setVariant('a');
      return;
    }

    // Check localStorage for existing variant
    const storageKey = getStorageKey(testId);
    const existingVariant = safeGetItem(storageKey);

    if (existingVariant) {
      // Validate existing variant is valid for this test
      const validVariantIds = test.variants.map((v) => v.id);
      if (validVariantIds.includes(existingVariant)) {
        setVariant(existingVariant);
        // Don't track exposure again for existing assignment
        exposureTrackedRef.current = true;
        return;
      }
      // Invalid variant in storage - will reassign
    }

    // Assign new variant based on weights
    const assignedVariant = assignVariant(test.variants);
    setVariant(assignedVariant);

    // Persist to localStorage
    safeSetItem(storageKey, assignedVariant);

    // Track exposure event (only on new assignment)
    if (!exposureTrackedRef.current) {
      trackExposure(testId, assignedVariant, test);
      exposureTrackedRef.current = true;
    }
  }, [testId]);

  return variant;
}

// ============================================================================
// Tracking Helpers
// ============================================================================

/**
 * Track A/B test exposure event
 */
function trackExposure(testId: string, variantId: string, test: ABTest): void {
  try {
    trackEvent('ab_test_exposure', {
      test_id: testId,
      variant: variantId,
      test_name: test.name,
      page: typeof window !== 'undefined' ? window.location.pathname : '',
      timestamp: new Date().toISOString(),
    });
  } catch {
    // Analytics errors should not break functionality
    // Silently fail
  }
}

/**
 * Track A/B test conversion event
 * Call this when a user completes the desired action (e.g., clicks CTA)
 *
 * @param testId - The test identifier
 * @param variantId - The variant identifier
 * @param conversionType - Type of conversion (e.g., 'cta_click', 'signup')
 */
export function trackConversion(
  testId: string,
  variantId: string,
  conversionType: string = 'cta_click'
): void {
  try {
    trackEvent('ab_test_conversion', {
      test_id: testId,
      variant: variantId,
      conversion_type: conversionType,
      page: typeof window !== 'undefined' ? window.location.pathname : '',
      timestamp: new Date().toISOString(),
    });
  } catch {
    // Analytics errors should not break functionality
    // Silently fail
  }
}

// ============================================================================
// Additional Hooks
// ============================================================================

/**
 * Hook to get the full test config for the current variant
 *
 * @param testId - The test identifier
 * @returns The variant config object or undefined
 */
export function useABTestConfig(testId: string): Record<string, unknown> | undefined {
  const variant = useABTest(testId);
  const [config, setConfig] = useState<Record<string, unknown> | undefined>(undefined);

  useEffect(() => {
    const test = getTestById(testId);
    if (!test) {
      setConfig(undefined);
      return;
    }

    const variantObj = test.variants.find((v) => v.id === variant);
    setConfig(variantObj?.config);
  }, [testId, variant]);

  return config;
}

/**
 * Hook to track A/B test conversion on click
 *
 * @param testId - The test identifier
 * @param conversionType - Type of conversion
 * @returns Click handler wrapper function
 *
 * @example
 * ```tsx
 * function MyButton() {
 *   const variant = useABTest('comparison-cta-text');
 *   const trackClick = useABTestConversion('comparison-cta-text', variant);
 *
 *   return (
 *     <button onClick={(e) => {
 *       trackClick();
 *       // ... other click handling
 *     }}>
 *       Click Me
 *     </button>
 *   );
 * }
 * ```
 */
export function useABTestConversion(testId: string, variantId: string) {
  return useCallback(() => {
    trackConversion(testId, variantId, 'cta_click');
  }, [testId, variantId]);
}

// ============================================================================
// Utility Functions
// ============================================================================

/**
 * Clear A/B test variant from localStorage
 * Useful for testing or when user logs out
 *
 * @param testId - The test identifier (optional, clears all if not provided)
 */
export function clearABTestVariant(testId?: string): void {
  try {
    if (typeof window === 'undefined' || !window.localStorage) {
      return;
    }

    if (testId) {
      localStorage.removeItem(getStorageKey(testId));
    } else {
      // Clear all A/B test keys
      const keysToRemove: string[] = [];
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith('ab-')) {
          keysToRemove.push(key);
        }
      }
      keysToRemove.forEach((key) => localStorage.removeItem(key));
    }
  } catch {
    // Silently fail
  }
}

/**
 * Get all assigned A/B test variants
 * Useful for debugging or server-side tracking
 *
 * @returns Object mapping test IDs to variant IDs
 */
export function getAllABTestVariants(): Record<string, string> {
  const result: Record<string, string> = {};

  try {
    if (typeof window === 'undefined' || !window.localStorage) {
      return result;
    }

    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith('ab-')) {
        const testId = key.substring(3); // Remove 'ab-' prefix
        const variant = localStorage.getItem(key);
        if (variant) {
          result[testId] = variant;
        }
      }
    }
  } catch {
    // Silently fail
  }

  return result;
}

export default useABTest;
