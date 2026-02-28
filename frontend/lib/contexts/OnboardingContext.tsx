"use client";

import * as React from "react";

import type { OnboardingContextValue, UserOnboarding } from "@/lib/types/onboarding";
import {
  useOnboarding,
  startOnboarding,
  updateOnboardingStep,
  completeOnboarding as apiCompleteOnboarding,
  skipOnboarding as apiSkipOnboarding,
} from "@/lib/api/onboarding";

const OnboardingContext = React.createContext<OnboardingContextValue | null>(null);

interface OnboardingProviderProps {
  children: React.ReactNode;
  /** If true, will automatically show onboarding for new users */
  autoStart?: boolean;
}

export function OnboardingProvider({ children, autoStart = true }: OnboardingProviderProps) {
  const { onboarding, isLoading, error, mutate } = useOnboarding();
  const [localError, setLocalError] = React.useState<string | null>(null);

  // Determine if onboarding should be shown
  const isActive = React.useMemo(() => {
    if (!onboarding) return false;
    // Show if not completed and not skipped
    return !onboarding.completedAt && !onboarding.firstKeyword;
  }, [onboarding]);

  // Start onboarding
  const handleStartOnboarding = React.useCallback(async () => {
    try {
      setLocalError(null);
      const result = await startOnboarding();
      await mutate(result);
    } catch (err) {
      const message = err instanceof Error ? err.message : "Failed to start onboarding";
      setLocalError(message);
      throw err;
    }
  }, [mutate]);

  // Update a step
  const handleUpdateStep = React.useCallback(
    async (step: number, data: Record<string, unknown>) => {
      try {
        setLocalError(null);
        const result = await updateOnboardingStep(step, data);
        await mutate(result);
      } catch (err) {
        const message = err instanceof Error ? err.message : "Failed to update step";
        setLocalError(message);
        throw err;
      }
    },
    [mutate]
  );

  // Complete onboarding
  const handleCompleteOnboarding = React.useCallback(async () => {
    try {
      setLocalError(null);
      const result = await apiCompleteOnboarding();
      // Update the onboarding state to reflect completion
      if (onboarding) {
        await mutate({
          ...onboarding,
          completedAt: result.completedAt,
          keywordId: result.keywordId,
          progressPercentage: 100,
        });
      }
    } catch (err) {
      const message = err instanceof Error ? err.message : "Failed to complete onboarding";
      setLocalError(message);
      throw err;
    }
  }, [mutate, onboarding]);

  // Skip onboarding
  const handleSkipOnboarding = React.useCallback(async () => {
    try {
      setLocalError(null);
      const skippedAt = await apiSkipOnboarding();
      // Update the onboarding state to reflect skip
      if (onboarding) {
        await mutate({
          ...onboarding,
          // Mark as skipped by adding a flag (we'll use completedAt as a marker)
          completedAt: skippedAt,
        } as UserOnboarding);
      }
    } catch (err) {
      const message = err instanceof Error ? err.message : "Failed to skip onboarding";
      setLocalError(message);
      throw err;
    }
  }, [mutate, onboarding]);

  // Refresh onboarding state
  const handleRefreshOnboarding = React.useCallback(async () => {
    await mutate();
  }, [mutate]);

  const value: OnboardingContextValue = {
    onboarding,
    isLoading,
    error: error ?? localError,
    isActive,
    startOnboarding: handleStartOnboarding,
    updateStep: handleUpdateStep,
    completeOnboarding: handleCompleteOnboarding,
    skipOnboarding: handleSkipOnboarding,
    refreshOnboarding: handleRefreshOnboarding,
  };

  return (
    <OnboardingContext.Provider value={value}>
      {children}
    </OnboardingContext.Provider>
  );
}

export function useOnboardingContext(): OnboardingContextValue {
  const context = React.useContext(OnboardingContext);
  if (!context) {
    throw new Error("useOnboardingContext must be used within an OnboardingProvider");
  }
  return context;
}

// Hook for just the current step
export function useOnboardingStep() {
  const { onboarding, updateStep, isLoading, error } = useOnboardingContext();
  return {
    currentStep: onboarding?.currentStep ?? 1,
    completedSteps: onboarding?.completedSteps ?? [],
    updateStep,
    isLoading,
    error,
  };
}

// Hook for just the completion status
export function useOnboardingStatus() {
  const { onboarding, isActive, completeOnboarding, skipOnboarding } = useOnboardingContext();
  return {
    isCompleted: !!onboarding?.completedAt,
    isActive,
    progressPercentage: onboarding?.progressPercentage ?? 0,
    completeOnboarding,
    skipOnboarding,
  };
}

export { OnboardingContext };
