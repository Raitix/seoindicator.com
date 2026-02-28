"use client";

import * as React from "react";

import { Button } from "@/components/ui/button";
import { useOnboardingContext } from "@/lib/contexts/OnboardingContext";

// SSRF blocked patterns
const SSRF_BLOCKED_PATTERNS = [
  /^https?:\/\/localhost/i,
  /^https?:\/\/127\./i,
  /^https?:\/\/10\./i,
  /^https?:\/\/172\.(1[6-9]|2[0-9]|3[01])\./i,
  /^https?:\/\/192\.168\./i,
  /^https?:\/\/169\.254\./i,
  /^https?:\/\/\[?::1\]?/i,
  /^https?:\/\/\[?fc/i,
  /^https?:\/\/\[?fd/i,
  /^file:\/\//i,
  /^gopher:\/\//i,
  /^ftp:\/\//i,
];

const URL_MAX_LENGTH = 500;

interface Step1_WebsiteProps {
  onNext?: () => void;
}

export function Step1_Website({ onNext }: Step1_WebsiteProps) {
  const { onboarding, updateStep, isLoading, error } = useOnboardingContext();
  const [url, setUrl] = React.useState(onboarding?.websiteUrl ?? "");
  const [validationError, setValidationError] = React.useState<string | null>(null);
  const [touched, setTouched] = React.useState(false);

  // Validate URL
  const validateUrl = (value: string): string | null => {
    if (!value) {
      return "Please enter a website URL";
    }

    if (value.length > URL_MAX_LENGTH) {
      return `URL must be ${URL_MAX_LENGTH} characters or less`;
    }

    try {
      const parsed = new URL(value);
      
      // Check for SSRF
      for (const pattern of SSRF_BLOCKED_PATTERNS) {
        if (pattern.test(value)) {
          return "This URL is not allowed. Please use a public website URL.";
        }
      }
    } catch {
      return "Please enter a valid URL (e.g., https://example.com)";
    }

    return null;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setValidationError(null);

    const err = validateUrl(url);
    if (err) {
      setValidationError(err);
      return;
    }

    try {
      await updateStep(1, { website_url: url });
      onNext?.();
    } catch (err) {
      console.error("Failed to save website URL:", err);
    }
  };

  const handleBlur = () => {
    setTouched(true);
    if (url) {
      const err = validateUrl(url);
      setValidationError(err);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setUrl(e.target.value);
    if (touched) {
      const err = validateUrl(e.target.value);
      setValidationError(err);
    }
  };

  const displayError = validationError || error;

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <h3 className="text-lg font-medium">Welcome to SEOIndicator!</h3>
        <p className="text-sm text-muted-foreground">
          We'll track rankings for pages on this site.
        </p>
      </div>

      <div className="space-y-2">
        <label htmlFor="website-url" className="text-sm font-medium">
          Website URL
        </label>
        <input
          id="website-url"
          type="url"
          placeholder="https://example.com"
          value={url}
          onChange={handleChange}
          onBlur={handleBlur}
          aria-required="true"
          aria-invalid={!!displayError}
          aria-describedby={displayError ? "website-url-error" : undefined}
          className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          disabled={isLoading}
        />
        {displayError && (
          <p 
            id="website-url-error" 
            role="alert" 
            className="text-sm text-destructive"
          >
            {displayError}
          </p>
        )}
      </div>

      <div className="flex justify-end gap-2">
        <Button
          type="submit"
          disabled={isLoading || !url}
          className="min-w-[120px]"
        >
          {isLoading ? "Saving..." : "Continue"}
        </Button>
      </div>
    </form>
  );
}

export default Step1_Website;
