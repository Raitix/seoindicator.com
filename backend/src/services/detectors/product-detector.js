/**
 * Product Detector
 * TASK-7-7: Detects product rich results in SERP
 * 
 * Product results appear with price, availability, and review info.
 * This detector identifies if any product listings are from the target domain.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect product listings in SERP.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional fields
 */
export function detectProduct(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'product',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for products data
  const products = serp.products;
  
  // Validate products is an array with content
  if (!products || !Array.isArray(products) || products.length === 0) {
    return defaultResult;
  }
  
  // Extract target domain for matching
  const targetHostname = extractHostname(targetUrl);
  if (!targetHostname) {
    return defaultResult;
  }
  
  const normalizedTarget = normalizeHostname(targetHostname);
  
  // Search for target URL in product entries
  for (let i = 0; i < products.length; i++) {
    const product = products[i];
    
    // Skip entries without valid object
    if (!product || typeof product !== 'object') {
      continue;
    }
    
    const productUrl = product.url;
    if (!productUrl || typeof productUrl !== 'string' || productUrl.trim() === '') {
      continue;
    }
    
    // Extract and compare domains
    const productHostname = extractHostname(productUrl);
    if (productHostname && normalizeHostname(productHostname) === normalizedTarget) {
      // Found match! Position is 1-indexed
      const result = {
        type: 'product',
        isPresent: true,
        position: i + 1
      };
      
      // Include optional fields if present
      if (product.name && typeof product.name === 'string') {
        result.name = product.name;
      }
      
      if (product.price !== undefined && product.price !== null) {
        result.price = product.price;
      }
      
      if (product.salePrice !== undefined && product.salePrice !== null) {
        result.salePrice = product.salePrice;
      }
      
      if (product.availability && typeof product.availability === 'string') {
        result.availability = product.availability;
      }
      
      if (product.image && typeof product.image === 'string') {
        result.image = product.image;
      }
      
      if (product.rating !== undefined && product.rating !== null) {
        const numericRating = parseFloat(product.rating);
        if (!isNaN(numericRating)) {
          result.rating = Math.max(0, Math.min(5, numericRating));
        }
      }
      
      if (product.reviewCount !== undefined && product.reviewCount !== null) {
        const count = parseInt(product.reviewCount, 10);
        if (!isNaN(count) && count >= 0) {
          result.reviewCount = count;
        }
      }
      
      result.url = productUrl;
      
      return result;
    }
  }
  
  // Target URL not found in products
  return defaultResult;
}

/**
 * Extract all product entries with their data.
 * @param {Object} serp - SERP result object
 * @returns {Array} Array of product entries
 */
export function getProductEntries(serp) {
  if (!isValidSerpObject(serp)) {
    return [];
  }
  
  const products = serp.products;
  
  if (!products || !Array.isArray(products)) {
    return [];
  }
  
  return products
    .filter(product => product && typeof product === 'object')
    .map((product, index) => ({
      position: index + 1,
      name: product.name || null,
      url: product.url || null,
      price: product.price || null,
      salePrice: product.salePrice || null,
      availability: product.availability || null,
      image: product.image || null,
      rating: product.rating !== undefined ? parseFloat(product.rating) : null,
      reviewCount: product.reviewCount !== undefined ? parseInt(product.reviewCount, 10) : null
    }));
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
