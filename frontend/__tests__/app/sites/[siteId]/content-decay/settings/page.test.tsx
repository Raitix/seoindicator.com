/**
 * Unit tests for Content Decay Settings Page
 * Route: /sites/[siteId]/content-decay/settings
 *
 * @see TASK-36-13
 * @see SPEC-036
 */

import React from "react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { BrowserRouter } from "react-router-dom";
import ContentDecaySettingsPage from "@/app/sites/[siteId]/content-decay/settings/page";

// Mock hooks with stable settings reference
const mockSettings = {
  detectionEnabled: true,
  windowDays: 28,
  minClicksThreshold: 10,
  minImpressionsThreshold: 100,
  digestEnabled: true,
  digestFrequency: "weekly" as const,
};

vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecaySettings: vi.fn(() => ({
    settings: mockSettings,
    isLoading: false,
    error: null,
  })),
  useSaveContentDecaySettings: vi.fn(() => ({
    trigger: vi.fn().mockResolvedValue({ success: true }),
    isMutating: false,
  })),
}));

// Mock next/navigation
vi.mock("next/navigation", () => ({
  useParams: vi.fn(() => ({ siteId: "1" })),
  useRouter: vi.fn(() => ({
    push: vi.fn(),
  })),
}));

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  LayoutDashboard: () => <span data-testid="layout-icon">LayoutDashboard</span>,
  Kanban: () => <span data-testid="kanban-icon">Kanban</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">TrendingUp</span>,
  Save: () => <span data-testid="save-icon">Save</span>,
  RotateCcw: () => <span data-testid="reset-icon">RotateCcw</span>,
  AlertCircle: () => <span data-testid="alert-icon">AlertCircle</span>,
  AlertTriangle: () => <span data-testid="alert-triangle-icon">AlertTriangle</span>,
  Info: () => <span data-testid="info-icon">Info</span>,
}));

const renderWithRouter = (component: React.ReactElement) => {
  return render(<BrowserRouter>{component}</BrowserRouter>);
};

