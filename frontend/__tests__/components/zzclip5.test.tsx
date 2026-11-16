import React from "react";
import { render, screen, act, waitFor } from "@testing-library/react";
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

describe("clip probe5", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockClipboardWrite.mockResolvedValue(undefined);
  });

  it("act flush after click (real timers)", async () => {
    const user = userEvent.setup();
    installClipboardMock();
    render(<MockComp />);
    await user.click(screen.getByTestId("copy-btn"));
    await act(async () => {
      await Promise.resolve();
    });
    console.log("real: indicator found:", !!screen.queryByTestId("copied-indicator"));
    expect(screen.getByTestId("copied-indicator")).toBeInTheDocument();
  });

  it("act flush after click (fake timers)", async () => {
    vi.useFakeTimers();
    const user = userEvent.setup({ delay: null });
    installClipboardMock();
    render(<MockComp />);
    await user.click(screen.getByTestId("copy-btn"));
    await act(async () => {
      await Promise.resolve();
    });
    console.log("fake: indicator found:", !!screen.queryByTestId("copied-indicator"));
    expect(screen.getByTestId("copied-indicator")).toBeInTheDocument();
    await act(async () => {
      vi.advanceTimersByTime(2000);
    });
    expect(screen.queryByTestId("copied-indicator")).not.toBeInTheDocument();
    vi.useRealTimers();
  });
});
