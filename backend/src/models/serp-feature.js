/**
 * SERP Feature Types and Model
 * Task: TASK-7-2
 * 
 * Defines TypeScript-like types, interfaces, and model classes for SERP feature data.
 * This module provides:
 * - SERPFeatureType: Union type for all 10 feature types
 * - SERPFeature: Interface for feature data
 * - SERPFeatureDetection: Interface for detection records
 * - SERP_FEATURE_META: Metadata constants for each feature type
 * - ALL_FEATURE_TYPES: Immutable array of all feature types
 * - SERPFeatureModel: Model class with serialization/deserialization
 */

// ========================================
// Type Definitions (as constants for JS)
// ========================================

/**
 * All valid SERP feature types
 * @typedef {'featured_snippet' | 'local_pack' | 'image_pack' | 'video' | 'people_also_ask' | 'knowledge_panel' | 'sitelinks' | 'reviews' | 'faq' | 'product'} SERPFeatureType
 */

/**
 * @type {readonly string[]}
 */
export const ALL_FEATURE_TYPES = Object.freeze([
  'featured_snippet',
  'local_pack',
  'image_pack',
  'video',
  'people_also_ask',
  'knowledge_panel',
  'sitelinks',
  'reviews',
  'faq',
  'product'
]);

/**
 * @typedef {Object} SERPFeature
 * @property {SERPFeatureType} type - The type of SERP feature
 * @property {boolean} isPresent - Whether the feature is present in results
 * @property {number} [position] - Position of the feature (optional)
 * @property {string} [url] - URL associated with the feature (optional)
 * @property {string} [title] - Title text of the feature (optional)
 * @property {string} [snippet] - Snippet/content text (optional)
 */

/**
 * @typedef {Object} SERPFeatureDetection
 * @property {number} keywordId - ID of the keyword
 * @property {number} rankingId - ID of the ranking record
 * @property {SERPFeature[]} features - Array of detected features
 * @property {Date} detectedAt - Timestamp when features were detected
 */

// ========================================
// Metadata Constants
// ========================================

/**
 * Metadata for each SERP feature type
 * @type {Readonly<Record<string, {name: string, icon: string, description: string, ctrImpact: string}>>}
 */
export const SERP_FEATURE_META = Object.freeze({
  featured_snippet: {
    name: 'Featured Snippet',
    icon: '🏆',
    description: 'Answer box at top of search results',
    ctrImpact: '+30-50%'
  },
  local_pack: {
    name: 'Local Pack',
    icon: '📍',
    description: 'Map with 3 local business listings',
    ctrImpact: 'High for local'
  },
  image_pack: {
    name: 'Image Pack',
    icon: '🖼️',
    description: 'Horizontal row of images in results',
    ctrImpact: 'Visual attraction'
  },
  video: {
    name: 'Video Result',
    icon: '🎬',
    description: 'Video thumbnail in search results',
    ctrImpact: 'High engagement'
  },
  people_also_ask: {
    name: 'People Also Ask',
    icon: '❓',
    description: 'Expandable related questions',
    ctrImpact: 'Secondary visibility'
  },
  knowledge_panel: {
    name: 'Knowledge Panel',
    icon: '📋',
    description: 'Entity information sidebar',
    ctrImpact: 'Brand authority'
  },
  sitelinks: {
    name: 'Sitelinks',
    icon: '🔗',
    description: 'Additional page links under result',
    ctrImpact: '+20-30%'
  },
  reviews: {
    name: 'Reviews/Ratings',
    icon: '⭐',
    description: 'Star ratings displayed with result',
    ctrImpact: '+10-15%'
  },
  faq: {
    name: 'FAQ Rich Result',
    icon: '📝',
    description: 'FAQ accordion in search results',
    ctrImpact: 'Increased visibility'
  },
  product: {
    name: 'Product Listing',
    icon: '🛒',
    description: 'Product rich result with price/availability',
    ctrImpact: 'Shopping intent'
  }
});

// ========================================
// Validation Functions
// ========================================

/**
 * Check if a value is a valid SERP feature type
 * @param {any} type - Value to check
 * @returns {boolean} True if valid SERPFeatureType
 */
export function isValidFeatureType(type) {
  return typeof type === 'string' && ALL_FEATURE_TYPES.includes(type);
}

/**
 * Get metadata for a feature type
 * @param {string} type - SERP feature type
 * @returns {{name: string, icon: string, description: string, ctrImpact: string}|null} Metadata or null if invalid
 */
export function getFeatureMeta(type) {
  if (!isValidFeatureType(type)) {
    return null;
  }
  return SERP_FEATURE_META[type] || null;
}

/**
 * Validate SERPFeature object
 * @param {any} data - Data to validate
 * @returns {{valid: boolean, errors: string[]}} Validation result
 */
export function validateSERPFeature(data) {
  const errors = [];

  if (!data || typeof data !== 'object') {
    return { valid: false, errors: ['Data must be an object'] };
  }

  // Required: type
  if (!isValidFeatureType(data.type)) {
    errors.push('type must be a valid SERPFeatureType');
  }

  // Required: isPresent (must be boolean)
  if (typeof data.isPresent !== 'boolean') {
    errors.push('isPresent must be a boolean');
  }

  // Optional: position (must be number if present)
  if (data.position !== undefined && typeof data.position !== 'number') {
    errors.push('position must be a number if provided');
  }

  // Optional: position must be non-negative
  if (typeof data.position === 'number' && data.position < 0) {
    errors.push('position must be non-negative');
  }

  // Optional: url (must be string if present)
  if (data.url !== undefined && typeof data.url !== 'string') {
    errors.push('url must be a string if provided');
  }

  // Optional: title (must be string if present)
  if (data.title !== undefined && typeof data.title !== 'string') {
    errors.push('title must be a string if provided');
  }

  // Optional: snippet (must be string if present)
  if (data.snippet !== undefined && typeof data.snippet !== 'string') {
    errors.push('snippet must be a string if provided');
  }

  return {
    valid: errors.length === 0,
    errors
  };
}

