"use client";

import * as React from "react";
import { X } from "lucide-react";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";

import { useOnboardingContext } from "@/lib/contexts/OnboardingContext";
import type { OnboardingStep } from "@/lib/types/onboarding";

// Step definitions
const ONBOARDING_STEPS: OnboardingStep[] = [
  {
    id: 1,
    title: "Your Website",
    description: "Add your website URL to start tracking",
    isOptional: false,
  },
  {
    id: 2,
    title: "First Keyword",
    description: "Add your first keyword to track rankings",
    isOptional: false,
  },
  {
    id: 3,
    title: "Competitors",
    description: "Add competitor domains for comparison",
    isOptional: true,
  },
  {
    id: 4,
    title: "Alerts",
    description: "Configure email notifications",
    isOptional: true,
  },
  {
    id: 5,
    title: "All Done!",
    description: "Your setup is complete",
    isOptional: false,
  },
];

interface OnboardingModalProps {
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  onComplete?: () => void;
  onSkip?: () => void;
}

export function OnboardingModal({
  open: controlledOpen,
  onOpenChange,
  onComplete,
  onSkip,
}: OnboardingModalProps) {
  const {
    onboarding,
    isActive,
    skipOnboarding,
    isLoading,
  } = useOnboardingContext();

  const [internalOpen, setInternalOpen] = React.useState(false);
  const open = controlledOpen ?? internalOpen;
  const setOpen = onOpenChange ?? setInternalOpen;

  // Auto-open if onboarding is active
  React.useEffect(() => {
    if (isActive && onboarding?.currentStep && onboarding.currentStep <= 5) {
      setOpen(true);
    }
  }, [isActive, onboarding?.currentStep, setOpen]);

  const currentStep = onboarding?.currentStep ?? 1;
  const progress = onboarding?.progressPercentage ?? 0;

  const handleSkip = async () => {
    try {
      await skipOnboarding();
      setOpen(false);
      onSkip?.();
    } catch (error) {
      console.error("Failed to skip onboarding:", error);
    }
  };

  const handleComplete = () => {
    setOpen(false);
    onComplete?.();
  };

  if (!open) return null;

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent className="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle className="text-xl font-semibold">
            Welcome to SEOIndicator!
          </DialogTitle>
          <DialogDescription>
            Let's get you set up. This will only take a minute.
          </DialogDescription>
        </DialogHeader>

        {/* Progress bar */}
        <div className="space-y-2">
          <div className="flex justify-between text-sm text-muted-foreground">
            <span>Step {currentStep} of 5</span>
            <span>{progress}% complete</span>
          </div>
          <Progress value={progress} className="h-2" />
        </div>

        {/* Step indicators */}
        <div className="flex justify-between py-4">
          {ONBOARDING_STEPS.map((step) => (
            <div
              key={step.id}
              className={cn(
                "flex flex-col items-center space-y-1",
                currentStep >= step.id ? "text-primary" : "text-muted-foreground"
              )}
            >
              <div
                className={cn(
                  "w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium border-2",
                  currentStep === step.id
                    ? "border-primary bg-primary text-primary-foreground"
                    : onboarding?.completedSteps?.includes(step.id)
                    ? "border-primary bg-primary/10 text-primary"
                    : "border-muted-foreground/30"
                )}
              >
                {onboarding?.completedSteps?.includes(step.id) ? "✓" : step.id}
              </div>
              <span className="text-xs hidden sm:block">{step.title}</span>
            </div>
          ))}
        </div>

        {/* Step content will be rendered by children */}
        <div className="min-h-[200px]">
          <OnboardingStepContent
            step={currentStep}
            onComplete={handleComplete}
          />
        </div>

        {/* Skip button */}
        <div className="flex justify-between pt-4 border-t">
          <Button
            variant="ghost"
            onClick={handleSkip}
            disabled={isLoading}
            className="text-muted-foreground"
          >
            Skip for now
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

// Step content component
interface OnboardingStepContentProps {
  step: number;
  onComplete: () => void;
}

function OnboardingStepContent({ step, onComplete }: OnboardingStepContentProps) {
  const { onboarding, updateStep, completeOnboarding, isLoading, error } = useOnboardingContext();

  // Step 1: Website URL
  if (step === 1) {
    return <WebsiteUrlStep />;
  }

  // Step 2: First Keyword
  if (step === 2) {
    return <FirstKeywordStep />;
  }

  // Step 3: Competitors (optional - skip for MVP)
  if (step === 3) {
    return <CompetitorsStep />;
  }

  // Step 4: Alert Preferences
  if (step === 4) {
    return <AlertPreferencesStep />;
  }

  // Step 5: Completion
  if (step === 5) {
    return <CompletionStep onComplete={onComplete} />;
  }

  return null;
}

// Step 1: Website URL
function WebsiteUrlStep() {
  const { onboarding, updateStep, isLoading, error } = useOnboardingContext();
  const [url, setUrl] = React.useState(onboarding?.websiteUrl ?? "");
  const [validationError, setValidationError] = React.useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setValidationError(null);

    // Basic validation
    if (!url) {
      setValidationError("Please enter a website URL");
      return;
    }

    try {
      new URL(url);
    } catch {
      setValidationError("Please enter a valid URL (e.g., https://example.com)");
      return;
    }

    try {
      await updateStep(1, { website_url: url });
    } catch (err) {
      console.error("Failed to save website URL:", err);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <label htmlFor="website-url" className="text-sm font-medium">
          What's your website URL?
        </label>
        <input
          id="website-url"
          type="url"
          placeholder="https://example.com"
          value={url}
          onChange={(e) => setUrl(e.target.value)}
          className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          disabled={isLoading}
        />
        {(validationError || error) && (
          <p className="text-sm text-destructive">{validationError || error}</p>
        )}
        <p className="text-sm text-muted-foreground">
          We'll use this to track your SEO performance.
        </p>
      </div>
      <Button type="submit" disabled={isLoading} className="w-full">
        {isLoading ? "Saving..." : "Continue"}
      </Button>
    </form>
  );
}

// Step 2: First Keyword
function FirstKeywordStep() {
  const { onboarding, updateStep, isLoading, error } = useOnboardingContext();
  const [keyword, setKeyword] = React.useState(onboarding?.firstKeyword ?? "");

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!keyword.trim()) {
      return;
    }

    try {
      await updateStep(2, { first_keyword: keyword.trim() });
    } catch (err) {
      console.error("Failed to save keyword:", err);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <label htmlFor="keyword" className="text-sm font-medium">
          What's the first keyword you'd like to track?
        </label>
        <input
          id="keyword"
          type="text"
          placeholder="e.g., seo tools"
          value={keyword}
          onChange={(e) => setKeyword(e.target.value)}
          className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          disabled={isLoading}
        />
        {error && <p className="text-sm text-destructive">{error}</p>}
        <p className="text-sm text-muted-foreground">
          Start tracking your ranking for this keyword.
        </p>
      </div>
      <Button type="submit" disabled={isLoading || !keyword.trim()} className="w-full">
        {isLoading ? "Saving..." : "Continue"}
      </Button>
    </form>
  );
}

