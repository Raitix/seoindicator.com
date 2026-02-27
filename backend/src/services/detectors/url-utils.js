/**
 * URL Utilities for SERP Feature Detection
 * Shared helper functions for URL parsing and domain matching
 */

/**
 * Normalize a hostname by removing www prefix and converting to lowercase.
 * Can accept either a bare hostname or a full URL.
 * @param {string} hostnameOrUrl - The hostname or URL to normalize
 * @returns {string} Normalized hostname
 */
export function normalizeHostname(hostnameOrUrl) {
  if (!hostnameOrUrl || typeof hostnameOrUrl !== 'string') {
    return '';
  }
  
  let input = hostnameOrUrl.toLowerCase().trim();
  
  // If it looks like a URL, extract the hostname first
  if (input.match(/^https?:\/\//i)) {
    try {
      const parsed = new URL(input);
      input = parsed.hostname;
    } catch (e) {
      // Invalid URL, continue with original input
    }
  }
  
  // Remove www prefix
  if (input.startsWith('www.')) {
    input = input.substring(4);
  }
  
  return input;
}

/**
 * Extract hostname from a URL string.
 * Handles various URL formats and edge cases.
 * @param {string} url - The URL to extract hostname from
 * @returns {string|null} The extracted hostname or null if invalid
 */
export function extractHostname(url) {
  if (!url || typeof url !== 'string') {
    return null;
  }
  
  const trimmed = url.trim();
  if (trimmed === '') {
    return null;
  }
  
  try {
    // Handle URLs without protocol
    let urlToParse = trimmed;
    if (!trimmed.match(/^https?:\/\//i)) {
      // Try to parse as-is first (might fail)
      // If it looks like a domain, add protocol
      if (trimmed.match(/^[a-z0-9.-]+/i)) {
        urlToParse = 'https://' + trimmed;
      } else {
        return null;
      }
    }
    
    const parsed = new URL(urlToParse);
    return parsed.hostname;
  } catch (e) {
    // Invalid URL
    return null;
  }
}

/**
 * Check if two URLs belong to the same domain (after normalization).
 * @param {string} url1 - First URL
 * @param {string} url2 - Second URL
 * @returns {boolean} True if domains match
 */
export function domainsMatch(url1, url2) {
  const host1 = extractHostname(url1);
  const host2 = extractHostname(url2);
  
  if (!host1 || !host2) {
    return false;
  }
  
  return normalizeHostname(host1) === normalizeHostname(host2);
}

/**
 * Validate that a value is a valid SERP result object.
 * @param {any} serp - Value to validate
 * @returns {boolean} True if valid SERP object
 */
export function isValidSerpObject(serp) {
  return serp !== null && typeof serp === 'object' && !Array.isArray(serp);
}
