/**
 * Comparison Registry
 * Task: TASK-32-2 - Comparison Registry and Data Loading
 * Task: TASK-32-15 - Sitemap Integration
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Central registry for competitor data and comparison slugs.
 * This module provides helper functions for loading, validating, and
 * transforming competitor data for programmatic SEO landing pages.
 */

import { CompetitorData, ComparisonData } from './types';
import { seoindicator } from './competitors/seoindicator';
import { ahrefs } from './competitors/ahrefs';
import { semrush } from './competitors/semrush';
import { moz } from './competitors/moz';
import { ubersuggest } from './competitors/ubersuggest';
import { gsc } from './competitors/gsc';
import { ga } from './competitors/ga';
import { answerthepublic } from './competitors/answerthepublic';

// Re-export seoindicator for direct access
export { seoindicator };

// ============================================================================
// Competitor Registry
// ============================================================================

/**
 * Registry of all competitors with their data.
 * Used for comparison pages and data access.
 */
export const COMPETITOR_REGISTRY: Record<string, CompetitorData> = {
  seoindicator,
  ahrefs,
  semrush,
  moz,
  ubersuggest,
  gsc,
  ga,
  answerthepublic,
};

/**
 * All valid comparison slugs.
 * Format: 'tool-a-vs-tool-b' where tool-a comes before tool-b alphabetically.
 * Used for static generation of comparison pages and sitemap.
 */
export const COMPARISON_SLUGS: string[] = [
  // SEOIndicator vs competitors (primary comparisons)
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-moz',
  'seoindicator-vs-semrush',
  'seoindicator-vs-ubersuggest',
  // SEOIndicator vs free alternatives
  'seoindicator-vs-gsc',
  'seoindicator-vs-ga',
  'seoindicator-vs-answerthepublic',
  // Cross-competitor comparisons
  'ahrefs-vs-moz',
  'ahrefs-vs-semrush',
  'ahrefs-vs-ubersuggest',
  'moz-vs-semrush',
  'moz-vs-ubersuggest',
  'semrush-vs-ubersuggest',
];

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Get competitor data by ID.
 * @param id - Competitor ID (e.g., 'ahrefs', 'semrush')
 * @returns CompetitorData or null if not found
 */
export function getCompetitorById(id: string): CompetitorData | null {
  return COMPETITOR_REGISTRY[id] || null;
}

/**
 * Parse a comparison slug into competitor IDs.
 * @param slug - Comparison slug (e.g., 'seoindicator-vs-ahrefs')
 * @returns Tuple of competitor IDs or null if invalid format
 */
export function parseSlug(slug: string): [string, string] | null {
  // Handle null, undefined, and non-string inputs
  if (slug === null || slug === undefined || typeof slug !== 'string') {
    return null;
  }

  const parts = slug.split('-vs-');
  if (parts.length !== 2) {
    return null;
  }

  const [competitorA, competitorB] = parts;

  // Validate both parts are non-empty
  if (!competitorA || !competitorB) {
    return null;
  }

  // Validate both competitors exist
  if (!COMPETITOR_REGISTRY[competitorA] || !COMPETITOR_REGISTRY[competitorB]) {
    return null;
  }

  return [competitorA, competitorB];
}

/**
 * Get comparison data by slug.
 * @param slug - Comparison slug (e.g., 'seoindicator-vs-ahrefs')
 * @returns ComparisonData or null if invalid
 */
export function getComparisonBySlug(slug: string): ComparisonData | null {
  const competitors = parseSlug(slug);
  if (!competitors) {
    return null;
  }

  const [competitorAId, competitorBId] = competitors;
  const competitorA = COMPETITOR_REGISTRY[competitorAId];
  const competitorB = COMPETITOR_REGISTRY[competitorBId];

  if (!competitorA || !competitorB) {
    return null;
  }

  return generateComparisonData(competitorA, competitorB);
}

/**
 * Generate comparison data between two competitors.
 * @param competitorA - First competitor ID (string) or CompetitorData
 * @param competitorB - Second competitor ID (string) or CompetitorData
 * @returns ComparisonData structure or null if invalid inputs
 */
