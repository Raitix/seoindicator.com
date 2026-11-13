import React from "react";
import { render, screen, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("ComparisonTable", () => {
  it("renders keyword rows and columns", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    expect(screen.getByText("Keyword")).toBeInTheDocument();
    expect(screen.getByText("My Position")).toBeInTheDocument();
    expect(screen.getByTestId("column-Searchly")).toBeInTheDocument();
    expect(screen.getByTestId("column-RankForge")).toBeInTheDocument();
    expect(screen.getByTestId("column-VistaSEO")).toBeInTheDocument();
    expect(screen.getByText("Status")).toBeInTheDocument();
    expect(screen.getByText("seo dashboard")).toBeInTheDocument();
    vi.useRealTimers();
  });

  it("shows diff colors and labels", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    const diffBadges = screen.getAllByTestId("diff-badge").map((badge) => badge.textContent ?? "");
    expect(diffBadges.some((text) => text.includes("-"))).toBe(true);
    expect(diffBadges.some((text) => text.includes("+"))).toBe(true);
    vi.useRealTimers();
  });

  it("shows status badges", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    expect(screen.getAllByText("I’m Winning").length).toBeGreaterThan(0);
    expect(screen.getAllByText("They’re Winning").length).toBeGreaterThan(0);
    expect(screen.getAllByText(/Keyword Gap/).length).toBeGreaterThan(0);
    expect(screen.getAllByText("Both Rank").length).toBeGreaterThan(0);
    vi.useRealTimers();
  });

  it("handles null positions", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    expect(screen.getAllByTestId("position-null").length).toBeGreaterThan(0);
    vi.useRealTimers();
  });
});
