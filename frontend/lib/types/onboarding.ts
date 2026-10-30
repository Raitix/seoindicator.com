/**
 * Types for the User Onboarding feature (FEAT-017)
 */

export interface AlertPreferences {
  email: boolean;
  weekly: boolean;
}

export interface UserOnboarding {
  id: number;
  currentStep: number;
  completedSteps: number[];
  websiteUrl: string | null;
  firstKeyword: string | null;
  keywordId: number | null;
  alertPreferences: AlertPreferences | null;
  completedAt: string | null;
  progressPercentage: number;
}

export interface OnboardingProgressResponse {
  success: boolean;
  data: UserOnboarding | null;
}

export interface OnboardingStartResponse {
  success: boolean;
  data: UserOnboarding;
}

export interface OnboardingStepPayload {
  step: number;
  data: {
    website_url?: string;
    first_keyword?: string;
    alert_preferences?: AlertPreferences;
  };
}

export interface OnboardingCompleteResponse {
  success: boolean;
  data: {
    completed_at: string;
    keyword_id: number | null;
  };
}

export interface OnboardingSkipResponse {
  success: boolean;
  data: {
    skipped_at: string;
  };
}

export interface OnboardingErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

// Context value type
export interface OnboardingContextValue {
  // State
  onboarding: UserOnboarding | null;
  isLoading: boolean;
  error: string | null;
  isActive: boolean; // true if onboarding should be shown (not completed/skipped)
  
  // Actions
  startOnboarding: () => Promise<void>;
  updateStep: (step: number, data: OnboardingStepPayload['data']) => Promise<void>;
  completeOnboarding: () => Promise<void>;
  skipOnboarding: () => Promise<void>;
  refreshOnboarding: () => Promise<void>;
}

// Wizard step configuration
export interface OnboardingStep {
  id: number;
  title: string;
  description: string;
  isOptional: boolean;
}
