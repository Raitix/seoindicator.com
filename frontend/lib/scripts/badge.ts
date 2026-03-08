/**
 * badge.ts - SEO Score Badge Embed Script
 * Task: TASK-33-16 - Implement badge.js Embed Script
 * 
 * Lightweight UMD embed script for displaying SEO score badges on third-party sites.
 * Size target: < 10KB gzip
 * No external dependencies
 */

// ============================================================================
// TYPE DEFINITIONS
// ============================================================================

export interface BadgeConfig {
  auditId: string;
  size?: 'small' | 'medium' | 'large';
  theme?: 'light' | 'dark';
  container?: string | HTMLElement;
  refreshInterval?: number;
  apiBaseUrl?: string;
  noTracking?: boolean;
  onClickTarget?: 'audit' | 'home';
}

export interface BadgeAPIResponse {
  success: boolean;
  data?: BadgeData;
  error?: string;
}

export interface BadgeData {
  hash: string;
  site_url: string;
  score: number;
  size?: string;
  theme?: string;
  audit_url: string;
  impressions_endpoint?: string;
  clicks_endpoint?: string;
}

export interface BadgeInstance {
  config: Required<BadgeConfig>;
  data: BadgeData | null;
  container: HTMLElement;
  refreshTimer: number | null;
  observer: IntersectionObserver | null;
  impressionTracked: boolean;
  destroyed: boolean;
}

export interface BadgeAPI {
  init(config: BadgeConfig): void;
  refresh(auditId?: string): void;
  setTheme(theme: 'light' | 'dark', auditId?: string): void;
  destroy(): void;
  noConflict(): BadgeAPI;
  version: string;
}

// ============================================================================
// CONSTANTS
// ============================================================================

const VERSION = '1.0.0';
const DEFAULT_BASE_URL = 'https://seoindicator.com';
const DEFAULT_REFRESH_INTERVAL = 86400000; // 24 hours
const MAX_RETRY_ATTEMPTS = 3;
const RETRY_DELAYS = [1000, 2000, 4000]; // Exponential backoff

const SIZES: Record<string, { width: number; height: number }> = {
  small: { width: 120, height: 40 },
  medium: { width: 150, height: 100 },
  large: { width: 300, height: 150 }
};

const THEMES = ['light', 'dark'] as const;
type Theme = typeof THEMES[number];

// Score color thresholds
const SCORE_COLORS: Record<string, { light: string; dark: string }> = {
  excellent: { light: '#22c55e', dark: '#16a34a' },   // 90-100 green
  good: { light: '#3b82f6', dark: '#2563eb' },        // 70-89 blue
  average: { light: '#eab308', dark: '#ca8a04' },     // 50-69 yellow
  poor: { light: '#f97316', dark: '#ea580c' },        // 30-49 orange
  bad: { light: '#ef4444', dark: '#dc2626' }          // 0-29 red
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

function getScoreCategory(score: number): string {
  if (score >= 90) return 'excellent';
  if (score >= 70) return 'good';
  if (score >= 50) return 'average';
  if (score >= 30) return 'poor';
  return 'bad';
}

function getScoreColor(score: number, theme: Theme): string {
  const category = getScoreCategory(score);
  return SCORE_COLORS[category]?.[theme] || SCORE_COLORS.average[theme];
}

function getReferrerDomain(): string | null {
  try {
    if (typeof document !== 'undefined' && document.referrer) {
      const url = new URL(document.referrer);
      return url.hostname;
    }
  } catch {
    // Invalid URL
  }
  return null;
}

function sanitizeForDisplay(str: string | null | undefined): string {
  if (!str || typeof str !== 'string') return '';
  // Basic XSS prevention - escape HTML entities
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#x27;');
}

function validateAuditId(id: string): boolean {
  if (!id || typeof id !== 'string') return false;
  const trimmed = id.trim();
  return trimmed.length > 0 && trimmed.length <= 256;
}

function normalizeSize(size: string | undefined): 'small' | 'medium' | 'large' {
  if (!size || typeof size !== 'string') return 'medium';
  const trimmed = size.trim().toLowerCase();
  if (trimmed in SIZES) return trimmed as 'small' | 'medium' | 'large';
  return 'medium';
}

function normalizeTheme(theme: string | undefined): Theme {
  if (!theme || typeof theme !== 'string') return 'light';
  const trimmed = theme.trim().toLowerCase();
  if (THEMES.includes(trimmed as Theme)) return trimmed as Theme;
  return 'light';
}

function truncateUrl(url: string | null | undefined, maxLength: number = 40): string {
  if (!url || typeof url !== 'string') return '';
  if (url.length <= maxLength) return url;
  return url.substring(0, maxLength - 3) + '...';
}

// ============================================================================
// API FUNCTIONS
// ============================================================================

async function fetchBadgeConfig(
  auditId: string,
  baseUrl: string,
  retryCount: number = 0
): Promise<BadgeAPIResponse> {
  const url = `${baseUrl}/api/v1/public-audit/${encodeURIComponent(auditId)}/badge`;
  
  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      },
      signal: controller.signal,
      credentials: 'omit' // No cookies
    });
    
    clearTimeout(timeoutId);
    
    if (!response.ok) {
      if (response.status === 404) {
        return { success: false, error: 'Audit not found' };
      }
      throw new Error(`HTTP ${response.status}`);
    }
    
    // API returns { success: true, data: { score, site_url, ... } }
    // Return the response as-is since it already has the correct structure
    return await response.json();
  } catch (error) {
    // Retry with exponential backoff
    if (retryCount < MAX_RETRY_ATTEMPTS) {
      await new Promise(resolve => setTimeout(resolve, RETRY_DELAYS[retryCount]));
      return fetchBadgeConfig(auditId, baseUrl, retryCount + 1);
    }
    
    return { 
      success: false, 
      error: error instanceof Error ? error.message : 'Network error' 
    };
  }
}

