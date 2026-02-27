/**
 * Retry Utility
 * TASK-16-12: Error Handling & User Feedback
 *
 * Client-side retry logic with exponential backoff.
 */

// Error codes that are retryable
const RETRYABLE_CODES = new Set([
  'FETCH_FAILED',
  'TIMEOUT',
  'RATE_LIMITED',
  'TOOL_ERROR',
]);

// Configuration
interface RetryConfig {
  maxAttempts: number;
  baseDelay: number;
  maxDelay: number;
  jitter: boolean;
  retryableCodes?: Set<string>;
}

// Default configuration
const DEFAULT_CONFIG: RetryConfig = {
  maxAttempts: 2,
  baseDelay: 1000,
  maxDelay: 10000,
  jitter: true,
  retryableCodes: RETRYABLE_CODES,
};

// Retry state
interface RetryState {
  attempt: number;
  isRetrying: boolean;
  lastError: Error | null;
  isCancelled: boolean;
}

/**
 * Check if an error is retryable.
 */
export function isRetryable(error: { code?: string; status?: number }): boolean {
  if (error.code) {
    return RETRYABLE_CODES.has(error.code);
  }
  
  // HTTP status codes that are retryable
  if (error.status) {
    return error.status === 429 || error.status >= 500;
  }
  
  return false;
}

/**
 * Calculate retry delay with exponential backoff.
 */
export function getRetryDelay(attempt: number, config: Partial<RetryConfig> = {}): number {
  const { baseDelay = 1000, maxDelay = 10000, jitter = true } = config;
  
  // Exponential backoff: base * 2^attempt
  let delay = baseDelay * Math.pow(2, attempt);
  
  // Cap at max delay
  delay = Math.min(delay, maxDelay);
  
  // Add jitter to prevent thundering herd
  if (jitter) {
    delay += Math.random() * 200;
  }
  
  return Math.floor(delay);
}

/**
 * Execute an async operation with retry logic.
 */
export async function withRetry<T>(
  operation: () => Promise<T>,
  config: Partial<RetryConfig> = {},
  onRetry?: (error: Error, attempt: number, delay: number) => void
): Promise<T> {
  const finalConfig = { ...DEFAULT_CONFIG, ...config };
  const state: RetryState = {
    attempt: 0,
    isRetrying: false,
    lastError: null,
    isCancelled: false,
  };

  while (state.attempt <= finalConfig.maxAttempts) {
    try {
      const result = await operation();
      return result;
    } catch (error) {
      state.lastError = error instanceof Error ? error : new Error(String(error));
      
      // Check if error is retryable
      const errorCode = (error as { code?: string }).code;
      const errorStatus = (error as { status?: number }).status;
      
      const retryable = finalConfig.retryableCodes?.has(errorCode || '') ||
        (errorStatus !== undefined && (errorStatus === 429 || errorStatus >= 500));
      
      // Don't retry if not retryable or max attempts reached
      if (!retryable || state.attempt >= finalConfig.maxAttempts || state.isCancelled) {
        throw state.lastError;
      }
      
      state.attempt++;
      state.isRetrying = true;
      
      // Calculate delay
      const delay = getRetryDelay(state.attempt, finalConfig);
      
      // Call retry callback
      if (onRetry) {
        onRetry(state.lastError, state.attempt, delay);
      }
      
      // Wait before next attempt
      await sleep(delay);
    }
  }
  
  throw state.lastError;
}

/**
 * Create a retry controller for manual retry management.
 */
export function createRetryController(config: Partial<RetryConfig> = {}) {
  const finalConfig = { ...DEFAULT_CONFIG, ...config };
  let state: RetryState = {
    attempt: 0,
    isRetrying: false,
    lastError: null,
    isCancelled: false,
  };

  return {
    get attempt() {
      return state.attempt;
    },
    get isRetrying() {
      return state.isRetrying;
    },
    get lastError() {
      return state.lastError;
    },
    
    async execute<T>(
      operation: () => Promise<T>,
      onRetry?: (error: Error, attempt: number, delay: number) => void
    ): Promise<T> {
      state = {
        attempt: 0,
        isRetrying: false,
        lastError: null,
        isCancelled: false,
      };

      while (state.attempt <= finalConfig.maxAttempts) {
        try {
          const result = await operation();
          state.isRetrying = false;
          return result;
        } catch (error) {
          state.lastError = error instanceof Error ? error : new Error(String(error));
          
          const errorCode = (error as { code?: string }).code;
          const errorStatus = (error as { status?: number }).status;
          
          const retryable = finalConfig.retryableCodes?.has(errorCode || '') ||
            (errorStatus !== undefined && (errorStatus === 429 || errorStatus >= 500));
          
          if (!retryable || state.attempt >= finalConfig.maxAttempts || state.isCancelled) {
            throw state.lastError;
          }
          
          state.attempt++;
          state.isRetrying = true;
          
          const delay = getRetryDelay(state.attempt, finalConfig);
          
          if (onRetry) {
            onRetry(state.lastError, state.attempt, delay);
          }
          
          await sleep(delay);
        }
      }
      
      throw state.lastError;
    },
    
    cancel(): void {
      state.isCancelled = true;
    },
    
    reset(): void {
      state = {
        attempt: 0,
        isRetrying: false,
        lastError: null,
        isCancelled: false,
      };
    },
    
    getRetryDelay(): number {
      return getRetryDelay(state.attempt + 1, finalConfig);
    },
  };
}

/**
 * Sleep utility.
 */
function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Fetch with retry.
 */
export async function fetchWithRetry(
  url: string,
  options: RequestInit = {},
  retryConfig: Partial<RetryConfig> = {}
): Promise<Response> {
  return withRetry(
    async () => {
      const response = await fetch(url, options);
      
      if (!response.ok) {
        const error = new Error(`HTTP ${response.status}`) as Error & { status: number };
        error.status = response.status;
        
        // Try to get error code from response
        try {
          const body = await response.clone().json();
          error.code = body?.error?.code || body?.code;
        } catch {
          // Ignore JSON parse errors
        }
        
        throw error;
      }
      
      return response;
    },
    retryConfig
  );
}

export default {
  isRetryable,
  getRetryDelay,
  withRetry,
  createRetryController,
  fetchWithRetry,
};
