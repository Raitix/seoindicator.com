import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("FilterDropdown", () => {
  it("shows filter options", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    expect(screen.getByRole("option", { name: "All" })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: "I’m Winning" })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: "They’re Winning" })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: "Keyword Gaps" })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: "Both Rank" })).toBeInTheDocument();
    jest.useRealTimers();
  });

  it("filters results", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    const filterSelect = screen.getByLabelText("Filter");
    fireEvent.change(filterSelect, { target: { value: "I’m Winning" } });

    expect(screen.getAllByText("I’m Winning").length).toBeGreaterThan(0);
    jest.useRealTimers();
  });
});
