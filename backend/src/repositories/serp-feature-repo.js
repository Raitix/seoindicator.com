/**
 * SERP Feature Repository
 * Task: TASK-7-3
 * 
 * Repository layer for SERP feature data access.
 * Provides methods for inserting, querying, and managing SERP feature records.
 */

import { 
  ALL_FEATURE_TYPES, 
  isValidFeatureType,
  SERPFeatureValidationError 
} from '../models/serp-feature.js';

/**
 * Valid actions for feature history
 * @type {readonly string[]}
 */
const VALID_ACTIONS = Object.freeze(['gained', 'lost']);

/**
 * Default limit for history queries
 */
const DEFAULT_HISTORY_LIMIT = 50;

/**
 * Default hours for recent changes
 */
const DEFAULT_RECENT_HOURS = 24;

/**
 * SERPFeatureRepository class
 * Handles all database operations for serp_features table
 */
class SERPFeatureRepository {
  /**
   * Create a new SERPFeatureRepository instance
   * @param {Object} db - Database connection (sqlite3 or better-sqlite3 compatible)
   */
  constructor(db) {
    if (!db) {
      throw new Error('Database connection is required');
    }
    this.db = db;
  }

  /**
   * Validate feature data before insert/update
   * @param {Object} feature - Feature data to validate
   * @throws {SERPFeatureValidationError} If validation fails
   */
  _validateFeature(feature) {
    if (!feature || typeof feature !== 'object') {
      throw new SERPFeatureValidationError('Feature must be a non-null object');
    }

    const errors = [];

    // Required: keyword_id (must be number)
    if (feature.keyword_id === undefined || feature.keyword_id === null) {
      errors.push('keyword_id is required');
    } else if (typeof feature.keyword_id !== 'number' || !Number.isInteger(feature.keyword_id)) {
      errors.push('keyword_id must be an integer');
    }

    // Required: feature_type (must be valid SERP feature type)
    if (feature.feature_type === undefined || feature.feature_type === null) {
      errors.push('feature_type is required');
    } else if (!isValidFeatureType(feature.feature_type)) {
      errors.push(`feature_type must be one of: ${ALL_FEATURE_TYPES.join(', ')}`);
    }

    // Required: is_present (must be boolean or 0/1)
    if (feature.is_present === undefined || feature.is_present === null) {
      errors.push('is_present is required');
    } else if (typeof feature.is_present !== 'boolean' && feature.is_present !== 0 && feature.is_present !== 1) {
      errors.push('is_present must be a boolean');
    }

    // Optional: position (must be non-negative integer if provided)
    if (feature.position !== undefined && feature.position !== null) {
      if (typeof feature.position !== 'number' || !Number.isInteger(feature.position) || feature.position < 0) {
        errors.push('position must be a non-negative integer');
      }
    }

    // Optional: url (must be string if provided)
    if (feature.url !== undefined && feature.url !== null && typeof feature.url !== 'string') {
      errors.push('url must be a string');
    }

    // Optional: title (must be string if provided)
    if (feature.title !== undefined && feature.title !== null && typeof feature.title !== 'string') {
      errors.push('title must be a string');
    }

    // Optional: snippet (must be string if provided)
    if (feature.snippet !== undefined && feature.snippet !== null && typeof feature.snippet !== 'string') {
      errors.push('snippet must be a string');
    }

    if (errors.length > 0) {
      throw new SERPFeatureValidationError('Invalid feature data', errors);
    }
  }

