/**
 * @jest-environment jsdom
 */
import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { DecayReasonBadges } from "@/components/content-decay/DecayReasonBadges";
import type { DecayReason, ContentDecayReasonCode } from "@/lib/types/content-decay";

// Mock tooltip components
vi.mock("@/components/ui/tooltip", () => ({
  TooltipProvider: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="tooltip-provider">{children}</div>
  ),
  Tooltip: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  TooltipTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  TooltipContent: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="tooltip-content">{children}</div>
  ),
}));

describe("DecayReasonBadges", () => {
  const mockReasons: DecayReason[] = [
    { type: "clicks_down", evidence: "Clicks decreased by 45%" },
    { type: "impressions_down", evidence: "Impressions dropped 23%" },
    { type: "avg_position_down", evidence: "Position fell from 3.2 to 7.8" },
  ];

  describe("Rendering", () => {
    it("renders primary badge with correct text", () => {
      render(<DecayReasonBadges reasons={mockReasons} primaryReason="clicks_down" />);
      expect(screen.getByText("Clicks Down")).toBeInTheDocument();
    });

    it("renders supporting badges", () => {
      render(<DecayReasonBadges reasons={mockReasons} primaryReason="clicks_down" />);
      expect(screen.getByText("Impressions Down")).toBeInTheDocument();
      expect(screen.getByText("Position Down")).toBeInTheDocument();
    });

    it("renders nothing for empty reasons array", () => {
      const { container } = render(<DecayReasonBadges reasons={[]} />);
      expect(container.firstChild).toBeNull();
    });

    it("renders nothing when reasons is null/undefined", () => {
      const { container } = render(<DecayReasonBadges reasons={null as unknown as DecayReason[]} />);
      expect(container.firstChild).toBeNull();
    });
  });

  describe("Color coding", () => {
    it("applies red color for clicks_down", () => {
      render(<DecayReasonBadges reasons={[{ type: "clicks_down", evidence: "test" }]} />);
      const badge = screen.getByText("Clicks Down");
      expect(badge.className).toMatch(/red/i);
    });

    it("applies orange color for impressions_down", () => {
      render(<DecayReasonBadges reasons={[{ type: "impressions_down", evidence: "test" }]} />);
      const badge = screen.getByText("Impressions Down");
      expect(badge.className).toMatch(/orange/i);
    });

    it("applies yellow color for ctr_down", () => {
      render(<DecayReasonBadges reasons={[{ type: "ctr_down", evidence: "test" }]} />);
      const badge = screen.getByText("CTR Down");
      expect(badge.className).toMatch(/yellow/i);
    });

    it("applies blue color for avg_position_down", () => {
      render(<DecayReasonBadges reasons={[{ type: "avg_position_down", evidence: "test" }]} />);
      const badge = screen.getByText("Position Down");
      expect(badge.className).toMatch(/blue/i);
    });

    it("applies purple color for audit_issues_present", () => {
      render(<DecayReasonBadges reasons={[{ type: "audit_issues_present", evidence: "test" }]} />);
      const badge = screen.getByText("Audit Issues");
      expect(badge.className).toMatch(/purple/i);
    });

    it("applies gray color for stale_content", () => {
      render(<DecayReasonBadges reasons={[{ type: "stale_content", evidence: "test" }]} />);
      const badge = screen.getByText("Stale Content");
      expect(badge.className).toMatch(/gray/i);
    });

    it("applies indigo/violet color for ai_visibility_down", () => {
      render(<DecayReasonBadges reasons={[{ type: "ai_visibility_down", evidence: "test" }]} />);
      const badge = screen.getByText("AI Visibility Down");
      expect(badge.className).toMatch(/violet|indigo/i);
    });
  });

  describe("Max supporting badges", () => {
    it("limits supporting badges to maxSupporting value", () => {
      const manyReasons: DecayReason[] = [
        { type: "clicks_down", evidence: "1" },
        { type: "impressions_down", evidence: "2" },
        { type: "ctr_down", evidence: "3" },
        { type: "avg_position_down", evidence: "4" },
        { type: "stale_content", evidence: "5" },
      ];
      render(<DecayReasonBadges reasons={manyReasons} primaryReason="clicks_down" maxSupporting={2} />);
      
      expect(screen.getByText("Clicks Down")).toBeInTheDocument();
      expect(screen.queryByText("Stale Content")).not.toBeInTheDocument();
    });

    it("shows all badges when count is within limit", () => {
      render(<DecayReasonBadges reasons={mockReasons} primaryReason="clicks_down" maxSupporting={5} />);
      
      expect(screen.getByText("Clicks Down")).toBeInTheDocument();
      expect(screen.getByText("Impressions Down")).toBeInTheDocument();
      expect(screen.getByText("Position Down")).toBeInTheDocument();
    });
  });

  describe("Custom className", () => {
    it("applies custom className", () => {
      const { container } = render(
        <DecayReasonBadges reasons={mockReasons} className="custom-class" />
      );
      expect(container.firstChild).toHaveClass("custom-class");
    });
  });

  describe("Accessibility", () => {
    it("has aria-label with reason name and evidence", () => {
      render(<DecayReasonBadges reasons={[{ type: "clicks_down", evidence: "Down 45%" }]} />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveAttribute("aria-label", "Clicks Down: Down 45%");
    });

    it("has proper role for each badge", () => {
      render(<DecayReasonBadges reasons={mockReasons} />);
      const badges = screen.getAllByRole("status");
      expect(badges).toHaveLength(3);
    });
  });

  describe("Size variants", () => {
    it("applies small size classes", () => {
      render(<DecayReasonBadges reasons={mockReasons} size="sm" />);
      const badge = screen.getByText("Clicks Down");
      expect(badge).toHaveClass("text-xs");
    });

    it("applies medium size classes by default", () => {
      render(<DecayReasonBadges reasons={mockReasons} />);
      const badge = screen.getByText("Clicks Down");
      expect(badge).toHaveClass("px-2");
    });
  });

  describe("Primary badge highlighting", () => {
    it("applies ring styling to primary badge", () => {
      render(<DecayReasonBadges reasons={mockReasons} primaryReason="clicks_down" />);
      const badge = screen.getByText("Clicks Down");
      expect(badge.className).toMatch(/ring/i);
    });

    it("does not apply ring styling to supporting badges", () => {
      render(<DecayReasonBadges reasons={mockReasons} primaryReason="clicks_down" />);
      const badge = screen.getByText("Impressions Down");
      expect(badge.className).not.toMatch(/ring-1/);
    });
  });

  describe("Unknown reason types", () => {
    it("handles unknown reason types gracefully", () => {
      render(<DecayReasonBadges reasons={[{ type: "unknown_reason" as ContentDecayReasonCode, evidence: "test" }]} />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveTextContent("unknown reason");
    });
  });
});
