"use client";

import * as React from "react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { cn } from "@/lib/utils";
import {
  getDensityStatusLabel,
  getDensityStatusBgColor,
  type DensityStatus,
} from "@/lib/utils/keywordDensityAnalyzer";
import type { Heading } from "@/lib/utils/headingAnalyzer";
import { AISuggestionButton } from "@/components/tools/AISuggestionButton";
import { AISuggestionPanel } from "@/components/tools/AISuggestionPanel";
import type { SuggestionResult } from "@/lib/types/ai-suggestions";

export interface KeywordWarning {
  type: "error" | "warning" | "info" | "success";
  message: string;
}

export interface KeywordDensityResult {
  keyword: string;
  density: number;
  occurrences: number;
  totalWords: number;
  positions: number[];
  status: DensityStatus;
  headings: Heading[];
  warnings: KeywordWarning[];
  analyzedAt: string;
}

interface KeywordDensityResultsProps {
  result: KeywordDensityResult;
  onExport?: () => void;
  className?: string;
}

export function KeywordDensityResults({
  result,
  onExport,
  className,
}: KeywordDensityResultsProps) {
  const {
    keyword,
    density,
    occurrences,
    totalWords,
    headings,
    warnings,
    analyzedAt,
    status,
  } = result;

  // AI suggestion state
  const [aiSuggestion, setAiSuggestion] = React.useState<SuggestionResult | null>(null);

  const statusLabel = getDensityStatusLabel(status);
  const statusBgColor = getDensityStatusBgColor(status);

  const headingsWithKeyword = headings.filter((h) => h.keywordFound);
  const headingsWithoutKeyword = headings.filter((h) => !h.keywordFound);

  const formatDate = (isoString: string) => {
    try {
      return new Date(isoString).toLocaleString();
    } catch {
      return "Unknown";
    }
  };

  // Copy to clipboard helper
  const copyToClipboard = async (text: string): Promise<boolean> => {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch {
      console.error('Failed to copy to clipboard');
      return false;
    }
  };

  // Build context for AI suggestions
  const aiContext = {
    keyword,
    density,
    occurrences,
    total_words: totalWords,
    headings: headings.slice(0, 10).map(h => ({ tag: h.tag, text: h.text, keyword_found: h.keywordFound })),
    status,
  };

  // Handle AI suggestion regeneration
  const handleRegenerateAiSuggestion = () => {
    setAiSuggestion(null);
  };

  return (
    <div
      data-testid="keyword-density-results"
      role="region"
      aria-label="Keyword Density Results"
      className={cn("space-y-6", className)}
    >
      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">
          Analysis Results for &quot;{keyword}&quot;
        </h2>
        <time
          dateTime={analyzedAt}
          className="text-sm text-slate-500"
        >
          Analyzed: {formatDate(analyzedAt)}
        </time>
      </div>

      {/* Density Section */}
      <Card>
        <CardHeader>
          <CardTitle>Keyword Density</CardTitle>
          <CardDescription>
            The percentage of times your keyword appears compared to total words
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center gap-6">
            {/* Density Value */}
            <div
              className={cn(
                "flex flex-col items-center justify-center p-6 rounded-lg border",
                statusBgColor
              )}
              data-testid="density-value"
              role="status"
            >
              <span className="text-4xl font-bold">
                {density.toFixed(1)}%
              </span>
              <span className="text-sm font-medium mt-1">{statusLabel}</span>
            </div>

            {/* Stats */}
            <div className="flex-1 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-slate-400">Occurrences</span>
                <span
                  data-testid="occurrences"
                  className="font-medium"
                >
                  {occurrences} occurrences
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-400">Total Words</span>
                <span
                  data-testid="total-words"
                  className="font-medium"
                >
                  {totalWords} total words
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-400">Status</span>
                <span
                  className={cn(
                    "px-2 py-1 rounded text-xs font-medium",
                    status === "good" && "bg-green-500/20 text-green-400",
                    status === "low" && "bg-yellow-500/20 text-yellow-400",
                    status === "over-optimized" && "bg-red-500/20 text-red-400"
                  )}
                >
                  {statusLabel}
                </span>
              </div>
            </div>
          </div>

          {/* Density Guide */}
          <div className="mt-6 p-4 bg-slate-800/50 rounded-lg">
            <h4 className="text-sm font-medium mb-2">Density Guidelines</h4>
            <div className="grid grid-cols-3 gap-4 text-xs">
              <div className="flex items-center gap-2">
                <span className="w-3 h-3 rounded-full bg-yellow-500" />
                <span>&lt; 1% - Too low</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="w-3 h-3 rounded-full bg-green-500" />
                <span>1-3% - Optimal</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="w-3 h-3 rounded-full bg-red-500" />
                <span>&gt; 3% - Over-optimized</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Headings Section */}
      <Card>
        <CardHeader>
          <CardTitle>Heading Analysis</CardTitle>
          <CardDescription>
            Check if your keyword appears in important headings
          </CardDescription>
        </CardHeader>
        <CardContent>
          {headings.length === 0 ? (
            <p className="text-slate-500 text-center py-4">
              No headings found in the content.
            </p>
          ) : (
            <ul className="space-y-3">
              {headings.map((heading, index) => (
                <li
                  key={index}
                  className={cn(
                    "flex items-center gap-3 p-3 rounded-lg border",
                    heading.keywordFound
                      ? "bg-green-500/5 border-green-500/20"
                      : "bg-slate-800/50 border-slate-700"
                  )}
                >
                  <span
                    className={cn(
                      "px-2 py-1 rounded text-xs font-bold uppercase",
                      heading.tag === "h1" && "bg-emerald-500/20 text-emerald-400",
                      heading.tag === "h2" && "bg-blue-500/20 text-blue-400",
                      heading.tag === "h3" && "bg-purple-500/20 text-purple-400",
                      heading.tag === "h4" && "bg-pink-500/20 text-pink-400",
                      heading.tag === "h5" && "bg-orange-500/20 text-orange-400",
                      heading.tag === "h6" && "bg-slate-500/20 text-slate-400"
                    )}
                  >
                    {heading.tag.toUpperCase()}
                  </span>
                  <span className="flex-1 truncate">{heading.text}</span>
                  {heading.keywordFound ? (
                    <span
                      className="text-green-400 text-sm flex items-center gap-1"
                      data-testid="keyword-found"
                    >
                      ✓ Keyword found
                    </span>
                  ) : (
                    <span
                      className="text-yellow-400 text-sm flex items-center gap-1"
                      data-testid="keyword-missing"
                    >
                      ⚠ Not found
                    </span>
                  )}
                </li>
              ))}
            </ul>
          )}

          {/* Summary */}
          {headings.length > 0 && (
            <div className="mt-4 p-4 bg-slate-800/50 rounded-lg">
              <p className="text-sm">
                Found keyword in{" "}
                <span className="font-medium text-emerald-400">
                  {headingsWithKeyword.length}
                </span>{" "}
                of{" "}
                <span className="font-medium">{headings.length}</span> headings.
              </p>
              {headingsWithoutKeyword.length > 0 && (
                <p className="text-sm text-slate-400 mt-2">
                  Consider adding &quot;{keyword}&quot; to:{" "}
                  {headingsWithoutKeyword.map((h) => h.text).join(", ")}
                </p>
              )}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Warnings Section */}
      {warnings.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Recommendations</CardTitle>
            <CardDescription>
              Suggestions to improve your keyword optimization
            </CardDescription>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {warnings.map((warning, index) => (
                <li
                  key={index}
                  className={cn(
                    "flex items-start gap-3 p-3 rounded-lg",
                    warning.type === "error" && "bg-red-500/10 text-red-400",
                    warning.type === "warning" && "bg-yellow-500/10 text-yellow-400",
                    warning.type === "info" && "bg-blue-500/10 text-blue-400",
                    warning.type === "success" && "bg-green-500/10 text-green-400"
                  )}
                  role="alert"
                >
                  <span className="mt-0.5">
                    {warning.type === "error" && "❌"}
                    {warning.type === "warning" && "⚠️"}
                    {warning.type === "info" && "ℹ️"}
                    {warning.type === "success" && "✅"}
                  </span>
                  <span>{warning.message}</span>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      )}

      {/* Export Button */}
      {onExport && (
        <div className="flex justify-end">
          <Button
            onClick={onExport}
            variant="outline"
          >
            Export Results
          </Button>
        </div>
      )}
    </div>
  );
}

export default KeywordDensityResults;
