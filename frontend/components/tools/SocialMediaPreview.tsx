"use client";

/**
 * SocialMediaPreview Component
 * TASK-16-8: Social Media Preview Tool
 *
 * Main component that combines the form and results for social media preview analysis.
 */

import * as React from "react";
import { cn } from "@/lib/utils";
import {
  SocialMediaPreviewForm,
  type SocialMediaPreviewFormData,
} from "./SocialMediaPreviewForm";
import {
  SocialMediaPreviewResults,
  type SocialMediaPreviewData,
} from "./SocialMediaPreviewResults";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface SocialMediaPreviewProps {
  className?: string;
  initialUrl?: string;
  onAnalysisComplete?: (result: SocialMediaPreviewData) => void;
}

export function SocialMediaPreview({
  className,
  initialUrl,
  onAnalysisComplete,
}: SocialMediaPreviewProps) {
  const [isLoading, setIsLoading] = React.useState(false);
  const [result, setResult] = React.useState<SocialMediaPreviewData | null>(null);
  const [error, setError] = React.useState<string | null>(null);

  const handleAnalyze = async (data: SocialMediaPreviewFormData) => {
    setIsLoading(true);
    setError(null);

    try {
      const response = await fetch("/api/v1/tools/analyze", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          tool: "social-media-preview",
          url: data.url,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(
          errorData.error || `HTTP ${response.status}: Failed to analyze URL`
        );
      }

      const responseData = await response.json();
      const analysisData = responseData.data || responseData;

      // Map the response to the expected format
      const previewData: SocialMediaPreviewData = {
        openGraph: {
          title: analysisData.openGraph?.title ?? null,
          description: analysisData.openGraph?.description ?? null,
          image: analysisData.openGraph?.image ?? null,
          url: analysisData.openGraph?.url ?? null,
          type: analysisData.openGraph?.type ?? null,
          siteName: analysisData.openGraph?.siteName ?? null,
          imageWidth: analysisData.openGraph?.imageWidth ?? null,
          imageHeight: analysisData.openGraph?.imageHeight ?? null,
          locale: analysisData.openGraph?.locale ?? null,
        },
        twitterCard: {
          card: analysisData.twitterCard?.card ?? null,
          site: analysisData.twitterCard?.site ?? null,
          creator: analysisData.twitterCard?.creator ?? null,
          title: analysisData.twitterCard?.title ?? null,
          description: analysisData.twitterCard?.description ?? null,
          image: analysisData.twitterCard?.image ?? null,
          player: analysisData.twitterCard?.player ?? null,
          playerWidth: analysisData.twitterCard?.playerWidth ?? null,
          playerHeight: analysisData.twitterCard?.playerHeight ?? null,
        },
        previews: {
          facebook: analysisData.previews?.facebook ?? {
            title: null,
            description: null,
            image: null,
            domain: null,
          },
          twitter: analysisData.previews?.twitter ?? {
            card: null,
            title: null,
            description: null,
            image: null,
          },
          linkedin: analysisData.previews?.linkedin ?? {
            title: null,
            description: null,
            image: null,
          },
        },
        missing: analysisData.missing ?? [],
        warnings: analysisData.warnings ?? [],
      };

      setResult(previewData);
      onAnalysisComplete?.(previewData);
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "An unexpected error occurred"
      );
    } finally {
      setIsLoading(false);
    }
  };

  const handleReset = () => {
    setResult(null);
    setError(null);
  };

  return (
    <div className={cn("space-y-6", className)}>
      {/* Form Card */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <span>📱</span>
            Social Media Preview
          </CardTitle>
          <p className="text-sm text-slate-400">
            See how your page appears when shared on Facebook, Twitter, and LinkedIn.
            Enter a URL to analyze Open Graph and Twitter Card tags.
          </p>
        </CardHeader>
        <CardContent>
          <SocialMediaPreviewForm
            onAnalyze={handleAnalyze}
            isLoading={isLoading}
            initialUrl={initialUrl}
          />
        </CardContent>
      </Card>

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
          <SocialMediaPreviewResults data={result} error={error ?? undefined} />
        </>
      )}

      {/* Error Message */}
      {error && !result && (
        <Card className="border-red-500/50 bg-red-500/10">
          <CardContent className="pt-6">
            <p className="text-red-400 flex items-center gap-2">
              <span>❌</span>
              {error}
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

export default SocialMediaPreview;