// ============================================================================
// TRACKING FUNCTIONS
// ============================================================================

function trackImpression(instance: BadgeInstance): void {
  if (instance.config.noTracking || instance.impressionTracked || instance.destroyed) {
    return;
  }
  
  instance.impressionTracked = true;
  
  const endpoint = instance.data?.impressions_endpoint || 
    `${instance.config.apiBaseUrl}/api/v1/public-audit/${instance.config.auditId}/impression`;
  
  const payload = {
    public_hash: instance.config.auditId,
    referrer_domain: getReferrerDomain(),
    timestamp: new Date().toISOString()
  };
  
  // Use sendBeacon for non-blocking tracking
  if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
    navigator.sendBeacon(endpoint, JSON.stringify(payload));
  }
}

function trackClick(instance: BadgeInstance, target: string): void {
  if (instance.config.noTracking || instance.destroyed) {
    return;
  }
  
  const endpoint = instance.data?.clicks_endpoint || 
    `${instance.config.apiBaseUrl}/api/v1/public-audit/${instance.config.auditId}/click`;
  
  const payload = {
    public_hash: instance.config.auditId,
    click_target: target,
    timestamp: new Date().toISOString()
  };
  
  if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
    navigator.sendBeacon(endpoint, JSON.stringify(payload));
  }
}

// ============================================================================
// RENDERING FUNCTIONS
// ============================================================================

function renderLoadingState(container: HTMLElement, size: 'small' | 'medium' | 'large'): void {
  const dims = SIZES[size];
  container.innerHTML = `
    <div style="
      width: ${dims.width}px;
      height: ${dims.height}px;
      background: #f3f4f6;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    ">
      <div style="
        width: 24px;
        height: 24px;
        border: 2px solid #e5e7eb;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: seoindicator-spin 1s linear infinite;
      "></div>
    </div>
    <style>
      @keyframes seoindicator-spin {
        to { transform: rotate(360deg); }
      }
    </style>
  `;
}

function renderErrorState(container: HTMLElement, size: 'small' | 'medium' | 'large', theme: Theme): void {
  const dims = SIZES[size];
  const bgColor = theme === 'dark' ? '#1f2937' : '#fef2f2';
  const textColor = theme === 'dark' ? '#9ca3af' : '#6b7280';
  
  container.innerHTML = `
    <div style="
      width: ${dims.width}px;
      height: ${dims.height}px;
      background: ${bgColor};
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: ${textColor};
      font-family: system-ui, -apple-system, sans-serif;
      font-size: 12px;
    ">
      Badge unavailable
    </div>
  `;
}

