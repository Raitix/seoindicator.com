import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("ExportButton", () => {
  it("triggers CSV download", () => {
    jest.useFakeTimers();
    const createObjectURL = jest.fn(() => "blob:mock");
    const revokeObjectURL = jest.fn();
    const click = jest.fn();

    Object.defineProperty(global, "URL", {
      value: { createObjectURL, revokeObjectURL },
      writable: true,
    });

    const originalCreateElement = document.createElement.bind(document);
    jest.spyOn(document, "createElement").mockImplementation((tagName: string) => {
      if (tagName === "a") {
        return { click, set href(value: string) {}, set download(value: string) {} } as unknown as HTMLAnchorElement;
      }
      return originalCreateElement(tagName);
    });

    render(<ComparisonDashboard />);

    act(() => {
      jest.runAllTimers();
    });

    fireEvent.click(screen.getByText("Export CSV"));

    expect(createObjectURL).toHaveBeenCalledTimes(1);
    expect(click).toHaveBeenCalledTimes(1);

    jest.useRealTimers();
  });
});