  /**
   * Insert a single SERP feature record
   * @param {Object} feature - Feature data to insert
   * @param {number} feature.keyword_id - Keyword ID (required)
   * @param {string} feature.feature_type - Feature type (required)
   * @param {boolean} feature.is_present - Whether feature is present (required)
   * @param {number} [feature.position] - Position within feature (optional)
   * @param {string} [feature.url] - URL associated with feature (optional)
   * @param {string} [feature.title] - Title text (optional)
   * @param {string} [feature.snippet] - Snippet text (optional)
   * @param {string} [feature.checked_at] - Check timestamp (optional, defaults to now)
   * @returns {Promise<Object>} Inserted record with id
   * @throws {SERPFeatureValidationError} If validation fails
   * @throws {Error} If database error occurs
   */
  async insert(feature) {
    this._validateFeature(feature);

    const checkedAt = feature.checked_at || new Date().toISOString();
    const isPresent = feature.is_present === true || feature.is_present === 1 ? 1 : 0;

    const sql = `
      INSERT INTO serp_features (keyword_id, feature_type, is_present, position, url, title, snippet, checked_at, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    `;

    const params = [
      feature.keyword_id,
      feature.feature_type,
      isPresent,
      feature.position ?? null,
      feature.url ?? null,
      feature.title ?? null,
      feature.snippet ?? null,
      checkedAt
    ];

    return new Promise((resolve, reject) => {
      this.db.run(sql, params, function(err) {
        if (err) {
          if (err.message.includes('UNIQUE constraint')) {
            reject(new Error(`Unique constraint violation: feature type "${feature.feature_type}" already exists for keyword_id ${feature.keyword_id}`));
          } else if (err.message.includes('FOREIGN KEY constraint')) {
            reject(new Error(`Foreign key constraint violation: keyword_id ${feature.keyword_id} does not exist`));
          } else {
            reject(err);
          }
          return;
        }

        resolve({
          id: this.lastID,
          keyword_id: feature.keyword_id,
          feature_type: feature.feature_type,
          is_present: isPresent === 1,
          position: feature.position ?? null,
          url: feature.url ?? null,
          title: feature.title ?? null,
          snippet: feature.snippet ?? null,
          checked_at: checkedAt
        });
      });
    });
  }

  /**
   * Insert multiple SERP feature records in a batch (atomic transaction)
   * @param {Array<Object>} features - Array of feature objects to insert
   * @returns {Promise<Array<Object>>} Array of inserted records with ids
   * @throws {SERPFeatureValidationError} If validation fails for any feature
   * @throws {Error} If database error occurs (entire batch rolled back)
   */
  async insertBatch(features) {
    if (!Array.isArray(features)) {
      throw new Error('Features must be an array');
    }

    if (features.length === 0) {
      return [];
    }

    // Validate all features first
    for (let i = 0; i < features.length; i++) {
      this._validateFeature(features[i]);
    }

    // Check for duplicates within the batch (same keyword_id + feature_type)
    const seen = new Set();
    for (const feature of features) {
      const key = `${feature.keyword_id}:${feature.feature_type}`;
      if (seen.has(key)) {
        throw new Error(`Duplicate feature type "${feature.feature_type}" in batch for keyword_id ${feature.keyword_id}`);
      }
      seen.add(key);
    }

    const results = [];

    return new Promise((resolve, reject) => {
      this.db.serialize(() => {
        this.db.run('BEGIN TRANSACTION', (err) => {
          if (err) {
            reject(err);
            return;
          }

          const stmt = this.db.prepare(`
            INSERT INTO serp_features (keyword_id, feature_type, is_present, position, url, title, snippet, checked_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
          `);

          let hasError = false;
          let inserted = 0;

          for (const feature of features) {
            const checkedAt = feature.checked_at || new Date().toISOString();
            const isPresent = feature.is_present === true || feature.is_present === 1 ? 1 : 0;

            stmt.run([
              feature.keyword_id,
              feature.feature_type,
              isPresent,
              feature.position ?? null,
              feature.url ?? null,
              feature.title ?? null,
              feature.snippet ?? null,
              checkedAt
            ], function(err) {
              if (err) {
                hasError = true;
                return;
              }

              results.push({
                id: this.lastID,
                keyword_id: feature.keyword_id,
                feature_type: feature.feature_type,
                is_present: isPresent === 1,
                position: feature.position ?? null,
                url: feature.url ?? null,
                title: feature.title ?? null,
                snippet: feature.snippet ?? null,
                checked_at: checkedAt
              });

              inserted++;

              if (inserted === features.length && !hasError) {
                stmt.finalize();
                this.db.run('COMMIT', (err) => {
                  if (err) {
                    reject(err);
                  } else {
                    resolve(results);
                  }
                });
              }
            }.bind(this));
          }

          // Handle errors
          if (hasError) {
            stmt.finalize();
            this.db.run('ROLLBACK', () => {
              reject(new Error('Batch insert failed - transaction rolled back'));
            });
          }
        });
      });
    });
  }

