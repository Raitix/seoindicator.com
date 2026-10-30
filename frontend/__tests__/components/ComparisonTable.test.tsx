import React from "react";
import { render, screen, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("ComparisonTable", () => {
  it("renders keyword rows and columns", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    expect(screen.getByText("Keyword")).toBeInTheDocument();
    expect(screen.getByText("My Position")).toBeInTheDocument();
    expect(screen.getByTestId("column-Searchly")).toBeInTheDocument();
    expect(screen.getByTestId("column-RankForge")).toBeInTheDocument();
    expect(screen.getByTestId("column-VistaSEO")).toBeInTheDocument();
    expect(screen.getByText("Status")).toBeInTheDocument();
    expect(screen.getByText("seo dashboard")).toBeInTheDocument();
    jest.useRealTimers();
  });

  it("shows diff colors and labels", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    const diffBadges = screen.getAllByTestId("diff-badge").map((badge) => badge.textContent ?? "");
    expect(diffBadges.some((text) => text.includes("-"))).toBe(true);
    expect(diffBadges.some((text) => text.includes("+"))).toBe(true);
    jest.useRealTimers();
  });

  it("shows status badges", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    expect(screen.getAllByText("I’m Winning").length).toBeGreaterThan(0);
    expect(screen.getAllByText("They’re Winning").length).toBeGreaterThan(0);
    expect(screen.getAllByText(/Keyword Gap/).length).toBeGreaterThan(0);
    expect(screen.getAllByText("Both Rank").length).toBeGreaterThan(0);
    jest.useRealTimers();
  });

  it("handles null positions", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    expect(screen.getAllByTestId("position-null").length).toBeGreaterThan(0);
    jest.useRealTimers();
  });
});
