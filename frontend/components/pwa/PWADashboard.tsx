"use client";

import * as React from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { RefreshCw, AlertCircle, CheckCircle, ArrowUpCircle } from "lucide-react";
import { cn } from "@/lib/utils";
import { usePWAAnalysis } from "@/lib/api/pwa";
import type { PWARecommendation } from "@/lib/types/pwa";
import { PWAScoreGauge } from "./PWAScoreGauge";
import { ManifestStatusCard } from "./ManifestStatusCard";
import { PWASkeleton, PWAErrorSkeleton, PWANoAnalysisSkeleton } from "./PWASkeleton";

export interface PWADashboardProps {
  siteId: number;
  onAnalyzeClick?: () => void;
  className?: string;
}

/**
 * Get priority icon and color
 */
function getPriorityStyles(priority: PWARecommendation["priority"]) {
  switch (priority) {
    case "high":
      return {
        icon: AlertCircle,
        color: "text-red-400",
        bgColor: "bg-red-500/10",
      };
    case "medium":
      return {
        icon: ArrowUpCircle,
        color: "text-yellow-400",
        bgColor: "bg-yellow-500/10",
      };
    case "low":
    default:
      return {
        icon: CheckCircle,
        color: "text-emerald-400",
        bgColor: "bg-emerald-500/10",
      };
  }
}

/**
 * Format analyzed date
 */
function formatAnalyzedAt(dateStr: string | null): string {
  if (!dateStr) return "Never";
  
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);
  
  if (diffMins < 1) return "Just now";
  if (diffMins < 60) return `${diffMins} minute${diffMins === 1 ? "" : "s"} ago`;
  if (diffHours < 24) return `${diffHours} hour${diffHours === 1 ? "" : "s"} ago`;
  if (diffDays < 7) return `${diffDays} day${diffDays === 1 ? "" : "s"} ago`;
  
  return date.toLocaleDateString();
}