export function generateComparisonData(
  competitorA: string | CompetitorData,
  competitorB: string | CompetitorData
): ComparisonData | null {
  // Handle null/undefined inputs
  if (competitorA === null || competitorA === undefined || 
      competitorB === null || competitorB === undefined) {
    return null;
  }

  // Resolve competitor data from string ID or use provided object
  const compA: CompetitorData | null = typeof competitorA === 'string' 
    ? getCompetitorById(competitorA) 
    : competitorA;
  const compB: CompetitorData | null = typeof competitorB === 'string' 
    ? getCompetitorById(competitorB) 
    : competitorB;

  // Validate both competitors exist
  if (!compA || !compB) {
    return null;
  }

  const slug = `${compA.slug}-vs-${compB.slug}`;

  return {
    slug,
    title: `${compA.name} vs ${compB.name}: Which SEO Tool Is Better?`,
    metaDescription: `Compare ${compA.name} vs ${compB.name}. Detailed analysis of features, pricing, pros and cons to help you choose the best SEO tool for your needs.`,
    competitors: [compA.id, compB.id],
    summary: `Find out how ${compA.name} compares to ${compB.name} in this comprehensive comparison. We analyze features, pricing, and performance to help you make an informed decision.`,
    featureComparison: mergeFeatures(compA.features, compB.features),
    pricingComparison: {
      seoindicator: compA.id === 'seoindicator' ? compA.pricing : compB.pricing,
      competitor: compA.id === 'seoindicator' ? compB.pricing : compA.pricing,
    },
    prosCons: {
      seoindicator: {
        pros: compA.id === 'seoindicator' ? compA.pros : compB.pros,
        cons: compA.id === 'seoindicator' ? compA.cons : compB.cons,
      },
      competitor: {
        pros: compA.id === 'seoindicator' ? compB.pros : compA.pros,
        cons: compA.id === 'seoindicator' ? compB.cons : compA.cons,
      },
    },
    useCases: {
      seoindicator: compA.id === 'seoindicator' ? compA.useCases : compB.useCases,
      competitor: compA.id === 'seoindicator' ? compB.useCases : compA.useCases,
    },
    verdict: generateVerdict(compA, compB),
    faqs: generateFAQs(compA, compB),
    relatedComparisons: getRelatedComparisons(compA.id, compB.id),
  };
}

/**
 * Merge feature categories from two competitors.
 */
function mergeFeatures(
  featuresA: CompetitorData['features'],
  featuresB: CompetitorData['features']
): CompetitorData['features'] {
  // For simplicity, use featuresA as the base
  // In production, this would merge both feature sets intelligently
  return featuresA.map((category) => ({
    name: category.name,
    features: category.features.map((feature) => ({
      ...feature,
      // Swap seoindicator/competitor values based on which tool is being compared
    })),
  }));
}

/**
 * Generate verdict text for comparison.
 */
function generateVerdict(competitorA: CompetitorData, competitorB: CompetitorData): string {
  if (competitorA.id === 'seoindicator') {
    return `${competitorA.name} is the best choice for budget-conscious users who need essential SEO features without the enterprise price tag. Choose ${competitorB.name} if you need advanced features like content marketing tools or social media management.`;
  }
  if (competitorB.id === 'seoindicator') {
    return `${competitorB.name} is the best choice for budget-conscious users who need essential SEO features without the enterprise price tag. Choose ${competitorA.name} if you need advanced features like content marketing tools or social media management.`;
  }
  return `Both ${competitorA.name} and ${competitorB.name} are excellent SEO tools. Choose based on your specific needs, budget, and preferred workflow.`;
}

/**
 * Generate FAQs for comparison.
 */
function generateFAQs(competitorA: CompetitorData, competitorB: CompetitorData): Array<{ question: string; answer: string }> {
  return [
    {
      question: `Which is better: ${competitorA.name} or ${competitorB.name}?`,
      answer: `The choice depends on your needs. ${competitorA.name} excels in ${competitorA.pros[0].toLowerCase()}. ${competitorB.name} is known for ${competitorB.pros[0].toLowerCase()}.`,
    },
    {
      question: `Is ${competitorA.name} cheaper than ${competitorB.name}?`,
      answer: `${competitorA.name} pricing starts at $${typeof competitorA.pricing[0].monthlyPrice === 'number' ? competitorA.pricing[0].monthlyPrice : 0}/mo. ${competitorB.name} pricing starts at $${typeof competitorB.pricing[0].monthlyPrice === 'number' ? competitorB.pricing[0].monthlyPrice : 0}/mo.`,
    },
    {
      question: `Can I switch from ${competitorA.name} to ${competitorB.name}?`,
      answer: `Yes, you can switch between SEO tools. Most platforms allow you to export your data and import it into a new tool.`,
    },
  ];
}

/**
 * Get related comparison slugs for a competitor.
 * @param competitorId - Competitor ID to find related comparisons for
 * @param excludeId - Optional competitor ID to exclude from results
 * @returns Array of related comparison slugs
 */
