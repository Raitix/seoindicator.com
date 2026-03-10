/**
 * @jest-environment jsdom
 */
import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import { SeverityBadge } from "@/components/content-decay/SeverityBadge";
import type { ContentDecaySeverity } from "@/lib/types/content-decay";

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  AlertTriangle: () => <span data-testid="alert-triangle-icon">AlertTriangle</span>,
  AlertCircle: () => <span data-testid="alert-circle-icon">AlertCircle</span>,
  Info: () => <span data-testid="info-icon">Info</span>,
  Minus: () => <span data-testid="minus-icon">Minus</span>,
}));

describe("SeverityBadge", () => {
  describe("Rendering", () => {
    it("renders critical severity with red styling", () => {
      render(<SeverityBadge severity="critical" />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveTextContent("Critical");
      expect(badge).toHaveClass("bg-red-500");
      expect(badge).toHaveClass("text-white");
    });

    it("renders high severity with orange styling", () => {
      render(<SeverityBadge severity="high" />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveTextContent("High");
      expect(badge).toHaveClass("bg-orange-500");
      expect(badge).toHaveClass("text-white");
    });

    it("renders medium severity with yellow styling", () => {
      render(<SeverityBadge severity="medium" />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveTextContent("Medium");
      expect(badge).toHaveClass("bg-yellow-500");
      expect(badge).toHaveClass("text-black");
    });

    it("renders low severity with gray styling", () => {
      render(<SeverityBadge severity="low" />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveTextContent("Low");
      expect(badge).toHaveClass("bg-gray-300");
      expect(badge).toHaveClass("text-black");
    });
  });

  describe("Icon indicators", () => {
    it("shows AlertTriangle icon for critical", () => {
      render(<SeverityBadge severity="critical" />);
      expect(screen.getByTestId("alert-triangle-icon")).toBeInTheDocument();
    });

    it("shows AlertCircle icon for high", () => {
      render(<SeverityBadge severity="high" />);
      expect(screen.getByTestId("alert-circle-icon")).toBeInTheDocument();
    });

    it("shows Info icon for medium", () => {
      render(<SeverityBadge severity="medium" />);
      expect(screen.getByTestId("info-icon")).toBeInTheDocument();
    });

    it("shows Minus icon for low", () => {
      render(<SeverityBadge severity="low" />);
      expect(screen.getByTestId("minus-icon")).toBeInTheDocument();
    });
  });

  describe("Text label", () => {
    it("displays correct label for critical", () => {
      render(<SeverityBadge severity="critical" />);
      expect(screen.getByText("Critical")).toBeInTheDocument();
    });

    it("displays correct label for high", () => {
      render(<SeverityBadge severity="high" />);
      expect(screen.getByText("High")).toBeInTheDocument();
    });

    it("displays correct label for medium", () => {
      render(<SeverityBadge severity="medium" />);
      expect(screen.getByText("Medium")).toBeInTheDocument();
    });

    it("displays correct label for low", () => {
      render(<SeverityBadge severity="low" />);
      expect(screen.getByText("Low")).toBeInTheDocument();
    });
  });

  describe("Icon only mode", () => {
    it("hides text when iconOnly is true", () => {
      render(<SeverityBadge severity="critical" iconOnly />);
      const badge = screen.getByRole("status");
      expect(badge).not.toHaveTextContent("Critical");
    });
  });

  describe("Custom className", () => {
    it("applies custom className", () => {
      render(<SeverityBadge severity="critical" className="custom-class" />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveClass("custom-class");
    });
  });

  describe("Accessibility", () => {
    it("has correct aria-label for critical", () => {
      render(<SeverityBadge severity="critical" />);
      expect(screen.getByRole("status")).toHaveAttribute("aria-label", "Severity: Critical");
    });

    it("has correct aria-label for high", () => {
      render(<SeverityBadge severity="high" />);
      expect(screen.getByRole("status")).toHaveAttribute("aria-label", "Severity: High");
    });

    it("has correct aria-label for medium", () => {
      render(<SeverityBadge severity="medium" />);
      expect(screen.getByRole("status")).toHaveAttribute("aria-label", "Severity: Medium");
    });

    it("has correct aria-label for low", () => {
      render(<SeverityBadge severity="low" />);
      expect(screen.getByRole("status")).toHaveAttribute("aria-label", "Severity: Low");
    });
  });

  describe("Invalid severity fallback", () => {
    it("falls back to low/gray for invalid severity", () => {
      render(<SeverityBadge severity={"invalid" as ContentDecaySeverity} />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveTextContent("Low");
      expect(badge).toHaveClass("bg-gray-300");
    });
  });

  describe("Size variants", () => {
    it("applies small size classes", () => {
      render(<SeverityBadge severity="critical" size="sm" />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveClass("text-xs");
      expect(badge).toHaveClass("px-1.5");
    });

    it("applies medium size classes by default", () => {
      render(<SeverityBadge severity="critical" />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveClass("px-2");
    });

    it("applies large size classes", () => {
      render(<SeverityBadge severity="critical" size="lg" />);
      const badge = screen.getByRole("status");
      expect(badge).toHaveClass("text-sm");
      expect(badge).toHaveClass("px-2.5");
    });
  });
});
