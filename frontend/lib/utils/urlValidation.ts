/**
 * URL Validation Utility
 * TASK-16-4: Tool Embedding System
 */

export interface URLValidationResult {
  valid: boolean;
  error?: string;
  normalized?: string;
}

const ALLOWED_PROTOCOLS = ['http:', 'https:'];
const BLOCKED_PROTOCOLS = [
  'javascript:',
  'data:',
  'vbscript:',
  'file:',
  'ftp:',
  'sftp:',
  'ssh:',
  'telnet:',
  'gopher:',
  'dict:',
];

const ERROR_MESSAGES = {
  EMPTY: 'Please enter a URL',
  INVALID_FORMAT: 'Please enter a valid URL (e.g., https://example.com)',
  BLOCKED_PROTOCOL: 'Please enter a valid URL (e.g., https://example.com)',
  INVALID_PROTOCOL: 'Please enter a valid URL (e.g., https://example.com)',
};

/**
 * Validate and normalize a URL string.
 * Returns validation result with normalized URL or error message.
 */
export function validateUrl(input: string): URLValidationResult {
  // Check for empty input
  const trimmed = input.trim();
  if (!trimmed) {
    return { valid: false, error: ERROR_MESSAGES.EMPTY };
  }

  // Check for blocked protocols (XSS prevention)
  const lowerInput = trimmed.toLowerCase();
  for (const protocol of BLOCKED_PROTOCOLS) {
    if (lowerInput.startsWith(protocol)) {
      return { valid: false, error: ERROR_MESSAGES.BLOCKED_PROTOCOL };
    }
  }

  // Add https:// if no protocol specified
  let urlToTest = trimmed;
  if (!trimmed.match(/^[a-z]+:\/\//i)) {
    urlToTest = `https://${trimmed}`;
  }

  try {
    const url = new URL(urlToTest);

    // Check for allowed protocols
    if (!ALLOWED_PROTOCOLS.includes(url.protocol)) {
      return { valid: false, error: ERROR_MESSAGES.INVALID_PROTOCOL };
    }

    // Ensure host exists
    if (!url.host) {
      return { valid: false, error: ERROR_MESSAGES.INVALID_FORMAT };
    }

    return {
      valid: true,
      normalized: url.toString(),
    };
  } catch {
    return { valid: false, error: ERROR_MESSAGES.INVALID_FORMAT };
  }
}

/**
 * Check if a URL looks like a valid URL (without full validation).
 * Useful for real-time input feedback.
 */
export function isValidUrlFormat(input: string): boolean {
  const trimmed = input.trim();
  if (!trimmed) return false;

  // Check for blocked protocols
  const lowerInput = trimmed.toLowerCase();
  for (const protocol of BLOCKED_PROTOCOLS) {
    if (lowerInput.startsWith(protocol)) {
      return false;
    }
  }

  // Add https:// if no protocol specified
  let urlToTest = trimmed;
  if (!trimmed.match(/^[a-z]+:\/\//i)) {
    urlToTest = `https://${trimmed}`;
  }

  try {
    const url = new URL(urlToTest);
    return ALLOWED_PROTOCOLS.includes(url.protocol) && !!url.host;
  } catch {
    return false;
  }
}

/**
 * Normalize a URL for display purposes.
 * Removes trailing slash and common tracking parameters.
 */
export function normalizeUrlForDisplay(url: string): string {
  try {
    const parsed = new URL(url);
    // Remove common tracking parameters
    const trackingParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'];
    trackingParams.forEach(param => parsed.searchParams.delete(param));
    
    let result = parsed.toString();
    // Remove trailing slash from path
    if (parsed.pathname === '/' && !parsed.search && !parsed.hash) {
      result = result.replace(/\/$/, '');
    }
    return result;
  } catch {
    return url;
  }
}

/**
 * Extract domain from a URL.
 */
export function extractDomain(url: string): string | null {
  try {
    const parsed = new URL(url);
    return parsed.hostname;
  } catch {
    return null;
  }
}

/**
 * Check if URL is from localhost or private IP.
 */
export function isLocalUrl(url: string): boolean {
  try {
    const parsed = new URL(url);
    const host = parsed.hostname.toLowerCase();
    
    // Check for localhost variations
    if (host === 'localhost' || host === '127.0.0.1' || host === '::1') {
      return true;
    }
    
    // Check for private IP ranges
    const privateIpPatterns = [
      /^10\./,
      /^172\.(1[6-9]|2[0-9]|3[0-1])\./,
      /^192\.168\./,
      /^169\.254\./,
    ];
    
    return privateIpPatterns.some(pattern => pattern.test(host));
  } catch {
    return false;
  }
}
