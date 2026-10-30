/**
 * @jest-environment jsdom
 */
import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { DecayTrendMiniChart } from "@/components/content-decay/DecayTrendMiniChart";
import type { DecayTrendData } from "@/lib/types/content-decay";

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

describe("DecayTrendMiniChart", () => {
  const mockTrendData: DecayTrendData = {
    previousWindow: {
      clicks: [120, 115, 110, 105, 100, 95],
      impressions: [1200, 1180, 1160, 1140, 1120, 1100],
    },
    currentWindow: {
      clicks: [60, 55, 50, 45, 40, 35],
      impressions: [900, 870, 840, 810, 780, 750],
    },
  };

  describe("Rendering", () => {
    it("renders SVG sparkline", () => {
      render(<DecayTrendMiniChart data={mockTrendData} />);
      const svg = document.querySelector("svg");
      expect(svg).toBeInTheDocument();
    });

    it("renders with custom dimensions", () => {
      render(<DecayTrendMiniChart data={mockTrendData} width={200} height={60} />);
      const svg = document.querySelector("svg");
      expect(svg).toHaveAttribute("width", "200");
      expect(svg).toHaveAttribute("height", "60");
    });

    it("renders with default dimensions", () => {
      render(<DecayTrendMiniChart data={mockTrendData} />);
      const svg = document.querySelector("svg");
      expect(svg).toHaveAttribute("width", "120");
      expect(svg).toHaveAttribute("height", "40");
    });
  });

  describe("Empty/Null data handling", () => {
    it("shows placeholder when data is null", () => {
      render(<DecayTrendMiniChart data={null} />);
      expect(screen.getByText("—")).toBeInTheDocument();
    });

    it("shows placeholder when previousWindow is missing", () => {
      render(<DecayTrendMiniChart data={{ previousWindow: null as unknown as DecayTrendData["previousWindow"], currentWindow: mockTrendData.currentWindow }} />);
      expect(screen.getByText("—")).toBeInTheDocument();
    });

    it("shows placeholder when currentWindow is missing", () => {
      render(<DecayTrendMiniChart data={{ previousWindow: mockTrendData.previousWindow, currentWindow: null as unknown as DecayTrendData["currentWindow"] }} />);
      expect(screen.getByText("—")).toBeInTheDocument();
    });

    it("shows placeholder when clicks arrays are empty", () => {
      const emptyData: DecayTrendData = {
        previousWindow: { clicks: [], impressions: [] },
        currentWindow: { clicks: [], impressions: [] },
      };
      render(<DecayTrendMiniChart data={emptyData} />);
      // Should still render SVG with empty data
      const svg = document.querySelector("svg");
      expect(svg).toBeInTheDocument();
    });
  });

  describe("Single data point", () => {
    it("renders without error with single data point", () => {
      const singlePointData: DecayTrendData = {
        previousWindow: { clicks: [100], impressions: [1000] },
        currentWindow: { clicks: [50], impressions: [800] },
      };
      render(<DecayTrendMiniChart data={singlePointData} />);
      const svg = document.querySelector("svg");
      expect(svg).toBeInTheDocument();
    });
  });

  describe("Trend indicators", () => {
    it("shows down trend for decreasing values", () => {
      render(<DecayTrendMiniChart data={mockTrendData} />);
      const svg = document.querySelector("svg");
      expect(svg).toHaveAttribute("aria-label", expect.stringContaining("-"));
    });

    it("shows up trend for increasing values", () => {
      const increasingData: DecayTrendData = {
        previousWindow: { clicks: [50, 55, 60], impressions: [500, 550, 600] },
        currentWindow: { clicks: [100, 110, 120], impressions: [1000, 1100, 1200] },
      };
      render(<DecayTrendMiniChart data={increasingData} />);
      const svg = document.querySelector("svg");
      expect(svg).toHaveAttribute("aria-label", expect.stringContaining("+"));
    });

    it("shows neutral trend for stable values", () => {
      const stableData: DecayTrendData = {
        previousWindow: { clicks: [100, 100, 100], impressions: [1000, 1000, 1000] },
        currentWindow: { clicks: [100, 100, 100], impressions: [1000, 1000, 1000] },
      };
      render(<DecayTrendMiniChart data={stableData} />);
      const svg = document.querySelector("svg");
      expect(svg).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("has role='img'", () => {
      render(<DecayTrendMiniChart data={mockTrendData} />);
      const svg = document.querySelector("svg");
      expect(svg).toHaveAttribute("role", "img");
    });

    it("has descriptive aria-label", () => {
      render(<DecayTrendMiniChart data={mockTrendData} />);
      const svg = document.querySelector("svg");
      const ariaLabel = svg?.getAttribute("aria-label");
      expect(ariaLabel).toContain("Trend chart");
      expect(ariaLabel).toContain("Clicks");
      expect(ariaLabel).toContain("Impressions");
    });
  });

  describe("Large dataset performance", () => {
    it("renders with 30 data points without lag", () => {
      const largeData: DecayTrendData = {
        previousWindow: {
          clicks: Array.from({ length: 30 }, (_, i) => 100 - i),
          impressions: Array.from({ length: 30 }, (_, i) => 1000 - i * 10),
        },
        currentWindow: {
          clicks: Array.from({ length: 30 }, (_, i) => 50 - i),
          impressions: Array.from({ length: 30 }, (_, i) => 800 - i * 10),
        },
      };
      
      const start = performance.now();
      render(<DecayTrendMiniChart data={largeData} />);
      const end = performance.now();
      
      // Should render in under 100ms
      expect(end - start).toBeLessThan(100);
      expect(document.querySelector("svg")).toBeInTheDocument();
    });
  });

  describe("Show/hide trend lines", () => {
    it("renders clicks line by default", () => {
      render(<DecayTrendMiniChart data={mockTrendData} />);
      const polylines = document.querySelectorAll("polyline");
      expect(polylines.length).toBeGreaterThan(0);
    });

    it("hides clicks line when showClicks is false", () => {
      render(<DecayTrendMiniChart data={mockTrendData} showClicks={false} />);
      const svg = document.querySelector("svg");
      expect(svg).toBeInTheDocument();
    });

    it("hides impressions line when showImpressions is false", () => {
      render(<DecayTrendMiniChart data={mockTrendData} showImpressions={false} />);
      const svg = document.querySelector("svg");
      expect(svg).toBeInTheDocument();
    });
  });

  describe("Custom className", () => {
    it("applies custom className to container", () => {
      const { container } = render(
        <DecayTrendMiniChart data={mockTrendData} className="custom-class" />
      );
      const trigger = container.querySelector(".cursor-pointer");
      expect(trigger).toHaveClass("custom-class");
    });
  });
});