/**
 * Validate SERPFeatureDetection object
 * @param {any} data - Data to validate
 * @returns {{valid: boolean, errors: string[]}} Validation result
 */
export function validateSERPFeatureDetection(data) {
  const errors = [];

  if (!data || typeof data !== 'object') {
    return { valid: false, errors: ['Data must be an object'] };
  }

  // Required: keywordId (must be number)
  if (typeof data.keywordId !== 'number') {
    errors.push('keywordId must be a number');
  }

  // Required: rankingId (must be number)
  if (typeof data.rankingId !== 'number') {
    errors.push('rankingId must be a number');
  }

  // Required: features (must be array)
  if (!Array.isArray(data.features)) {
    errors.push('features must be an array');
  } else {
    // Validate each feature in the array
    for (let i = 0; i < data.features.length; i++) {
      const featureValidation = validateSERPFeature(data.features[i]);
      if (!featureValidation.valid) {
        errors.push(`features[${i}]: ${featureValidation.errors.join(', ')}`);
      }
    }
  }

  // Required: detectedAt (must be Date)
  if (!(data.detectedAt instanceof Date)) {
    errors.push('detectedAt must be a Date object');
  }

  return {
    valid: errors.length === 0,
    errors
  };
}

// ========================================
// Model Class
// ========================================

/**
 * Custom error class for SERP Feature validation errors
 */
export class SERPFeatureValidationError extends Error {
  constructor(message, errors = []) {
    super(message);
    this.name = 'SERPFeatureValidationError';
    this.errors = errors;
  }
}

/**
 * Custom error class for SERP Feature parse errors
 */
export class SERPFeatureParseError extends Error {
  constructor(message) {
    super(message);
    this.name = 'SERPFeatureParseError';
  }
}

/**
 * SERPFeature Model Class
 * Handles serialization/deserialization and validation
 */
export class SERPFeatureModel {
  /**
   * Create a new SERPFeatureModel instance
   * @param {Object} data - Feature data
   * @param {SERPFeatureType} data.type - Feature type (required)
   * @param {boolean} data.isPresent - Whether feature is present (required)
   * @param {number} [data.position] - Position (optional)
   * @param {string} [data.url] - URL (optional)
   * @param {string} [data.title] - Title (optional)
   * @param {string} [data.snippet] - Snippet (optional)
   * @throws {SERPFeatureValidationError} If validation fails
   */
  constructor(data) {
    const validation = validateSERPFeature(data);
    if (!validation.valid) {
      throw new SERPFeatureValidationError(
        'Invalid SERPFeature data',
        validation.errors
      );
    }

    /** @type {SERPFeatureType} */
    this.type = data.type;

    /** @type {boolean} */
    this.isPresent = data.isPresent;

    // Optional fields - only set if defined
    if (data.position !== undefined) {
      /** @type {number|undefined} */
      this.position = data.position;
    }
    if (data.url !== undefined) {
      /** @type {string|undefined} */
      this.url = data.url;
    }
    if (data.title !== undefined) {
      /** @type {string|undefined} */
      this.title = data.title;
    }
    if (data.snippet !== undefined) {
      /** @type {string|undefined} */
      this.snippet = data.snippet;
    }
  }

  /**
   * Convert model to plain object (for serialization)
   * Omits undefined optional fields
   * @returns {Object} Plain object representation
   */
  toObject() {
    const obj = {
      type: this.type,
      isPresent: this.isPresent
    };

    // Only include optional fields if they are defined
    if (this.position !== undefined) obj.position = this.position;
    if (this.url !== undefined) obj.url = this.url;
    if (this.title !== undefined) obj.title = this.title;
    if (this.snippet !== undefined) obj.snippet = this.snippet;

    return obj;
  }

  /**
   * Convert model to JSON string
   * Omits undefined optional fields
   * @returns {string} JSON string
   */
  toJSON() {
    return JSON.stringify(this.toObject());
  }

  /**
   * Create a SERPFeatureModel from a JSON string
   * @param {string} json - JSON string to parse
   * @returns {SERPFeatureModel} New instance
   * @throws {SERPFeatureParseError} If JSON is malformed
   * @throws {SERPFeatureValidationError} If data is invalid
   */
  static fromJSON(json) {
    let data;
    try {
      data = JSON.parse(json);
    } catch (e) {
      throw new SERPFeatureParseError(`Failed to parse JSON: ${e.message}`);
    }

    return new SERPFeatureModel(data);
  }

  /**
   * Check if a type is a valid SERP feature type
   * @param {any} type - Type to check
   * @returns {boolean} True if valid
   */
  static isValidType(type) {
    return isValidFeatureType(type);
  }

  /**
   * Get all valid feature types
   * @returns {readonly string[]} Array of all feature types
   */
  static getAllTypes() {
    return ALL_FEATURE_TYPES;
  }

  /**
   * Get metadata for a feature type
   * @param {string} type - Feature type
   * @returns {Object|null} Metadata or null if invalid
   */
  static getMeta(type) {
    return getFeatureMeta(type);
  }
}
