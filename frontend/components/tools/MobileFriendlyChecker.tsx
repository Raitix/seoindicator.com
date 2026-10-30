"use client";

/**
 * MobileFriendlyChecker Component
 * TASK-29-8: Tool Page Integration
 *
 * Main component for mobile-friendly analysis.
 */

import * as React from "react";
import { cn } from "@/lib/utils";
import {
  MobileFriendlyResults,
  type MobileFriendlyResult,
} from "./MobileFriendlyResults";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToolAnalysis } from "@/hooks/useToolAnalysis";

interface MobileFriendlyCheckerProps {
  className?: string;
  initialUrl?: string;
  onAnalysisComplete?: (result: MobileFriendlyResult) => void;
}

export function MobileFriendlyChecker({
  className,
  initialUrl,
  onAnalysisComplete,
}: MobileFriendlyCheckerProps) {
  const [url, setUrl] = React.useState(initialUrl || "");
  const [analyzedUrl, setAnalyzedUrl] = React.useState<string | null>(null);

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
    tool: "mobile-friendly",
    apiEndpoint: "/api/v1/tools/analyze",
    onSuccess: (analysisResult) => {
      setAnalyzedUrl(url);
      onAnalysisComplete?.(analysisResult.data as MobileFriendlyResult);
    },
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (url.trim()) {
      await analyze(url.trim());
    }
  };

  const handleReset = () => {
    reset();
    setUrl("");
    setAnalyzedUrl(null);
  };

  // Map API response to MobileFriendlyResult
  const mobileResult: MobileFriendlyResult | null = result?.data
    ? {
        mobileScore: (result.data as Record<string, unknown>).mobileScore as number ?? 0,
        viewport: (result.data as Record<string, unknown>).viewport as MobileFriendlyResult['viewport'] ?? { present: false, valid: false, content: '' },
        responsiveIndicators: (result.data as Record<string, unknown>).responsiveIndicators as MobileFriendlyResult['responsiveIndicators'] ?? { hasMediaQueries: false, hasFlexibleGrid: false },
        readability: (result.data as Record<string, unknown>).readability as MobileFriendlyResult['readability'] ?? { fontSizeMin: 16, fontSizeWarning: false },
        tapTargets: (result.data as Record<string, unknown>).tapTargets as MobileFriendlyResult['tapTargets'] ?? { links: 0, smallTargets: 0 },
        horizontalScroll: (result.data as Record<string, unknown>).horizontalScroll as MobileFriendlyResult['horizontalScroll'] ?? { detected: false },
        issues: (result.data as Record<string, unknown>).issues as MobileFriendlyResult['issues'] ?? [],
        recommendations: (result.data as Record<string, unknown>).recommendations as MobileFriendlyResult['recommendations'] ?? [],
        analyzedAt: result.analyzedAt,
      }
    : null;

  return (
    <div className={cn("space-y-6", className)}>
      {/* Form Card */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <span>📱</span>
            Mobile-Friendly Checker
          </CardTitle>
          <CardDescription>
            Test how mobile-friendly your page is. Check viewport, tap targets, and responsiveness.
          </CardDescription>
        </CardHeader>
        <CardContent>
          {state !== "success" ? (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="mobile-url-input" className="text-slate-300">
                  URL to analyze
                </Label>
                <Input
                  id="mobile-url-input"
                  type="url"
                  value={url}
                  onChange={(e) => setUrl(e.target.value)}
                  placeholder="https://example.com"
                  disabled={isLoading}
                  className="bg-slate-800 border-slate-700 focus:border-blue-500"
                  aria-describedby={validationError ? "url-error" : undefined}
                  aria-invalid={!!validationError}
                />
              </div>

              {(validationError || error) && (
                <p
                  id="url-error"
                  className="text-sm text-red-400"
                  role="alert"
                >
                  {validationError || error?.message}
                </p>
              )}

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
                  "Analyze"
                )}
              </Button>
            </form>
          ) : (
            <div className="flex justify-end">
              <Button
                variant="outline"
                onClick={handleReset}
              >
                Analyze Another
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Loading State */}
      {state === "loading" && (
        <div className="flex items-center justify-center gap-3 py-8" role="status" aria-live="polite">
          <svg
            className="animate-spin h-6 w-6 text-blue-500"
            xmlns="http://www.w3.org/2000/svg"
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
          <span className="text-slate-400">Checking mobile-friendliness...</span>
        </div>
      )}

      {/* Error State */}
      {state === "error" && error && (
        <Card className="border-red-500/50 bg-red-500/10">
          <CardContent className="pt-6">
            <div className="flex flex-col items-center gap-4">
              <div className="flex items-center gap-2 text-red-400">
                <span>❌</span>
                <span>{error.message}</span>
              </div>
              <Button
                variant="outline"
                size="sm"
                onClick={retry}
                className="text-slate-300 border-slate-600 hover:bg-slate-800"
              >
                Try Again
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Results */}
      {state === "success" && mobileResult && (
        <MobileFriendlyResults
          result={mobileResult}
          url={analyzedUrl || undefined}
        />
      )}
    </div>
  );
}

export default MobileFriendlyChecker;
