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
};

/**
 * All valid comparison slugs.
 * Format: 'tool-a-vs-tool-b' where tool-a comes before tool-b alphabetically.
 * Used for static generation of comparison pages and sitemap.
 */
export const COMPARISON_SLUGS: string[] = [
  // SEOIndicator vs competitors (primary comparisons)
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
  // Cross-competitor comparisons
  'ahrefs-vs-semrush',
  'ahrefs-vs-moz',
  'ahrefs-vs-ubersuggest',
  'semrush-vs-moz',
  'semrush-vs-ubersuggest',
  'moz-vs-ubersuggest',
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
  const parts = slug.split('-vs-');
  if (parts.length !== 2) {
    return null;
  }

  const [competitorA, competitorB] = parts;

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
 * @param competitorA - First competitor data
 * @param competitorB - Second competitor data
 * @returns ComparisonData structure
 */
export function generateComparisonData(
  competitorA: CompetitorData,
  competitorB: CompetitorData
): ComparisonData {
  const slug = `${competitorA.slug}-vs-${competitorB.slug}`;

  return {
    slug,
    title: `${competitorA.name} vs ${competitorB.name}: Which SEO Tool Is Better?`,
    metaDescription: `Compare ${competitorA.name} vs ${competitorB.name}. Detailed analysis of features, pricing, pros and cons to help you choose the best SEO tool for your needs.`,
    competitors: [competitorA.id, competitorB.id],
    summary: `Find out how ${competitorA.name} compares to ${competitorB.name} in this comprehensive comparison. We analyze features, pricing, and performance to help you make an informed decision.`,
    featureComparison: mergeFeatures(competitorA.features, competitorB.features),
    pricingComparison: {
      seoindicator: competitorA.id === 'seoindicator' ? competitorA.pricing : competitorB.pricing,
      competitor: competitorA.id === 'seoindicator' ? competitorB.pricing : competitorA.pricing,
    },
    prosCons: {
      seoindicator: {
        pros: competitorA.id === 'seoindicator' ? competitorA.pros : competitorB.pros,
        cons: competitorA.id === 'seoindicator' ? competitorA.cons : competitorB.cons,
      },
      competitor: {
        pros: competitorA.id === 'seoindicator' ? competitorB.pros : competitorA.pros,
        cons: competitorA.id === 'seoindicator' ? competitorB.cons : competitorA.cons,
      },
    },
    useCases: {
      seoindicator: competitorA.id === 'seoindicator' ? competitorA.useCases : competitorB.useCases,
      competitor: competitorA.id === 'seoindicator' ? competitorB.useCases : competitorA.useCases,
    },
    verdict: generateVerdict(competitorA, competitorB),
    faqs: generateFAQs(competitorA, competitorB),
    relatedComparisons: getRelatedComparisons(competitorA.id, competitorB.id),
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
  });
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

  return true;
}