  /**
   * Find all SERP features for a specific keyword
   * @param {number} keywordId - Keyword ID to search for
   * @returns {Promise<Array<Object>>} Array of feature records, ordered by checked_at DESC
   * @throws {Error} If keywordId is invalid
   */
  async findByKeywordId(keywordId) {
    if (typeof keywordId !== 'number' || !Number.isInteger(keywordId)) {
      throw new Error('keyword_id must be an integer');
    }

    if (keywordId < 0) {
      return [];
    }

    const sql = `
      SELECT id, keyword_id, feature_type, is_present, position, url, title, snippet, checked_at, created_at, updated_at
      FROM serp_features
      WHERE keyword_id = ?
      ORDER BY checked_at DESC
    `;

    return new Promise((resolve, reject) => {
      this.db.all(sql, [keywordId], (err, rows) => {
        if (err) {
          reject(err);
          return;
        }

        resolve((rows || []).map(row => ({
          ...row,
          is_present: row.is_present === 1
        })));
      });
    });
  }

  /**
   * Find all SERP features for a specific keyword (alias for backward compatibility)
   * @param {number} rankingId - This is treated as keyword_id for compatibility
   * @returns {Promise<Array<Object>>} Array of feature records
   */
  async findByRankingId(rankingId) {
    // For backward compatibility with tests that use ranking_id
    // In the actual schema, we use keyword_id directly
    return this.findByKeywordId(rankingId);
  }

  /**
   * Find all SERP features of a specific type, optionally filtered by site
   * @param {string} featureType - SERP feature type to search for
   * @param {number} [siteId] - Optional site ID to filter by
   * @returns {Promise<Array<Object>>} Array of feature records with keyword info
   * @throws {SERPFeatureValidationError} If featureType is invalid
   */
  async findFeaturesByType(featureType, siteId = undefined) {
    if (!isValidFeatureType(featureType)) {
      throw new SERPFeatureValidationError(`Invalid feature type: ${featureType}. Must be one of: ${ALL_FEATURE_TYPES.join(', ')}`);
    }

    let sql = `
      SELECT sf.id, sf.keyword_id, sf.feature_type, sf.is_present, sf.position, sf.url, sf.title, sf.snippet, sf.checked_at,
             k.keyword, k.site_id
      FROM serp_features sf
      JOIN keywords k ON sf.keyword_id = k.id
      WHERE sf.feature_type = ? AND sf.is_present = 1
    `;

    const params = [featureType];

    if (siteId !== undefined && siteId !== null) {
      if (typeof siteId !== 'number' || !Number.isInteger(siteId)) {
        throw new Error('siteId must be an integer');
      }
      sql += ' AND k.site_id = ?';
      params.push(siteId);
    }

    sql += ' ORDER BY sf.checked_at DESC';

    return new Promise((resolve, reject) => {
      this.db.all(sql, params, (err, rows) => {
        if (err) {
          reject(err);
          return;
        }

        resolve((rows || []).map(row => ({
          ...row,
          is_present: row.is_present === 1
        })));
      });
    });
  }