// Step 3: Competitors (placeholder for MVP)
function CompetitorsStep() {
  const { updateStep, isLoading, error } = useOnboardingContext();

  const handleSkip = async () => {
    try {
      // Mark step as complete without adding competitors
      await updateStep(3, {});
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
      <div className="flex gap-2">
        <Button variant="outline" onClick={handleSkip} disabled={isLoading}>
          Skip for now
        </Button>
      </div>
      {error && <p className="text-sm text-destructive">{error}</p>}
    </div>
  );
}

// Step 4: Alert Preferences
function AlertPreferencesStep() {
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
    } catch (err) {
      console.error("Failed to save preferences:", err);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-4">
        <h3 className="text-lg font-medium">Notification preferences</h3>
        
        <div className="flex items-center justify-between">
          <div>
            <p className="font-medium">Email alerts</p>
            <p className="text-sm text-muted-foreground">
              Get notified about significant ranking changes
            </p>
          </div>
          <input
            type="checkbox"
            checked={emailAlerts}
            onChange={(e) => setEmailAlerts(e.target.checked)}
            className="h-4 w-4"
            disabled={isLoading}
          />
        </div>

        <div className="flex items-center justify-between">
          <div>
            <p className="font-medium">Weekly digest</p>
            <p className="text-sm text-muted-foreground">
              Receive a weekly summary of your SEO performance
            </p>
          </div>
          <input
            type="checkbox"
            checked={weeklyDigest}
            onChange={(e) => setWeeklyDigest(e.target.checked)}
            className="h-4 w-4"
            disabled={isLoading}
          />
        </div>
      </div>

      {error && <p className="text-sm text-destructive">{error}</p>}

      <Button type="submit" disabled={isLoading} className="w-full">
        {isLoading ? "Saving..." : "Continue"}
      </Button>
    </form>
  );
}

// Step 5: Completion
function CompletionStep({ onComplete }: { onComplete: () => void }) {
  const { onboarding, completeOnboarding, isLoading, error } = useOnboardingContext();

  const handleFinish = async () => {
    try {
      await completeOnboarding();
      onComplete();
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

      {error && <p className="text-sm text-destructive">{error}</p>}

      <Button onClick={handleFinish} disabled={isLoading} className="w-full">
        {isLoading ? "Finishing..." : "Start Tracking"}
      </Button>
    </div>
  );
}

export default OnboardingModal;
