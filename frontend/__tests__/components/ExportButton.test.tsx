import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";

describe("ExportButton", () => {
  it("triggers CSV download", () => {
    vi.useFakeTimers();
    const createObjectURL = vi.fn(() => "blob:mock");
    const revokeObjectURL = vi.fn();
    const click = vi.fn();

    Object.defineProperty(global, "URL", {
      value: { createObjectURL, revokeObjectURL },
      writable: true,
    });

    const originalCreateElement = document.createElement.bind(document);
    vi.spyOn(document, "createElement").mockImplementation((tagName: string) => {
      if (tagName === "a") {
        return { click, set href(value: string) {}, set download(value: string) {} } as unknown as HTMLAnchorElement;
      }
      return originalCreateElement(tagName);
    });

    render(<ComparisonDashboard />);

    act(() => {
      vi.runAllTimers();
    });

    fireEvent.click(screen.getByText("Export CSV"));

    expect(createObjectURL).toHaveBeenCalledTimes(1);
    expect(click).toHaveBeenCalledTimes(1);

    vi.useRealTimers();
  });
});