  /**
   * Get a summary of SERP feature counts for a site
   * @param {number} siteId - Site ID to get summary for
   * @returns {Promise<Object>} Object with feature type counts and totalKeywords
   * @throws {Error} If siteId is invalid
   */
  async getFeatureSummary(siteId) {
    if (typeof siteId !== 'number' || !Number.isInteger(siteId)) {
      throw new Error('siteId must be an integer');
    }

    if (siteId === null) {
      throw new Error('siteId cannot be null');
    }

    const sql = `
      SELECT sf.feature_type, COUNT(*) as count
      FROM serp_features sf
      JOIN keywords k ON sf.keyword_id = k.id
      WHERE k.site_id = ? AND sf.is_present = 1
      GROUP BY sf.feature_type
    `;

    const totalKeywordsSql = `
      SELECT COUNT(DISTINCT k.id) as totalKeywords
      FROM keywords k
      WHERE k.site_id = ?
    `;

    return new Promise((resolve, reject) => {
      this.db.all(sql, [siteId], (err, rows) => {
        if (err) {
          reject(err);
          return;
        }

        this.db.get(totalKeywordsSql, [siteId], (err, totalRow) => {
          if (err) {
            reject(err);
            return;
          }

          // Initialize all feature types with 0
          const summary = {};
          for (const type of ALL_FEATURE_TYPES) {
            summary[type] = 0;
          }

          // Populate counts from query results
          for (const row of rows || []) {
            if (summary.hasOwnProperty(row.feature_type)) {
              summary[row.feature_type] = row.count;
            }
          }

          summary.totalKeywords = totalRow?.totalKeywords || 0;

          resolve(summary);
        });
      });
    });
  }
}

/**
 * SERPFeatureHistoryRepository class
 * Handles all database operations for serp_feature_history table
 */
class SERPFeatureHistoryRepository {
  /**
   * Create a new SERPFeatureHistoryRepository instance
   * @param {Object} db - Database connection
   */
  constructor(db) {
    if (!db) {
      throw new Error('Database connection is required');
    }
    this.db = db;
  }

  /**
   * Validate history entry data
   * @param {Object} entry - Entry data to validate
   * @throws {Error} If validation fails
   */
  _validateEntry(entry) {
    if (!entry || typeof entry !== 'object') {
      throw new Error('Entry must be a non-null object');
    }

    const errors = [];

    // Required: keyword_id
    if (typeof entry.keyword_id !== 'number' || !Number.isInteger(entry.keyword_id)) {
      errors.push('keyword_id must be an integer');
    }

    // Required: feature_type
    if (!isValidFeatureType(entry.feature_type)) {
      errors.push(`feature_type must be one of: ${ALL_FEATURE_TYPES.join(', ')}`);
    }

    // Required: action
    if (!VALID_ACTIONS.includes(entry.action)) {
      errors.push(`action must be one of: ${VALID_ACTIONS.join(', ')}`);
    }

    // Optional: previous_url
    if (entry.previous_url !== undefined && entry.previous_url !== null && typeof entry.previous_url !== 'string') {
      errors.push('previous_url must be a string');
    }

    // Optional: current_url
    if (entry.current_url !== undefined && entry.current_url !== null && typeof entry.current_url !== 'string') {
      errors.push('current_url must be a string');
    }

    if (errors.length > 0) {
      throw new Error(`Invalid history entry: ${errors.join(', ')}`);
    }
  }

