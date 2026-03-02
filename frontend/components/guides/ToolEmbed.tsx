"use client";

/**
 * ToolEmbed Component
 * TASK-20-22: Integrate Tool Embedding for Meta Tag Analyzer Guides
 *
 * Reusable component for embedding SEO tools within guide pages.
 * Wraps ToolWidget with guide-specific features like CTA buttons and error boundaries.
 */

import * as React from 'react';
import dynamic from 'next/dynamic';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ErrorBoundary } from '@/components/tools/ErrorBoundary';
import type { ToolType } from '@/hooks/useToolAnalysis';

// Valid tool slugs
export type ToolSlug = 'meta-tag-analyzer' | 'keyword-density' | 'social-preview' | 'mobile-friendly' | 'mobile-friendly-checker';

// Tool slug to ToolType mapping
const TOOL_SLUG_MAP: Record<ToolSlug, ToolType> = {
  'meta-tag-analyzer': 'meta-tag-analyzer',
  'keyword-density': 'keyword-density',
  'social-preview': 'social-preview',
  'mobile-friendly': 'mobile-friendly',
  'mobile-friendly-checker': 'mobile-friendly',
};

// Tool slug to route mapping
const TOOL_ROUTE_MAP: Record<ToolSlug, string> = {
  'meta-tag-analyzer': '/tools/meta-tag-analyzer/',
  'keyword-density': '/tools/keyword-density/',
  'social-preview': '/tools/social-preview/',
  'mobile-friendly': '/tools/mobile-friendly/',
  'mobile-friendly-checker': '/tools/mobile-friendly-checker/',
};

// Tool display names
const TOOL_NAMES: Record<ToolSlug, string> = {
  'meta-tag-analyzer': 'Meta Tag Analyzer',
  'keyword-density': 'Keyword Density Checker',
  'social-preview': 'Social Preview',
  'mobile-friendly': 'Mobile-Friendly Test',
  'mobile-friendly-checker': 'Mobile-Friendly Checker',
};

// Props for the ToolEmbed component
export interface ToolEmbedProps {
  /** The slug of the tool to embed */
  toolSlug: ToolSlug | string;
  /** Optional custom title (overrides tool name) */
  title?: string;
  /** Optional custom description */
  description?: string;
  /** Pre-select a specific tool tab */
  defaultToolTab?: 'title' | 'description' | 'og' | 'twitter';
  /** Show CTA button to full tool page */
  showCta?: boolean;
  /** Custom CTA button text */
  ctaText?: string;
  /** Additional CSS classes */
  className?: string;
  /** Enable lazy loading (default: true) */
  lazy?: boolean;
  /** State key for persistence */
  stateKey?: string;
  /** Callback when analysis completes */
  onAnalysisComplete?: (result: unknown) => void;
}

// Loading skeleton component
function LoadingSkeleton() {
  return (
    <div className="w-full animate-pulse" role="status" aria-label="Loading tool">
      <div className="rounded-lg border border-slate-700 bg-slate-800/50 p-6">
        <div className="h-6 bg-slate-700 rounded w-1/3 mb-4" />
        <div className="h-4 bg-slate-700 rounded w-2/3 mb-6" />
        <div className="space-y-4">
          <div className="h-10 bg-slate-700 rounded" />
          <div className="h-10 bg-slate-600 rounded w-1/4" />
        </div>
      </div>
    </div>
  );
}

// Fallback UI for invalid/missing tools
interface FallbackUIProps {
  toolSlug?: string;
  showCta?: boolean;
  ctaText?: string;
}

function FallbackUI({ toolSlug, showCta = true, ctaText = 'Browse Tools' }: FallbackUIProps) {
  return (
    <div
      className="rounded-lg border border-slate-700 bg-slate-800/50 p-6 text-center"
      role="alert"
      aria-live="polite"
    >
      <div className="flex justify-center mb-4">
        <svg
          className="h-12 w-12 text-slate-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
          />
        </svg>
      </div>
      <h3 className="text-lg font-medium text-slate-300 mb-2">
        Tool Unavailable
      </h3>
      <p className="text-sm text-slate-400 mb-4">
        {toolSlug
          ? `The tool "${toolSlug}" is not available for embedding.`
          : 'No tool specified for embedding.'}
      </p>
      {showCta && (
        <Button
          asChild
          variant="outline"
          className="border-slate-600 text-slate-300 hover:bg-slate-700"
        >
          <a href="/tools/" aria-label="Browse all available tools">
            {ctaText}
          </a>
        </Button>
      )}
    </div>
  );
}

