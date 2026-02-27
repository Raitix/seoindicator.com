/**
 * People Also Ask Detector
 * TASK-7-7: Detects People Also Ask results in SERP
 * 
 * People Also Ask is an expandable accordion of related questions.
 * This detector identifies if any answers are from the target domain.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect People Also Ask in SERP results.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional fields
 */
export function detectPeopleAlsoAsk(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'people_also_ask',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for peopleAlsoAsk data
  const paa = serp.peopleAlsoAsk;
  
  // Validate PAA is an array with content
  if (!paa || !Array.isArray(paa) || paa.length === 0) {
    return defaultResult;
  }
  
  // Extract target domain for matching
  const targetHostname = extractHostname(targetUrl);
  if (!targetHostname) {
    return defaultResult;
  }
  
  const normalizedTarget = normalizeHostname(targetHostname);
  
  // Track matched questions
  const matchedQuestions = [];
  
  // Search for target URL in PAA entries
  for (let i = 0; i < paa.length; i++) {
    const item = paa[i];
    
    // Skip entries without valid object
    if (!item || typeof item !== 'object') {
      continue;
    }
    
    // Check if answer exists and has a URL
    const answer = item.answer;
    if (!answer || typeof answer !== 'object') {
      continue;
    }
    
    const answerUrl = answer.url;
    if (!answerUrl || typeof answerUrl !== 'string' || answerUrl.trim() === '') {
      continue;
    }
    
    // Extract and compare domains
    const answerHostname = extractHostname(answerUrl);
    if (answerHostname && normalizeHostname(answerHostname) === normalizedTarget) {
      // Found match!
      if (item.question && typeof item.question === 'string') {
        matchedQuestions.push(item.question);
      }
    }
  }
  
  // Build result
  const result = {
    type: 'people_also_ask',
    isPresent: matchedQuestions.length > 0,
    questionCount: paa.length
  };
  
  // Include matched questions if any
  if (matchedQuestions.length > 0) {
    result.matchedQuestions = matchedQuestions;
    result.matchedCount = matchedQuestions.length;
  }
  
  return result;
}

/**
 * Extract all PAA entries with their data.
 * Useful for content ideas and competitor analysis.
 * @param {Object} serp - SERP result object
 * @returns {Array} Array of PAA entries
 */
export function getPeopleAlsoAskEntries(serp) {
  if (!isValidSerpObject(serp)) {
    return [];
  }
  
  const paa = serp.peopleAlsoAsk;
  
  if (!paa || !Array.isArray(paa)) {
    return [];
  }
  
  return paa
    .filter(item => item && typeof item === 'object')
    .map((item) => ({
      question: item.question || null,
      answer: item.answer && typeof item.answer === 'object' ? {
        text: item.answer.text || item.answer.snippet || null,
        url: item.answer.url || null,
        title: item.answer.title || null
      } : null
    }));
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
