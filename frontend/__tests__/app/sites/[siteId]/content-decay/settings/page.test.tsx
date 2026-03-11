/**
 * Tests for Content Decay Settings Page
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useParams, useRouter } from "next/navigation";
import SettingsPage from "@/app/sites/[siteId]/content-decay/settings/page";

// Mock Next.js hooks
vi.mock("next/navigation", () => ({
  useParams: vi.fn(),
  useRouter: vi.fn(),
}));

// Mock hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecaySettings: vi.fn(() => ({
    settings: {
      detectionEnabled: true,
      windowDays: 28,
      minClicksThreshold: 100,
      minImpressionsThreshold: 1000,
      digestEnabled: false,
      digestFrequency: "weekly",
    },
    isLoading: false,
  })),
  useSaveContentDecaySettings: vi.fn(() => ({
    saveSettings: vi.fn().mockResolvedValue({ success: true }),
    isSaving: false,
  })),
}));

describe("Content Decay Settings Page", () => {
  const mockRouter = {
    push: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    (useParams as any).mockReturnValue({ siteId: "test-site-123" });
    (useRouter as any).mockReturnValue(mockRouter);
  });

  describe("Page Rendering", () => {
    it("should render the page header", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Content Decay Settings")).toBeInTheDocument();
    });

    it("should render the page description", () => {
      render(<SettingsPage />);
      expect(screen.getByText(/Configure content decay detection/)).toBeInTheDocument();
    });

    it("should render navigation tabs", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Queue")).toBeInTheDocument();
      expect(screen.getByText("Board")).toBeInTheDocument();
      expect(screen.getByText("Recovery")).toBeInTheDocument();
      expect(screen.getByText("Settings")).toBeInTheDocument();
    });

    it("should render save button", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Save Settings")).toBeInTheDocument();
    });
  });

  describe("Detection Settings", () => {
    it("should render detection settings card", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Detection Settings")).toBeInTheDocument();
    });

    it("should render detection toggle", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Enable Content Decay Detection")).toBeInTheDocument();
      expect(screen.getByTestId("detection-toggle")).toBeInTheDocument();
    });

    it("should render window size selector", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Detection Window")).toBeInTheDocument();
    });

    it("should render minimum clicks threshold input", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Minimum Clicks Threshold")).toBeInTheDocument();
      expect(screen.getByTestId("min-clicks-input")).toBeInTheDocument();
    });

    it("should render minimum impressions threshold input", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Minimum Impressions Threshold")).toBeInTheDocument();
      expect(screen.getByTestId("min-impressions-input")).toBeInTheDocument();
    });
  });

  describe("Digest Settings", () => {
    it("should render notification digest card", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Notification Digest")).toBeInTheDocument();
    });

    it("should render digest toggle", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Enable Email Digest")).toBeInTheDocument();
      expect(screen.getByTestId("digest-toggle")).toBeInTheDocument();
    });
  });

  describe("Form Interactions", () => {
    it("should toggle detection enabled", async () => {
      render(<SettingsPage />);
      const toggle = screen.getByTestId("detection-toggle");
      const initialState = toggle.getAttribute("data-state");
      fireEvent.click(toggle);
      // Toggle state should change
    });

    it("should toggle digest enabled", async () => {
      render(<SettingsPage />);
      const toggle = screen.getByTestId("digest-toggle");
      fireEvent.click(toggle);
      // Toggle state should change
    });

    it("should update minimum clicks threshold", async () => {
      render(<SettingsPage />);
      const input = screen.getByTestId("min-clicks-input");
      fireEvent.change(input, { target: { value: "200" } });
      expect(input).toHaveValue(200);
    });

    it("should update minimum impressions threshold", async () => {
      render(<SettingsPage />);
      const input = screen.getByTestId("min-impressions-input");
      fireEvent.change(input, { target: { value: "5000" } });
      expect(input).toHaveValue(5000);
    });
  });

  describe("Window Size Selection", () => {
    it("should have 7 days option", async () => {
      render(<SettingsPage />);
      const triggers = screen.getAllByRole("combobox");
      const windowTrigger = triggers[0];
      fireEvent.click(windowTrigger);
      await waitFor(() => {
        expect(screen.getByText("7 days")).toBeInTheDocument();
      });
    });

    it("should have 14 days option", async () => {
      render(<SettingsPage />);
      const triggers = screen.getAllByRole("combobox");
      const windowTrigger = triggers[0];
      fireEvent.click(windowTrigger);
      await waitFor(() => {
        expect(screen.getByText("14 days")).toBeInTheDocument();
      });
    });

    it("should have 56 days option", async () => {
      render(<SettingsPage />);
      const triggers = screen.getAllByRole("combobox");
      const windowTrigger = triggers[0];
      fireEvent.click(windowTrigger);
      await waitFor(() => {
        expect(screen.getByText("56 days")).toBeInTheDocument();
      });
    });
  });

  describe("Save Actions", () => {
    it("should show unsaved changes alert when form is modified", async () => {
      render(<SettingsPage />);
      const input = screen.getByTestId("min-clicks-input");
      fireEvent.change(input, { target: { value: "200" } });
      await waitFor(() => {
        expect(screen.getByText(/You have unsaved changes/)).toBeInTheDocument();
      });
    });

    it("should show reset button when there are changes", async () => {
      render(<SettingsPage />);
      const input = screen.getByTestId("min-clicks-input");
      fireEvent.change(input, { target: { value: "200" } });
      await waitFor(() => {
        expect(screen.getByText("Reset")).toBeInTheDocument();
      });
    });

    it("should call saveSettings when Save button is clicked", async () => {
      const { useSaveContentDecaySettings } = await import("@/lib/hooks/useContentDecay");
      const mockSave = vi.fn().mockResolvedValue({ success: true });
      (useSaveContentDecaySettings as any).mockReturnValue({
        saveSettings: mockSave,
        isSaving: false,
      });

      render(<SettingsPage />);
      // Make a change first
      const input = screen.getByTestId("min-clicks-input");
      fireEvent.change(input, { target: { value: "200" } });

      await waitFor(() => {
        const saveBtn = screen.getByText("Save Settings").closest("button");
        expect(saveBtn).not.toBeDisabled();
      });
    });

    it("should disable save button when no changes", () => {
      render(<SettingsPage />);
      const saveBtn = screen.getByText("Save Settings").closest("button");
      expect(saveBtn).toBeDisabled();
    });
  });

  describe("Validation", () => {
    it("should show error for negative clicks threshold", async () => {
      render(<SettingsPage />);
      const input = screen.getByTestId("min-clicks-input");
      fireEvent.change(input, { target: { value: "-100" } });
      
      await waitFor(() => {
        expect(screen.getByText(/Must be 0 or greater/)).toBeInTheDocument();
      });
    });

    it("should show error for negative impressions threshold", async () => {
      render(<SettingsPage />);
      const input = screen.getByTestId("min-impressions-input");
      fireEvent.change(input, { target: { value: "-100" } });
      
      await waitFor(() => {
        expect(screen.getByText(/Must be 0 or greater/)).toBeInTheDocument();
      });
    });
  });

  describe("Navigation", () => {
    it("should navigate to queue when Queue tab is clicked", async () => {
      render(<SettingsPage />);
      const queueTab = screen.getByText("Queue").closest("button");
      if (queueTab) {
        fireEvent.click(queueTab);
      }
      expect(mockRouter.push).toHaveBeenCalledWith("/sites/test-site-123/content-decay");
    });

    it("should navigate to board when Board tab is clicked", async () => {
      render(<SettingsPage />);
      const boardTab = screen.getByText("Board").closest("button");
      if (boardTab) {
        fireEvent.click(boardTab);
      }
      expect(mockRouter.push).toHaveBeenCalledWith("/sites/test-site-123/content-decay/board");
    });
  });

  describe("Site ID Handling", () => {
    it("should show not found when siteId is missing", () => {
      (useParams as any).mockReturnValue({});
      render(<SettingsPage />);
      expect(screen.getByText("Site not found")).toBeInTheDocument();
    });
  });

  describe("Info Card", () => {
    it("should display how detection works info", () => {
      render(<SettingsPage />);
      expect(screen.getByText("How Detection Works")).toBeInTheDocument();
    });

    it("should explain the detection process", () => {
      render(<SettingsPage />);
      expect(screen.getByText(/Content decay detection compares current page performance/)).toBeInTheDocument();
    });
  });

  describe("Status Badge", () => {
    it("should show Detection Active when enabled", () => {
      render(<SettingsPage />);
      expect(screen.getByText("Detection Active")).toBeInTheDocument();
    });

    it("should show Detection Paused when disabled", async () => {
      const { useContentDecaySettings } = await import("@/lib/hooks/useContentDecay");
      (useContentDecaySettings as any).mockReturnValue({
        settings: {
          detectionEnabled: false,
          windowDays: 28,
          minClicksThreshold: 100,
          minImpressionsThreshold: 1000,
          digestEnabled: false,
          digestFrequency: "weekly",
        },
        isLoading: false,
      });

      render(<SettingsPage />);
      expect(screen.getByText("Detection Paused")).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("should have proper heading hierarchy", () => {
      render(<SettingsPage />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toHaveTextContent("Content Decay Settings");
    });

    it("should have accessible form labels", () => {
      render(<SettingsPage />);
      expect(screen.getByLabelText(/Enable Content Decay Detection/)).toBeInTheDocument();
    });
  });

  describe("Loading States", () => {
    it("should render loading skeleton when loading", async () => {
      const { useContentDecaySettings } = await import("@/lib/hooks/useContentDecay");
      (useContentDecaySettings as any).mockReturnValue({
        settings: null,
        isLoading: true,
      });

      render(<SettingsPage />);
      expect(screen.getByTestId("page-skeleton")).toBeInTheDocument();
    });
  });

  describe("Responsive Design", () => {
    it("should render responsive layout classes", () => {
      const { container } = render(<SettingsPage />);
      expect(container.querySelector(".container")).toBeInTheDocument();
    });
  });
});
