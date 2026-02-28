"use client";

import * as React from "react";

import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { useOnboardingContext } from "@/lib/contexts/OnboardingContext";

interface Step4_AlertsProps {
  onNext?: () => void;
}

export function Step4_Alerts({ onNext }: Step4_AlertsProps) {
  const { onboarding, updateStep, isLoading, error } = useOnboardingContext();
  const [emailAlerts, setEmailAlerts] = React.useState(
    onboarding?.alertPreferences?.email ?? true
  );
  const [weeklyDigest, setWeeklyDigest] = React.useState(
    onboarding?.alertPreferences?.weekly ?? true
  );

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      await updateStep(4, {
        alert_preferences: {
          email: emailAlerts,
          weekly: weeklyDigest,
        },
      });
      onNext?.();
    } catch (err) {
      console.error("Failed to save preferences:", err);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <h3 className="text-lg font-medium">Notification preferences</h3>
        <p className="text-sm text-muted-foreground">
          Configure how you want to receive updates about your SEO performance.
        </p>
      </div>

      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="space-y-0.5">
            <p className="font-medium">Email alerts</p>
            <p className="text-sm text-muted-foreground">
              Get notified about significant ranking changes
            </p>
          </div>
          <Switch
            checked={emailAlerts}
            onCheckedChange={setEmailAlerts}
            disabled={isLoading}
          />
        </div>

        <div className="flex items-center justify-between">
          <div className="space-y-0.5">
            <p className="font-medium">Weekly digest</p>
            <p className="text-sm text-muted-foreground">
              Receive a weekly summary of your SEO performance
            </p>
          </div>
          <Switch
            checked={weeklyDigest}
            onCheckedChange={setWeeklyDigest}
            disabled={isLoading}
          />
        </div>
      </div>

      {error && (
        <p className="text-sm text-destructive">{error}</p>
      )}

      <div className="flex justify-end gap-2">
        <Button
          type="submit"
          disabled={isLoading}
          className="min-w-[120px]"
        >
          {isLoading ? "Saving..." : "Continue"}
        </Button>
      </div>
    </form>
  );
}

export default Step4_Alerts;
