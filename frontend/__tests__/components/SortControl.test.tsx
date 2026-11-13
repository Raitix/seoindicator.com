import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("SortControl", () => {
  it("shows sort options", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    expect(screen.getByText("Sort by Keyword")).toBeInTheDocument();
    expect(screen.getByText("Sort by My Position")).toBeInTheDocument();
    expect(screen.getByText("Sort by Competitor Position")).toBeInTheDocument();
    expect(screen.getByText("Sort by Diff")).toBeInTheDocument();
    vi.useRealTimers();
  });

  it("changes sort selection", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    const sortSelect = screen.getByLabelText("Sort");
    fireEvent.change(sortSelect, { target: { value: "My Position" } });

    expect(sortSelect).toHaveValue("My Position");
    vi.useRealTimers();
  });
});
