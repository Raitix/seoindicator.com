import { render, screen, waitFor, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import {
  OnboardingProvider,
  useOnboardingContext,
  useOnboardingStep,
  useOnboardingStatus,
} from "@/lib/contexts/OnboardingContext";
import type { UserOnboarding } from "@/lib/types/onboarding";

// Mock SWR
const mockMutate = jest.fn();
jest.mock("swr", () => ({
  __esModule: true,
  default: jest.fn(),
  mutate: jest.fn(),
}));

// Mock the onboarding API
jest.mock("@/lib/api/onboarding", () => ({
  useOnboarding: jest.fn(),
  startOnboarding: jest.fn(),
  updateOnboardingStep: jest.fn(),
  completeOnboarding: jest.fn(),
  skipOnboarding: jest.fn(),
}));

import { useOnboarding, startOnboarding, updateOnboardingStep, completeOnboarding, skipOnboarding } from "@/lib/api/onboarding";

const mockOnboardingData: UserOnboarding = {
  id: 1,
  currentStep: 2,
  completedSteps: [1],
  websiteUrl: "https://example.com",
  firstKeyword: null,
  keywordId: null,
  alertPreferences: null,
  completedAt: null,
  progressPercentage: 20,
};

// Test component to access context
function TestComponent() {
  const context = useOnboardingContext();
  return (
    <div>
      <span data-testid="current-step">{context.onboarding?.currentStep}</span>
      <span data-testid="is-active">{context.isActive.toString()}</span>
      <span data-testid="is-loading">{context.isLoading.toString()}</span>
      <span data-testid="error">{context.error ?? "none"}</span>
      <button onClick={() => context.startOnboarding()} data-testid="start-btn">
        Start
      </button>
      <button onClick={() => context.updateStep(1, { website_url: "https://test.com" })} data-testid="update-btn">
        Update
      </button>
      <button onClick={() => context.completeOnboarding()} data-testid="complete-btn">
        Complete
      </button>
      <button onClick={() => context.skipOnboarding()} data-testid="skip-btn">
        Skip
      </button>
    </div>
  );
}

describe("OnboardingContext", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (useOnboarding as jest.Mock).mockReturnValue({
      onboarding: null,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });
  });

  describe("initial state", () => {
    it("provides null onboarding when not started", () => {
      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      expect(screen.getByTestId("current-step").textContent).toBe("");
      expect(screen.getByTestId("is-active").textContent).toBe("false");
    });

    it("provides loading state", () => {
      (useOnboarding as jest.Mock).mockReturnValue({
        onboarding: null,
        isLoading: true,
        error: null,
        mutate: mockMutate,
      });

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      expect(screen.getByTestId("is-loading").textContent).toBe("true");
    });

    it("provides error state", () => {
      (useOnboarding as jest.Mock).mockReturnValue({
        onboarding: null,
        isLoading: false,
        error: "Failed to fetch",
        mutate: mockMutate,
      });

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      expect(screen.getByTestId("error").textContent).toBe("Failed to fetch");
    });
  });

  describe("isActive calculation", () => {
    it("is active when onboarding exists but not completed", () => {
      (useOnboarding as jest.Mock).mockReturnValue({
        onboarding: mockOnboardingData,
        isLoading: false,
        error: null,
        mutate: mockMutate,
      });

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      expect(screen.getByTestId("is-active").textContent).toBe("true");
    });

    it("is not active when onboarding is completed", () => {
      (useOnboarding as jest.Mock).mockReturnValue({
        onboarding: { ...mockOnboardingData, completedAt: "2024-01-01T00:00:00Z" },
        isLoading: false,
        error: null,
        mutate: mockMutate,
      });

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      expect(screen.getByTestId("is-active").textContent).toBe("false");
    });
  });

  describe("actions", () => {
    it("calls startOnboarding and mutates state", async () => {
      (startOnboarding as jest.Mock).mockResolvedValue(mockOnboardingData);

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      await act(async () => {
        screen.getByTestId("start-btn").click();
      });

      expect(startOnboarding).toHaveBeenCalled();
      expect(mockMutate).toHaveBeenCalledWith(mockOnboardingData);
    });

    it("calls updateStep with correct parameters", async () => {
      const updatedData = { ...mockOnboardingData, currentStep: 2, completedSteps: [1] };
      (updateOnboardingStep as jest.Mock).mockResolvedValue(updatedData);
      (useOnboarding as jest.Mock).mockReturnValue({
        onboarding: mockOnboardingData,
        isLoading: false,
        error: null,
        mutate: mockMutate,
      });

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      await act(async () => {
        screen.getByTestId("update-btn").click();
      });

      expect(updateOnboardingStep).toHaveBeenCalledWith(1, { website_url: "https://test.com" });
    });

    it("calls completeOnboarding and updates state", async () => {
      (completeOnboarding as jest.Mock).mockResolvedValue({
        completedAt: "2024-01-01T00:00:00Z",
        keywordId: 1,
      });
      (useOnboarding as jest.Mock).mockReturnValue({
        onboarding: mockOnboardingData,
        isLoading: false,
        error: null,
        mutate: mockMutate,
      });

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      await act(async () => {
        screen.getByTestId("complete-btn").click();
      });

      expect(completeOnboarding).toHaveBeenCalled();
    });

    it("calls skipOnboarding and updates state", async () => {
      (skipOnboarding as jest.Mock).mockResolvedValue("2024-01-01T00:00:00Z");
      (useOnboarding as jest.Mock).mockReturnValue({
        onboarding: mockOnboardingData,
        isLoading: false,
        error: null,
        mutate: mockMutate,
      });

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      await act(async () => {
        screen.getByTestId("skip-btn").click();
      });

      expect(skipOnboarding).toHaveBeenCalled();
    });
  });

  describe("error handling", () => {
    it("sets error when startOnboarding fails", async () => {
      (startOnboarding as jest.Mock).mockRejectedValue(new Error("Network error"));

      render(
        <OnboardingProvider>
          <TestComponent />
        </OnboardingProvider>
      );

      await act(async () => {
        screen.getByTestId("start-btn").click();
      });

      // Error should be set
      await waitFor(() => {
        expect(screen.getByTestId("error").textContent).toBe("Network error");
      });
    });
  });
});

