/**
 * A/B Test Configuration
 * Task: TASK-32-18 - A/B Test Infrastructure for CTAs
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Defines A/B tests for CTA optimization on comparison pages.
 * Tests use localStorage for variant persistence and track events via analytics.
 */

// ============================================================================
// Types
// ============================================================================

/**
 * A variant in an A/B test
 */
export interface Variant {
  /** Unique variant identifier within the test (e.g., 'a', 'b', 'c') */
  id: string;
  /** Human-readable variant name */
  name: string;
  /** Weight percentage for variant assignment (must sum to 100 across all variants) */
  weight: number;
  /** Variant configuration object */
  config: Record<string, unknown>;
}

/**
 * An A/B test definition
 */
export interface ABTest {
  /** Unique test identifier */
  id: string;
  /** Human-readable test name */
  name: string;
  /** Array of variants for this test */
  variants: Variant[];
  /** Percentage of users to include in test (0-100) */
  trafficAllocation: number;
  /** ISO date string for test start date */
  startDate: string;
  /** Optional ISO date string for test end date */
  endDate?: string;
}

// ============================================================================
// A/B Test Definitions
// ============================================================================

/**
 * CTA Tests for comparison pages
 *
 * These tests optimize conversion rates on competitor comparison landing pages.
 * Each test runs independently and tracks exposure and conversion events.
 */
export const CTA_TESTS: ABTest[] = [
  {
    id: 'comparison-cta-text',
    name: 'CTA Button Text Test',
    variants: [
      { id: 'a', name: 'Control', weight: 50, config: { text: 'Try SEOIndicator Free' } },
      { id: 'b', name: 'Variant', weight: 50, config: { text: 'Start Free Analysis' } },
    ],
    trafficAllocation: 100,
    startDate: '2026-03-01',
  },
  {
    id: 'comparison-cta-position',
    name: 'CTA Position Test',
    variants: [
      { id: 'a', name: 'Top Only', weight: 50, config: { position: 'top' } },
      { id: 'b', name: 'Top + Bottom', weight: 50, config: { position: 'both' } },
    ],
    trafficAllocation: 100,
    startDate: '2026-03-01',
  },
  {
    id: 'comparison-cta-color',
    name: 'CTA Color Test',
    variants: [
      { id: 'a', name: 'Blue', weight: 50, config: { color: 'blue' } },
      { id: 'b', name: 'Green', weight: 50, config: { color: 'green' } },
    ],
    trafficAllocation: 100,
    startDate: '2026-03-01',
  },
  {
    id: 'comparison-sticky-cta',
    name: 'Mobile Sticky CTA Test',
    variants: [
      { id: 'a', name: 'Show', weight: 50, config: { enabled: true } },
      { id: 'b', name: 'Hide', weight: 50, config: { enabled: false } },
    ],
    trafficAllocation: 100,
    startDate: '2026-03-01',
  },
  // Test configurations for edge case testing
  {
    id: 'expired-test',
    name: 'Expired Test',
    variants: [
      { id: 'a', name: 'Control', weight: 100, config: { text: 'Control' } },
    ],
    trafficAllocation: 100,
    startDate: '2025-01-01',
    endDate: '2025-12-31',
  },
  {
    id: 'future-test',
    name: 'Future Test',
    variants: [
      { id: 'a', name: 'Control', weight: 100, config: { text: 'Control' } },
    ],
    trafficAllocation: 100,
    startDate: '2030-01-01',
  },
  {
    id: 'disabled-test',
    name: 'Disabled Test',
    variants: [
      { id: 'a', name: 'Control', weight: 100, config: { text: 'Control' } },
    ],
    trafficAllocation: 0,
    startDate: '2026-03-01',
  },
];

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Get a test by its ID
 *
 * @param testId - The test identifier
 * @returns The test object or undefined if not found
 */
export function getTestById(testId: string): ABTest | undefined {
  return CTA_TESTS.find((test) => test.id === testId);
}

/**
 * Get the config for a specific variant in a test
 *
 * @param testId - The test identifier
 * @param variantId - The variant identifier
 * @returns The variant config or undefined if not found
 */
export function getTestConfig(
  testId: string,
  variantId: string
): Record<string, unknown> | undefined {
  const test = getTestById(testId);
  if (!test) return undefined;

  const variant = test.variants.find((v) => v.id === variantId);
  return variant?.config;
}

