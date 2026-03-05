"use client";

/**
 * MobileFriendlyResults Component
 * TASK-29-8: Tool Page Integration - ExportResultsButton
 *
 * Displays the mobile-friendly analysis results with export functionality.
 */

import * as React from "react";
import { cn } from "@/lib/utils";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { ExportResultsButton } from "@/components/tools/ExportResultsButton";

export interface MobileFriendlyResult {
  mobileScore: number;
  viewport: {
    present: boolean;
    valid: boolean;
    content: string;
  };
  responsiveIndicators: {
    hasMediaQueries: boolean;
    hasFlexibleGrid: boolean;
  };
  readability: {
    fontSizeMin: number;
    fontSizeWarning: boolean;
  };
  tapTargets: {
    links: number;
    smallTargets: number;
  };
  horizontalScroll: {
    detected: boolean;
  };
  issues: Array<{
    severity: string;
    message: string;
    category: string;
  }>;
  recommendations: Array<{
    type: string;
    message: string;
  }>;
  analyzedAt: string;
}

interface MobileFriendlyResultsProps {
  result: MobileFriendlyResult;
  url?: string;
  className?: string;
}

// Score badge component
function ScoreBadge({ score }: { score: number }) {
  let bgColor = "bg-red-500/20 text-red-400";
  let label = "Poor";

  if (score >= 90) {
    bgColor = "bg-green-500/20 text-green-400";
    label = "Excellent";
  } else if (score >= 70) {
    bgColor = "bg-yellow-500/20 text-yellow-400";
    label = "Good";
  } else if (score >= 50) {
    bgColor = "bg-orange-500/20 text-orange-400";
    label = "Needs Work";
  }

  return (
    <div
      className={cn(
        "flex flex-col items-center justify-center p-6 rounded-lg border",
        bgColor
      )}
      data-testid="mobile-score"
      role="status"
    >
      <span className="text-4xl font-bold">{score}</span>
      <span className="text-sm font-medium mt-1">{label}</span>
    </div>
  );
}