export function PWADashboard({
  siteId,
  onAnalyzeClick,
  className,
}: PWADashboardProps) {
  // Fetch PWA analysis using SWR hook
  const {
    data: analysis,
    isLoading,
    error,
    revalidate,
    triggerAnalysis,
    refreshAnalysis,
  } = usePWAAnalysis(siteId);
  
  // Track if an action is in progress
  const [isAnalyzing, setIsAnalyzing] = React.useState(false);
  
  // Handle analyze click
  const handleAnalyze = React.useCallback(async () => {
    onAnalyzeClick?.();
    setIsAnalyzing(true);
    try {
      await triggerAnalysis();
    } finally {
      setIsAnalyzing(false);
    }
  }, [onAnalyzeClick, triggerAnalysis]);
  
  // Handle refresh click
  const handleRefresh = React.useCallback(async () => {
    setIsAnalyzing(true);
    try {
      await refreshAnalysis();
    } finally {
      setIsAnalyzing(false);
    }
  }, [refreshAnalysis]);
  
  // Handle retry
  const handleRetry = React.useCallback(() => {
    revalidate();
  }, [revalidate]);
  
  // Loading state
  if (isLoading) {
    return (
      <div className={className} data-testid="pwa-dashboard">
        <PWASkeleton />
      </div>
    );
  }
  
  // Error state
  if (error) {
    const errorMessage = error instanceof Error ? error.message : "An error occurred";
    const isNotFound = errorMessage.includes("No PWA analysis found") || errorMessage.includes("not found");
    
    if (isNotFound) {
      return (
        <div className={className} data-testid="pwa-dashboard">
          <PWANoAnalysisSkeleton onAnalyze={handleAnalyze} />
        </div>
      );
    }
    
    return (
      <div className={className} data-testid="pwa-dashboard">
        <PWAErrorSkeleton message={errorMessage} onRetry={handleRetry} />
      </div>
    );
  }
  
  // No analysis data
  if (!analysis) {
    return (
      <div className={className} data-testid="pwa-dashboard">
        <PWANoAnalysisSkeleton onAnalyze={handleAnalyze} />
      </div>
    );
  }
  
  return (
    <div
      className={cn("space-y-6", className)}
      data-testid="pwa-dashboard"
      aria-label="PWA Analysis Dashboard"
    >
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-xl font-semibold text-white">PWA Analysis</h2>
          <p className="text-sm text-slate-400">
            Last analyzed: {formatAnalyzedAt(analysis.analyzedAt)}
          </p>
        </div>
        <button
          onClick={handleRefresh}
          disabled={isAnalyzing}
          className={cn(
            "flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors",
            "bg-slate-800 text-slate-300 hover:bg-slate-700",
            "disabled:opacity-50 disabled:cursor-not-allowed"
          )}
          aria-label="Refresh PWA analysis"
        >
          <RefreshCw className={cn("h-4 w-4", isAnalyzing && "animate-spin")} />
          Refresh
        </button>
      </div>
      
      {/* Content Grid */}
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {/* Score Gauge */}
        <Card className="border-slate-800 bg-slate-900/60">
          <CardContent className="p-6 flex justify-center">
            <PWAScoreGauge
              score={analysis.pwaScore}
              readinessLevel={analysis.readinessLevel}
            />
          </CardContent>
        </Card>
        
        {/* Manifest Status */}
        <ManifestStatusCard
          hasManifest={analysis.hasManifest}
          manifestUrl={analysis.manifestUrl}
          manifestValid={analysis.manifestValid}
          fields={analysis.fields}
        />
        
        {/* Recommendations */}
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardTitle className="text-sm font-medium text-slate-300">
              Recommendations
            </CardTitle>
          </CardHeader>
          <CardContent>
            {analysis.recommendations.length === 0 ? (
              <div className="flex items-center gap-2 text-emerald-400">
                <CheckCircle className="h-5 w-5" />
                <span className="text-sm">All checks passed!</span>
              </div>
            ) : (
              <ul className="space-y-3">
                {analysis.recommendations.slice(0, 5).map((rec, index) => {
                  const styles = getPriorityStyles(rec.priority);
                  const Icon = styles.icon;
                  
                  return (
                    <li
                      key={index}
                      className="flex items-start gap-2"
                    >
                      <Icon
                        className={cn("h-4 w-4 mt-0.5 flex-shrink-0", styles.color)}
                        aria-hidden="true"
                      />
                      <div className="flex-1">
                        <p className="text-sm text-slate-300">{rec.message}</p>
                        {rec.field && (
                          <span className="text-xs text-slate-500">Field: {rec.field}</span>
                        )}
                      </div>
                    </li>
                  );
                })}
                {analysis.recommendations.length > 5 && (
                  <li className="text-xs text-slate-400">
                    +{analysis.recommendations.length - 5} more recommendations
                  </li>
                )}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>
      
      {/* Icon Status (if manifest exists) */}
      {analysis.hasManifest && analysis.icons.total > 0 && (
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardTitle className="text-sm font-medium text-slate-300">
              Icons ({analysis.icons.valid}/{analysis.icons.total} valid)
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex flex-wrap gap-3">
              {/* Required icon sizes */}
              {["192x192", "512x512"].map((size) => {
                const isPresent = analysis.icons.required[size as keyof typeof analysis.icons.required]?.present;
                
                return (
                  <div
                    key={size}
                    className={cn(
                      "flex items-center gap-2 px-3 py-2 rounded-lg border",
                      isPresent
                        ? "bg-emerald-500/10 border-emerald-500/20 text-emerald-400"
                        : "bg-red-500/10 border-red-500/20 text-red-400"
                    )}
                  >
                    {isPresent ? (
                      <CheckCircle className="h-4 w-4" />
                    ) : (
                      <AlertCircle className="h-4 w-4" />
                    )}
                    <span className="text-sm font-medium">{size}</span>
                  </div>
                );
              })}
              
              {/* Icon errors */}
              {analysis.icons.errors.length > 0 && (
                <div className="w-full mt-2 space-y-1">
                  {analysis.icons.errors.map((err, idx) => (
                    <div key={idx} className="flex items-center gap-2 text-xs text-yellow-400">
                      <AlertCircle className="h-3 w-3" />
                      <span>{err.size}: {err.error}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

export default PWADashboard;