export function getRelatedComparisons(competitorId: string, excludeId?: string): string[] {
  return COMPARISON_SLUGS.filter((slug) => {
    const parts = parseSlug(slug);
    if (!parts) return false;

    const [a, b] = parts;

    // Include if competitorId is in the slug
    const includesCompetitor = a === competitorId || b === competitorId;

    // Exclude if excludeId is in the slug
    const includesExcluded = excludeId && (a === excludeId || b === excludeId);

    return includesCompetitor && !includesExcluded;
  }).slice(0, 5);
}

/**
 * Validate competitor data structure.
 * @param data - Competitor data to validate
 * @returns True if valid, false otherwise
 */
export function validateCompetitorData(data: unknown): data is CompetitorData {
  if (typeof data !== 'object' || data === null) return false;

  const obj = data as Record<string, unknown>;

  // Check required string fields
  const requiredStrings = ['id', 'name', 'slug', 'website', 'tagline', 'lastUpdated', 'source'];
  for (const field of requiredStrings) {
    if (typeof obj[field] !== 'string') return false;
  }

  // Check required arrays
  if (!Array.isArray(obj.pricing)) return false;
  if (!Array.isArray(obj.features)) return false;
  if (!Array.isArray(obj.pros)) return false;
  if (!Array.isArray(obj.cons)) return false;
  if (!Array.isArray(obj.useCases)) return false;

  // Validate pricing array is not empty
  if (obj.pricing.length === 0) return false;

  // Validate pricing items have required fields
  for (const tier of obj.pricing) {
    if (typeof tier !== 'object' || tier === null) return false;
    const t = tier as Record<string, unknown>;
    if (typeof t.name !== 'string') return false;
    // Check for negative prices
    if (typeof t.monthlyPrice === 'number' && t.monthlyPrice < 0) return false;
    if (typeof t.annualPrice === 'number' && t.annualPrice < 0) return false;
  }

  // Validate lastUpdated format (ISO date or YYYY-MM-DD)
  if (!/^\d{4}-\d{2}-\d{2}/.test(obj.lastUpdated as string)) return false;

  // Validate use case audience values
  const validAudiences = ['solo', 'small-team', 'agency', 'enterprise'];
  for (const uc of obj.useCases) {
    if (typeof uc !== 'object' || uc === null) return false;
    const u = uc as Record<string, unknown>;
    if (typeof u.audience === 'string' && !validAudiences.includes(u.audience)) return false;
  }

  return true;
}

/**
 * Validate comparison data structure.
 * @param data - Comparison data to validate
 * @returns True if valid, false otherwise
 */
export function validateComparisonData(data: unknown): data is ComparisonData {
  if (typeof data !== 'object' || data === null) return false;

  const obj = data as Record<string, unknown>;

  // Check required string fields
  const requiredStrings = ['slug', 'title', 'metaDescription', 'summary', 'verdict'];
  for (const field of requiredStrings) {
    if (typeof obj[field] !== 'string') return false;
  }

  // Check competitors array has exactly 2 items
  if (!Array.isArray(obj.competitors) || obj.competitors.length !== 2) return false;
  if (!obj.competitors.every((c: unknown) => typeof c === 'string')) return false;

  // Check featureComparison is an array
  if (!Array.isArray(obj.featureComparison)) return false;

  // Check pricingComparison is an object with valid structure
  if (typeof obj.pricingComparison !== 'object' || obj.pricingComparison === null) return false;
  const pricing = obj.pricingComparison as Record<string, unknown>;
  if (!Array.isArray(pricing.seoindicator) || !Array.isArray(pricing.competitor)) return false;
  
  // Validate pricing tier items in pricingComparison
  const validatePricingTier = (tier: unknown): boolean => {
    if (typeof tier !== 'object' || tier === null) return false;
    const t = tier as Record<string, unknown>;
    return typeof t.name === 'string';
  };
  if (!pricing.seoindicator.every(validatePricingTier)) return false;
  if (!pricing.competitor.every(validatePricingTier)) return false;

  // Check prosCons is an object with valid structure
  if (typeof obj.prosCons !== 'object' || obj.prosCons === null) return false;
  const prosCons = obj.prosCons as Record<string, unknown>;
  if (typeof prosCons.seoindicator !== 'object' || typeof prosCons.competitor !== 'object') return false;

  // Check faqs is an array
  if (!Array.isArray(obj.faqs)) return false;

  // Validate slug format (tool-a-vs-tool-b)
  if (!/^[a-z0-9-]+-vs-[a-z0-9-]+$/.test(obj.slug as string)) return false;

  return true;
}
