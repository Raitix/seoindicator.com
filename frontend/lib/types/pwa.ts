/**
 * PWA Analysis Types - TASK-24-10
 * 
 * Types for PWA (Progressive Web App) analysis data.
 * 
 * @see TASK-24-8 (PWA Analysis API Endpoints)
 * @see TASK-24-10 (PWA Dashboard Components)
 */

/**
 * Individual manifest field status
 */
export interface ManifestField {
  present: boolean;
  value?: string;
}

/**
 * Required manifest fields
 */
export interface ManifestFields {
  name: ManifestField;
  short_name: ManifestField;
  start_url: ManifestField;
  display: ManifestField;
  background_color?: ManifestField;
  theme_color?: ManifestField;
  description?: ManifestField;
}

/**
 * Required icon sizes
 */
export interface RequiredIcons {
  '192x192': { present: boolean };
  '512x512': { present: boolean };
}

/**
 * Icon error details
 */
export interface IconError {
  size: string;
  error: string;
  severity: 'error' | 'warning';
}

/**
 * Icon analysis results
 */
export interface IconAnalysis {
  total: number;
  valid: number;
  required: RequiredIcons;
  errors: IconError[];
}

/**
 * PWA recommendation
 */
export interface PWARecommendation {
  type: string;
  field?: string;
  message: string;
  priority: 'high' | 'medium' | 'low';
  guide?: string; // Optional anchor link to guide section
}

/**
 * PWA readiness level
 */
export type ReadinessLevel = 'none' | 'basic' | 'enhanced' | 'full';

/**
 * PWA analysis data
 */
export interface PWAAnalysisData {
  hasManifest: boolean;
  manifestUrl: string | null;
  manifestValid: boolean;
  pwaScore: number;
  readinessLevel: ReadinessLevel;
  fields: ManifestFields;
  icons: IconAnalysis;
  recommendations: PWARecommendation[];
  analyzedAt: string | null;
}

/**
 * PWA analysis API response
 */
export interface PWAAnalysisResponse {
  success: boolean;
  data: PWAAnalysisData;
}

/**
 * PWA analysis error response
 */
export interface PWAAnalysisErrorResponse {
  success: false;
  error: {
    code: string;
    message: string;
    suggestion?: string;
    retryAfter?: number;
  };
}

/**
 * PWA analyze trigger response
 */
export interface PWAAnalyzeResponse {
  success: boolean;
  data: {
    jobId: string;
    status: 'queued';
    message: string;
  };
}

/**
 * React Query keys for PWA data
 */
export const pwaKeys = {
  all: ['pwa'] as const,
  analysis: (siteId: number) => [...pwaKeys.all, 'analysis', siteId] as const,
};
