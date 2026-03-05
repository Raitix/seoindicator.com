/**
 * Comparison Types and Interfaces
 * Task: TASK-32-1 - Create Comparison Types and Interfaces
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * This module defines TypeScript interfaces and type guards for the
 * competitor comparison system used in programmatic SEO landing pages.
 */

// ============================================================================
// Constants
// ============================================================================

/**
 * Valid audience types for UseCase
 */
export const AUDIENCE_TYPES = ['solo', 'small-team', 'enterprise', 'agency'] as const;

/**
 * Valid budget types for UseCase
 */
export const BUDGET_TYPES = ['free', 'budget', 'mid-range', 'premium'] as const;

// ============================================================================
// Core Interfaces
// ============================================================================

/**
 * Represents a pricing tier for a competitor or SEOIndicator.
 * Prices can be numeric or 'Free' or 'Custom' (for enterprise tiers).
 */
export interface PricingTier {
  /** Name of the pricing tier (e.g., 'Lite', 'Pro', 'Enterprise') */
  name: string;
  /** Monthly price - either a number, 'Free', or 'Custom' (enterprise) */
  monthlyPrice: number | 'Free' | 'Custom';
  /** Annual price - either a number, 'Free', or 'Custom' (enterprise) */
  annualPrice: number | 'Free' | 'Custom';
  /** List of features included in this tier */
  features: string[];
  /** Whether this tier should be highlighted in the UI */
  highlighted?: boolean;
}

/**
 * Represents a single feature comparison between SEOIndicator and a competitor.
 * Values can be boolean or string (e.g., true, false, 'Yes', 'Partial', 'N/A').
 */
export interface Feature {
  /** Name of the feature */
  name: string;
  /** SEOIndicator's support for this feature */
  seoindicator: boolean | string;
  /** Competitor's support for this feature */
  competitor: boolean | string;
  /** Optional tooltip text explaining the feature */
  tooltip?: string;
}

/**
 * Groups related features into a category for organized display.
 */
export interface FeatureCategory {
  /** Category name (e.g., 'Backlink Features', 'Keyword Research') */
  name: string;
  /** List of features in this category */
  features: Feature[];
}

/**
 * Represents a target audience use case for the tool.
 */
export interface UseCase {
  /** Title of the use case */
  title: string;
  /** Description of who should use this and why */
  description: string;
  /** Target audience type */
  audience: (typeof AUDIENCE_TYPES)[number];
  /** Budget level this use case is suited for */
  budget: (typeof BUDGET_TYPES)[number];
}

/**
 * Complete data structure for a competitor in the comparison system.
 * Contains all information needed for comparison landing pages.
 */
export interface CompetitorData {
  /** Unique identifier (e.g., 'ahrefs', 'semrush') */
  id: string;
  /** Display name for the competitor */
  name: string;
  /** URL-safe slug for use in URLs */
  slug: string;
  /** Official website URL */
  website: string;
  /** Optional logo URL */
  logo?: string;
  /** Short tagline describing the tool */
  tagline: string;
  /** Available pricing tiers */
  pricing: PricingTier[];
  /** Feature categories with comparisons */
  features: FeatureCategory[];
  /** List of pros/advantages */
  pros: string[];
  /** List of cons/disadvantages */
  cons: string[];
  /** Target use cases for this tool */
  useCases: UseCase[];
  /** ISO date string for freshness tracking */
  lastUpdated: string;
  /** Data source attribution */
  source: string;
}

/**
 * Represents a Frequently Asked Question for comparison pages.
 */
export interface FAQ {
  /** The question text */
  question: string;
  /** The answer text */
  answer: string;
}

/**
 * Complete comparison data structure for a single comparison page.
 * Contains all data needed to render a comparison landing page.
 */