export function MobileFriendlyResults({
  result,
  url,
  className,
}: MobileFriendlyResultsProps) {
  const {
    mobileScore,
    viewport,
    responsiveIndicators,
    readability,
    tapTargets,
    horizontalScroll,
    issues,
    recommendations,
    analyzedAt,
  } = result;

  const formatDate = (isoString: string) => {
    try {
      return new Date(isoString).toLocaleString();
    } catch {
      return "Unknown";
    }
  };

  // Calculate summary stats
  const passCount = [
    viewport.present && viewport.valid,
    responsiveIndicators.hasMediaQueries,
    responsiveIndicators.hasFlexibleGrid,
    !horizontalScroll.detected,
    tapTargets.smallTargets === 0,
  ].filter(Boolean).length;

  const warningCount = issues.filter(i => i.severity === "warning").length;
  const failCount = issues.filter(i => i.severity === "error").length;

  return (
    <div
      data-testid="mobile-friendly-results"
      role="region"
      aria-label="Mobile-Friendly Results"
      className={cn("space-y-6", className)}
    >
      {/* Header with Export Button */}
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Mobile-Friendly Analysis</h2>
        <time
          dateTime={analyzedAt}
          className="text-sm text-slate-500"
        >
          Analyzed: {formatDate(analyzedAt)}
        </time>
      </div>

      {/* Export Button */}
      {url && (
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="mobile-friendly-checker"
            url={url}
            data={{
              analyzedAt,
              mobileScore,
              viewport,
              responsiveIndicators,
              readability,
              tapTargets,
              horizontalScroll,
              issues,
              recommendations,
              results: {
                mobileScore: { value: mobileScore, status: mobileScore >= 70 ? 'ok' : 'warning' },
                viewport: { value: viewport.content, status: viewport.valid ? 'ok' : 'error' },
                tapTargets: { value: tapTargets.links, status: tapTargets.smallTargets === 0 ? 'ok' : 'warning' },
              },
              summary: {
                score: mobileScore,
                passCount,
                warningCount,
                failCount,
              },
            }}
          />
        </div>
      )}

      {/* Score Section */}
      <Card>
        <CardHeader>
          <CardTitle>Mobile Score</CardTitle>
          <CardDescription>
            Overall mobile-friendliness score based on viewport, responsiveness, and usability
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center gap-6">
            <ScoreBadge score={mobileScore} />
            <div className="flex-1 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-slate-400">Viewport</span>
                <span
                  className={cn(
                    "font-medium",
                    viewport.present && viewport.valid ? "text-green-400" : "text-red-400"
                  )}
                  data-testid="viewport-status"
                >
                  {viewport.present ? (viewport.valid ? "Valid" : "Invalid") : "Missing"}
                </span>
              </div>
              {viewport.present && (
                <div className="flex items-center justify-between">
                  <span className="text-slate-400">Content</span>
                  <span className="font-medium text-slate-300 text-xs truncate max-w-[200px]">
                    {viewport.content}
                  </span>
                </div>
              )}
              <div className="flex items-center justify-between">
                <span className="text-slate-400">Horizontal Scroll</span>
                <span
                  className={cn(
                    "font-medium",
                    !horizontalScroll.detected ? "text-green-400" : "text-red-400"
                  )}
                >
                  {horizontalScroll.detected ? "Detected" : "None"}
                </span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Responsive Indicators */}
      <Card>
        <CardHeader>
          <CardTitle>Responsive Design</CardTitle>
          <CardDescription>
            Check if the page uses responsive design techniques
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 gap-4">
            <div
              className={cn(
                "p-4 rounded-lg border",
                responsiveIndicators.hasMediaQueries
                  ? "bg-green-500/5 border-green-500/20"
                  : "bg-slate-800/50 border-slate-700"
              )}
            >
              <div className="flex items-center gap-2">
                <span
                  className={cn(
                    "w-3 h-3 rounded-full",
                    responsiveIndicators.hasMediaQueries ? "bg-green-500" : "bg-slate-500"
                  )}
                />
                <span className="font-medium">Media Queries</span>
              </div>
              <p className="text-sm text-slate-400 mt-1">
                {responsiveIndicators.hasMediaQueries ? "Found in CSS" : "Not detected"}
              </p>
            </div>
            <div
              className={cn(
                "p-4 rounded-lg border",
                responsiveIndicators.hasFlexibleGrid
                  ? "bg-green-500/5 border-green-500/20"
                  : "bg-slate-800/50 border-slate-700"
              )}
            >
              <div className="flex items-center gap-2">
                <span
                  className={cn(
                    "w-3 h-3 rounded-full",
                    responsiveIndicators.hasFlexibleGrid ? "bg-green-500" : "bg-slate-500"
                  )}
                />
                <span className="font-medium">Flexible Grid</span>
              </div>
              <p className="text-sm text-slate-400 mt-1">
                {responsiveIndicators.hasFlexibleGrid ? "Uses flexible units" : "Not detected"}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Tap Targets */}
      <Card>
        <CardHeader>
          <CardTitle>Tap Targets</CardTitle>
          <CardDescription>
            Check if links and buttons are easy to tap on mobile
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 gap-4">
            <div className="p-4 bg-slate-800/50 rounded-lg border border-slate-700">
              <span className="text-slate-400 text-sm">Total Links</span>
              <p className="text-2xl font-bold text-white">{tapTargets.links}</p>
            </div>
            <div
              className={cn(
                "p-4 rounded-lg border",
                tapTargets.smallTargets === 0
                  ? "bg-green-500/5 border-green-500/20"
                  : "bg-red-500/5 border-red-500/20"
              )}
            >
              <span className="text-slate-400 text-sm">Small Targets</span>
              <p
                className={cn(
                  "text-2xl font-bold",
                  tapTargets.smallTargets === 0 ? "text-green-400" : "text-red-400"
                )}
              >
                {tapTargets.smallTargets}
              </p>
            </div>
          </div>
          {tapTargets.smallTargets > 0 && (
            <p className="text-sm text-yellow-400 mt-4">
              ⚠️ {tapTargets.smallTargets} tap target(s) may be too small for mobile users.
              Consider increasing their size to at least 48x48 pixels.
            </p>
          )}
        </CardContent>
      </Card>

      {/* Issues */}
      {issues.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Issues Found</CardTitle>
            <CardDescription>
              Problems that may affect mobile user experience
            </CardDescription>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {issues.map((issue, index) => (
                <li
                  key={index}
                  className={cn(
                    "flex items-start gap-3 p-3 rounded-lg",
                    issue.severity === "error" && "bg-red-500/10 text-red-400",
                    issue.severity === "warning" && "bg-yellow-500/10 text-yellow-400",
                    issue.severity === "info" && "bg-blue-500/10 text-blue-400"
                  )}
                  role="alert"
                >
                  <span className="mt-0.5">
                    {issue.severity === "error" && "❌"}
                    {issue.severity === "warning" && "⚠️"}
                    {issue.severity === "info" && "ℹ️"}
                  </span>
                  <span>{issue.message}</span>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      )}

      {/* Recommendations */}
      {recommendations.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Recommendations</CardTitle>
            <CardDescription>
              Suggestions to improve mobile experience
            </CardDescription>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {recommendations.map((rec, index) => (
                <li
                  key={index}
                  className={cn(
                    "flex items-start gap-3 p-3 rounded-lg",
                    rec.type === "success" && "bg-green-500/10 text-green-400",
                    rec.type === "warning" && "bg-yellow-500/10 text-yellow-400",
                    rec.type === "info" && "bg-blue-500/10 text-blue-400"
                  )}
                >
                  <span className="mt-0.5">
                    {rec.type === "success" && "✅"}
                    {rec.type === "warning" && "⚠️"}
                    {rec.type === "info" && "ℹ️"}
                  </span>
                  <span>{rec.message}</span>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

export default MobileFriendlyResults;
