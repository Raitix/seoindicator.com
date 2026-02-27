/**
 * Local Pack Detector
 * TASK-7-6: Detects local pack results in SERP
 * 
 * Local pack appears for queries with local intent and shows 3 businesses on a map.
 * This detector identifies if the target URL appears in the local pack and at what position.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect local pack in SERP results.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional position
 */
export function detectLocalPack(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'local_pack',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for local pack data
  const localPack = serp.localPack;
  
  // Validate local pack is an array
  if (!localPack || !Array.isArray(localPack) || localPack.length === 0) {
    return defaultResult;
  }
  
  // Extract target domain for matching
  const targetHostname = extractHostname(targetUrl);
  if (!targetHostname) {
    return defaultResult;
  }
  
  const normalizedTarget = normalizeHostname(targetHostname);
  
  // Search for target URL in local pack entries
  for (let i = 0; i < localPack.length; i++) {
    const entry = localPack[i];
    
    // Skip entries without valid URL
    if (!entry || typeof entry !== 'object') {
      continue;
    }
    
    const entryUrl = entry.url;
    if (!entryUrl || typeof entryUrl !== 'string' || entryUrl.trim() === '') {
      continue;
    }
    
    // Extract and compare domains
    const entryHostname = extractHostname(entryUrl);
    if (entryHostname && normalizeHostname(entryHostname) === normalizedTarget) {
      // Found match! Position is 1-indexed
      return {
        type: 'local_pack',
        isPresent: true,
        position: i + 1
      };
    }
  }
  
  // Target URL not found in local pack
  return defaultResult;
}

/**
 * Extract all local pack entries with their data.
 * Useful for competitor analysis.
 * @param {Object} serp - SERP result object
 * @returns {Array} Array of local pack entries
 */
export function getLocalPackEntries(serp) {
  if (!isValidSerpObject(serp)) {
    return [];
  }
  
  const localPack = serp.localPack;
  
  if (!localPack || !Array.isArray(localPack)) {
    return [];
  }
  
  return localPack
    .filter(entry => entry && typeof entry === 'object')
    .map((entry, index) => ({
      position: index + 1,
      name: entry.name || null,
      url: entry.url || null,
      rating: entry.rating || null,
      reviewCount: entry.reviewCount || null,
      address: entry.address || null,
      phone: entry.phone || null
    }));
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