describe("Content Decay Settings Page", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("Page Rendering", () => {
    it("renders the page container", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByTestId("settings-page")).toBeInTheDocument();
    });

    it("renders the page title", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByRole("heading", { name: /content decay settings/i })).toBeInTheDocument();
    });

    it("renders the page description", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByText(/configure detection and notification/i)).toBeInTheDocument();
    });

    it("renders navigation tabs", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByRole("link", { name: /queue/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /board/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /recovery/i })).toBeInTheDocument();
    });
  });

  describe("Detection Settings Section", () => {
    it("renders detection settings section", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByRole("region", { name: /detection settings/i })).toBeInTheDocument();
    });

    it("renders detection toggle", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByText(/enable detection/i)).toBeInTheDocument();
    });

    it("renders detection toggle switch", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      const toggle = screen.getByRole("switch", { name: /enable detection/i });
      expect(toggle).toBeInTheDocument();
    });

    it("renders window size selector", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByText(/detection window/i)).toBeInTheDocument();
    });

    it("renders window size options", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByRole("button", { name: /7 days/i })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: /14 days/i })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: /28 days/i })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: /56 days/i })).toBeInTheDocument();
    });
  });

  describe("Threshold Configuration", () => {
    it("renders thresholds section", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByText(/thresholds/i)).toBeInTheDocument();
    });

    it("renders minimum clicks input", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByLabelText(/minimum clicks/i)).toBeInTheDocument();
    });

    it("renders minimum impressions input", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByLabelText(/minimum impressions/i)).toBeInTheDocument();
    });
  });

  describe("Digest Settings Section", () => {
    it("renders digest settings section", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByRole("region", { name: /digest settings/i })).toBeInTheDocument();
    });

    it("renders digest toggle", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByText(/enable digest/i)).toBeInTheDocument();
    });

    it("renders digest toggle switch", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      const toggle = screen.getByRole("switch", { name: /enable digest/i });
      expect(toggle).toBeInTheDocument();
    });

    it("renders digest frequency selector", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByLabelText(/digest frequency/i)).toBeInTheDocument();
    });

    it("renders digest frequency options", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByRole("option", { name: /weekly/i })).toBeInTheDocument();
      expect(screen.getByRole("option", { name: /monthly/i })).toBeInTheDocument();
    });
  });

  describe("Form Actions", () => {
    it("renders save button", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByRole("button", { name: /save settings/i })).toBeInTheDocument();
    });

    it("renders reset button", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByRole("button", { name: /reset/i })).toBeInTheDocument();
    });

    it("save button is disabled when no changes", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      const saveButton = screen.getByRole("button", { name: /save settings/i });
      expect(saveButton).toBeDisabled();
    });

    it("reset button is disabled when no changes", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      const resetButton = screen.getByRole("button", { name: /reset/i });
      expect(resetButton).toBeDisabled();
    });
  });

  describe("Form Interactions", () => {
    it("enables save button after change", async () => {
      const user = userEvent.setup();
      renderWithRouter(<ContentDecaySettingsPage />);
      
      // Click the detection toggle to make a change
      const toggle = screen.getByRole("switch", { name: /enable detection/i });
      await user.click(toggle);
      
      // Save button should now be enabled
      const saveButton = screen.getByRole("button", { name: /save settings/i });
      expect(saveButton).not.toBeDisabled();
    });

    it("shows unsaved changes indicator after change", async () => {
      const user = userEvent.setup();
      renderWithRouter(<ContentDecaySettingsPage />);
      
      // Click the detection toggle to make a change
      const toggle = screen.getByRole("switch", { name: /enable detection/i });
      await user.click(toggle);
      
      // Unsaved changes indicator should appear
      expect(screen.getByText(/unsaved changes/i)).toBeInTheDocument();
    });

    it("can change window size selection", async () => {
      const user = userEvent.setup();
      renderWithRouter(<ContentDecaySettingsPage />);
      
      // Click the 7 days button
      const button7Days = screen.getByRole("button", { name: /7 days/i });
      await user.click(button7Days);
      
      // Button should now have blue background (selected state)
      expect(button7Days).toHaveClass("bg-blue-600");
    });

    it("can toggle detection on and off", async () => {
      const user = userEvent.setup();
      renderWithRouter(<ContentDecaySettingsPage />);
      
      const toggle = screen.getByRole("switch", { name: /enable detection/i });
      expect(toggle).toHaveAttribute("aria-checked", "true");
      
      await user.click(toggle);
      expect(toggle).toHaveAttribute("aria-checked", "false");
    });
  });

  describe("Validation", () => {
    it("shows error for negative clicks threshold", async () => {
      const user = userEvent.setup();
      renderWithRouter(<ContentDecaySettingsPage />);
      
      const clicksInput = screen.getByLabelText(/minimum clicks/i);
      await user.clear(clicksInput);
      await user.type(clicksInput, "-5");
      
      // Trigger save to show validation
      const saveButton = screen.getByRole("button", { name: /save settings/i });
      await user.click(saveButton);
      
      // Should show validation error
      await waitFor(() => {
        expect(screen.getByText(/must be 0 or greater/i)).toBeInTheDocument();
      });
    });

    it("shows error for negative impressions threshold", async () => {
      const user = userEvent.setup();
      renderWithRouter(<ContentDecaySettingsPage />);
      
      const impressionsInput = screen.getByLabelText(/minimum impressions/i);
      await user.clear(impressionsInput);
      await user.type(impressionsInput, "-10");
      
      // Trigger save to show validation
      const saveButton = screen.getByRole("button", { name: /save settings/i });
      await user.click(saveButton);
      
      // Should show validation error
      await waitFor(() => {
        expect(screen.getByText(/must be 0 or greater/i)).toBeInTheDocument();
      });
    });
  });

  describe("Loading States", () => {
    it("shows loading state when settings are loading", () => {
      vi.mocked(require("@/lib/hooks/useContentDecay").useContentDecaySettings).mockReturnValue({
        settings: null,
        isLoading: true,
        error: null,
      });
      renderWithRouter(<ContentDecaySettingsPage />);
      // Page should still render with loading state
      expect(screen.getByTestId("settings-page")).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("has proper heading hierarchy", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toBeInTheDocument();
    });

    it("has accessible navigation", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      const nav = screen.getByRole("navigation", { name: /content decay views/i });
      expect(nav).toBeInTheDocument();
    });

    it("has accessible form inputs", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      expect(screen.getByLabelText(/minimum clicks/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/minimum impressions/i)).toBeInTheDocument();
    });

    it("has accessible toggle switches", () => {
      renderWithRouter(<ContentDecaySettingsPage />);
      const toggles = screen.getAllByRole("switch");
      expect(toggles.length).toBe(2); // Detection and Digest toggles
    });
  });

  describe("Reset Functionality", () => {
    it("reset button restores original values", async () => {
      const user = userEvent.setup();
      renderWithRouter(<ContentDecaySettingsPage />);
      
      // Make a change
      const toggle = screen.getByRole("switch", { name: /enable detection/i });
      await user.click(toggle);
      
      // Reset button should be enabled
      const resetButton = screen.getByRole("button", { name: /reset/i });
      expect(resetButton).not.toBeDisabled();
      
      // Click reset
      await user.click(resetButton);
      
      // Toggle should be back to original state
      expect(toggle).toHaveAttribute("aria-checked", "true");
    });
  });
});
