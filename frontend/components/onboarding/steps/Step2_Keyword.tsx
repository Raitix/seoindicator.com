"use client";

import * as React from "react";

import { Button } from "@/components/ui/button";
import { useOnboardingContext } from "@/lib/contexts/OnboardingContext";

const KEYWORD_MAX_LENGTH = 100;

interface Step2_KeywordProps {
  onNext?: () => void;
}

export function Step2_Keyword({ onNext }: Step2_KeywordProps) {
  const { onboarding, updateStep, isLoading, error } = useOnboardingContext();
  const [keyword, setKeyword] = React.useState(onboarding?.firstKeyword ?? "");
  const [validationError, setValidationError] = React.useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setValidationError(null);

    const trimmed = keyword.trim();

    if (!trimmed) {
      setValidationError("Please enter a keyword");
      return;
    }

    if (trimmed.length > KEYWORD_MAX_LENGTH) {
      setValidationError(`Keyword must be ${KEYWORD_MAX_LENGTH} characters or less`);
      return;
    }

    try {
      await updateStep(2, { first_keyword: trimmed });
      onNext?.();
    } catch (err) {
      console.error("Failed to save keyword:", err);
    }
  };

  const displayError = validationError || error;

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <h3 className="text-lg font-medium">Add your first keyword</h3>
        <p className="text-sm text-muted-foreground">
          Start tracking your ranking for this keyword on your website.
        </p>
      </div>

      <div className="space-y-2">
        <label htmlFor="keyword" className="text-sm font-medium">
          Keyword
        </label>
        <input
          id="keyword"
          type="text"
          placeholder="e.g., seo tools"
          value={keyword}
          onChange={(e) => setKeyword(e.target.value)}
          aria-required="true"
          aria-invalid={!!displayError}
          aria-describedby={displayError ? "keyword-error" : undefined}
          className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          disabled={isLoading}
        />
        {displayError && (
          <p 
            id="keyword-error" 
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
          disabled={isLoading || !keyword.trim()}
          className="min-w-[120px]"
        >
          {isLoading ? "Saving..." : "Continue"}
        </Button>
      </div>
    </form>
  );
}

export default Step2_Keyword;
