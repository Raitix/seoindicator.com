/**
 * Image Pack Detector
 * TASK-7-7: Detects image pack results in SERP
 * 
 * Image packs appear as a horizontal row of images in search results.
 * This detector identifies if any images are from the target domain.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect image pack in SERP results.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional position
 */
export function detectImagePack(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'image_pack',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for images data
  const images = serp.images;
  
  // Validate images is an array with content
  if (!images || !Array.isArray(images) || images.length === 0) {
    return defaultResult;
  }
  
  // Extract target domain for matching
  const targetHostname = extractHostname(targetUrl);
  if (!targetHostname) {
    return defaultResult;
  }
  
  const normalizedTarget = normalizeHostname(targetHostname);
  
  // Search for target URL in image entries
  for (let i = 0; i < images.length; i++) {
    const image = images[i];
    
    // Skip entries without valid object
    if (!image || typeof image !== 'object') {
      continue;
    }
    
    const imageUrl = image.url;
    if (!imageUrl || typeof imageUrl !== 'string' || imageUrl.trim() === '') {
      continue;
    }
    
    // Extract and compare domains
    const imageHostname = extractHostname(imageUrl);
    if (imageHostname && normalizeHostname(imageHostname) === normalizedTarget) {
      // Found match! Position is 1-indexed
      const result = {
        type: 'image_pack',
        isPresent: true,
        position: i + 1
      };
      
      // Include optional fields if present
      if (image.title && typeof image.title === 'string') {
        result.title = image.title;
      }
      
      if (image.source && typeof image.source === 'string') {
        result.source = image.source;
      }
      
      return result;
    }
  }
  
  // Target URL not found in images
  return defaultResult;
}

/**
 * Extract all image pack entries with their data.
 * Useful for competitor analysis.
 * @param {Object} serp - SERP result object
 * @returns {Array} Array of image entries
 */
export function getImagePackEntries(serp) {
  if (!isValidSerpObject(serp)) {
    return [];
  }
  
  const images = serp.images;
  
  if (!images || !Array.isArray(images)) {
    return [];
  }
  
  return images
    .filter(image => image && typeof image === 'object')
    .map((image, index) => ({
      position: index + 1,
      url: image.url || null,
      title: image.title || null,
      source: image.source || null,
      thumbnail: image.thumbnail || null
    }));
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
