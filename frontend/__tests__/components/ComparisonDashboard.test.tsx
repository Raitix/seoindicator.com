import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("ComparisonDashboard", () => {
  it("renders summary cards", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    expect(screen.getByText("Total Keywords")).toBeInTheDocument();
    expect(screen.getAllByText("I’m Winning").length).toBeGreaterThan(0);
    expect(screen.getAllByText("They’re Winning").length).toBeGreaterThan(0);
    expect(screen.getAllByText("Keyword Gaps").length).toBeGreaterThan(0);
    vi.useRealTimers();
  });

  it("shows loading skeleton", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    expect(screen.queryByText("Comparison Dashboard")).not.toBeInTheDocument();

    vi.useRealTimers();
  });

  it("handles empty state", () => {
    vi.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    const search = screen.getByLabelText("Search keyword");
    fireEvent.change(search, { target: { value: "not-a-keyword" } });

    expect(screen.getByText("No keywords to compare.")).toBeInTheDocument();
    vi.useRealTimers();
  });
});