/**
 * Check if a test is currently active
 *
 * A test is active if:
 * - Current date is after startDate
 * - Current date is before endDate (if specified)
 * - trafficAllocation > 0
 *
 * @param test - The test to check
 * @returns True if the test is active
 */
export function isTestActive(test: ABTest): boolean {
  const now = new Date();
  const startDate = new Date(test.startDate);

  // Check if test has started
  if (now < startDate) return false;

  // Check if test has ended
  if (test.endDate) {
    const endDate = new Date(test.endDate);
    if (now > endDate) return false;
  }

  // Check if test is enabled (has traffic allocation)
  return test.trafficAllocation > 0;
}

/**
 * Check if a test is enabled (has traffic allocation)
 *
 * @param test - The test to check
 * @returns True if the test has traffic allocation > 0
 */
export function isTestEnabled(test: ABTest): boolean {
  return test.trafficAllocation > 0;
}

/**
 * Validate a test configuration
 *
 * @param test - The test to validate
 * @throws Error if the test configuration is invalid
 */
export function validateTest(test: unknown): void {
  if (typeof test !== 'object' || test === null) {
    throw new Error('Test must be an object');
  }

  const t = test as Record<string, unknown>;

  // Validate id
  if (typeof t.id !== 'string' || t.id.trim() === '') {
    throw new Error('Test must have a valid id');
  }

  // Validate name
  if (typeof t.name !== 'string' || t.name.trim() === '') {
    throw new Error('Test must have a valid name');
  }

  // Validate variants
  if (!Array.isArray(t.variants) || t.variants.length === 0) {
    throw new Error('Test must have at least one variant');
  }

  // Validate variant weights sum to 100
  const variants = t.variants as Variant[];
  const weightSum = variants.reduce((sum, v) => sum + v.weight, 0);
  if (weightSum !== 100) {
    throw new Error(`Variant weights must sum to 100, got ${weightSum}`);
  }

  // Validate traffic allocation
  if (
    typeof t.trafficAllocation !== 'number' ||
    t.trafficAllocation < 0 ||
    t.trafficAllocation > 100
  ) {
    throw new Error('Traffic allocation must be between 0 and 100');
  }

  // Validate startDate
  if (typeof t.startDate !== 'string') {
    throw new Error('Test must have a startDate');
  }

  const startDate = new Date(t.startDate as string);
  if (isNaN(startDate.getTime())) {
    throw new Error('Invalid startDate format');
  }

  // Validate endDate if present
  if (t.endDate !== undefined) {
    const endDate = new Date(t.endDate as string);
    if (isNaN(endDate.getTime())) {
      throw new Error('Invalid endDate format');
    }
    if (endDate < startDate) {
      throw new Error('endDate must be after startDate');
    }
  }
}

/**
 * Get all currently active tests
 *
 * @returns Array of active tests
 */
export function getAllActiveTests(): ABTest[] {
  return CTA_TESTS.filter(isTestActive);
}

/**
 * Assign a variant based on weights
 *
 * @param variants - Array of variants to choose from
 * @returns The assigned variant ID
 */
export function assignVariant(variants: Variant[]): string {
  const random = Math.random() * 100;
  let cumulative = 0;

  for (const variant of variants) {
    cumulative += variant.weight;
    if (random < cumulative) {
      return variant.id;
    }
  }

  // Fallback to last variant (should not happen if weights sum to 100)
  return variants[variants.length - 1].id;
}

/**
 * Check if user should be included in test based on traffic allocation
 *
 * @param trafficAllocation - Percentage of users to include (0-100)
 * @returns True if user should be included in the test
 */
export function shouldIncludeInTest(trafficAllocation: number): boolean {
  if (trafficAllocation <= 0) return false;
  if (trafficAllocation >= 100) return true;
  return Math.random() * 100 < trafficAllocation;
}

// Freeze the CTA_TESTS array to prevent modification
Object.freeze(CTA_TESTS);
CTA_TESTS.forEach((test) => {
  Object.freeze(test);
  Object.freeze(test.variants);
  test.variants.forEach((variant) => {
    Object.freeze(variant);
    Object.freeze(variant.config);
  });
});
