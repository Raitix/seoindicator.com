// AISuggestionButton component for AI-powered SEO suggestions
// frontend/components/tools/AISuggestionButton.tsx

'use client';

import React, { useState, useCallback, useRef } from 'react';
import {
  getAISuggestion,
  isRateLimitError,
  RateLimitError,
} from '@/lib/api/ai-suggestions';

import type { AISuggestionButtonProps, SuggestionResult } from '@/lib/types/ai-suggestions';

/**
 * Button component that triggers AI suggestion generation
 * Handles loading, error, and rate limit states
 */
export function AISuggestionButton({
  indicator,
  context,
  onSuggestion,
  onError,
  buttonText,
  showProvider = false,
  className = '',
  disabled = false,
}: AISuggestionButtonProps) {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [rateLimitRemaining, setRateLimitRemaining] = useState<number | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);

  const isDisabled = disabled || context === null || isLoading || rateLimitRemaining !== null;

  const handleClick = useCallback(async () => {
    if (isLoading || context === null) return;

    // Cancel any pending request
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    abortControllerRef.current = new AbortController();

    setIsLoading(true);
    setError(null);

    try {
      const result = await getAISuggestion(indicator, context);
      onSuggestion(result);
      
      if (showProvider && result.provider) {
        // Could display provider badge
      }
    } catch (err) {
      if (isRateLimitError(err)) {
        const retryAfter = (err as RateLimitError).retryAfter;
        setError('Rate limit exceeded');
        setRateLimitRemaining(retryAfter);

        // Countdown timer
        const interval = setInterval(() => {
          setRateLimitRemaining((prev) => {
            if (prev === null || prev <= 1) {
              clearInterval(interval);
              return null;
            }
            return prev - 1;
          });
        }, 1000);
      } else {
        const message = err instanceof Error ? err.message : 'An error occurred';
        setError(message);
        onError?.(err as Error);
      }
    } finally {
      setIsLoading(false);
    }
  }, [indicator, context, isLoading, onSuggestion, onError, showProvider]);

  const handleRetry = useCallback(() => {
    setError(null);
    handleClick();
  }, [handleClick]);

  const getIndicatorLabel = (): string => {
    const labels: Record<string, string> = {
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
  };

  const defaultButtonText = buttonText || `Get ${getIndicatorLabel()} Suggestion`;

  if (error && !isLoading) {
    return (
      <div className="flex flex-col gap-2">
        <span className="text-sm text-red-500" role="alert">
          {error}
        </span>
        <button
          onClick={handleRetry}
          className={`px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 ${className}`}
          aria-label="Retry suggestion generation"
        >
          Retry
        </button>
      </div>
    );
  }

  if (rateLimitRemaining !== null) {
    return (
      <button
        disabled
        className={`px-4 py-2 rounded-md bg-gray-300 text-gray-500 cursor-not-allowed ${className}`}
        aria-label={`Rate limited. Retry in ${rateLimitRemaining} seconds`}
      >
        Rate limited ({rateLimitRemaining}s)
      </button>
    );
  }

  return (
    <button
      onClick={handleClick}
      disabled={isDisabled}
      className={`px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed ${className}`}
      aria-label={`Get AI suggestion for ${getIndicatorLabel()}`}
      title={`Analyze ${getIndicatorLabel()} with AI`}
    >
      {isLoading ? (
        <span className="flex items-center gap-2">
          <svg
            className="animate-spin h-4 w-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              className="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              strokeWidth="4"
            />
            <path
              className="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
          </svg>
          Analyzing...
        </span>
      ) : (
        defaultButtonText
      )}
    </button>
  );
}

export default AISuggestionButton;
