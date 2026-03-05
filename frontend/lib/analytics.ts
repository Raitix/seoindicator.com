/**
 * Analytics tracking module
 * Provides event tracking functionality for user interactions
 */

// Track an analytics event
export function trackEvent(eventName: string, properties?: Record<string, unknown>): void {
  // In production, this would send to an analytics service
  // For now, just log in development
  if (process.env.NODE_ENV === 'development') {
    console.log('[Analytics]', eventName, properties);
  }
}

// Track page view
export function trackPageView(path: string): void {
  trackEvent('page_view', { path });
}

// Track CTA click
export function trackCTAClick(ctaName: string, properties?: Record<string, unknown>): void {
  trackEvent('cta_click', { ctaName, ...properties });
}

// Track comparison view
export function trackComparisonView(comparison: string): void {
  trackEvent('comparison_view', { comparison });
}
