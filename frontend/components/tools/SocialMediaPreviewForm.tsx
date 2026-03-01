"use client";

/**
 * SocialMediaPreviewForm Component
 * TASK-16-8: Social Media Preview Tool
 *
 * Form component for entering URL to analyze social media preview data.
 */

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export interface SocialMediaPreviewFormData {
  url: string;
}

interface SocialMediaPreviewFormProps {
  onAnalyze: (data: SocialMediaPreviewFormData) => void;
  isLoading?: boolean;
  initialUrl?: string;
  className?: string;
}

/**
 * Validate URL format
 */
function validateUrl(value: string): string | null {
  if (!value) return "URL is required";
  try {
    const parsed = new URL(value);
    if (!["http:", "https:"].includes(parsed.protocol)) {
      return "URL must use HTTP or HTTPS protocol";
    }
    return null;
  } catch {
    return "Please enter a valid URL";
  }
}

export function SocialMediaPreviewForm({
  onAnalyze,
  isLoading = false,
  initialUrl = "",
  className,
}: SocialMediaPreviewFormProps) {
  const [url, setUrl] = React.useState(initialUrl);
  const [error, setError] = React.useState<string | null>(null);
  const inputRef = React.useRef<HTMLInputElement>(null);

  // Handle form submit
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const validationError = validateUrl(url);
    if (validationError) {
      setError(validationError);
      return;
    }
    setError(null);
    onAnalyze({ url });
  };

  // Handle input change - clear error
  const handleUrlChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setUrl(e.target.value);
    if (error) {
      setError(null);
    }
  };

  return (
    <form
      onSubmit={handleSubmit}
      data-testid="social-media-form"
      className={cn("space-y-4", className)}
    >
      <div className="space-y-2">
        <Label htmlFor="url-input" className="text-slate-300">
          Enter page URL
        </Label>
        <Input
          ref={inputRef}
          id="url-input"
          type="url"
          placeholder="https://example.com/page"
          value={url}
          onChange={handleUrlChange}
          disabled={isLoading}
          aria-describedby={error ? "url-error" : undefined}
          aria-invalid={!!error}
          className="bg-slate-800 border-slate-700 focus:border-emerald-500"
        />
        {error && (
          <span
            id="url-error"
            role="alert"
            className="text-sm text-red-400"
          >
            {error}
          </span>
        )}
      </div>
      <Button
        type="submit"
        disabled={isLoading}
        data-testid="analyze-button"
        className="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700"
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
          "Preview Social Shares"
        )}
      </Button>
    </form>
  );
}

export default SocialMediaPreviewForm;
