"use client";

import * as React from "react";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Loader2 } from "lucide-react";

import { useOnboardingContext } from "@/lib/contexts/OnboardingContext";
import { Step1_Website } from "./steps/Step1_Website";
import { Step2_Keyword } from "./steps/Step2_Keyword";
import { Step3_Preview } from "./steps/Step3_Preview";
import { Step4_Alerts } from "./steps/Step4_Alerts";
import { Step5_Complete } from "./steps/Step5_Complete";

interface OnboardingWizardProps {
  onComplete?: () => void;
  onSkip?: () => void;
}

export function OnboardingWizard({ onComplete, onSkip }: OnboardingWizardProps) {
  const {
    onboarding,
    isLoading,
    error,
    updateStep,
    skipOnboarding,
    completeOnboarding,
  } = useOnboardingContext();

  const currentStep = onboarding?.currentStep ?? 1;
  const progress = onboarding?.progressPercentage ?? 0;

  // Handle skip
  const handleSkip = async () => {
    try {
      await skipOnboarding();
      onSkip?.();
    } catch (err) {
      console.error("Failed to skip onboarding:", err);
    }
  };

  // Handle next step
  const handleNextStep = async (data: Record<string, unknown>) => {
    try {
      await updateStep(currentStep, data);
    } catch (err) {
      console.error("Failed to update step:", err);
    }
  };

  // Handle previous step (local state only)
  const handlePrevStep = () => {
    // Previous step is handled locally by the context
    // This is a no-op for the wizard since the context manages state
  };

  // Handle complete
  const handleComplete = async () => {
    try {
      await completeOnboarding();
      onComplete?.();
    } catch (err) {
      console.error("Failed to complete onboarding:", err);
    }
  };

  // Render current step
  const renderStep = () => {
    switch (currentStep) {
      case 1:
        return <Step1_Website />;
      case 2:
        return <Step2_Keyword />;
      case 3:
        return <Step3_Preview />;
      case 4:
        return <Step4_Alerts />;
      case 5:
        return <Step5_Complete onComplete={handleComplete} />;
      default:
        return <Step1_Website />;
    }
  };

  return (
    <div className="wizard-content transition" data-testid="onboarding-wizard">
      {/* Progress indicator */}
      <div className="mb-6">
        <div className="flex justify-between text-sm text-muted-foreground mb-2">
          <span data-testid="step-count">Step {currentStep} of 5</span>
          <span>{progress}%</span>
        </div>
        <Progress value={progress} className="h-2" />
      </div>

      {/* Error display */}
      {error && (
        <div 
          role="alert" 
          className="mb-4 p-3 bg-destructive/10 text-destructive rounded-md text-sm"
        >
          {error}
        </div>
      )}

      {/* Loading spinner */}
      {isLoading && (
        <div 
          data-testid="loading-spinner" 
          className="flex justify-center py-4"
        >
          <Loader2 className="h-6 w-6 animate-spin text-primary" />
        </div>
      )}

      {/* Step content */}
      <div className="wizard-step">
        {renderStep()}
      </div>

      {/* Navigation buttons */}
      <div className="flex justify-between mt-6 pt-4 border-t">
        <Button
          variant="outline"
          onClick={handlePrevStep}
          disabled={currentStep === 1 || isLoading}
        >
          Back
        </Button>

        <div className="flex gap-2">
          <Button
            variant="ghost"
            onClick={handleSkip}
            disabled={isLoading}
            className="text-muted-foreground"
          >
            Skip
          </Button>
        </div>
      </div>
    </div>
  );
}

export default OnboardingWizard;
