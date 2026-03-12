/**
 * Schema Validation Utilities
 * Task: TASK-16-10 - Schema Markup & SEO Optimization
 * 
 * JSON-LD schema validation for SoftwareApplication, FAQPage, and BreadcrumbList schemas.
 * Includes Google Rich Results Test URL generation.
 */

import type {
  SoftwareApplicationSchema,
  FAQPageSchema,
  BreadcrumbListSchema,
  ValidationError,
  ValidationWarning,
  ValidationResult,
} from '@/lib/types/tools';

/**
 * Base schema validation
 */
export function validateSchema(schema: unknown): ValidationResult {
  const errors: ValidationError[] = [];
  const warnings: ValidationWarning[] = [];

  if (!schema || typeof schema !== 'object') {
    errors.push({
      path: '',
      message: 'Schema must be an object',
      type: 'type',
    });
    return { valid: false, errors, warnings };
  }

  const s = schema as Record<string, unknown>;

  if (!s['@context']) {
    errors.push({
      path: '@context',
      message: '@context is required',
      type: 'required',
    });
  } else if (s['@context'] !== 'https://schema.org') {
    errors.push({
      path: '@context',
      message: '@context must be "https://schema.org"',
      type: 'value',
    });
  }

  if (!s['@type']) {
    errors.push({
      path: '@type',
      message: '@type is required',
      type: 'required',
    });
  }

  return {
    valid: errors.length === 0,
    errors,
    warnings,
  };
}

/**
 * Validate SoftwareApplication schema
 */
export function validateSoftwareApplication(schema: unknown): ValidationResult {
  const baseResult = validateSchema(schema);
  if (!baseResult.valid) return baseResult;

  const errors: ValidationError[] = [...baseResult.errors];
  const s = schema as Record<string, unknown>;

  if (s['@type'] !== 'SoftwareApplication') {
    errors.push({
      path: '@type',
      message: '@type must be "SoftwareApplication"',
      type: 'value',
    });
  }

  // Required fields for SoftwareApplication
  const requiredFields = ['name', 'description', 'applicationCategory', 'operatingSystem'];
  requiredFields.forEach(field => {
    if (!s[field]) {
      errors.push({
        path: field,
        message: `${field} is required for SoftwareApplication`,
        type: 'required',
      });
    }
  });

  // Validate offers object
  if (s.offers && typeof s.offers === 'object') {
    const offers = s.offers as Record<string, unknown>;
    if (!offers['@type'] || offers['@type'] !== 'Offer') {
      errors.push({
        path: 'offers.@type',
        message: 'offers.@type must be "Offer"',
        type: 'value',
      });
    }
    if (!offers.price) {
      errors.push({
        path: 'offers.price',
        message: 'offers.price is required',
        type: 'required',
      });
    }
  }

  // Validate aggregateRating if present
  if (s.aggregateRating && typeof s.aggregateRating === 'object') {
    const rating = s.aggregateRating as Record<string, unknown>;
    if (rating.ratingValue !== undefined) {
      const value = parseFloat(rating.ratingValue as string);
      if (isNaN(value) || value < 0 || value > 5) {
        errors.push({
          path: 'aggregateRating.ratingValue',
          message: 'ratingValue must be between 0 and 5',
          type: 'value',
        });
      }
    }
  }

  return { valid: errors.length === 0, errors, warnings: baseResult.warnings };
}

/**
 * Validate FAQPage schema
 */
export function validateFAQPage(schema: unknown): ValidationResult {
  const baseResult = validateSchema(schema);
  if (!baseResult.valid) return baseResult;

  const errors: ValidationError[] = [...baseResult.errors];
  const s = schema as Record<string, unknown>;

  if (s['@type'] !== 'FAQPage') {
    errors.push({
      path: '@type',
      message: '@type must be "FAQPage"',
      type: 'value',
    });
  }

  if (!s.mainEntity) {
    errors.push({
      path: 'mainEntity',
      message: 'mainEntity is required for FAQPage',
      type: 'required',
    });
  } else if (!Array.isArray(s.mainEntity)) {
    errors.push({
      path: 'mainEntity',
      message: 'mainEntity must be an array',
      type: 'type',
    });
  } else {
    s.mainEntity.forEach((item: Record<string, unknown>, index: number) => {
      if (item['@type'] !== 'Question') {
        errors.push({
          path: `mainEntity[${index}].@type`,
          message: 'Each item must be of type "Question"',
          type: 'value',
        });
      }
      if (!item.name) {
        errors.push({
          path: `mainEntity[${index}].name`,
          message: 'Question name is required',
          type: 'required',
        });
      }
      if (!item.acceptedAnswer) {
        errors.push({
          path: `mainEntity[${index}].acceptedAnswer`,
          message: 'acceptedAnswer is required',
          type: 'required',
        });
      }
    });
  }

  return { valid: errors.length === 0, errors, warnings: baseResult.warnings };
}

