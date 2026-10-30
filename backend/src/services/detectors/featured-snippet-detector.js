/**
 * Featured Snippet Detector
 * TASK-7-5: Detects featured snippets (answer boxes) in SERP results
 * 
 * Featured snippets appear at the top of search results and can be:
 * - Paragraph snippets
 * - List snippets
 * - Table snippets
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect featured snippet in SERP results.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional fields
 */
export function detectFeaturedSnippet(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'featured_snippet',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for answer box
  const answerBox = serp.answerBox;
  
  if (!answerBox || typeof answerBox !== 'object' || Array.isArray(answerBox)) {
    return defaultResult;
  }
  
  // Build result object
  const result = {
    type: 'featured_snippet',
    isPresent: false
  };
  
  // Extract optional fields if present
  if (answerBox.url && typeof answerBox.url === 'string') {
    result.url = answerBox.url;
  }
  
  if (answerBox.title && typeof answerBox.title === 'string') {
    result.title = answerBox.title;
  }
  
  if (answerBox.snippet && typeof answerBox.snippet === 'string') {
    result.snippet = answerBox.snippet;
  }
  
  // Check if our URL owns the featured snippet
  if (answerBox.url && targetUrl) {
    const answerHostname = extractHostname(answerBox.url);
    const targetHostname = extractHostname(targetUrl);
    
    if (answerHostname && targetHostname) {
      result.isPresent = normalizeHostname(answerHostname) === normalizeHostname(targetHostname);
    }
  }
  
  return result;
}

// Re-export normalizeHostname for tests
export { normalizeHostname } from './url-utils.js';
