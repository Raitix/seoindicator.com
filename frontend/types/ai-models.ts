/**
 * AI Models Types - TASK-31-15
 *
 * TypeScript type definitions for AI model selection.
 *
 * @see TASK-31-15
 * @see TEST-31-15
 */

// ==================== AI Model ====================

export interface AIModel {
  /** Unique model identifier */
  id: string;

  /** Display name */
  name: string;

  /** Provider name (e.g., "Google", "Alibaba", "Mistral AI") */
  provider: string;

  /** Context window size in tokens */
  context_window: number;

  /** Whether this model is free to use */
  is_free: boolean;

  /** Whether this model is recommended */
  is_recommended: boolean;

  /** Model capabilities */
  capabilities: string[];

  /** Average latency in milliseconds */
  avg_latency_ms: number;

  /** Best use case description */
  best_for: string;
}

// ==================== API Response ====================

export interface AIModelsResponse {
  /** Success flag */
  success: boolean;

  /** Response data */
  data: {
    /** Available models */
    models: AIModel[];

    /** Default model ID */
    default_model: string;

    /** User's preferred model ID (if authenticated) */
    user_preference: string | null;
  };
}

// ==================== Hook Return Type ====================

export interface UseAIModelsReturn {
  /** Available models */
  models: AIModel[];

  /** Default model ID */
  defaultModel: string | null;

  /** User's preferred model ID */
  userPreference: string | null;

  /** Loading state */
  isLoading: boolean;

  /** Error state */
  error: Error | null;

  /** Refetch models */
  refetch: () => void;
}

// ==================== Component Props ====================

export interface ModelPickerProps {
  /** Currently selected model ID */
  selectedModel: string | null;

  /** Callback when model selection changes */
  onModelChange: (modelId: string) => void;

  /** Estimated tokens for the content being analyzed */
  estimatedTokens?: number;

  /** Whether the picker is disabled */
  disabled?: boolean;

  /** Optional CSS class name */
  className?: string;
}

// ==================== Helper Functions ====================

/**
 * Format context window size for display
 */
export function formatContextWindow(tokens: number): string {
  if (tokens >= 1000000) {
    return '1M tokens';
  }
  if (tokens >= 1000) {
    // Use floor to avoid rounding up (e.g., 32.7K should show as 32K, not 33K)
    return `${Math.floor(tokens / 1000)}K tokens`;
  }
  return `${tokens} tokens`;
}

/**
 * Format latency for display
 */
export function formatLatency(ms: number): string {
  return `~${(ms / 1000).toFixed(1)}s`;
}

/**
 * Check if content fits within model's context window
 */
export function contentFitsModel(
  estimatedTokens: number | undefined,
  contextWindow: number
): boolean {
  if (!estimatedTokens) return true;
  return estimatedTokens < contextWindow;
}
