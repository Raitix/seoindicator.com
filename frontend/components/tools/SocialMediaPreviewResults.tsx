"use client";

/**
 * SocialMediaPreviewResults Component
 * TASK-16-8: Social Media Preview Tool
 * TASK-29-8: Tool Page Integration - ExportResultsButton
 *
 * Displays the social media preview analysis results with platform tabs.
 */

import * as React from "react";
import { cn } from "@/lib/utils";
import { PlatformTabs, type Platform } from "./PlatformTabs";
import { PreviewCard, type PlatformPreview } from "./PreviewCard";
import { MissingTagsList } from "./MissingTagsList";
import { WarningsList, type SocialWarning } from "./WarningsList";
import { ExportResultsButton } from "@/components/tools/ExportResultsButton";

export interface SocialMediaPreviewData {
  openGraph: {
    title: string | null;
    description: string | null;
    image: string | null;
    url: string | null;
    type: string | null;
    siteName: string | null;
    imageWidth?: number | null;
    imageHeight?: number | null;
    locale?: string | null;
  };
  twitterCard: {
    card: string | null;
    site: string | null;
    creator: string | null;
    title: string | null;
    description: string | null;
    image: string | null;
    player?: string | null;
    playerWidth?: number | null;
    playerHeight?: number | null;
  };
  previews: {
    facebook: PlatformPreview;
    twitter: PlatformPreview;
    linkedin: PlatformPreview;
  };
  missing: string[];
  warnings: SocialWarning[];
}

interface SocialMediaPreviewResultsProps {
  data: SocialMediaPreviewData | null;
  url?: string;
  analyzedAt?: string;
  error?: string;
  className?: string;
}

export function SocialMediaPreviewResults({
  data,
  url,
  analyzedAt,
  error,
  className,
}: SocialMediaPreviewResultsProps) {
  const [activeTab, setActiveTab] = React.useState<Platform>("facebook");

  // Error state
  if (error) {
    return (
      <div
        role="alert"
        data-testid="error-message"
        className={cn(
          "bg-red-500/10 border border-red-500/20 rounded-lg p-4",
          className
        )}
      >
        <div className="flex items-center gap-2 text-red-400">
          <svg
            className="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <span>{error}</span>
        </div>
      </div>
    );
  }

  // No data
  if (!data) {
    return null;
  }

  return (
    <div data-testid="social-media-results" className={cn("space-y-6", className)}>
      {/* Export Button */}
      {url && (
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="social-media-preview"
            url={url}
            data={{
              analyzedAt: analyzedAt || new Date().toISOString(),
              openGraph: data.openGraph,
              twitterCard: data.twitterCard,
              previews: data.previews,
              missing: data.missing,
              warnings: data.warnings,
              results: {
                openGraph: { 
                  status: data.openGraph.title ? 'ok' : 'warning',
                  title: data.openGraph.title,
                  description: data.openGraph.description,
                  image: data.openGraph.image,
                },
                twitterCard: { 
                  status: data.twitterCard.card ? 'ok' : 'warning',
                  card: data.twitterCard.card,
                  title: data.twitterCard.title,
                },
              },
              summary: {
                score: data.missing.length === 0 ? 100 : 80,
                passCount: data.missing.length === 0 ? 2 : 1,
                warningCount: data.missing.length,
                failCount: 0,
              },
            }}
          />
        </div>
      )}

      {/* Platform Tabs */}
      <PlatformTabs activeTab={activeTab} onTabChange={setActiveTab} />

      {/* Tab Panel */}
      <div
        role="tabpanel"
        id={`${activeTab}-panel`}
        aria-labelledby={`tab-${activeTab}`}
      >
        <PreviewCard platform={activeTab} data={data.previews[activeTab]} />
      </div>

      {/* Analysis Details */}
      <div className="grid gap-4 md:grid-cols-2">
        <MissingTagsList missing={data.missing} />
        <WarningsList warnings={data.warnings} />
      </div>

      {/* Raw Data Section (Collapsible) */}
      <details className="bg-slate-800/50 rounded-lg border border-slate-700">
        <summary className="px-4 py-3 cursor-pointer text-sm font-medium text-slate-400 hover:text-slate-300">
          View Raw Tag Data
        </summary>
        <div className="px-4 pb-4">
          <div className="grid gap-4 md:grid-cols-2">
            {/* Open Graph */}
            <div>
              <h5 className="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">
                Open Graph Tags
              </h5>
              <pre className="text-xs text-slate-300 bg-slate-900/50 rounded p-3 overflow-auto">
                {JSON.stringify(data.openGraph, null, 2)}
              </pre>
            </div>

            {/* Twitter Card */}
            <div>
              <h5 className="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">
                Twitter Card Tags
              </h5>
              <pre className="text-xs text-slate-300 bg-slate-900/50 rounded p-3 overflow-auto">
                {JSON.stringify(data.twitterCard, null, 2)}
              </pre>
            </div>
          </div>
        </div>
      </details>
    </div>
  );
}

export default SocialMediaPreviewResults;
