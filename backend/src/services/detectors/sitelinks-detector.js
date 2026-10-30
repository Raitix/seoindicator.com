/**
 * Sitelinks Detector
 * TASK-7-7: Detects sitelinks in SERP results
 * 
 * Sitelinks appear under the first organic result and show additional
 * page links. This detector identifies if the first result has sitelinks
 * and belongs to the target domain.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect sitelinks in SERP results.
 * Note: Sitelinks only appear on position 1 organic results.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional fields
 */
export function detectSitelinks(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'sitelinks',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for organic results
  const organicResults = serp.organicResults;
  
  // Validate organicResults is an array with content
  if (!organicResults || !Array.isArray(organicResults) || organicResults.length === 0) {
    return defaultResult;
  }
  
  // Get the first result (position 1)
  const firstResult = organicResults[0];
  
  // Validate first result
  if (!firstResult || typeof firstResult !== 'object') {
    return defaultResult;
  }
  
  // Check if first result belongs to target domain
  const firstUrl = firstResult.url;
  if (!firstUrl || typeof firstUrl !== 'string') {
    return defaultResult;
  }
  
  // Extract target domain for matching
  const targetHostname = extractHostname(targetUrl);
  if (!targetHostname) {
    return defaultResult;
  }
  
  const normalizedTarget = normalizeHostname(targetHostname);
  const firstHostname = extractHostname(firstUrl);
  
  // First result must be ours
  if (!firstHostname || normalizeHostname(firstHostname) !== normalizedTarget) {
    return defaultResult;
  }
  
  // Check for sitelinks
  const sitelinks = firstResult.sitelinks;
  
  // Validate sitelinks is an array with content
  if (!sitelinks || !Array.isArray(sitelinks) || sitelinks.length === 0) {
    return defaultResult;
  }
  
  // Build result
  const result = {
    type: 'sitelinks',
    isPresent: true,
    count: sitelinks.length
  };
  
  // Extract sitelink URLs and titles
  const validSitelinks = sitelinks
    .filter(sl => sl && typeof sl === 'object')
    .map(sl => ({
      title: sl.title || null,
      url: sl.url || null
    }));
  
  if (validSitelinks.length > 0) {
    result.sitelinks = validSitelinks;
  }
  
  return result;
}

/**
 * Extract sitelinks data from SERP.
 * @param {Object} serp - SERP result object
 * @returns {Array} Array of sitelink objects
 */
export function getSitelinks(serp) {
  if (!isValidSerpObject(serp)) {
    return [];
  }
  
  const organicResults = serp.organicResults;
  
  if (!organicResults || !Array.isArray(organicResults) || organicResults.length === 0) {
    return [];
  }
  
  const firstResult = organicResults[0];
  
  if (!firstResult || typeof firstResult !== 'object') {
    return [];
  }
  
  const sitelinks = firstResult.sitelinks;
  
  if (!sitelinks || !Array.isArray(sitelinks)) {
    return [];
  }
  
  return sitelinks
    .filter(sl => sl && typeof sl === 'object')
    .map((sl, index) => ({
      position: index + 1,
      title: sl.title || null,
      url: sl.url || null,
      description: sl.description || sl.snippet || null
    }));
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
