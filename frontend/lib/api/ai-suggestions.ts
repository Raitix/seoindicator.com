// API client for AI Suggestions feature (SPEC-021)

import type {
  AIIndicatorType,
  AISuggestionOptions,
  SuggestionResult,
  DebugSuggestionResult,
  AISuggestionAPIResponse,
  AISuggestionError,
} from '@/lib/types/ai-suggestions';

const API_BASE = '/api/v1';

/**
 * Custom error class for rate limit errors
 */
export class RateLimitError extends Error {
  public readonly retryAfter: number;

  constructor(message: string, retryAfter: number) {
    super(message);
    this.name = 'RateLimitError';
    this.retryAfter = retryAfter;
  }
}

/**
 * Custom error class for network errors
 */
export class NetworkError extends Error {
  constructor(message: string = 'Network request failed') {
    super(message);
    this.name = 'NetworkError';
  }
}

/**
 * Custom error class for API errors
 */
export class APIError extends Error {
  public readonly code: string;

  constructor(message: string, code: string = 'UNKNOWN_ERROR') {
    super(message);
    this.name = 'APIError';
    this.code = code;
  }
}

/**
 * Extract error message from API response
 */
function extractErrorMessage(response: AISuggestionAPIResponse): string {
  if ('error' in response && response.error) {
    return response.error.message;
  }
  return 'An unexpected error occurred';
}

/**
 * Get AI suggestion for a specific indicator
 *
 * @param indicator - SEO indicator type to analyze
 * @param context - Context data for the indicator
 * @param options - Optional request options (provider, debug)
 * @returns Promise resolving to SuggestionResult
 * @throws RateLimitError if rate limit exceeded
 * @throws NetworkError if network request fails
 * @throws APIError for other API errors
 */
export async function getAISuggestion(
  indicator: AIIndicatorType,
  context: Record<string, unknown>,
  options: AISuggestionOptions = {}
): Promise<SuggestionResult> {
  const { provider, debug = false } = options;

  // Build request body, filtering out undefined values
  const body: Record<string, unknown> = { indicator, context };
  if (provider !== undefined) {
    body.provider = provider;
  }
  if (debug) {
    body.debug = true;
  }

  const response = await fetch(`${API_BASE}/ai/suggestions`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  // Handle rate limiting
  if (response.status === 429) {
    const retryAfter = parseInt(response.headers.get('Retry-After') || '60', 10);
    const errorData = await response.json().catch(() => null);
    const message = errorData?.error?.message || 'Rate limit exceeded. Please try again later.';
    throw new RateLimitError(message, retryAfter);
  }

  // Handle other HTTP errors
  if (!response.ok) {
    const errorData = await response.json().catch(() => null);
    const message = errorData?.error?.message || errorData?.message || `HTTP error: ${response.status}`;
    const code = errorData?.error?.code || 'HTTP_ERROR';
    throw new APIError(message, code);
  }

  // Parse response
  let data: AISuggestionAPIResponse;
  try {
    data = await response.json();
  } catch {
    throw new APIError('Failed to parse API response', 'PARSE_ERROR');
  }

  // Check for API-level errors
  if ('success' in data && data.success === false) {
    throw new APIError(extractErrorMessage(data), data.error?.code || 'API_ERROR');
  }

  // Extract result
  const result = 'data' in data ? data.data : data;

  // If debug mode, return the result without debug info (use getAISuggestionWithDebug for debug)
  if ('debug' in result) {
    const { debug: _, ...suggestionResult } = result as DebugSuggestionResult;
    return suggestionResult as SuggestionResult;
  }

  return result as SuggestionResult;
}

/**
 * Get AI suggestion with debug information
 *
 * @param indicator - SEO indicator type to analyze
 * @param context - Context data for the indicator
 * @param options - Optional request options (provider)
 * @returns Promise resolving to DebugSuggestionResult
 * @throws RateLimitError if rate limit exceeded
 * @throws NetworkError if network request fails
 * @throws APIError for other API errors
 */
export async function getAISuggestionWithDebug(
  indicator: AIIndicatorType,
  context: Record<string, unknown>,
  options: Omit<AISuggestionOptions, 'debug'> = {}
): Promise<DebugSuggestionResult> {
  const { provider } = options;

  const body: Record<string, unknown> = { indicator, context, debug: true };
  if (provider !== undefined) {
    body.provider = provider;
  }

  const response = await fetch(`${API_BASE}/ai/suggestions`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  // Handle rate limiting
  if (response.status === 429) {
    const retryAfter = parseInt(response.headers.get('Retry-After') || '60', 10);
    const errorData = await response.json().catch(() => null);
    const message = errorData?.error?.message || 'Rate limit exceeded. Please try again later.';
    throw new RateLimitError(message, retryAfter);
  }

  // Handle other HTTP errors
  if (!response.ok) {
    const errorData = await response.json().catch(() => null);
    const message = errorData?.error?.message || errorData?.message || `HTTP error: ${response.status}`;
    const code = errorData?.error?.code || 'HTTP_ERROR';
    throw new APIError(message, code);
  }

  // Parse response
  let data: AISuggestionAPIResponse;
  try {
    data = await response.json();
  } catch {
    throw new APIError('Failed to parse API response', 'PARSE_ERROR');
  }

  // Check for API-level errors
  if ('success' in data && data.success === false) {
    throw new APIError(extractErrorMessage(data), data.error?.code || 'API_ERROR');
  }

  // Extract result with debug info
  const result = 'data' in data ? data.data : data;
  return result as DebugSuggestionResult;
}

/**
 * Check if an error is a rate limit error
 */
export function isRateLimitError(error: unknown): error is RateLimitError {
  return error instanceof RateLimitError;
}

/**
 * Check if an error is a network error
 */
export function isNetworkError(error: unknown): error is NetworkError {
  return error instanceof NetworkError;
}

/**
 * Check if an error is an API error
 */
export function isAPIError(error: unknown): error is APIError {
  return error instanceof APIError;
}

/**
 * Get human-readable label for an indicator
 */
export function getIndicatorLabel(indicator: AIIndicatorType): string {
  const labels: Record<AIIndicatorType, string> = {
    title: 'Title Tag',
    meta_description: 'Meta Description',
    robots: 'Robots.txt',
    sitemap: 'XML Sitemap',
    headings: 'Heading Structure',
    image_alt: 'Image Alt Text',
    filenames: 'File Names',
    url: 'URL Structure',
    links: 'Link Analysis',
    '404': '404 Page',
  };
  return labels[indicator] || indicator;
}
