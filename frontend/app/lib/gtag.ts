// Google Analytics utilities
// Measurement ID from environment variable

export const GA_MEASUREMENT_ID = process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID || 'G-RWCSLW7EXF';

// Check if GA is enabled (only in production or when explicitly enabled)
export const isGAEnabled = () => {
  if (typeof window === 'undefined') return false;
  return process.env.NODE_ENV === 'production' || process.env.NEXT_PUBLIC_GA_DEBUG === 'true';
};

// Initialize Google Analytics
export const initGA = () => {
  if (typeof window === 'undefined' || !isGAEnabled()) return;

  // Load gtag.js
  const script = document.createElement('script');
  script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`;
  script.async = true;
  document.head.appendChild(script);

  // Initialize dataLayer
  window.dataLayer = window.dataLayer || [];
  function gtag(...args: unknown[]) {
    window.dataLayer.push(args);
  }
  window.gtag = gtag;

  gtag('js', new Date());
  gtag('config', GA_MEASUREMENT_ID, {
    page_path: window.location.pathname,
  });
};

// Track page views (for client-side navigation)
export const trackPageView = (url: string) => {
  if (typeof window === 'undefined' || !window.gtag || !isGAEnabled()) return;

  window.gtag('config', GA_MEASUREMENT_ID, {
    page_path: url,
  });
};

// Track custom events
export const trackEvent = (
  action: string,
  category: string,
  label?: string,
  value?: number
) => {
  if (typeof window === 'undefined' || !window.gtag || !isGAEnabled()) return;

  window.gtag('event', action, {
    event_category: category,
    event_label: label,
    value: value,
  });
};

// SEO-specific event tracking
export const trackSEOEvent = {
  analysisStarted: (domain: string) => {
    trackEvent('seo_analysis', 'engagement', domain);
  },
  analysisCompleted: (domain: string, score: number) => {
    trackEvent('seo_analysis_complete', 'engagement', domain, score);
  },
  guideViewed: (guideName: string) => {
    trackEvent('guide_view', 'content', guideName);
  },
  errorOccurred: (errorType: string) => {
    trackEvent('error', 'technical', errorType);
  },
};

// Type declarations for window
declare global {
  interface Window {
    dataLayer: unknown[];
    gtag: (...args: unknown[]) => void;
  }
}
