import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, expect, it, vi, beforeEach } from "vitest";

vi.mock("lucide-react", () => ({
  Copy: () => <span data-testid="icon-copy">Copy</span>,
}));

const mockClipboardWrite = vi.fn();
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

const MockComp = () => {
  const [copied, setCopied] = React.useState(false);
  const handleCopy = async () => {
    await navigator.clipboard.writeText("hello");
    setCopied(true);
  };
  return (
    <button data-testid="copy-btn" onClick={handleCopy}>
      {copied ? <span data-testid="copied-indicator">Copied!</span> : "Copy"}
    </button>
  );
};

describe("clip probe", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockClipboardWrite.mockResolvedValue(undefined);
  });

  it("writes", async () => {
    const user = userEvent.setup();
    render(<MockComp />);
    await user.click(screen.getByTestId("copy-btn"));
    console.log("calls:", mockClipboardWrite.mock.calls.length);
    console.log("identity:", navigator.clipboard?.writeText === mockClipboardWrite);
    expect(mockClipboardWrite).toHaveBeenCalled();
  });
});