export interface ComparisonData {
  /** URL slug for this comparison (e.g., 'seoindicator-vs-ahrefs') */
  slug: string;
  /** Page title for SEO */
  title: string;
  /** Meta description for SEO */
  metaDescription: string;
  /** Tuple of exactly two competitor IDs being compared */
  competitors: [string, string];
  /** Summary/introduction text for the comparison */
  summary: string;
  /** Feature comparison table data */
  featureComparison: FeatureCategory[];
  /** Pricing comparison for both tools */
  pricingComparison: {
    seoindicator: PricingTier[];
    competitor: PricingTier[];
  };
  /** Pros and cons for both tools */
  prosCons: {
    seoindicator: { pros: string[]; cons: string[] };
    competitor: { pros: string[]; cons: string[] };
  };
  /** Use cases for both tools */
  useCases: {
    seoindicator: UseCase[];
    competitor: UseCase[];
  };
  /** Final verdict/recommendation text */
  verdict: string;
  /** FAQs for the comparison page */
  faqs: FAQ[];
  /** Slugs of related comparison pages */
  relatedComparisons: string[];
}

// ============================================================================
// Component Props Interfaces
// ============================================================================

/**
 * Props for the ComparisonCard component.
 * Displays a card linking to a comparison page.
 */
export interface ComparisonCardProps {
  /** URL slug for the comparison (e.g., 'seoindicator-vs-ahrefs') */
  slug: string;
  /** Title to display on the card */
  title: string;
  /** Description text for the card */
  description: string;
  /** First competitor in the comparison */
  competitorA: { name: string; logo?: string };
  /** Second competitor in the comparison */
  competitorB: { name: string; logo?: string };
}

/**
 * Props for the ComparisonTable component.
 * Displays a feature comparison table between SEOIndicator and a competitor.
 */
export interface ComparisonTableProps {
  /** Feature categories to display in the table */
  features: FeatureCategory[];
  /** Whether to highlight the winning tool for each feature */
  highlightWinner?: boolean;
  /** Whether to show tooltips for features */
  showTooltips?: boolean;
}

/**
 * Props for the PricingComparison component.
 * Displays pricing tiers side by side for comparison.
 */
export interface PricingComparisonProps {
  /** SEOIndicator pricing tiers */
  seoindicator: PricingTier[];
  /** Competitor pricing tiers */
  competitor: PricingTier[];
  /** Whether to show/hide free tier */
  showFreeTier?: boolean;
}

/**
 * Props for the ProsConsCard component.
 * Displays pros and cons for a single tool.
 */
export interface ProsConsCardProps {
  /** Tool name to display */
  tool: string;
  /** List of advantages */
  pros: string[];
  /** List of disadvantages */
  cons: string[];
  /** Optional overall rating (e.g., 4.5) */
  overallRating?: number;
}

/**
 * Props for the UseCaseSection component.
 * Displays use cases for both tools side by side.
 */
export interface UseCaseSectionProps {
  /** SEOIndicator use cases */
  seoindicator: UseCase[];
  /** Competitor use cases */
  competitor: UseCase[];
}

/**
 * Props for the ComparisonCTA component.
 * Displays a call-to-action button or banner.
 */
export interface ComparisonCTAProps {
  /** Visual variant of the CTA */
  variant?: 'primary' | 'secondary' | 'sticky';
  /** Custom button text */
  text?: string;
  /** Link destination */
  href?: string;
  /** A/B test group identifier */
  abTestGroup?: string;
}

/**
 * Props for the ComparisonSchema component.
 * Generates structured data (JSON-LD) for SEO.
 */
export interface ComparisonSchemaProps {
  /** Complete comparison data */
  comparison: ComparisonData;
  /** FAQs for FAQ schema */
  faqs: FAQ[];
}

/**
 * Props for comparison page components (Next.js dynamic route).
 */
export interface ComparisonPageProps {
  /** Route parameters */
  params: { slug: string };
}

// ============================================================================
// Type Guards
// ============================================================================

/**
 * Type guard for PricingTier interface.
 * Validates that an object is a valid PricingTier.
 */
