/**
 * Knowledge Panel Detector
 * TASK-7-7: Detects Knowledge Panel results in SERP
 * 
 * Knowledge panels appear as entity information sidebars on the right.
 * This detector identifies if the panel is about the target brand/entity.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect Knowledge Panel in SERP results.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @param {string} [targetName] - Optional target brand/entity name for matching
 * @returns {Object} SERPFeature object with type, isPresent, and optional fields
 */
export function detectKnowledgePanel(serp, targetUrl, targetName = null) {
  // Default return structure
  const defaultResult = {
    type: 'knowledge_panel',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for knowledgePanel data
  const panel = serp.knowledgePanel;
  
  // Validate panel is a non-empty object
  if (!panel || typeof panel !== 'object' || Array.isArray(panel)) {
    return defaultResult;
  }
  
  // Check if panel has meaningful content
  const hasContent = panel.title || panel.name || panel.url || panel.description;
  if (!hasContent) {
    return defaultResult;
  }
  
  // Extract target domain for matching
  const targetHostname = targetUrl ? extractHostname(targetUrl) : null;
  const normalizedTarget = targetHostname ? normalizeHostname(targetHostname) : null;
  
  // Determine if panel is about our entity
  let isOurs = false;
  
  // Method 1: URL match in panel
  if (panel.url && normalizedTarget) {
    const panelHostname = extractHostname(panel.url);
    if (panelHostname && normalizeHostname(panelHostname) === normalizedTarget) {
      isOurs = true;
    }
  }
  
  // Method 2: Name match (if targetName provided)
  if (!isOurs && targetName && (panel.title || panel.name)) {
    const panelName = (panel.title || panel.name || '').toLowerCase().trim();
    const normalizedTargetName = targetName.toLowerCase().trim();
    
    // Exact match or partial match (e.g., "Brand Inc." matches "Brand")
    if (panelName === normalizedTargetName || 
        panelName.includes(normalizedTargetName) ||
        normalizedTargetName.includes(panelName)) {
      isOurs = true;
    }
  }
  
  // Build result
  const result = {
    type: 'knowledge_panel',
    isPresent: isOurs
  };
  
  // Include optional fields if present
  if (panel.title || panel.name) {
    result.title = panel.title || panel.name;
  }
  
  if (panel.description && typeof panel.description === 'string') {
    result.description = panel.description;
  }
  
  if (panel.url && typeof panel.url === 'string') {
    result.url = panel.url;
  }
  
  if (panel.type && typeof panel.type === 'string') {
    result.entityType = panel.type;
  }
  
  return result;
}

/**
 * Extract knowledge panel data.
 * @param {Object} serp - SERP result object
 * @returns {Object|null} Panel data or null
 */
export function getKnowledgePanelData(serp) {
  if (!isValidSerpObject(serp)) {
    return null;
  }
  
  const panel = serp.knowledgePanel;
  
  if (!panel || typeof panel !== 'object' || Array.isArray(panel)) {
    return null;
  }
  
  return {
    type: panel.type || null,
    title: panel.title || panel.name || null,
    description: panel.description || null,
    url: panel.url || null,
    image: panel.image || null,
    attributes: panel.attributes || null
  };
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
