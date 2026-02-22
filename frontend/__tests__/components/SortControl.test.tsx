import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("SortControl", () => {
  it("shows sort options", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    expect(screen.getByText("Sort by Keyword")).toBeInTheDocument();
    expect(screen.getByText("Sort by My Position")).toBeInTheDocument();
    expect(screen.getByText("Sort by Competitor Position")).toBeInTheDocument();
    expect(screen.getByText("Sort by Diff")).toBeInTheDocument();
    jest.useRealTimers();
  });

  it("changes sort selection", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    const sortSelect = screen.getByLabelText("Sort");
    fireEvent.change(sortSelect, { target: { value: "My Position" } });

    expect(sortSelect).toHaveValue("My Position");
    jest.useRealTimers();
  });
});
