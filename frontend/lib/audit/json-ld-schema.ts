/**
 * JSON-LD Schema Generator for Public Audit Pages
 * Generates structured data for SEO rich snippets - WebPage + Analysis + Rating schemas
 * @module lib/audit/json-ld-schema
 */

/**
 * Public audit data interface for JSON-LD schema generation
 */
export interface PublicAuditData {
  siteUrl: string;
  auditedAt: string;
  overallScore: number;
  categoryScores: Array<{
    name: string;
    score: number;
    maxScore: number;
    issues: number;
  }>;
  topIssues: Array<{
    id: string;
    title: string;
    severity: 'critical' | 'warning' | 'info';
    description: string;
  }>;
  recommendations: string[];
  isPartial: boolean;
  publicHash: string;
}

/**
 * JSON-LD Schema structure following schema.org specification
 */
export interface JsonLdSchema {
  '@context': string;
  '@type': string;
  name: string;
  description?: string;
  url?: string;
  publisher?: {
    '@type': string;
    name: string;
    url: string;
    logo?: {
      '@type': string;
      url: string;
    };
  };
  mainEntity?: {
    '@type': string;
    result?: {
      '@type': string;
      ratingValue: string;
      bestRating: string;
      worstRating: string;
    };
  };
  datePublished?: string;
  dateModified?: string;
}

/**
 * Sanitize a string by removing HTML tags and dangerous content
 * This ensures no script injection can occur in JSON-LD output
 *
 * @param input - String to sanitize
 * @returns Sanitized string
 */
function sanitizeString(input: string): string {
  return input
    // Remove complete HTML tags
    .replace(/<[^>]*>/g, '')
    // Remove partial/unclosed script tags
    .replace(/<script[^>]*>/gi, '')
    .replace(/<\/script>/gi, '')
    // Remove potential XSS vectors
    .replace(/javascript:/gi, '')
    .replace(/on\w+\s*=/gi, '')
    // Remove onerror and similar event handlers
    .replace(/onerror/gi, '')
    .replace(/onclick/gi, '')
    .replace(/onload/gi, '')
    .replace(/onmouseover/gi, '');
}

/**
 * Generate JSON-LD schema for public audit page
 *
 * @param audit - Public audit data (null returns null)
 * @param siteUrl - Base site URL (default: https://seoindicator.com)
 * @returns JSON-LD schema object or null if audit is null
 *
 * @example
 * const audit = { siteUrl: 'https://example.com', overallScore: 85, ... };
 * const schema = generateJsonLdSchema(audit);
 * // Use in page: <script type="application/ld+json">{JSON.stringify(schema)}</script>
 */
export function generateJsonLdSchema(
  audit: PublicAuditData | null,
  siteUrl = 'https://seoindicator.com'
): JsonLdSchema | null {
  if (!audit) {
    return null;
  }

  // Process URL for display - strip protocol and trailing slash, then sanitize
  const displayUrl = sanitizeString(
    audit.siteUrl
      .replace(/^https?:\/\//, '')
      .replace(/\/$/, '')
  );

  // Build canonical audit URL
  const pageUrl = `${siteUrl}/audit/${audit.publicHash}/`;

  return {
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    name: `${displayUrl} SEO Audit Report`,
    description: `SEO audit results for ${displayUrl} - Score: ${audit.overallScore}/100`,
    url: pageUrl,
    publisher: {
      '@type': 'Organization',
      name: 'SEOIndicator',
      url: siteUrl,
      logo: {
        '@type': 'ImageObject',
        url: `${siteUrl}/logo.png`,
      },
    },
    mainEntity: {
      '@type': 'Analysis',
      result: {
        '@type': 'Rating',
        ratingValue: String(audit.overallScore),
        bestRating: '100',
        worstRating: '0',
      },
    },
    datePublished: audit.auditedAt,
    dateModified: audit.auditedAt,
  };
}

/**
 * Validate that a schema object is a valid JSON-LD WebPage schema
 *
 * @param schema - Object to validate
 * @returns True if schema is valid JSON-LD WebPage schema
 */
export function isValidJsonLd(schema: unknown): schema is JsonLdSchema {
  if (!schema || typeof schema !== 'object') return false;
  const s = schema as Record<string, unknown>;

  return (
    s['@context'] === 'https://schema.org' &&
    typeof s['@type'] === 'string' &&
    typeof s.name === 'string'
  );
}

/**
 * Parse and validate JSON-LD script content
 *
 * @param scriptContent - JSON string from script tag
 * @returns Parsed schema if valid, null otherwise
 */
export function parseJsonLdScript(scriptContent: string): JsonLdSchema | null {
  try {
    const parsed = JSON.parse(scriptContent);
    return isValidJsonLd(parsed) ? parsed : null;
  } catch {
    return null;
  }
}