/**
 * Validate BreadcrumbList schema
 */
export function validateBreadcrumbList(schema: unknown): ValidationResult {
  const baseResult = validateSchema(schema);
  if (!baseResult.valid) return baseResult;

  const errors: ValidationError[] = [...baseResult.errors];
  const s = schema as Record<string, unknown>;

  if (s['@type'] !== 'BreadcrumbList') {
    errors.push({
      path: '@type',
      message: '@type must be "BreadcrumbList"',
      type: 'value',
    });
  }

  if (!s.itemListElement) {
    errors.push({
      path: 'itemListElement',
      message: 'itemListElement is required for BreadcrumbList',
      type: 'required',
    });
  } else if (!Array.isArray(s.itemListElement)) {
    errors.push({
      path: 'itemListElement',
      message: 'itemListElement must be an array',
      type: 'type',
    });
  } else {
    s.itemListElement.forEach((item: Record<string, unknown> | null, index: number) => {
      if (!item) {
        errors.push({
          path: `itemListElement[${index}]`,
          message: 'Item cannot be null',
          type: 'type',
        });
        return;
      }
      if (item['@type'] !== 'ListItem') {
        errors.push({
          path: `itemListElement[${index}].@type`,
          message: 'Each item must be of type "ListItem"',
          type: 'value',
        });
      }
      if (typeof item.position !== 'number') {
        errors.push({
          path: `itemListElement[${index}].position`,
          message: 'position must be a number',
          type: 'type',
        });
      }
      if (!item.name) {
        errors.push({
          path: `itemListElement[${index}].name`,
          message: 'name is required',
          type: 'required',
        });
      }
      if (item.item && typeof item.item === 'string') {
        if (!item.item.startsWith('https://')) {
          errors.push({
            path: `itemListElement[${index}].item`,
            message: 'item URL must use HTTPS',
            type: 'format',
          });
        }
      }
    });
  }

  return { valid: errors.length === 0, errors, warnings: baseResult.warnings };
}

/**
 * Generate Google Rich Results Test URL
 */
export function generateGoogleRichResultsTestUrl(url: string): string {
  const encodedUrl = encodeURIComponent(url);
  return `https://search.google.com/test/richresults?url=${encodedUrl}`;
}

/**
 * Validate URL format
 */
export function validateUrl(url: string): boolean {
  try {
    new URL(url);
    return url.startsWith('https://');
  } catch {
    return false;
  }
}

/**
 * Validate ISO 8601 date format
 */
export function validateDateFormat(dateStr: string): boolean {
  const iso8601Regex = /^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:?\d{2})?)?$/;
  return iso8601Regex.test(dateStr);
}

/**
 * Validate JSON-LD string
 */
export function validateJsonLd(jsonString: string): ValidationResult {
  const errors: ValidationError[] = [];

  try {
    const parsed = JSON.parse(jsonString);
    return validateSchema(parsed);
  } catch (e) {
    errors.push({
      path: '',
      message: `Invalid JSON: ${e instanceof Error ? e.message : 'Unknown error'}`,
      type: 'format',
    });
    return { valid: false, errors };
  }
}

/**
 * Validate all common schema types
 */
export function validateAnySchema(schema: unknown): ValidationResult {
  if (!schema || typeof schema !== 'object') {
    return validateSchema(schema);
  }

  const s = schema as Record<string, unknown>;
  const type = s['@type'];

  switch (type) {
    case 'SoftwareApplication':
      return validateSoftwareApplication(schema);
    case 'FAQPage':
      return validateFAQPage(schema);
    case 'BreadcrumbList':
      return validateBreadcrumbList(schema);
    default:
      return validateSchema(schema);
  }
}

/**
 * Batch validate multiple schemas
 */
export function validateSchemas(schemas: unknown[]): {
  valid: boolean;
  results: Array<{ schema: unknown; result: ValidationResult }>;
} {
  const results = schemas.map(schema => ({
    schema,
    result: validateAnySchema(schema),
  }));

  return {
    valid: results.every(r => r.result.valid),
    results,
  };
}
