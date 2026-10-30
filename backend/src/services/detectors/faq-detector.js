/**
 * FAQ Detector
 * TASK-7-7: Detects FAQ rich results in SERP
 * 
 * FAQ rich results appear as expandable question/answer sections.
 * This detector identifies if any FAQ items are from the target domain.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect FAQ rich results in SERP.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional fields
 */
export function detectFAQ(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'faq',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for faqs data
  const faqs = serp.faqs;
  
  // Validate faqs is an array with content
  if (!faqs || !Array.isArray(faqs) || faqs.length === 0) {
    return defaultResult;
  }
  
  // Extract target domain for matching
  const targetHostname = extractHostname(targetUrl);
  if (!targetHostname) {
    return defaultResult;
  }
  
  const normalizedTarget = normalizeHostname(targetHostname);
  
  // Track matched FAQs
  const matchedFaqs = [];
  
  // Search for target URL in FAQ entries
  for (let i = 0; i < faqs.length; i++) {
    const faq = faqs[i];
    
    // Skip entries without valid object
    if (!faq || typeof faq !== 'object') {
      continue;
    }
    
    const faqUrl = faq.url;
    if (!faqUrl || typeof faqUrl !== 'string' || faqUrl.trim() === '') {
      continue;
    }
    
    // Extract and compare domains
    const faqHostname = extractHostname(faqUrl);
    if (faqHostname && normalizeHostname(faqHostname) === normalizedTarget) {
      // Found match!
      matchedFaqs.push({
        question: faq.question || null,
        answer: faq.answer || null,
        url: faqUrl
      });
    }
  }
  
  // Build result
  const result = {
    type: 'faq',
    isPresent: matchedFaqs.length > 0,
    count: faqs.length
  };
  
  // Include matched FAQs if any
  if (matchedFaqs.length > 0) {
    result.matchedFaqs = matchedFaqs;
    result.matchedCount = matchedFaqs.length;
  }
  
  return result;
}

/**
 * Extract all FAQ entries with their data.
 * @param {Object} serp - SERP result object
 * @returns {Array} Array of FAQ entries
 */
export function getFAQEntries(serp) {
  if (!isValidSerpObject(serp)) {
    return [];
  }
  
  const faqs = serp.faqs;
  
  if (!faqs || !Array.isArray(faqs)) {
    return [];
  }
  
  return faqs
    .filter(faq => faq && typeof faq === 'object')
    .map((faq) => ({
      question: faq.question || null,
      answer: faq.answer || null,
      url: faq.url || null
    }));
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
