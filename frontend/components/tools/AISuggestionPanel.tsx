// AISuggestionPanel component for displaying AI suggestion results
// frontend/components/tools/AISuggestionPanel.tsx

'use client';

import React, { useState, useCallback } from 'react';

import type { AISuggestionPanelProps, SuggestionResult } from '@/lib/types/ai-suggestions';

/**
 * Panel component that displays AI suggestion results
 * Shows score, issues, suggestions with copy buttons, and best practices
 */
export function AISuggestionPanel({
  result,
  isLoading = false,
  error = null,
  showRegenerate = false,
  onRegenerate,
  defaultCollapsed = false,
  className = '',
}: AISuggestionPanelProps) {
  const [isCollapsed, setIsCollapsed] = useState(defaultCollapsed);
  const [copiedIndex, setCopiedIndex] = useState<number | null>(null);

  const handleCopy = useCallback(async (text: string, index: number) => {
    try {
      await navigator.clipboard.writeText(text);
      setCopiedIndex(index);
      setTimeout(() => setCopiedIndex(null), 2000);
    } catch {
      console.error('Failed to copy to clipboard');
    }
  }, []);

  const getScoreColor = (score: number): string => {
    if (score >= 70) return 'bg-green-500';
    if (score >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
  };

  const getRelativeTime = (timestamp: string): string => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  };

  const toggleCollapse = () => {
    setIsCollapsed(!isCollapsed);
  };

  // Loading state
  if (isLoading) {
    return (
      <div
        className={`animate-pulse bg-slate-100 rounded-lg p-6 ${className}`}
        data-testid="skeleton"
        role="status"
        aria-label="Loading suggestions"
      >
        <div className="h-4 bg-slate-200 rounded w-1/4 mb-4" />
        <div className="h-4 bg-slate-200 rounded w-3/4 mb-2" />
        <div className="h-4 bg-slate-200 rounded w-1/2" />
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className={`bg-red-50 border border-red-200 rounded-lg p-4 ${className}`}>
        <p className="text-red-700" role="alert">
          {error}
        </p>
      </div>
    );
  }

  // Empty state
  if (!result) {
    return (
      <div className={`bg-slate-50 rounded-lg p-6 text-center ${className}`}>
        <p className="text-slate-500">No suggestion available</p>
        <p className="text-sm text-slate-400 mt-1">
          Click the button above to generate AI suggestions
        </p>
      </div>
    );
  }

  const hasIssues = result.issues.length > 0;
  const hasSuggestions = result.suggestions.length > 0;
  const hasBestPractices = result.best_practices.length > 0;
  const isFallback = !!result.fallback_reason;

  return (
    <div
      className={`bg-white border border-slate-200 rounded-lg overflow-hidden ${className}`}
    >
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b border-slate-200">
        <div className="flex items-center gap-3">
          {/* Score Badge */}
          <div
            className={`${getScoreColor(result.score)} text-white font-bold px-3 py-1 rounded-full text-sm`}
            aria-label={`Score: ${result.score} out of 100`}
          >
            {result.score}
          </div>
          <h3 className="text-lg font-semibold text-slate-900">
            {result.indicator.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())} Analysis
          </h3>
        </div>

        <div className="flex items-center gap-2">
          {/* Collapse Toggle */}
          <button
            onClick={toggleCollapse}
            className="p-1 hover:bg-slate-100 rounded"
            aria-label={isCollapsed ? 'Expand panel' : 'Collapse panel'}
            aria-expanded={!isCollapsed}
          >
            <svg
              className={`w-5 h-5 text-slate-500 transition-transform ${isCollapsed ? 'rotate-180' : ''}`}
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          {/* Regenerate Button */}
          {showRegenerate && onRegenerate && (
            <button
              onClick={onRegenerate}
              className="text-sm text-blue-600 hover:text-blue-700"
              aria-label="Regenerate suggestions"
            >
              Regenerate
            </button>
          )}
        </div>
      </div>

      {/* Fallback Warning */}
      {isFallback && (
        <div className="bg-yellow-50 border-b border-yellow-200 px-4 py-2">
          <p className="text-sm text-yellow-700">
            ⚠️ Fallback mode: {result.fallback_reason}
          </p>
        </div>
      )}

      {/* Content */}
      {!isCollapsed && (
        <div className="p-4 space-y-4">
          {/* Issues */}
          {hasIssues && (
            <div>
              <h4 className="text-sm font-medium text-slate-700 mb-2">Issues</h4>
              <ul className="space-y-1" role="list">
                {result.issues.map((issue, index) => (
                  <li
                    key={index}
                    className="text-sm text-red-600 flex items-start gap-2"
                  >
                    <span className="text-red-400">•</span>
                    {issue}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Suggestions */}
          <div>
            <h4 className="text-sm font-medium text-slate-700 mb-2">Suggestions</h4>
            {hasSuggestions ? (
              <div className="space-y-3">
                {result.suggestions.map((suggestion, index) => (
                  <div
                    key={index}
                    className="bg-slate-50 rounded-lg p-3 relative"
                  >
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1">
                        {/* Type Badge */}
                        <span
                          className={`inline-block text-xs font-medium px-2 py-0.5 rounded mb-2 ${
                            suggestion.type === 'rewrite'
                              ? 'bg-purple-100 text-purple-700'
                              : 'bg-blue-100 text-blue-700'
                          }`}
                        >
                          {suggestion.type}
                        </span>
                        <p className="text-sm text-slate-900">{suggestion.text}</p>
                        {suggestion.reason && (
                          <p className="text-xs text-slate-500 mt-1">
                            {suggestion.reason}
                          </p>
                        )}
                      </div>
                      {/* Copy Button */}
                      <button
                        onClick={() => handleCopy(suggestion.text, index)}
                        className="p-1.5 hover:bg-slate-200 rounded text-slate-400 hover:text-slate-600"
                        aria-label="Copy suggestion"
                      >
                        {copiedIndex === index ? (
                          <span className="text-xs text-green-600">Copied!</span>
                        ) : (
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                            />
                          </svg>
                        )}
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-slate-500">No suggestions available</p>
            )}
          </div>

          {/* Best Practices */}
          {hasBestPractices && (
            <div>
              <h4 className="text-sm font-medium text-slate-700 mb-2">Best Practices</h4>
              <ul className="space-y-1" role="list">
                {result.best_practices.map((practice, index) => (
                  <li
                    key={index}
                    className="text-sm text-slate-600 flex items-start gap-2"
                  >
                    <span className="text-green-400">✓</span>
                    {practice}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Footer */}
          <div className="flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-slate-100">
            <div className="flex items-center gap-2">
              <span>Powered by {result.provider}</span>
              {result.model && (
                <span className="text-slate-300">•</span>
              )}
              {result.model && (
                <span>{result.model}</span>
              )}
            </div>
            <span>{getRelativeTime(result.generated_at)}</span>
          </div>
        </div>
      )}

      {/* Screen reader announcements */}
      <div className="sr-only" role="status" aria-live="polite">
        {result && `Analysis complete. Score: ${result.score}. ${result.issues.length} issues found, ${result.suggestions.length} suggestions available.`}
      </div>
    </div>
  );
}

export default AISuggestionPanel;
