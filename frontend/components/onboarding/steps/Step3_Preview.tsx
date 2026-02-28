"use client";

import * as React from "react";

import { Button } from "@/components/ui/button";
import { useOnboardingContext } from "@/lib/contexts/OnboardingContext";

interface Step3_PreviewProps {
  onNext?: () => void;
}

export function Step3_Preview({ onNext }: Step3_PreviewProps) {
  const { onboarding, updateStep, isLoading, error } = useOnboardingContext();

  const handleSkip = async () => {
    try {
      // Mark step as complete without adding competitors
      await updateStep(3, {});
      onNext?.();
    } catch (err) {
      console.error("Failed to skip competitors step:", err);
    }
  };

  return (
    <div className="space-y-4">
      <div className="space-y-2">
        <h3 className="text-lg font-medium">Track your competitors</h3>
        <p className="text-sm text-muted-foreground">
          Add competitor domains to compare your SEO performance. You can add these later from the Competitors section.
        </p>
      </div>

      {onboarding?.websiteUrl && (
        <div className="bg-muted/50 rounded-lg p-4">
          <p className="text-sm text-muted-foreground">Your website</p>
          <p className="font-medium">{onboarding.websiteUrl}</p>
        </div>
      )}

      {onboarding?.firstKeyword && (
        <div className="bg-muted/50 rounded-lg p-4">
          <p className="text-sm text-muted-foreground">Tracking keyword</p>
          <p className="font-medium">{onboarding.firstKeyword}</p>
        </div>
      )}

      {error && (
        <p className="text-sm text-destructive">{error}</p>
      )}

      <div className="flex justify-end gap-2">
        <Button
          variant="outline"
          onClick={handleSkip}
          disabled={isLoading}
        >
          {isLoading ? "Saving..." : "Skip for now"}
        </Button>
      </div>
    </div>
  );
}

export default Step3_Preview;
