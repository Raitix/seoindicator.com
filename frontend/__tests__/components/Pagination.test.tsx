import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("Pagination", () => {
  it("navigates pages", () => {
    jest.useFakeTimers();
    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    expect(screen.getByText(/Page 1 of/)).toBeInTheDocument();

    fireEvent.click(screen.getByText("Next"));

    expect(screen.getByText(/Page 2 of/)).toBeInTheDocument();
    jest.useRealTimers();
  });
});
