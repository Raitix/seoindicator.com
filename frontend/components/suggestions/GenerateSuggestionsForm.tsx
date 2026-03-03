/**
 * GenerateSuggestionsForm Component - TASK-5-11
 *
 * React/Next.js form component for generating new keyword suggestions.
 *
 * Features:
 * - Seed keyword input (required)
 * - Source checkboxes: Google Suggest (default), Question Keywords (default)
 * - Max results slider: 10-100 (default 50)
 * - Generate button triggers API call
 * - Loading state during generation
 * - Success message with count of new suggestions
 * - Error handling with user-friendly messages
 * - Form reset after successful generation
 * - Rate limit indicator (X/10 requests remaining)
 *
 * @see TASK-5-11
 * @see TASK-5-8 (Generate suggestions endpoint)
 */

'use client';

import React, { useState, useCallback, useRef } from 'react';
import { generateSuggestions } from '@/lib/api/suggestions';
import { SuggestionSource } from '@/lib/types/suggestions';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Sparkles, Loader2, AlertCircle, CheckCircle, Zap } from 'lucide-react';

export interface GenerateSuggestionsFormProps {
  siteId: string | number;
  onSuccess?: (count: number) => void;
  className?: string;
}

interface SourceConfig {
  id: SuggestionSource;
  label: string;
  description?: string;
  disabled?: boolean;
  disabledReason?: string;
}

const AVAILABLE_SOURCES: SourceConfig[] = [
  {
    id: 'suggest',
    label: 'Google Suggest',
    description: 'Autocomplete suggestions from Google',
  },
  {
    id: 'question',
    label: 'Question Keywords',
    description: 'Who, what, where, when, why, how questions',
  },
  {
    id: 'related',
    label: 'Related Searches',
    description: 'Coming soon',
    disabled: true,
    disabledReason: 'Coming soon',
  },
];

const MAX_KEYWORD_LENGTH = 255;
const RATE_LIMIT_DEFAULT = 10;

