/**
 * Tool Types
 * Task: TASK-16-10 - Schema Markup & SEO Optimization
 * 
 * Type definitions for SEO tools and schema generation.
 */

/**
 * Tool information for schema generation
 */
export interface ToolInfo {
  /** URL slug for the tool (e.g., 'meta-tag-analyzer') */
  slug: string;
  /** Display name of the tool */
  name: string;
  /** SEO description (150-160 characters recommended) */
  description: string;
  /** List of feature descriptions */
  features: string[];
  /** Related SEO guide slugs */
  relatedGuides: string[];
  /** Optional icon emoji */
  icon?: string;
  /** Optional screenshot/preview image URL */
  image?: string;
  /** Optional rating information */
  rating?: {
    /** Rating value (1-5) */
    value: number;
    /** Number of ratings */
    count: number;
  };
  /** Tool availability status */
  status?: 'available' | 'coming-soon' | 'beta';
}

/**
 * FAQ item for FAQPage schema
 */
export interface FAQItem {
  /** The question text */
  question: string;
  /** The answer text */
  answer: string;
}

/**
 * Breadcrumb item for BreadcrumbList schema
 */
export interface BreadcrumbItem {
  /** Display name for the breadcrumb */
  name: string;
  /** Full URL for the breadcrumb */
  url: string;
}

/**
 * Page information for meta tag generation
 */
export interface PageInfo {
  /** URL slug */
  slug: string;
  /** Page name/title */
  name: string;
  /** Page description */
  description: string;
  /** Full page URL */
  url: string;
  /** Optional preview image URL */
  image?: string;
  /** Optional keywords array */
  keywords?: string[];
}

/**
 * SoftwareApplication schema type
 */
export interface SoftwareApplicationSchema {
  '@context': string;
  '@type': string;
  name: string;
  description: string;
  applicationCategory: string;
  operatingSystem: string;
  offers: {
    '@type': string;
    price: string;
    priceCurrency: string;
  };
  aggregateRating?: {
    '@type': string;
    ratingValue: string;
    ratingCount: string;
  };
  url?: string;
  screenshot?: string;
  featureList?: string[];
}

/**
 * FAQPage schema type
 */
export interface FAQPageSchema {
  '@context': string;
  '@type': string;
  mainEntity: Array<{
    '@type': string;
    name: string;
    acceptedAnswer: {
      '@type': string;
      text: string;
    };
  }>;
}

/**
 * BreadcrumbList schema type
 */
export interface BreadcrumbListSchema {
  '@context': string;
  '@type': string;
  itemListElement: Array<{
    '@type': string;
    position: number;
    name: string;
    item: string;
  }>;
}

/**
 * ItemList schema type (for tools listing)
 */
export interface ItemListSchema {
  '@context': string;
  '@type': string;
  name: string;
  description: string;
  numberOfItems: number;
  itemListElement: Array<{
    '@type': string;
    position: number;
    item: {
      '@type': string;
      name: string;
      applicationCategory: string;
      operatingSystem: string;
      offers: {
        '@type': string;
        price: string;
        priceCurrency: string;
      };
      description: string;
    };
  }>;
}

/**
 * Meta tags configuration
 */
export interface MetaTags {
  title: string;
  description: string;
  keywords?: string;
  canonical: string;
  robots?: string;
}

/**
 * Open Graph tags configuration
 */
export interface OpenGraphTags {
  type: 'website';
  title: string;
  description: string;
  url: string;
  siteName: string;
  image?: string;
  imageAlt?: string;
}

/**
 * Twitter Card tags configuration
 */
export interface TwitterCardTags {
  card: 'summary' | 'summary_large_image';
  title: string;
  description: string;
  image?: string;
  imageAlt?: string;
}

/**
 * Validation error type
 */
export interface ValidationError {
  path: string;
  message: string;
  type: 'required' | 'type' | 'format' | 'value';
}

/**
 * Validation warning type
 */
export interface ValidationWarning {
  path: string;
  message: string;
}

/**
 * Validation result type
 */
export interface ValidationResult {
  valid: boolean;
  errors: ValidationError[];
  warnings?: ValidationWarning[];
}
