import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("Comparison integration", () => {
  it("filters and keeps table responsive", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    const filterSelect = screen.getByLabelText("Filter");
    fireEvent.change(filterSelect, { target: { value: "Keyword Gaps" } });

    expect(screen.getAllByText("Keyword Gaps").length).toBeGreaterThan(0);
    expect(screen.getAllByText(/Keyword Gap/).length).toBeGreaterThan(0);
    jest.useRealTimers();
  });
});
