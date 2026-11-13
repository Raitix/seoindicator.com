import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("CompetitorVisibilityToggle", () => {
  it("toggles competitor visibility", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    expect(screen.getByTestId("column-Searchly")).toBeInTheDocument();

    fireEvent.click(screen.getByLabelText("Toggle Searchly"));

    expect(screen.queryByTestId("column-Searchly")).not.toBeInTheDocument();
    vi.useRealTimers();
  });

  it("keeps at least one competitor visible", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    fireEvent.click(screen.getByLabelText("Toggle Searchly"));
    fireEvent.click(screen.getByLabelText("Toggle RankForge"));
    fireEvent.click(screen.getByLabelText("Toggle VistaSEO"));

    const remaining = ["Searchly", "RankForge", "VistaSEO"].filter((label) =>
      screen.queryByTestId(`column-${label}`)
    );

    expect(remaining.length).toBeGreaterThanOrEqual(1);
    vi.useRealTimers();
  });
});
