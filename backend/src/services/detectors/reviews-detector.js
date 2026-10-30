/**
 * Reviews Detector
 * TASK-7-7: Detects reviews/ratings in SERP results
 * 
 * Reviews appear as star ratings alongside organic results.
 * This detector identifies if the target result has review ratings.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect reviews/ratings in SERP results.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional fields
 */
export function detectReviews(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'reviews',
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
  
  // Extract target domain for matching
  const targetHostname = extractHostname(targetUrl);
  if (!targetHostname) {
    return defaultResult;
  }
  
  const normalizedTarget = normalizeHostname(targetHostname);
  
  // Search for target URL in organic results
  for (let i = 0; i < organicResults.length; i++) {
    const result = organicResults[i];
    
    // Skip entries without valid object
    if (!result || typeof result !== 'object') {
      continue;
    }
    
    const resultUrl = result.url;
    if (!resultUrl || typeof resultUrl !== 'string') {
      continue;
    }
    
    // Extract and compare domains
    const resultHostname = extractHostname(resultUrl);
    if (!resultHostname || normalizeHostname(resultHostname) !== normalizedTarget) {
      continue;
    }
    
    // Found our result, check for reviews
    const reviews = result.reviews;
    
    if (!reviews || typeof reviews !== 'object' || Array.isArray(reviews)) {
      continue;
    }
    
    // Check for rating
    const rating = reviews.rating;
    if (rating === undefined || rating === null) {
      continue;
    }
    
    // Validate rating is a number in valid range (0-5)
    const numericRating = parseFloat(rating);
    if (isNaN(numericRating)) {
      continue;
    }
    
    // Clamp rating to valid range
    const clampedRating = Math.max(0, Math.min(5, numericRating));
    
    // Build result
    const featureResult = {
      type: 'reviews',
      isPresent: true,
      position: result.position || i + 1,
      rating: clampedRating
    };
    
    // Include optional fields if present
    if (reviews.count !== undefined && reviews.count !== null) {
      const count = parseInt(reviews.count, 10);
      if (!isNaN(count) && count >= 0) {
        featureResult.count = count;
      }
    }
    
    if (reviews.source && typeof reviews.source === 'string') {
      featureResult.source = reviews.source;
    }
    
    return featureResult;
  }
  
  // Target URL not found or no reviews
  return defaultResult;
}

/**
 * Extract all results with reviews from SERP.
 * @param {Object} serp - SERP result object
 * @returns {Array} Array of results with reviews
 */
export function getResultsWithReviews(serp) {
  if (!isValidSerpObject(serp)) {
    return [];
  }
  
  const organicResults = serp.organicResults;
  
  if (!organicResults || !Array.isArray(organicResults)) {
    return [];
  }
  
  return organicResults
    .filter(result => result && typeof result === 'object' && result.reviews && typeof result.reviews === 'object')
    .map(result => ({
      position: result.position || null,
      url: result.url || null,
      title: result.title || null,
      reviews: {
        rating: result.reviews.rating !== undefined ? parseFloat(result.reviews.rating) : null,
        count: result.reviews.count !== undefined ? parseInt(result.reviews.count, 10) : null,
        source: result.reviews.source || null
      }
    }));
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
