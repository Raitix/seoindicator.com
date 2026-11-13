/**
 * Tool Analysis Hook
 * TASK-16-4: Tool Embedding System
 * 
 * Provides state management for tool analysis operations.
 */

import * as React from 'react';
import { validateUrl, type URLValidationResult } from '@/lib/utils/urlValidation';

// Tool types
export type ToolType = 'meta-tag-analyzer' | 'keyword-density' | 'mobile-friendly' | 'social-preview';

// Analysis states
export type AnalysisState = 'idle' | 'loading' | 'success' | 'error';

// Error codes
export type ErrorCode = 
  | 'INVALID_URL'
  | 'FETCH_FAILED'
  | 'TIMEOUT'
  | 'RATE_LIMITED'
  | 'NETWORK_ERROR'
  | 'UNKNOWN';

// Error messages (exact text for consistency)
export const ERROR_MESSAGES: Record<ErrorCode, string> = {
  INVALID_URL: 'Please enter a valid URL (e.g., https://example.com)',
  FETCH_FAILED: 'Could not access the page. Please check the URL and try again.',
  TIMEOUT: 'Analysis took too long. Please try again.',
  RATE_LIMITED: 'You\'ve made too many requests. Please wait a moment.',
  NETWORK_ERROR: 'Network error. Please check your connection.',
  UNKNOWN: 'An unexpected error occurred. Please try again.',
};

// Analysis result
export interface AnalysisResult {
  success: boolean;
  data?: unknown;
  error?: {
    code: ErrorCode;
    message: string;
    retryAfter?: number;
  };
  analyzedAt: string;
}

// Hook options
export interface UseToolAnalysisOptions {
  tool: ToolType;
  apiEndpoint?: string;
  timeout?: number;
  onSuccess?: (result: AnalysisResult) => void;
  onError?: (error: { code: ErrorCode; message: string }) => void;
}

// Hook return type
export interface UseToolAnalysisReturn {
  state: AnalysisState;
  result: AnalysisResult | null;
  error: { code: ErrorCode; message: string } | null;
  validationError: string | null;
  isLoading: boolean;
  analyze: (url: string, keyword?: string) => Promise<void>;
  reset: () => void;
  retry: () => void;
}

// Default timeout (30 seconds)
const DEFAULT_TIMEOUT = 30000;

// Default API endpoint.
//
// The Laravel route is registered inside a `Route::prefix('v1')` group
// (backend/routes/api.php), so the real path is /api/v1/tools/analyze.
// Posting to /api/tools/analyze returns 404, which this hook reported as
// "Could not access the page" - the user's URL was never the problem.
const DEFAULT_API_ENDPOINT = '/api/v1/tools/analyze';

// Tool id translation.
//
// The backend validates the tool against a fixed list
// (meta-tag-analyzer, keyword-density-checker, social-media-preview,
// mobile-friendly). The frontend's own slugs differ for two of them, so
// the slug is translated here rather than at every call site.
//
// This map is the single source of truth for the frontend slug -> API id
// translation. Anything else that needs to talk to the tools endpoint must
// use it rather than writing the id out again.
export const API_TOOL_IDS: Record<ToolType, string> = {
  'meta-tag-analyzer': 'meta-tag-analyzer',
  'keyword-density': 'keyword-density-checker',
  'social-preview': 'social-media-preview',
  'mobile-friendly': 'mobile-friendly',
};

/**
 * Shape the backend response the way the result renderers expect.
 *
 * The API returns the tool-specific payload under `data`, with `url` and
 * `analyzedAt` as siblings at the top level. The renderers read a flat
 * object, and the keyword-density payload names two fields differently
 * from the rest of the API (keywordCount / densityStatus rather than
 * occurrences / status).
 *
 * Normalising here keeps one contract between the hook and the renderers
 * instead of making every renderer know about the API's own naming.
 */
function normalizeToolResponse(tool: ToolType, response: Record<string, any>): Record<string, any> {
  const payload: Record<string, any> = { ...(response.data ?? response) };

  // url / analyzedAt live on the envelope, not inside `data`.
  if (response.url !== undefined && payload.url === undefined) {
    payload.url = response.url;
  }
  if (response.analyzedAt !== undefined && payload.analyzedAt === undefined) {
    payload.analyzedAt = response.analyzedAt;
  }

  if (tool === 'keyword-density') {
    if (payload.occurrences === undefined && payload.keywordCount !== undefined) {
      payload.occurrences = payload.keywordCount;
    }
    if (payload.status === undefined && payload.densityStatus !== undefined) {
      payload.status = payload.densityStatus;
    }
  }

  return payload;
}

