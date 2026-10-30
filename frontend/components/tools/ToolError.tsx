"use client";

/**
 * ToolError Component
 * TASK-16-12: Error Handling & User Feedback
 *
 * Displays error messages for SEO tools with retry functionality.
 */

import * as React from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

// Error codes matching backend
export type ErrorCode =
  | 'INVALID_URL'
  | 'URL_BLOCKED'
  | 'FETCH_FAILED'
  | 'TIMEOUT'
  | 'RATE_LIMITED'
  | 'PARSE_ERROR'
  | 'TOOL_ERROR';

// Error severity
export type ErrorSeverity = 'error' | 'warning' | 'info';

// Component props
export interface ToolErrorProps {
  code: ErrorCode;
  message: string;
  suggestion?: string;
  details?: string;
  onRetry?: () => void;
  onDismiss?: () => void;
  showRetry?: boolean;
  severity?: ErrorSeverity;
  isLoading?: boolean;
  className?: string;
}

// Error code configurations
const ERROR_CONFIG: Record<ErrorCode, { color: string; icon: React.ReactNode }> = {
  INVALID_URL: {
    color: 'bg-red-500/10 border-red-500/30 text-red-400',
    icon: (
      <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
      </svg>
    ),
  },
  URL_BLOCKED: {
    color: 'bg-orange-500/10 border-orange-500/30 text-orange-400',
    icon: (
      <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
      </svg>
    ),
  },
  FETCH_FAILED: {
    color: 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
    icon: (
      <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    ),
  },
  TIMEOUT: {
    color: 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
    icon: (
      <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
  },
  RATE_LIMITED: {
    color: 'bg-orange-500/10 border-orange-500/30 text-orange-400',
    icon: (
      <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
      </svg>
    ),
  },
  PARSE_ERROR: {
    color: 'bg-red-500/10 border-red-500/30 text-red-400',
    icon: (
      <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
    ),
  },
  TOOL_ERROR: {
    color: 'bg-red-500/10 border-red-500/30 text-red-400',
    icon: (
      <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
  },
};

// Severity icons
const SEVERITY_ICONS: Record<ErrorSeverity, React.ReactNode> = {
  error: (
    <svg className="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  ),
  warning: (
    <svg className="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
  ),
  info: (
    <svg className="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  ),
};

export function ToolError({
  code,
  message,
  suggestion,
  details,
  onRetry,
  onDismiss,
  showRetry = true,
  severity = 'error',
  isLoading = false,
  className,
}: ToolErrorProps) {
  const config = ERROR_CONFIG[code] || ERROR_CONFIG.TOOL_ERROR;
  const severityIcon = SEVERITY_ICONS[severity];

  return (
    <div
      className={cn(
        'rounded-lg border p-4 animate-in fade-in duration-200',
        config.color,
        className
      )}
      role="alert"
      aria-live="assertive"
    >
      <div className="flex items-start gap-3">
        {/* Icon */}
        <div className="flex-shrink-0 mt-0.5">
          {config.icon}
        </div>

        {/* Content */}
        <div className="flex-1 min-w-0">
          {/* Error code badge */}
          <div className="flex items-center gap-2 mb-1">
            <span className="text-xs font-mono uppercase tracking-wide opacity-75">
              {code}
            </span>
          </div>

          {/* Message */}
          <p className="text-sm font-medium">
            {escapeHtml(message)}
          </p>

          {/* Suggestion */}
          {suggestion && (
            <p className="text-sm mt-2 opacity-80">
              {escapeHtml(suggestion)}
            </p>
          )}

          {/* Details (collapsible) */}
          {details && (
            <details className="mt-2 text-xs opacity-60">
              <summary className="cursor-pointer hover:opacity-80">
                Technical details
              </summary>
              <pre className="mt-1 p-2 bg-black/20 rounded overflow-x-auto">
                {escapeHtml(details)}
              </pre>
            </details>
          )}

          {/* Actions */}
          <div className="flex items-center gap-2 mt-3">
            {showRetry && onRetry && (
              <Button
                size="sm"
                variant="outline"
                onClick={onRetry}
                disabled={isLoading}
                className="h-8"
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
                    Retrying...
                  </>
                ) : (
                  <>
                    <svg className="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Try Again
                  </>
                )}
              </Button>
            )}

            {onDismiss && (
              <Button
                size="sm"
                variant="ghost"
                onClick={onDismiss}
                className="h-8 opacity-60 hover:opacity-100"
              >
                Dismiss
              </Button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

/**
 * Escape HTML to prevent XSS.
 */
function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

export default ToolError;