  /**
   * Insert a history entry for a feature change
   * @param {Object} entry - History entry data
   * @param {number} entry.keyword_id - Keyword ID (required)
   * @param {string} entry.feature_type - Feature type (required)
   * @param {string} entry.action - Action type: 'gained' or 'lost' (required)
   * @param {string} [entry.previous_url] - Previous URL (optional)
   * @param {string} [entry.current_url] - Current URL (optional)
   * @param {string} [entry.detected_at] - Detection timestamp (optional, defaults to now)
   * @returns {Promise<Object>} Inserted record with id
   */
  async insertHistory(entry) {
    this._validateEntry(entry);

    const detectedAt = entry.detected_at || new Date().toISOString();

    const sql = `
      INSERT INTO serp_feature_history (keyword_id, feature_type, action, previous_url, current_url, detected_at, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    `;

    const params = [
      entry.keyword_id,
      entry.feature_type,
      entry.action,
      entry.previous_url ?? null,
      entry.current_url ?? null,
      detectedAt
    ];

    return new Promise((resolve, reject) => {
      this.db.run(sql, params, function(err) {
        if (err) {
          if (err.message.includes('FOREIGN KEY constraint')) {
            reject(new Error(`Foreign key constraint violation: keyword_id ${entry.keyword_id} does not exist`));
          } else {
            reject(err);
          }
          return;
        }

        resolve({
          id: this.lastID,
          keyword_id: entry.keyword_id,
          feature_type: entry.feature_type,
          action: entry.action,
          previous_url: entry.previous_url ?? null,
          current_url: entry.current_url ?? null,
          detected_at: detectedAt
        });
      });
    });
  }

  /**
   * Get feature history for a specific keyword
   * @param {number} keywordId - Keyword ID to get history for
   * @param {number} [limit=50] - Maximum number of entries to return
   * @returns {Promise<Array<Object>>} Array of history entries, ordered by detected_at DESC
   */
  async getHistoryByKeyword(keywordId, limit = DEFAULT_HISTORY_LIMIT) {
    if (typeof keywordId !== 'number' || !Number.isInteger(keywordId)) {
      throw new Error('keywordId must be an integer');
    }

    if (keywordId < 0) {
      throw new Error('keywordId cannot be negative');
    }

    if (typeof limit !== 'number' || !Number.isInteger(limit) || limit < 0) {
      if (limit === 0) {
        return [];
      }
      throw new Error('limit must be a non-negative integer');
    }

    if (limit === 0) {
      return [];
    }

    const sql = `
      SELECT id, keyword_id, feature_type, action, previous_url, current_url, detected_at, created_at, updated_at
      FROM serp_feature_history
      WHERE keyword_id = ?
      ORDER BY detected_at DESC
      LIMIT ?
    `;

    return new Promise((resolve, reject) => {
      this.db.all(sql, [keywordId, limit], (err, rows) => {
        if (err) {
          reject(err);
          return;
        }

        resolve(rows || []);
      });
    });
  }

  /**
   * Get recent feature changes for a site
   * @param {number} siteId - Site ID to get changes for
   * @param {number} [hours=24] - Number of hours to look back
   * @returns {Promise<Array<Object>>} Array of change entries with keyword info
   */
  async getRecentChanges(siteId, hours = DEFAULT_RECENT_HOURS) {
    if (typeof siteId !== 'number' || !Number.isInteger(siteId)) {
      throw new Error('siteId must be an integer');
    }

    if (siteId === null) {
      throw new Error('siteId cannot be null');
    }

    if (typeof hours !== 'number' || !Number.isInteger(hours) || hours < 0) {
      if (hours === 0) {
        return [];
      }
      throw new Error('hours must be a non-negative integer');
    }

    if (hours === 0) {
      return [];
    }

    const sql = `
      SELECT sfh.id, sfh.keyword_id, sfh.feature_type, sfh.action, sfh.previous_url, sfh.current_url, sfh.detected_at,
             k.keyword, k.site_id
      FROM serp_feature_history sfh
      JOIN keywords k ON sfh.keyword_id = k.id
      WHERE k.site_id = ?
        AND sfh.detected_at >= datetime('now', '-' || ? || ' hours')
      ORDER BY sfh.detected_at DESC
    `;

    return new Promise((resolve, reject) => {
      this.db.all(sql, [siteId, hours], (err, rows) => {
        if (err) {
          reject(err);
          return;
        }

        resolve(rows || []);
      });
    });
  }
}

// Export classes for testing and direct instantiation
export { SERPFeatureRepository, SERPFeatureHistoryRepository };

// Default export: factory functions that accept db connection
export default {
  SERPFeatureRepository,
  SERPFeatureHistoryRepository
};
