import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, expect, it, vi, beforeEach } from "vitest";

const mockClipboardWrite = vi.fn();

function installClipboardMock() {
  Object.defineProperty(navigator, "clipboard", {
    value: { writeText: mockClipboardWrite },
    configurable: true,
    writable: true,
  });
}

const MockComp = () => {
  const [copied, setCopied] = React.useState(false);
  const handleCopy = async () => {
    await navigator.clipboard.writeText("hello");
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };
  return (
    <button data-testid="copy-btn" onClick={handleCopy}>
      {copied ? <span data-testid="copied-indicator">Copied!</span> : "Copy"}
    </button>
  );
};

describe("clip probe4", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockClipboardWrite.mockResolvedValue(undefined);
  });

  it("writes with real timers", async () => {
    const user = userEvent.setup();
    installClipboardMock();
    render(<MockComp />);
    await user.click(screen.getByTestId("copy-btn"));
    console.log("calls:", mockClipboardWrite.mock.calls.length);
    expect(mockClipboardWrite).toHaveBeenCalled();
    expect(screen.getByTestId("copied-indicator")).toBeInTheDocument();
  });

  it("writes with fake timers", async () => {
    vi.useFakeTimers();
    const user = userEvent.setup({ delay: null });
    installClipboardMock();
    render(<MockComp />);
    await user.click(screen.getByTestId("copy-btn"));
    console.log("fake calls:", mockClipboardWrite.mock.calls.length);
    expect(screen.getByTestId("copied-indicator")).toBeInTheDocument();
    vi.advanceTimersByTime(2000);
    expect(screen.queryByTestId("copied-indicator")).not.toBeInTheDocument();
    vi.useRealTimers();
  });
});