function renderBadge(instance: BadgeInstance): void {
  const { container, data, config } = instance;
  if (!data) return;
  
  const size = config.size;
  const theme = config.theme;
  const dims = SIZES[size];
  const scoreColor = getScoreColor(data.score, theme);
  
  const bgColor = theme === 'dark' ? '#1a1a1a' : '#ffffff';
  const textColor = theme === 'dark' ? '#ffffff' : '#1f2937';
  const subtextColor = theme === 'dark' ? '#9ca3af' : '#6b7280';
  const borderColor = theme === 'dark' ? '#374151' : '#e5e7eb';
  const displayUrl = truncateUrl(sanitizeForDisplay(data.site_url));
  
  // Create badge HTML
  const badgeHtml = `
    <a href="${sanitizeForDisplay(data.audit_url)}"
       target="_blank"
       rel="noopener noreferrer"
       style="
         display: inline-block;
         text-decoration: none;
         font-family: system-ui, -apple-system, sans-serif;
       "
       data-seoindicator-link="true"
    >
      <div style="
        width: ${dims.width}px;
        height: ${dims.height}px;
        background: ${bgColor};
        border: 1px solid ${borderColor};
        border-radius: 8px;
        padding: 8px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        transition: box-shadow 0.2s;
      " onmouseover="this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
        <div style="
          font-size: ${size === 'large' ? '32px' : size === 'medium' ? '24px' : '18px'};
          font-weight: 700;
          color: ${scoreColor};
        ">
          ${data.score}
        </div>
        ${size !== 'small' ? `
          <div style="
            font-size: 11px;
            color: ${subtextColor};
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          ">
            ${displayUrl}
          </div>
        ` : ''}
        ${size === 'large' ? `
          <div style="
            font-size: 10px;
            color: ${subtextColor};
          ">
            SEO Score
          </div>
        ` : ''}
      </div>
    </a>
    <div style="
      font-size: 10px;
      color: ${subtextColor};
      text-align: center;
      margin-top: 4px;
    ">
      Powered by <a href="${config.apiBaseUrl}" target="_blank" rel="noopener" style="color: ${scoreColor}">SEOIndicator</a>
    </div>
  `;
  
  container.innerHTML = badgeHtml;
  
  // Add click tracking
  const link = container.querySelector('[data-seoindicator-link]');
  if (link) {
    link.addEventListener('click', () => {
      trackClick(instance, config.onClickTarget || 'audit');
    });
  }
}

// ============================================================================
// BADGE INSTANCE MANAGEMENT
// ============================================================================

const instances: Map<string, BadgeInstance> = new Map();
let autoInitialized = false;

function createInstance(config: BadgeConfig): BadgeInstance | null {
  // Validate audit ID
  if (!validateAuditId(config.auditId)) {
    console.error('[SEOIndicatorBadge] Invalid audit ID');
    return null;
  }
  
  // Determine container
  let container: HTMLElement | null = null;
  if (config.container) {
    if (typeof config.container === 'string') {
      container = document.querySelector(config.container);
    } else {
      container = config.container;
    }
  } else {
    // Find container by data attribute
    container = document.querySelector(`[data-seoindicator-badge="${config.auditId}"]`);
  }
  
  if (!container) {
    console.error('[SEOIndicatorBadge] Container not found');
    return null;
  }
  
  // Check for existing instance
  const existing = instances.get(config.auditId);
  if (existing && !existing.destroyed) {
    return existing;
  }
  
  // Create normalized config
  const normalizedConfig: Required<BadgeConfig> = {
    auditId: config.auditId.trim(),
    size: normalizeSize(config.size),
    theme: normalizeTheme(config.theme),
    container: config.container || '',
    refreshInterval: config.refreshInterval ?? DEFAULT_REFRESH_INTERVAL,
    apiBaseUrl: config.apiBaseUrl || DEFAULT_BASE_URL,
    noTracking: config.noTracking ?? false,
    onClickTarget: config.onClickTarget ?? 'audit'
  };
  
  // Create instance
  const instance: BadgeInstance = {
    config: normalizedConfig,
    data: null,
    container,
    refreshTimer: null,
    observer: null,
    impressionTracked: false,
    destroyed: false
  };
  
  instances.set(instance.config.auditId, instance);
  
  return instance;
}

function setupIntersectionObserver(instance: BadgeInstance): void {
  if (typeof IntersectionObserver === 'undefined') {
    // Fallback: track impression immediately
    trackImpression(instance);
    return;
  }
  
  instance.observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting && !instance.impressionTracked) {
          trackImpression(instance);
          // Disconnect after first impression
          instance.observer?.disconnect();
        }
      }
    },
    { threshold: 0.5 }
  );
  
  instance.observer.observe(instance.container);
}

function setupAutoRefresh(instance: BadgeInstance): void {
  if (instance.config.refreshInterval <= 0 || instance.destroyed) {
    return;
  }
  
  // Clear existing timer
  if (instance.refreshTimer) {
    clearTimeout(instance.refreshTimer);
  }
  
  instance.refreshTimer = window.setTimeout(() => {
    if (!instance.destroyed) {
      loadBadge(instance.config.auditId);
    }
  }, instance.config.refreshInterval);
}