export function useToolAnalysis(options: UseToolAnalysisOptions): UseToolAnalysisReturn {
  const {
    tool,
    apiEndpoint = DEFAULT_API_ENDPOINT,
    timeout = DEFAULT_TIMEOUT,
    onSuccess,
    onError,
  } = options;

  const [state, setState] = React.useState<AnalysisState>('idle');
  const [result, setResult] = React.useState<AnalysisResult | null>(null);
  const [error, setError] = React.useState<{ code: ErrorCode; message: string } | null>(null);
  const [validationError, setValidationError] = React.useState<string | null>(null);
  const [lastRequest, setLastRequest] = React.useState<{ url: string; keyword?: string } | null>(null);

  // Abort controller for canceling requests
  const abortControllerRef = React.useRef<AbortController | null>(null);

  // Cleanup on unmount
  React.useEffect(() => {
    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, []);

  // Analyze function
  const analyze = React.useCallback(async (url: string, keyword?: string) => {
    // Clear previous errors
    setValidationError(null);
    setError(null);

    // Validate URL
    const validation = validateUrl(url);
    if (!validation.valid) {
      setValidationError(validation.error || ERROR_MESSAGES.INVALID_URL);
      return;
    }

    // Store request for retry
    setLastRequest({ url: validation.normalized!, keyword });

    // Set loading state
    setState('loading');

    // Cancel previous request
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    abortControllerRef.current = new AbortController();

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeout);

      const response = await fetch(apiEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          tool: API_TOOL_IDS[tool],
          url: validation.normalized,
          keyword,
        }),
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        // Handle HTTP errors
        let errorCode: ErrorCode = 'FETCH_FAILED';
        let errorMessage = ERROR_MESSAGES.FETCH_FAILED;
        let retryAfter: number | undefined;

        if (response.status === 429) {
          errorCode = 'RATE_LIMITED';
          errorMessage = ERROR_MESSAGES.RATE_LIMITED;
          const retryAfterHeader = response.headers.get('Retry-After');
          if (retryAfterHeader) {
            retryAfter = parseInt(retryAfterHeader, 10);
          }
        } else if (response.status >= 500) {
          errorCode = 'FETCH_FAILED';
          errorMessage = ERROR_MESSAGES.FETCH_FAILED;
        } else if (response.status === 422) {
          // The request itself was rejected (bad tool id, missing keyword).
          // Telling the user to check their URL would be wrong - their URL
          // is fine.
          errorCode = 'INVALID_URL';
          errorMessage = ERROR_MESSAGES.INVALID_URL;
        } else if (response.status === 404) {
          errorCode = 'UNKNOWN';
          errorMessage = 'This tool is temporarily unavailable. Please try again later.';
        } else {
          // Prefer the server's own message: it says what actually went
          // wrong (SSRF block, timeout, unreachable host).
          const serverError = await response.json().catch(() => null);
          const serverMessage =
            serverError?.error?.message ?? serverError?.error ?? null;
          if (typeof serverMessage === 'string' && serverMessage.trim()) {
            errorMessage = serverMessage;
          }
        }

        const errorResult: AnalysisResult = {
          success: false,
          error: { code: errorCode, message: errorMessage, retryAfter },
          analyzedAt: new Date().toISOString(),
        };

        setState('error');
        setError({ code: errorCode, message: errorMessage });
        setResult(errorResult);
        onError?.({ code: errorCode, message: errorMessage });
        return;
      }

      const data = await response.json();

      const successResult: AnalysisResult = {
        success: true,
        data: normalizeToolResponse(tool, data),
        analyzedAt: data.analyzedAt || new Date().toISOString(),
      };

      setState('success');
      setResult(successResult);
      onSuccess?.(successResult);
    } catch (err) {
      // Don't update state if request was aborted
      if (err instanceof Error && err.name === 'AbortError') {
        return;
      }

      let errorCode: ErrorCode = 'UNKNOWN';
      let errorMessage = ERROR_MESSAGES.UNKNOWN;

      if (err instanceof TypeError && err.message.includes('fetch')) {
        errorCode = 'NETWORK_ERROR';
        errorMessage = ERROR_MESSAGES.NETWORK_ERROR;
      } else if (err instanceof Error && err.name === 'AbortError') {
        errorCode = 'TIMEOUT';
        errorMessage = ERROR_MESSAGES.TIMEOUT;
      }

      const errorResult: AnalysisResult = {
        success: false,
        error: { code: errorCode, message: errorMessage },
        analyzedAt: new Date().toISOString(),
      };

      setState('error');
      setError({ code: errorCode, message: errorMessage });
      setResult(errorResult);
      onError?.({ code: errorCode, message: errorMessage });
    }
  }, [tool, apiEndpoint, timeout, onSuccess, onError]);

  // Reset function
  const reset = React.useCallback(() => {
    setState('idle');
    setResult(null);
    setError(null);
    setValidationError(null);
    setLastRequest(null);

    // Cancel any pending request
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
      abortControllerRef.current = null;
    }
  }, []);

  // Retry function
  const retry = React.useCallback(() => {
    if (lastRequest) {
      analyze(lastRequest.url, lastRequest.keyword);
    }
  }, [lastRequest, analyze]);

  return {
    state,
    result,
    error,
    validationError,
    isLoading: state === 'loading',
    analyze,
    reset,
    retry,
  };
}

export default useToolAnalysis;
