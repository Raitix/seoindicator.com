"use client";

import * as React from "react";

import { Button } from "@/components/ui/button";
import { useOnboardingContext } from "@/lib/contexts/OnboardingContext";

interface Step5_CompleteProps {
  onComplete?: () => void;
}

export function Step5_Complete({ onComplete }: Step5_CompleteProps) {
  const { onboarding, completeOnboarding, isLoading, error } = useOnboardingContext();

  const handleFinish = async () => {
    try {
      await completeOnboarding();
      onComplete?.();
    } catch (err) {
      console.error("Failed to complete onboarding:", err);
    }
  };

  return (
    <div className="space-y-4 text-center">
      <div className="space-y-2">
        <h3 className="text-xl font-semibold">You're all set!</h3>
        <p className="text-muted-foreground">
          Your SEO tracking is now configured. You can always update your settings later.
        </p>
      </div>

      {onboarding?.websiteUrl && (
        <div className="bg-muted/50 rounded-lg p-4 text-left">
          <p className="text-sm text-muted-foreground">Website</p>
          <p className="font-medium">{onboarding.websiteUrl}</p>
        </div>
      )}

      {onboarding?.firstKeyword && (
        <div className="bg-muted/50 rounded-lg p-4 text-left">
          <p className="text-sm text-muted-foreground">First Keyword</p>
          <p className="font-medium">{onboarding.firstKeyword}</p>
        </div>
      )}

      {onboarding?.alertPreferences && (
        <div className="bg-muted/50 rounded-lg p-4 text-left">
          <p className="text-sm text-muted-foreground">Notifications</p>
          <p className="font-medium">
            {onboarding.alertPreferences.email ? "Email alerts enabled" : "Email alerts disabled"}
            {onboarding.alertPreferences.weekly ? ", weekly digest" : ""}
          </p>
        </div>
      )}

      {error && (
        <p className="text-sm text-destructive">{error}</p>
      )}

      <Button
        onClick={handleFinish}
        disabled={isLoading}
        className="w-full min-w-[200px]"
      >
        {isLoading ? "Finishing..." : "Start Tracking"}
      </Button>
    </div>
  );
}

export default Step5_Complete;