describe("useOnboardingStep hook", () => {
  it("returns current step information", () => {
    (useOnboarding as jest.Mock).mockReturnValue({
      onboarding: mockOnboardingData,
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    function StepTestComponent() {
      const { currentStep, completedSteps } = useOnboardingStep();
      return (
        <div>
          <span data-testid="step">{currentStep}</span>
          <span data-testid="completed">{completedSteps.join(",")}</span>
        </div>
      );
    }

    render(
      <OnboardingProvider>
        <StepTestComponent />
      </OnboardingProvider>
    );

    expect(screen.getByTestId("step").textContent).toBe("2");
    expect(screen.getByTestId("completed").textContent).toBe("1");
  });
});

describe("useOnboardingStatus hook", () => {
  it("returns completion status", () => {
    (useOnboarding as jest.Mock).mockReturnValue({
      onboarding: { ...mockOnboardingData, completedAt: "2024-01-01T00:00:00Z" },
      isLoading: false,
      error: null,
      mutate: mockMutate,
    });

    function StatusTestComponent() {
      const { isCompleted, isActive, progressPercentage } = useOnboardingStatus();
      return (
        <div>
          <span data-testid="completed">{isCompleted.toString()}</span>
          <span data-testid="active">{isActive.toString()}</span>
          <span data-testid="progress">{progressPercentage}</span>
        </div>
      );
    }

    render(
      <OnboardingProvider>
        <StatusTestComponent />
      </OnboardingProvider>
    );

    expect(screen.getByTestId("completed").textContent).toBe("true");
    expect(screen.getByTestId("active").textContent).toBe("false");
    expect(screen.getByTestId("progress").textContent).toBe("100");
  });
});
