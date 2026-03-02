// Types for AI Suggestions feature (SPEC-021)

/**
 * Supported SEO indicator types for AI suggestions
 */
export type AIIndicatorType =
  | 'title'
  | 'meta_description'
  | 'robots'
  | 'sitemap'
  | 'headings'
  | 'image_alt'
  | 'filenames'
  | 'url'
  | 'links'
  | '404';

/**
 * Type of suggestion returned by AI
 */
export type SuggestionType = 'improve' | 'rewrite';

/**
 * Individual suggestion item
 */
export interface Suggestion {
  /** Type of suggestion: improve existing content or rewrite completely */
  type: SuggestionType;
  /** The suggested text content */
  text: string;
  /** Explanation of why this suggestion is beneficial */
  reason: string;
}

/**
 * Debug information returned when debug mode is enabled
 */
export interface DebugInfo {
  /** The full prompt sent to the AI provider */
  prompt: string;
  /** Raw JSON response string from the provider */
  raw_response: string;
  /** Total tokens used (prompt + completion) */
  tokens_used: number;
  /** Request latency in milliseconds */
  latency_ms: number;
  /** AI provider name */
  provider: string;
  /** Model identifier used */
  model: string | null;
}

/**
 * Result from AI suggestion API
 */
export interface SuggestionResult {
  /** The SEO indicator that was analyzed */
  indicator: string;
  /** Score from 0-100 indicating SEO quality */
  score: number;
  /** List of issues detected */
  issues: string[];
  /** Actionable suggestions for improvement */
  suggestions: Suggestion[];
  /** Best practice recommendations */
  best_practices: string[];
  /** AI provider name (openrouter, vercel, static_fallback) */
  provider: string;
  /** Model identifier used (null for static fallback) */
  model: string | null;
  /** ISO 8601 timestamp when suggestion was generated */
  generated_at: string;
  /** Reason for using fallback (if applicable) */
  fallback_reason?: string;
}

/**
 * Extended result with debug information
 */
export interface DebugSuggestionResult extends SuggestionResult {
  /** Debug information (only present when debug=true) */
  debug: DebugInfo;
}

/**
 * Options for AI suggestion request
 */
export interface AISuggestionOptions {
  /** AI provider to use (openrouter, vercel, static) */
  provider?: 'openrouter' | 'vercel' | 'static';
  /** Enable debug mode (requires authentication) */
  debug?: boolean;
}

/**
 * Request payload for AI suggestion endpoint
 */
export interface AISuggestionRequest {
  /** SEO indicator type to analyze */
  indicator: AIIndicatorType;
  /** Context data for the indicator */
  context: Record<string, unknown>;
  /** AI provider override */
  provider?: string;
  /** Enable debug mode */
  debug?: boolean;
}

/**
 * Error response from API
 */
export interface AISuggestionError {
  /** Error code */
  code: string;
  /** Human-readable error message */
  message: string;
  /** Seconds until retry is allowed (for rate limiting) */
  retry_after?: number;
}

/**
 * API response wrapper for success
 */
export interface AISuccessResponse {
  success: true;
  data: SuggestionResult | DebugSuggestionResult;
}

/**
 * API response wrapper for error
 */
export interface AIErrorResponse {
  success: false;
  error: AISuggestionError;
}

/**
 * Union type for all API responses
 */
export type AISuggestionAPIResponse = AISuccessResponse | AIErrorResponse;

/**
 * Props for AISuggestionButton component
 */
export interface AISuggestionButtonProps {
  /** SEO indicator type to analyze */
  indicator: AIIndicatorType;
  /** Context data for analysis */
  context: Record<string, unknown> | null;
  /** Callback when suggestion is received */
  onSuggestion: (result: SuggestionResult) => void;
  /** Callback when error occurs */
  onError?: (error: Error) => void;
  /** Custom button text */
  buttonText?: string;
  /** Show provider badge */
  showProvider?: boolean;
  /** Additional CSS classes */
  className?: string;
  /** Disable the button */
  disabled?: boolean;
}

/**
 * Props for AISuggestionPanel component
 */
export interface AISuggestionPanelProps {
  /** Suggestion result to display */
  result: SuggestionResult | null;
  /** Loading state */
  isLoading?: boolean;
  /** Error message to display */
  error?: string | null;
  /** Show regenerate button */
  showRegenerate?: boolean;
  /** Callback for regenerate action */
  onRegenerate?: () => void;
  /** Start collapsed */
  defaultCollapsed?: boolean;
  /** Additional CSS classes */
  className?: string;
}