async function loadBadge(auditId: string): Promise<void> {
  const instance = instances.get(auditId);
  if (!instance || instance.destroyed) return;
  
  const { config, container } = instance;
  
  // Show loading state
  renderLoadingState(container, config.size);
  
  // Fetch badge config
  const response = await fetchBadgeConfig(auditId, config.apiBaseUrl);
  
  if (instance.destroyed) return; // Check again after async
  
  if (!response.success || !response.data) {
    renderErrorState(container, config.size, config.theme);
    return;
  }
  
  instance.data = response.data;
  
  // Render badge
  renderBadge(instance);
  
  // Setup impression tracking
  setupIntersectionObserver(instance);
  
  // Setup auto-refresh
  setupAutoRefresh(instance);
}

function destroyInstance(auditId: string): void {
  const instance = instances.get(auditId);
  if (!instance) return;
  
  instance.destroyed = true;
  
  if (instance.refreshTimer) {
    clearTimeout(instance.refreshTimer);
    instance.refreshTimer = null;
  }
  
  if (instance.observer) {
    instance.observer.disconnect();
    instance.observer = null;
  }
  
  instance.container.innerHTML = '';
  instances.delete(auditId);
}

// ============================================================================
// PUBLIC API
// ============================================================================

function init(config: BadgeConfig): void {
  const instance = createInstance(config);
  if (instance) {
    loadBadge(instance.config.auditId);
  }
}

function refresh(auditId?: string): void {
  if (auditId) {
    loadBadge(auditId);
  } else {
    // Refresh all badges
    for (const id of instances.keys()) {
      loadBadge(id);
    }
  }
}

function setTheme(theme: 'light' | 'dark', auditId?: string): void {
  const normalizedTheme = normalizeTheme(theme);
  
  if (auditId) {
    const instance = instances.get(auditId);
    if (instance && instance.data) {
      instance.config.theme = normalizedTheme;
      renderBadge(instance);
    }
  } else {
    // Update all instances
    for (const instance of instances.values()) {
      if (instance.data) {
        instance.config.theme = normalizedTheme;
        renderBadge(instance);
      }
    }
  }
}

function destroy(): void {
  // Destroy all instances
  for (const auditId of instances.keys()) {
    destroyInstance(auditId);
  }
  autoInitialized = false;
}

// Save previous value for noConflict (set during initialization)
let _previousSEOIndicatorBadge: unknown = null;

function noConflict(): BadgeAPI {
  // Restore previous value
  if (typeof window !== 'undefined') {
    (window as unknown as Record<string, unknown>).SEOIndicatorBadge = _previousSEOIndicatorBadge;
  }
  return api;
}

function autoInit(): void {
  // Check for no-auto-init flag
  if (typeof window !== 'undefined' && 
      (window as unknown as Record<string, unknown>).SEOINDICATOR_NO_AUTO_INIT) {
    return;
  }
  
  // Prevent double auto-init
  if (autoInitialized) return;
  autoInitialized = true;
  
  // Find all badge containers
  const containers = document.querySelectorAll('[data-seoindicator-badge]');
  
  for (let i = 0; i < containers.length; i++) {
    const container = containers[i] as HTMLElement;
    const auditId = container.getAttribute('data-seoindicator-badge');
    
    if (!auditId) continue;
    
    const size = container.getAttribute('data-size') || undefined;
    const theme = container.getAttribute('data-theme') || undefined;
    const refreshAttr = container.getAttribute('data-refresh');
    const refreshInterval = refreshAttr ? parseInt(refreshAttr, 10) * 1000 : undefined;
    
    init({
      auditId,
      size: size as 'small' | 'medium' | 'large' | undefined,
      theme: theme as 'light' | 'dark' | undefined,
      refreshInterval,
      container
    });
  }
}

// ============================================================================
// PUBLIC API
// ============================================================================

const api: BadgeAPI = {
  init,
  refresh,
  setTheme,
  destroy,
  noConflict,
  version: VERSION
};

// Initialize on load
if (typeof window !== 'undefined') {
  // Save previous value for noConflict
  _previousSEOIndicatorBadge = (window as unknown as Record<string, unknown>).SEOIndicatorBadge;
  
  // Export to global
  (window as unknown as Record<string, BadgeAPI>).SEOIndicatorBadge = api;
  
  // Auto-init on DOM ready
  if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', autoInit);
    } else {
      // DOM already loaded
      autoInit();
    }
  }
}

// Export version as a constant for ESM consumers
export const version = VERSION;

export default api;
export { init, refresh, setTheme, destroy, noConflict, VERSION };