// Error fallback for error boundary
function ErrorFallback({ onReset }: { onReset?: () => void }) {
  return (
    <div
      className="rounded-lg border border-red-500/30 bg-red-500/10 p-6 text-center"
      role="alert"
      aria-live="assertive"
    >
      <div className="flex justify-center mb-4">
        <svg
          className="h-12 w-12 text-red-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      <h3 className="text-lg font-medium text-red-400 mb-2">
        Something went wrong
      </h3>
      <p className="text-sm text-slate-400 mb-4">
        An error occurred while loading the tool.
      </p>
      {onReset && (
        <Button
          onClick={onReset}
          variant="outline"
          className="border-red-500/30 text-red-400 hover:bg-red-500/20"
        >
          Try Again
        </Button>
      )}
    </div>
  );
}

// Validate tool slug
function isValidToolSlug(slug: string): slug is ToolSlug {
  return slug in TOOL_SLUG_MAP;
}

// Inner component that renders the actual ToolWidget (for error boundary)
interface ToolEmbedInnerProps {
  toolType: ToolType;
  title?: string;
  description?: string;
  showCta: boolean;
  ctaText: string;
  toolRoute: string;
  toolName: string;
  className?: string;
  onAnalysisComplete?: (result: unknown) => void;
}

function ToolEmbedInner({
  toolType,
  title,
  description,
  showCta,
  ctaText,
  toolRoute,
  toolName,
  className,
  onAnalysisComplete,
}: ToolEmbedInnerProps) {
  // Dynamic import of ToolWidget
  const ToolWidget = React.useMemo(
    () =>
      dynamic(
        () => import('@/components/tools/ToolWidget').then((mod) => mod.ToolWidget),
        {
          ssr: false,
          loading: LoadingSkeleton,
        }
      ),
    []
  );

  return (
    <div className={cn('w-full', className)}>
      <div className="mb-4">
        <ToolWidget
          tool={toolType}
          title={title || toolName}
          description={description}
          onAnalysisComplete={onAnalysisComplete}
        />
      </div>
      {showCta && (
        <div className="flex justify-center">
          <Button
            asChild
            variant="outline"
            className="border-slate-600 text-slate-300 hover:bg-slate-700 w-full sm:w-auto"
          >
            <a
              href={toolRoute}
              target="_blank"
              rel="noopener noreferrer"
              aria-label={`Open ${toolName} in a new tab`}
            >
              <svg
                className="h-4 w-4 mr-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                />
              </svg>
              {ctaText}
            </a>
          </Button>
        </div>
      )}
    </div>
  );
}

// Main ToolEmbed component
export function ToolEmbed({
  toolSlug,
  title,
  description,
  defaultToolTab,
  showCta = true,
  ctaText = 'Use Full Tool',
  className,
  lazy = true,
  stateKey,
  onAnalysisComplete,
}: ToolEmbedProps) {
  const [errorKey, setErrorKey] = React.useState(0);

  // Reset error boundary
  const handleReset = React.useCallback(() => {
    setErrorKey((prev) => prev + 1);
  }, []);

  // Validate tool slug
  if (!toolSlug || !isValidToolSlug(toolSlug)) {
    return (
      <FallbackUI
        toolSlug={toolSlug}
        showCta={showCta}
        ctaText={ctaText}
      />
    );
  }

  const toolType = TOOL_SLUG_MAP[toolSlug];
  const toolRoute = TOOL_ROUTE_MAP[toolSlug];
  const toolName = TOOL_NAMES[toolSlug];

  return (
    <section
      className={cn('tool-embed', className)}
      aria-labelledby={`tool-embed-title-${toolSlug}`}
      data-tool-slug={toolSlug}
      data-state-key={stateKey}
    >
      <h2 id={`tool-embed-title-${toolSlug}`} className="sr-only">
        {title || toolName}
      </h2>
      <ErrorBoundary
        key={errorKey}
        fallback={<ErrorFallback onReset={handleReset} />}
        onReset={handleReset}
      >
        <ToolEmbedInner
          toolType={toolType}
          title={title}
          description={description}
          showCta={showCta}
          ctaText={ctaText}
          toolRoute={toolRoute}
          toolName={toolName}
          onAnalysisComplete={onAnalysisComplete}
        />
      </ErrorBoundary>
    </section>
  );
}

export default ToolEmbed;