export function isPricingTier(value: unknown): value is PricingTier {
  if (typeof value !== 'object' || value === null) return false;
  
  const obj = value as Record<string, unknown>;
  
  if (typeof obj.name !== 'string') return false;
  
  const monthlyPrice = obj.monthlyPrice;
  if (typeof monthlyPrice !== 'number' && monthlyPrice !== 'Free' && monthlyPrice !== 'Custom') return false;
  
  const annualPrice = obj.annualPrice;
  if (typeof annualPrice !== 'number' && annualPrice !== 'Free' && annualPrice !== 'Custom') return false;
  
  if (!Array.isArray(obj.features)) return false;
  if (!obj.features.every((f: unknown) => typeof f === 'string')) return false;
  
  if (obj.highlighted !== undefined && typeof obj.highlighted !== 'boolean') return false;
  
  return true;
}

/**
 * Type guard for Feature interface.
 * Validates that an object is a valid Feature.
 */
export function isFeature(value: unknown): value is Feature {
  if (typeof value !== 'object' || value === null) return false;
  
  const obj = value as Record<string, unknown>;
  
  if (typeof obj.name !== 'string') return false;
  
  const seoindicator = obj.seoindicator;
  if (typeof seoindicator !== 'boolean' && typeof seoindicator !== 'string') return false;
  
  const competitor = obj.competitor;
  if (typeof competitor !== 'boolean' && typeof competitor !== 'string') return false;
  
  if (obj.tooltip !== undefined && typeof obj.tooltip !== 'string') return false;
  
  return true;
}

/**
 * Type guard for FeatureCategory interface.
 * Validates that an object is a valid FeatureCategory.
 */
export function isFeatureCategory(value: unknown): value is FeatureCategory {
  if (typeof value !== 'object' || value === null) return false;
  
  const obj = value as Record<string, unknown>;
  
  if (typeof obj.name !== 'string') return false;
  
  if (!Array.isArray(obj.features)) return false;
  if (!obj.features.every((f: unknown) => isFeature(f))) return false;
  
  return true;
}

/**
 * Type guard for UseCase interface.
 * Validates that an object is a valid UseCase.
 */
export function isUseCase(value: unknown): value is UseCase {
  if (typeof value !== 'object' || value === null) return false;
  
  const obj = value as Record<string, unknown>;
  
  if (typeof obj.title !== 'string') return false;
  if (typeof obj.description !== 'string') return false;
  
  if (typeof obj.audience !== 'string') return false;
  if (!AUDIENCE_TYPES.includes(obj.audience as (typeof AUDIENCE_TYPES)[number])) return false;
  
  if (typeof obj.budget !== 'string') return false;
  if (!BUDGET_TYPES.includes(obj.budget as (typeof BUDGET_TYPES)[number])) return false;
  
  return true;
}

/**
 * Type guard for CompetitorData interface.
 * Validates that an object is a valid CompetitorData.
 */
export function isCompetitorData(value: unknown): value is CompetitorData {
  if (typeof value !== 'object' || value === null) return false;
  
  const obj = value as Record<string, unknown>;
  
  // Required string fields
  if (typeof obj.id !== 'string') return false;
  if (typeof obj.name !== 'string') return false;
  if (typeof obj.slug !== 'string') return false;
  if (typeof obj.website !== 'string') return false;
  if (typeof obj.tagline !== 'string') return false;
  if (typeof obj.lastUpdated !== 'string') return false;
  if (typeof obj.source !== 'string') return false;
  
  // Optional string fields
  if (obj.logo !== undefined && typeof obj.logo !== 'string') return false;
  
  // Required array fields
  if (!Array.isArray(obj.pricing)) return false;
  if (!obj.pricing.every((p: unknown) => isPricingTier(p))) return false;
  
  if (!Array.isArray(obj.features)) return false;
  if (!obj.features.every((f: unknown) => isFeatureCategory(f))) return false;
  
  if (!Array.isArray(obj.pros)) return false;
  if (!obj.pros.every((p: unknown) => typeof p === 'string')) return false;
  
  if (!Array.isArray(obj.cons)) return false;
  if (!obj.cons.every((c: unknown) => typeof c === 'string')) return false;
  
  if (!Array.isArray(obj.useCases)) return false;
  if (!obj.useCases.every((u: unknown) => isUseCase(u))) return false;
  
  return true;
}

