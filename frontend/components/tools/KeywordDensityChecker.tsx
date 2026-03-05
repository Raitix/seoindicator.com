"use client";

import * as React from "react";
import {
  KeywordDensityForm,
  type KeywordDensityFormData,
} from "./KeywordDensityForm";
import {
  KeywordDensityResults,
  type KeywordDensityResult,
  type KeywordWarning,
} from "./KeywordDensityResults";
import {
  analyzeKeywordDensity,
  getDensityStatus,
} from "@/lib/utils/keywordDensityAnalyzer";
import { analyzeHeadings } from "@/lib/utils/headingAnalyzer";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";

interface KeywordDensityCheckerProps {
  className?: string;
  initialUrl?: string;
  initialHtml?: string;
  initialKeyword?: string;
  onAnalysisComplete?: (result: KeywordDensityResult) => void;
}

export function KeywordDensityChecker({
  className,
  initialUrl,
  initialHtml,
  initialKeyword,
  onAnalysisComplete,
}: KeywordDensityCheckerProps) {
  const [isLoading, setIsLoading] = React.useState(false);
  const [result, setResult] = React.useState<KeywordDensityResult | null>(null);
  const [error, setError] = React.useState<string | null>(null);
  const [analyzedUrl, setAnalyzedUrl] = React.useState<string | null>(null);

  const handleAnalyze = async (data: KeywordDensityFormData) => {
    setIsLoading(true);
    setError(null);

    try {
      let htmlContent = data.html || "";
      let url = data.url || "";

      // If URL is provided, fetch content via API proxy
      if (url && !htmlContent) {
        // TODO: Use API proxy from TASK-16-3
        // For now, show a message that URL analysis requires the backend
        throw new Error(
          "URL analysis requires the backend API proxy. Please paste HTML content instead, or wait for the backend to be deployed."
        );
      }

      // Analyze keyword density
      const densityResult = analyzeKeywordDensity(htmlContent, data.keyword);
      const status = getDensityStatus(densityResult.density);

      // Analyze headings
      const headingResult = analyzeHeadings(htmlContent, data.keyword);

      // Generate warnings
      const warnings: KeywordWarning[] = [];

      // Density warnings
      if (status === "low") {
        warnings.push({
          type: "warning",
          message: `Keyword density is too low (${densityResult.density.toFixed(
            1
          )}%). Consider adding more instances of "${data.keyword}" naturally throughout your content.`,
        });
      } else if (status === "over-optimized") {
        warnings.push({
          type: "warning",
          message: `Keyword density is too high (${densityResult.density.toFixed(
            1
          )}%). This may be seen as keyword stuffing. Consider reducing keyword usage or adding more content.`,
        });
      } else {
        warnings.push({
          type: "success",
          message: `Keyword density is optimal (${densityResult.density.toFixed(
            1
          )}%). Good job!`,
        });
      }

      // Heading warnings
      headingResult.warnings.forEach((w) => {
        warnings.push({
          type: "warning",
          message: w,
        });
      });

      // Heading keyword warnings
      const headingsWithKeyword = headingResult.headings.filter(
        (h) => h.keywordFound
      );
      const headingsWithoutKeyword = headingResult.headings.filter(
        (h) => !h.keywordFound
      );

      if (headingResult.headings.length > 0 && headingsWithKeyword.length === 0) {
        warnings.push({
          type: "info",
          message: `Keyword "${data.keyword}" not found in any headings. Consider adding it to your H1 or H2 tags for better SEO.`,
        });
      } else if (
        headingsWithoutKeyword.length > 0 &&
        headingsWithKeyword.length > 0
      ) {
        headingsWithoutKeyword.slice(0, 3).forEach((h) => {
          warnings.push({
            type: "info",
            message: `Consider adding "${data.keyword}" to the ${h.tag.toUpperCase()} heading: "${h.text}"`,
          });
        });
      }

      const finalResult: KeywordDensityResult = {
        keyword: data.keyword,
        density: densityResult.density,
        occurrences: densityResult.occurrences,
        totalWords: densityResult.totalWords,
        positions: densityResult.positions,
        status,
        headings: headingResult.headings,
        warnings,
        analyzedAt: new Date().toISOString(),
      };

      setResult(finalResult);
      setAnalyzedUrl(data.url || null);
      onAnalysisComplete?.(finalResult);
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "An unexpected error occurred"
      );
    } finally {
      setIsLoading(false);
    }
  };

  const handleExport = () => {
    if (!result) return;

    const exportData = {
      ...result,
      exportedAt: new Date().toISOString(),
    };

    const blob = new Blob([JSON.stringify(exportData, null, 2)], {
      type: "application/json",
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `keyword-density-${result.keyword.replace(/\s+/g, "-")}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  const handleReset = () => {
    setResult(null);
    setError(null);
    setAnalyzedUrl(null);
  };

  return (
    <div className={cn("space-y-6", className)}>
      {/* Form Card */}
      <Card>
        <CardHeader>
          <CardTitle>Keyword Density Checker</CardTitle>
        </CardHeader>
        <CardContent>
          <KeywordDensityForm
            onAnalyze={handleAnalyze}
            isLoading={isLoading}
            initialUrl={initialUrl}
            initialHtml={initialHtml}
            initialKeyword={initialKeyword}
          />
        </CardContent>
      </Card>

      {/* Error Message */}
      {error && (
        <Card className="border-red-500/50 bg-red-500/10">
          <CardContent className="pt-6">
            <p className="text-red-400 flex items-center gap-2">
              <span>❌</span>
              {error}
            </p>
          </CardContent>
        </Card>
      )}

      {/* Results */}
      {result && (
        <>
          <div className="flex justify-end">
            <button
              onClick={handleReset}
              className="text-sm text-slate-400 hover:text-white transition-colors"
            >
              Start New Analysis
            </button>
          </div>
          <KeywordDensityResults 
            result={result} 
            url={analyzedUrl || undefined}
            onExport={handleExport} 
          />
        </>
      )}
    </div>
  );
}

export default KeywordDensityChecker;
