"use client";

/**
 * ToolWidget Component
 * TASK-16-4: Tool Embedding System
 * 
 * Reusable widget for embedding SEO tools in guide pages.
 * Supports meta-tag-analyzer, keyword-density, mobile-friendly, and social-preview tools.
 */

import * as React from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToolAnalysis, type ToolType, type ErrorCode, ERROR_MESSAGES } from '@/hooks/useToolAnalysis';
import { validateUrl } from '@/lib/utils/urlValidation';

// Tool configuration
interface ToolConfig {
  name: string;
  description: string;
  placeholder: string;
  requiresKeyword: boolean;
  keywordPlaceholder?: string;
}

const TOOL_CONFIGS: Record<ToolType, ToolConfig> = {
  'meta-tag-analyzer': {
    name: 'Meta Tag Analyzer',
    description: 'Analyze meta tags including title, description, Open Graph, and Twitter Card tags.',
    placeholder: 'https://example.com',
    requiresKeyword: false,
  },
  'keyword-density': {
    name: 'Keyword Density Checker',
    description: 'Check keyword density and get optimization recommendations.',
    placeholder: 'https://example.com',
    requiresKeyword: true,
    keywordPlaceholder: 'Enter target keyword',
  },
  'mobile-friendly': {
    name: 'Mobile-Friendly Test',
    description: 'Test how mobile-friendly your page is.',
    placeholder: 'https://example.com',
    requiresKeyword: false,
  },
  'social-preview': {
    name: 'Social Preview',
    description: 'Preview how your page appears on social media.',
    placeholder: 'https://example.com',
    requiresKeyword: false,
  },
};

// Component props
export interface ToolWidgetProps {
  tool: ToolType;
  title?: string;
  description?: string;
  placeholder?: string;
  showHelp?: boolean;
  className?: string;
  onAnalysisComplete?: (result: unknown) => void;
}

// Loading spinner component
function LoadingSpinner() {
  return (
    <div className="flex items-center justify-center gap-3 py-8" role="status" aria-live="polite">
      <svg
        className="animate-spin h-6 w-6 text-blue-500"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        aria-hidden="true"
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
      <span className="text-slate-400">Analyzing...</span>
    </div>
  );
}

// Error display component
interface ErrorDisplayProps {
  message: string;
  onRetry?: () => void;
}

function ErrorDisplay({ message, onRetry }: ErrorDisplayProps) {
  return (
    <div
      className="flex flex-col items-center gap-4 py-6"
      role="alert"
      aria-live="assertive"
    >
      <div className="flex items-center gap-2 text-red-400">
        <svg
          className="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{message}</span>
      </div>
      {onRetry && (
        <Button
          variant="outline"
          size="sm"
          onClick={onRetry}
          className="text-slate-300 border-slate-600 hover:bg-slate-800"
        >
          Try Again
        </Button>
      )}
    </div>
  );
}

// Results display component (generic)
interface ResultsDisplayProps {
  tool: ToolType;
  data: unknown;
  analyzedAt: string;
}

