/**
 * Public Audit Utilities
 * 
 * Task: TASK-33-23 - Implement Lead Capture Flow
 * 
 * Provides utilities for:
 * - Cookie management (unlock state)
 * - Email deduplication
 * - Redirect URL generation
 */

// ============================================================
// Constants
// ============================================================

export const UNLOCK_COOKIE_PREFIX = 'seoindicator_unlock_';
export const UNLOCK_EXPIRY_HOURS = 24;
const EMAIL_STORAGE_PREFIX = 'seoindicator_email_';

// ============================================================
// Cookie Management
// ============================================================

interface UnlockData {
  hash: string;
  email: string;
  timestamp: number;
}

/**
 * Generate a cookie key for an audit hash
 */
export function generateUnlockKey(hash: string): string {
  // Sanitize hash to ensure valid cookie characters
  const sanitized = hash.replace(/[^a-zA-Z0-9_-]/g, '_');
  return `${UNLOCK_COOKIE_PREFIX}${sanitized}`;
}

/**
 * Set unlock cookie for an audit
 * Returns true if successful
 */
export function setUnlockedAudit(hash: string, email: string): boolean {
  try {
    const key = generateUnlockKey(hash);
    const data: UnlockData = {
      hash,
      email,
      timestamp: Date.now(),
    };
    
    // Encode data as base64
    const value = btoa(JSON.stringify(data));
    
    // Set cookie with 24-hour expiry
    const expiryDate = new Date();
    expiryDate.setTime(expiryDate.getTime() + UNLOCK_EXPIRY_HOURS * 60 * 60 * 1000);
    
    document.cookie = `${key}=${value}; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax`;
    
    return true;
  } catch (error) {
    console.error('Failed to set unlock cookie:', error);
    return false;
  }
}

/**
 * Check if an audit is unlocked (cookie exists)
 */
export function isAuditUnlocked(hash: string): boolean {
  const data = getUnlockData(hash);
  if (!data) return false;
  
  // Check if expired
  if (isUnlockExpired(data.timestamp)) {
    clearUnlockCookie(hash);
    return false;
  }
  
  return true;
}

/**
 * Get unlock data from cookie
 * Returns null if no cookie or invalid data
 */
export function getUnlockData(hash: string): UnlockData | null {
  try {
    const key = generateUnlockKey(hash);
    const cookies = document.cookie.split(';');
    
    for (const cookie of cookies) {
      const [name, value] = cookie.trim().split('=');
      if (name === key && value) {
        try {
          const decoded = atob(value);
          const data = JSON.parse(decoded) as UnlockData;
          return data;
        } catch {
          // Invalid or corrupted cookie
          clearUnlockCookie(hash);
          return null;
        }
      }
    }
    
    return null;
  } catch (error) {
    console.error('Failed to get unlock data:', error);
    return null;
  }
}

/**
 * Check if unlock timestamp is expired (24+ hours old)
 */
export function isUnlockExpired(timestamp: number): boolean {
  const hoursSinceUnlock = (Date.now() - timestamp) / (1000 * 60 * 60);
  return hoursSinceUnlock >= UNLOCK_EXPIRY_HOURS;
}

/**
 * Clear unlock cookie for an audit
 */
export function clearUnlockCookie(hash: string): void {
  try {
    const key = generateUnlockKey(hash);
    document.cookie = `${key}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
  } catch (error) {
    console.error('Failed to clear unlock cookie:', error);
  }
}

// ============================================================
// Email Deduplication (Session Storage)
// ============================================================

/**
 * Check if email has already been captured in this session
 */
export function checkEmailCaptured(email: string): boolean {
  try {
    const normalizedEmail = email.toLowerCase().trim();
    const key = `${EMAIL_STORAGE_PREFIX}${normalizedEmail}`;
    return sessionStorage.getItem(key) !== null;
  } catch (error) {
    console.error('Failed to check email captured:', error);
    return false;
  }
}

/**
 * Store email as captured in session storage
 */
export function storeEmailCaptured(email: string): void {
  try {
    const normalizedEmail = email.toLowerCase().trim();
    const key = `${EMAIL_STORAGE_PREFIX}${normalizedEmail}`;
    sessionStorage.setItem(key, Date.now().toString());
  } catch (error) {
    console.error('Failed to store email captured:', error);
  }
}

/**
 * Clear stored email from session storage
 */
export function clearEmailCaptured(email: string): void {
  try {
    const normalizedEmail = email.toLowerCase().trim();
    const key = `${EMAIL_STORAGE_PREFIX}${normalizedEmail}`;
    sessionStorage.removeItem(key);
  } catch (error) {
    console.error('Failed to clear email captured:', error);
  }
}

// ============================================================
// Redirect Logic
// ============================================================

/**
 * Generate signup redirect URL with optional email and audit hash
 */
export function generateSignupRedirectUrl(email?: string, hash?: string): string {
  const params = new URLSearchParams();
  
  if (email) {
    params.set('email', email.trim());
  }
  
  if (hash) {
    params.set('audit', hash);
  }
  
  const queryString = params.toString();
  return queryString ? `/signup?${queryString}` : '/signup';
}

/**
 * Get redirect target path
 * Can be customized for different flows
 */
export function getRedirectTarget(customPath?: string): string {
  return customPath || '/signup';
}

// ============================================================
// Utility Functions
// ============================================================

/**
 * Hash email for privacy-safe storage
 * Uses SHA-256 if available, otherwise simple hash
 */
export async function hashEmail(email: string): Promise<string> {
  const normalized = email.toLowerCase().trim();
  
  // Try native crypto API
  if (typeof crypto !== 'undefined' && crypto.subtle) {
    try {
      const encoder = new TextEncoder();
      const data = encoder.encode(normalized);
      const hashBuffer = await crypto.subtle.digest('SHA-256', data);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    } catch {
      // Fall through to simple hash
    }
  }
  
  // Simple hash fallback
  let hash = 0;
  for (let i = 0; i < normalized.length; i++) {
    const char = normalized.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash;
  }
  return Math.abs(hash).toString(16).padStart(8, '0');
}