/**
 * Type guard for FAQ interface.
 * Validates that an object is a valid FAQ.
 */
export function isFAQ(value: unknown): value is FAQ {
  if (typeof value !== 'object' || value === null) return false;
  
  const obj = value as Record<string, unknown>;
  
  if (typeof obj.question !== 'string') return false;
  if (typeof obj.answer !== 'string') return false;
  
  return true;
}

/**
 * Type guard for ComparisonData interface.
 * Validates that an object is a valid ComparisonData.
 */
export function isComparisonData(value: unknown): value is ComparisonData {
  if (typeof value !== 'object' || value === null) return false;
  
  const obj = value as Record<string, unknown>;
  
  // Required string fields
  if (typeof obj.slug !== 'string') return false;
  if (typeof obj.title !== 'string') return false;
  if (typeof obj.metaDescription !== 'string') return false;
  if (typeof obj.summary !== 'string') return false;
  if (typeof obj.verdict !== 'string') return false;
  
  // Competitors must be exactly 2 strings
  if (!Array.isArray(obj.competitors)) return false;
  if (obj.competitors.length !== 2) return false;
  if (!obj.competitors.every((c: unknown) => typeof c === 'string')) return false;
  
  // Feature comparison
  if (!Array.isArray(obj.featureComparison)) return false;
  if (!obj.featureComparison.every((f: unknown) => isFeatureCategory(f))) return false;
  
  // Pricing comparison
  if (typeof obj.pricingComparison !== 'object' || obj.pricingComparison === null) return false;
  const pricingComp = obj.pricingComparison as Record<string, unknown>;
  if (!Array.isArray(pricingComp.seoindicator)) return false;
  if (!pricingComp.seoindicator.every((p: unknown) => isPricingTier(p))) return false;
  if (!Array.isArray(pricingComp.competitor)) return false;
  if (!pricingComp.competitor.every((p: unknown) => isPricingTier(p))) return false;
  
  // Pros/Cons
  if (typeof obj.prosCons !== 'object' || obj.prosCons === null) return false;
  const prosCons = obj.prosCons as Record<string, unknown>;
  if (!isValidProsCons(prosCons.seoindicator)) return false;
  if (!isValidProsCons(prosCons.competitor)) return false;
  
  // Use cases
  if (typeof obj.useCases !== 'object' || obj.useCases === null) return false;
  const useCases = obj.useCases as Record<string, unknown>;
  if (!Array.isArray(useCases.seoindicator)) return false;
  if (!useCases.seoindicator.every((u: unknown) => isUseCase(u))) return false;
  if (!Array.isArray(useCases.competitor)) return false;
  if (!useCases.competitor.every((u: unknown) => isUseCase(u))) return false;
  
  // FAQs
  if (!Array.isArray(obj.faqs)) return false;
  if (!obj.faqs.every((f: unknown) => isFAQ(f))) return false;
  
  // Related comparisons
  if (!Array.isArray(obj.relatedComparisons)) return false;
  if (!obj.relatedComparisons.every((r: unknown) => typeof r === 'string')) return false;
  
  return true;
}

/**
 * Helper function to validate pros/cons structure.
 */
function isValidProsCons(value: unknown): boolean {
  if (typeof value !== 'object' || value === null) return false;
  
  const obj = value as Record<string, unknown>;
  
  if (!Array.isArray(obj.pros)) return false;
  if (!obj.pros.every((p: unknown) => typeof p === 'string')) return false;
  
  if (!Array.isArray(obj.cons)) return false;
  if (!obj.cons.every((c: unknown) => typeof c === 'string')) return false;
  
  return true;
}
