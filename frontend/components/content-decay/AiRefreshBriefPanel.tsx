/**
 * AiRefreshBriefPanel Component
 * AI-generated refresh suggestions for content decay candidates
 * 
 * @see TASK-36-12 for implementation requirements
 */

"use client";

import * as React from "react";
import {
  Sparkles,
  AlertTriangle,
  ChevronDown,
  ChevronUp,
  Copy,
  RefreshCw,
  Loader2,
  FileText,
  Check,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import type { AiRefreshBrief, AiRefreshSuggestion } from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface AiRefreshBriefPanelProps {
  /** AI brief data */
  brief: AiRefreshBrief | null;
  /** Callback to request brief generation */
  onRequestBrief?: () => void;
  /** Callback to regenerate brief */
  onRegenerate?: () => void;
  /** Show loading state */
  isLoading?: boolean;
  /** Show regeneration loading state */
  isRegenerating?: boolean;
  /** Error message to display */
  error?: string | null;
  /** Additional CSS classes */
  className?: string;
}

const PRIORITY_CONFIG: Record<string, { label: string; color: string }> = {
  high: { label: "High", color: "bg-red-100 text-red-700 border-red-200" },
  medium: { label: "Medium", color: "bg-yellow-100 text-yellow-700 border-yellow-200" },
  low: { label: "Low", color: "bg-gray-100 text-gray-700 border-gray-200" },
};

function SuggestionCard({
  suggestion,
  isExpanded,
  onToggle,
}: {
  suggestion: AiRefreshSuggestion;
  isExpanded: boolean;
  onToggle: () => void;
}) {
  const [copied, setCopied] = React.useState(false);

  const handleCopy = async () => {
    const text = `${suggestion.title}\n\n${suggestion.description}`;
    await navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const priorityConfig = PRIORITY_CONFIG[suggestion.priority] || PRIORITY_CONFIG.low;

  return (
    <Card data-testid={`suggestion-${suggestion.id}`} className="mb-3">
      <CardHeader className="pb-2">
        <div data-testid={`suggestion-header-${suggestion.id}`} className="flex items-start justify-between gap-2">
          <div className="flex items-center gap-2">
            <FileText className="h-4 w-4 text-muted-foreground" data-testid="icon-file-text" />
            <span
              data-testid={`suggestion-title-${suggestion.id}`}
              className="font-medium text-sm"
            >
              {suggestion.title}
            </span>
          </div>
          <div className="flex items-center gap-1.5">
            <Badge
              data-testid={`suggestion-priority-${suggestion.id}`}
              data-priority={suggestion.priority}
              variant="outline"
              className={cn("text-xs", priorityConfig.color)}
            >
              {priorityConfig.label}
            </Badge>
            <Badge
              data-testid={`suggestion-category-${suggestion.id}`}
              variant="secondary"
              className="text-xs"
            >
              {suggestion.category}
            </Badge>
          </div>
        </div>
      </CardHeader>
      <CardContent className="pt-0">
        <p
          data-testid={`suggestion-description-${suggestion.id}`}
          className="text-sm text-muted-foreground mb-3"
        >
          {suggestion.description}
        </p>

        {/* Toggle Details Button */}
        {suggestion.details && suggestion.details.length > 0 && (
          <>
            <Button
              data-testid={`toggle-details-${suggestion.id}`}
              variant="ghost"
              size="sm"
              onClick={onToggle}
              aria-expanded={isExpanded}
              aria-label={isExpanded ? "Collapse details" : "Expand details"}
              className="h-7 px-2 text-xs"
            >
              {isExpanded ? (
                <ChevronUp className="h-3 w-3 mr-1" data-testid="icon-chevron-up" />
              ) : (
                <ChevronDown className="h-3 w-3 mr-1" data-testid="icon-chevron-down" />
              )}
              {isExpanded ? "Hide Details" : "Show Details"}
            </Button>

            {/* Details List */}
            {isExpanded && (
              <ul
                data-testid={`suggestion-details-${suggestion.id}`}
                className="mt-2 space-y-1 text-sm text-muted-foreground"
              >
                {suggestion.details.map((detail, i) => (
                  <li key={i} className="flex items-start gap-2">
                    <span className="text-primary mt-0.5">•</span>
                    {detail}
                  </li>
                ))}
              </ul>
            )}
          </>
        )}

        {/* Copy Button */}
        <Button
          data-testid={`copy-btn-${suggestion.id}`}
          variant="ghost"
          size="sm"
          onClick={handleCopy}
          aria-label="Copy suggestion"
          className="mt-2 h-7 px-2 text-xs"
        >
          {copied ? (
            <>
              <Check className="h-3 w-3 mr-1 text-green-600" />
              <span data-testid="copied-indicator" className="text-green-600">Copied!</span>
            </>
          ) : (
            <>
              <Copy className="h-3 w-3 mr-1" data-testid="icon-copy" />
              Copy
            </>
          )}
        </Button>
      </CardContent>
    </Card>
  );
}

export function AiRefreshBriefPanel({
  brief,
  onRequestBrief,
  onRegenerate,
  isLoading = false,
  isRegenerating = false,
  error = null,
  className,
}: AiRefreshBriefPanelProps) {
  const [expandedSuggestions, setExpandedSuggestions] = React.useState<Set<string>>(new Set());

  const toggleSuggestion = (id: string) => {
    setExpandedSuggestions((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  if (isLoading) {
    return (
      <div data-testid="brief-loading" className={cn("space-y-3", className)}>
        <Skeleton data-testid="skeleton-brief" className="h-32 w-full" />
        <Skeleton className="h-24 w-full" />
        <Skeleton className="h-24 w-full" />
      </div>
    );
  }

  if (error) {
    return (
      <div
        data-testid="brief-error"
        className={cn("text-center text-red-600 py-4", className)}
      >
        {error}
      </div>
    );
  }

  if (!brief) {
    return (
      <div
        data-testid="brief-empty"
        className={cn("text-center py-8", className)}
      >
        <Sparkles className="h-8 w-8 mx-auto mb-3 text-muted-foreground" />
        <p className="text-muted-foreground mb-4">No AI brief generated yet</p>
        <Button
          data-testid="request-brief-btn"
          onClick={onRequestBrief}
          aria-label="Request AI brief"
          className="gap-1.5"
        >
          <Sparkles className="h-4 w-4" data-testid="icon-sparkles" />
          Request AI Brief
        </Button>
      </div>
    );
  }

  return (
    <div
      data-testid="ai-refresh-brief-panel"
      className={className}
      role="region"
      aria-label="AI refresh brief"
    >
      {/* Warning Label */}
      <div
        data-testid="warning-label"
        role="alert"
        className="flex items-center gap-2 p-3 mb-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm"
      >
        <AlertTriangle className="h-4 w-4 flex-shrink-0" data-testid="icon-alert-triangle" />
        <span>AI-generated, verify before use</span>
      </div>

      {/* Generated Timestamp */}
      <div className="text-xs text-muted-foreground mb-4">
        <span data-testid="generated-at">Generated: {new Date(brief.generatedAt).toLocaleString()}</span>
      </div>

      {/* Suggestions */}
      <div data-testid="suggestions-container" className="space-y-3">
        {brief.suggestions.map((suggestion) => (
          <SuggestionCard
            key={suggestion.id}
            suggestion={suggestion}
            isExpanded={expandedSuggestions.has(suggestion.id)}
            onToggle={() => toggleSuggestion(suggestion.id)}
          />
        ))}
      </div>

      {/* Disclaimer */}
      <div
        data-testid="disclaimer"
        className="mt-4 p-3 bg-muted/50 rounded-lg text-xs text-muted-foreground"
      >
        {brief.disclaimer}
      </div>

      {/* Regenerate Button */}
      <Button
        data-testid="regenerate-btn"
        variant="outline"
        size="sm"
        onClick={onRegenerate}
        disabled={isRegenerating}
        aria-label="Regenerate brief"
        className="mt-4 w-full"
      >
        {isRegenerating ? (
          <>
            <Loader2 className="h-4 w-4 mr-1.5 animate-spin" data-testid="icon-loader" />
            Regenerating...
          </>
        ) : (
          <>
            <RefreshCw className="h-4 w-4 mr-1.5" data-testid="icon-refresh" />
            Regenerate Brief
          </>
        )}
      </Button>
    </div>
  );
}
