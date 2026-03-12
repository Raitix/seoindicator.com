/**
 * SEO Utilities Index
 * Task: TASK-16-10 - Schema Markup & SEO Optimization
 * 
 * Central export for SEO utilities.
 */

// Schema generation
export {
  SITE_CONFIG,
  escapeHtml,
  schemaToJsonLd,
  getToolUrl,
  generateSoftwareApplicationSchema,
  generateFAQSchema,
  generateBreadcrumbSchema,
  generateToolsListSchema,
  generateToolBreadcrumbs,
  generateToolsLandingBreadcrumbs,
  DEFAULT_TOOL_FAQS,
  TOOLS,
  getToolBySlug,
  generateToolPageSchemas,
} from './schema-markup';

// Meta tags
export {
  truncateText,
  generateMetaTags,
  generateOpenGraphTags,
  generateTwitterCardTags,
  generateCanonicalUrl,
  getToolsLandingPageInfo,
  getToolPageInfo,
  generateToolMetadata,
  generateToolsLandingMetadata,
  TOOL_METADATA,
  getToolMetadataBySlug,
} from './meta-tags';

// Schema validation
export {
  validateSchema,
  validateSoftwareApplication,
  validateFAQPage,
  validateBreadcrumbList,
  generateGoogleRichResultsTestUrl,
  validateUrl,
  validateDateFormat,
  validateJsonLd,
  validateAnySchema,
  validateSchemas,
} from './schema-validator';

// Re-export types
export type {
  ToolInfo,
  FAQItem,
  BreadcrumbItem,
  PageInfo,
  SoftwareApplicationSchema,
  FAQPageSchema,
  BreadcrumbListSchema,
  MetaTags,
  OpenGraphTags,
  TwitterCardTags,
  ValidationError,
  ValidationWarning,
  ValidationResult,
} from '@/lib/types/tools';