function ResultsDisplay({ tool, data, analyzedAt }: ResultsDisplayProps) {
  // Render tool-specific results
  const renderMetaTagResults = (result: Record<string, unknown>) => {
    const title = result.title as Record<string, unknown> | undefined;
    const description = result.description as Record<string, unknown> | undefined;
    const warnings = result.warnings as Array<{ type: string; message: string; severity: string }> | undefined;

    return (
      <div className="space-y-4">
        {/* Title */}
        <div className="bg-slate-800/50 rounded-lg p-4">
          <h4 className="text-sm font-medium text-slate-400 mb-2">Title</h4>
          <p className="text-white font-medium">{(title?.value as string) || 'No title found'}</p>
          {Boolean(title?.value) && (
            <p className="text-xs text-slate-500 mt-1">{title?.length as number} characters</p>
          )}
        </div>

        {/* Description */}
        <div className="bg-slate-800/50 rounded-lg p-4">
          <h4 className="text-sm font-medium text-slate-400 mb-2">Meta Description</h4>
          <p className="text-slate-300">{(description?.value as string) || 'No description found'}</p>
          {Boolean(description?.value) && (
            <p className="text-xs text-slate-500 mt-1">{description.length as number} characters</p>
          )}
        </div>

        {/* Warnings */}
        {warnings && warnings.length > 0 && (
          <div className="space-y-2">
            <h4 className="text-sm font-medium text-slate-400">Warnings ({warnings.length})</h4>
            {warnings.map((warning, index) => (
              <div
                key={index}
                className={cn(
                  'rounded-lg p-3 text-sm',
                  warning.severity === 'critical' && 'bg-red-500/10 text-red-300',
                  warning.severity === 'warning' && 'bg-yellow-500/10 text-yellow-300',
                  warning.severity === 'notice' && 'bg-blue-500/10 text-blue-300'
                )}
              >
                {warning.message}
              </div>
            ))}
          </div>
        )}

        {/* Timestamp */}
        <p className="text-xs text-slate-500 text-right">
          Analyzed at: {new Date(analyzedAt).toLocaleString()}
        </p>
      </div>
    );
  };

  const renderKeywordDensityResults = (result: Record<string, unknown>) => {
    const density = result.density as number | undefined;
    const occurrences = result.occurrences as number | undefined;
    const totalWords = result.totalWords as number | undefined;
    const keyword = result.keyword as string | undefined;
    const warnings = result.warnings as Array<{ type: string; message: string }> | undefined;

    return (
      <div className="space-y-4">
        {/* Density meter */}
        <div className="bg-slate-800/50 rounded-lg p-4">
          <h4 className="text-sm font-medium text-slate-400 mb-2">Keyword Density</h4>
          <div className="flex items-center gap-4">
            <span className="text-3xl font-bold text-white">{density?.toFixed(2)}%</span>
            <div className="flex-1">
              <div className="h-2 bg-slate-700 rounded-full overflow-hidden">
                <div
                  className={cn(
                    'h-full rounded-full transition-all',
                    (density ?? 0) < 1 && 'bg-yellow-500',
                    (density ?? 0) >= 1 && (density ?? 0) <= 3 && 'bg-green-500',
                    (density ?? 0) > 3 && 'bg-red-500'
                  )}
                  style={{ width: `${Math.min((density ?? 0) * 25, 100)}%` }}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 gap-4">
          <div className="bg-slate-800/50 rounded-lg p-4">
            <h4 className="text-sm font-medium text-slate-400 mb-1">Occurrences</h4>
            <p className="text-2xl font-bold text-white">{occurrences}</p>
          </div>
          <div className="bg-slate-800/50 rounded-lg p-4">
            <h4 className="text-sm font-medium text-slate-400 mb-1">Total Words</h4>
            <p className="text-2xl font-bold text-white">{totalWords}</p>
          </div>
        </div>

        {/* Warnings/Recommendations */}
        {warnings && warnings.length > 0 && (
          <div className="space-y-2">
            <h4 className="text-sm font-medium text-slate-400">Recommendations</h4>
            {warnings.map((warning, index) => (
              <div
                key={index}
                className={cn(
                  'rounded-lg p-3 text-sm',
                  warning.type === 'success' && 'bg-green-500/10 text-green-300',
                  warning.type === 'warning' && 'bg-yellow-500/10 text-yellow-300',
                  warning.type === 'info' && 'bg-blue-500/10 text-blue-300'
                )}
              >
                {warning.message}
              </div>
            ))}
          </div>
        )}

        {/* Timestamp */}
        <p className="text-xs text-slate-500 text-right">
          Analyzed at: {new Date(analyzedAt).toLocaleString()}
        </p>
      </div>
    );
  };

  const renderDefaultResults = (result: Record<string, unknown>) => {
    return (
      <div className="bg-slate-800/50 rounded-lg p-4">
        <pre className="text-sm text-slate-300 whitespace-pre-wrap overflow-auto">
          {JSON.stringify(result, null, 2)}
        </pre>
        <p className="text-xs text-slate-500 text-right mt-4">
          Analyzed at: {new Date(analyzedAt).toLocaleString()}
        </p>
      </div>
    );
  };

  const resultData = data as Record<string, unknown>;

  switch (tool) {
    case 'meta-tag-analyzer':
      return renderMetaTagResults(resultData);
    case 'keyword-density':
      return renderKeywordDensityResults(resultData);
    default:
      return renderDefaultResults(resultData);
  }
}