export function GenerateSuggestionsForm({
  siteId,
  onSuccess,
  className = '',
}: GenerateSuggestionsFormProps) {
  // Form state
  const [seedKeyword, setSeedKeyword] = useState('');
  const [selectedSources, setSelectedSources] = useState<Set<SuggestionSource>>(
    new Set(['suggest', 'question'])
  );
  const [maxResults, setMaxResults] = useState(50);

  // UI state
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successCount, setSuccessCount] = useState<number | null>(null);
  const [rateLimitRemaining, setRateLimitRemaining] = useState(RATE_LIMIT_DEFAULT);

  // Debounce ref for preventing double submission
  const submitRef = useRef<boolean>(false);

  // Handle source checkbox toggle
  const handleSourceToggle = useCallback((sourceId: SuggestionSource) => {
    setSelectedSources((prev) => {
      const next = new Set(prev);
      if (next.has(sourceId)) {
        next.delete(sourceId);
      } else {
        next.add(sourceId);
      }
      return next;
    });
  }, []);

  // Handle form submission
  const handleSubmit = useCallback(async (e: React.FormEvent) => {
    e.preventDefault();

    // Prevent double submission
    if (submitRef.current || isLoading) {
      return;
    }

    // Validate seed keyword
    const trimmedKeyword = seedKeyword.trim();
    if (!trimmedKeyword) {
      setError('Please enter a seed keyword');
      return;
    }

    // Validate sources
    if (selectedSources.size === 0) {
      setError('Please select at least one source');
      return;
    }

    // Start loading
    submitRef.current = true;
    setIsLoading(true);
    setError(null);
    setSuccessCount(null);

    try {
      const result = await generateSuggestions(Number(siteId), {
        seedKeyword: trimmedKeyword,
        sources: Array.from(selectedSources),
        maxResults,
      });

      // Success
      setSuccessCount(result.count ?? 0);
      setRateLimitRemaining((prev) => Math.max(0, prev - 1));
      setSeedKeyword(''); // Reset form

      // Call success callback
      onSuccess?.(result.count ?? 0);
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to generate suggestions';
      setError(message);
    } finally {
      setIsLoading(false);
      submitRef.current = false;
    }
  }, [seedKeyword, selectedSources, maxResults, siteId, isLoading, onSuccess]);

  // Determine if submit should be disabled
  const isSubmitDisabled = isLoading || !seedKeyword.trim() || selectedSources.size === 0;

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Sparkles className="w-5 h-5 text-primary" />
          Generate Keyword Suggestions
        </CardTitle>
        <CardDescription>
          Enter a seed keyword to discover new keyword opportunities
        </CardDescription>
      </CardHeader>

      <CardContent>
        <form onSubmit={handleSubmit} data-testid="generate-form">
          {/* Seed Keyword Input */}
          <div className="space-y-2 mb-6">
            <Label htmlFor="seed-keyword" className="flex items-center gap-1">
              Seed Keyword
              <span className="text-destructive" aria-hidden="true">*</span>
            </Label>
            <Input
              id="seed-keyword"
              type="text"
              data-testid="seed-keyword-input"
              placeholder="Enter a keyword to discover suggestions"
              value={seedKeyword}
              onChange={(e) => setSeedKeyword(e.target.value)}
              maxLength={MAX_KEYWORD_LENGTH}
              aria-required="true"
              disabled={isLoading}
            />
            <p className="text-xs text-muted-foreground">
              {seedKeyword.length}/{MAX_KEYWORD_LENGTH} characters
            </p>
          </div>

          {/* Source Checkboxes */}
          <fieldset className="space-y-3 mb-6" data-testid="source-checkboxes">
            <legend className="text-sm font-medium mb-2">Sources</legend>
            <div className="space-y-2">
              {AVAILABLE_SOURCES.map((source) => (
                <div key={source.id} className="flex items-start space-x-3">
                  <Checkbox
                    id={`source-${source.id}`}
                    data-testid={`source-${source.id}`}
                    checked={selectedSources.has(source.id)}
                    onCheckedChange={() => handleSourceToggle(source.id)}
                    disabled={source.disabled || isLoading}
                  />
                  <div className="grid gap-1 leading-none">
                    <Label
                      htmlFor={`source-${source.id}`}
                      className={`cursor-pointer ${source.disabled ? 'text-muted-foreground' : ''}`}
                    >
                      {source.label}
                      {source.disabled && (
                        <span className="text-xs ml-2 text-muted-foreground">
                          ({source.disabledReason})
                        </span>
                      )}
                    </Label>
                    {source.description && !source.disabled && (
                      <p className="text-xs text-muted-foreground">
                        {source.description}
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </fieldset>

          {/* Max Results Slider */}
          <div className="space-y-2 mb-6">
            <Label htmlFor="max-results" className="flex items-center gap-2">
              Max Results: <span data-testid="max-results-value" className="font-bold">{maxResults}</span>
            </Label>
            <input
              id="max-results"
              type="range"
              data-testid="max-results-slider"
              min="10"
              max="100"
              step="10"
              value={maxResults}
              onChange={(e) => setMaxResults(Number(e.target.value))}
              className="w-full h-2 bg-secondary rounded-lg appearance-none cursor-pointer accent-primary"
              disabled={isLoading}
            />
            <div className="flex justify-between text-xs text-muted-foreground">
              <span>10</span>
              <span>100</span>
            </div>
          </div>

          {/* Submit Button */}
          <Button
            type="submit"
            data-testid="generate-button"
            disabled={isSubmitDisabled}
            className="w-full"
          >
            {isLoading ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Generating...
              </>
            ) : (
              <>
                <Zap className="w-4 h-4" />
                Generate Suggestions
              </>
            )}
          </Button>

          {/* Rate Limit Indicator */}
          <div
            className="mt-4 flex items-center justify-center gap-2 text-sm text-muted-foreground"
            data-testid="rate-limit"
          >
            <span>API Calls:</span>
            <span
              data-testid="rate-limit-remaining"
              className={rateLimitRemaining <= 2 ? 'text-destructive font-medium' : ''}
            >
              {rateLimitRemaining}
            </span>
            <span>/10 remaining</span>
          </div>

          {/* Loading State (explicit indicator for accessibility) */}
          {isLoading && (
            <div
              data-testid="loading-indicator"
              className="mt-4 flex items-center justify-center gap-2 text-sm text-muted-foreground"
              role="status"
              aria-live="polite"
            >
              <Loader2 className="w-4 h-4 animate-spin" />
              Generating suggestions...
            </div>
          )}

          {/* Success Message */}
          {successCount !== null && (
            <div
              data-testid="success-message"
              className="mt-4 flex items-center justify-center gap-2 text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950 p-3 rounded-md"
              role="status"
              aria-live="polite"
            >
              <CheckCircle className="w-4 h-4" />
              <span data-testid="success-count">{successCount}</span> new suggestions generated!
            </div>
          )}

          {/* Error Message */}
          {error && (
            <div
              data-testid="error-message"
              className="mt-4 flex items-center justify-center gap-2 text-sm text-destructive bg-destructive/10 p-3 rounded-md"
              role="alert"
            >
              <AlertCircle className="w-4 h-4" />
              <span data-testid="error-text">{error}</span>
            </div>
          )}
        </form>
      </CardContent>
    </Card>
  );
}

export default GenerateSuggestionsForm;