// Main ToolWidget component
export function ToolWidget({
  tool,
  title,
  description,
  placeholder,
  showHelp = false,
  className,
  onAnalysisComplete,
}: ToolWidgetProps) {
  const config = TOOL_CONFIGS[tool];
  const [url, setUrl] = React.useState('');
  const [keyword, setKeyword] = React.useState('');
  const [inputError, setInputError] = React.useState<string | null>(null);
  const inputRef = React.useRef<HTMLInputElement>(null);

  const {
    state,
    result,
    error,
    validationError,
    isLoading,
    analyze,
    reset,
    retry,
  } = useToolAnalysis({
    tool,
    onSuccess: (result) => {
      onAnalysisComplete?.(result.data);
    },
  });

  // Clear input error when URL changes
  React.useEffect(() => {
    if (inputError) {
      setInputError(null);
    }
  }, [url]);

  // Handle form submit
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setInputError(null);

    // Validate URL
    const validation = validateUrl(url);
    if (!validation.valid) {
      setInputError(validation.error || ERROR_MESSAGES.INVALID_URL);
      return;
    }

    // Check keyword requirement
    if (config.requiresKeyword && !keyword.trim()) {
      setInputError('Please enter a keyword');
      return;
    }

    await analyze(url, keyword.trim() || undefined);
  };

  // Handle reset
  const handleReset = () => {
    setUrl('');
    setKeyword('');
    setInputError(null);
    reset();
    // Focus input after reset
    setTimeout(() => inputRef.current?.focus(), 0);
  };

  // Handle retry
  const handleRetry = () => {
    retry();
  };

  const displayTitle = title || config.name;
  const displayDescription = description || config.description;
  const displayPlaceholder = placeholder || config.placeholder;

  return (
    <div className={cn('w-full max-w-2xl mx-auto', className)}>
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            {displayTitle}
            {showHelp && (
              <button
                type="button"
                className="text-slate-400 hover:text-white transition-colors"
                aria-label="Help"
                title={displayDescription}
              >
                <svg
                  className="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </button>
            )}
          </CardTitle>
          <CardDescription>{displayDescription}</CardDescription>
        </CardHeader>
        <CardContent>
          {/* Form */}
          {state !== 'success' && (
            <form onSubmit={handleSubmit} className="space-y-4">
              {/* URL Input */}
              <div className="space-y-2">
                <Label htmlFor="url-input" className="text-slate-300">
                  URL to analyze
                </Label>
                <Input
                  ref={inputRef}
                  id="url-input"
                  type="url"
                  value={url}
                  onChange={(e) => setUrl(e.target.value)}
                  placeholder={displayPlaceholder}
                  disabled={isLoading}
                  className="bg-slate-800 border-slate-700 focus:border-blue-500"
                  aria-describedby={inputError ? 'url-error' : undefined}
                  aria-invalid={!!inputError}
                />
              </div>

              {/* Keyword Input (if required) */}
              {config.requiresKeyword && (
                <div className="space-y-2">
                  <Label htmlFor="keyword-input" className="text-slate-300">
                    Target keyword
                  </Label>
                  <Input
                    id="keyword-input"
                    type="text"
                    value={keyword}
                    onChange={(e) => setKeyword(e.target.value)}
                    placeholder={config.keywordPlaceholder}
                    disabled={isLoading}
                    className="bg-slate-800 border-slate-700 focus:border-blue-500"
                  />
                </div>
              )}

              {/* Error Message */}
              {(inputError || validationError) && (
                <p
                  id="url-error"
                  className="text-sm text-red-400"
                  role="alert"
                >
                  {inputError || validationError}
                </p>
              )}

              {/* Submit Button */}
              <Button
                type="submit"
                disabled={isLoading || !url.trim()}
                className="w-full sm:w-auto"
              >
                {isLoading ? (
                  <>
                    <svg
                      className="animate-spin -ml-1 mr-2 h-4 w-4"
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
                  </>
                ) : (
                  'Analyze'
                )}
              </Button>
            </form>
          )}

          {/* Loading State */}
          {state === 'loading' && <LoadingSpinner />}

          {/* Error State */}
          {state === 'error' && error && (
            <ErrorDisplay message={error.message} onRetry={handleRetry} />
          )}

          {/* Results */}
          {state === 'success' && result?.data && (
            <div className="space-y-4" aria-live="polite">
              <ResultsDisplay
                tool={tool}
                data={result.data}
                analyzedAt={result.analyzedAt}
              />
              <Button
                variant="outline"
                onClick={handleReset}
                className="w-full sm:w-auto"
              >
                Analyze Another
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

export default ToolWidget;
