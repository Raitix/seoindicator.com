import React from "react";
import { describe, expect, it, vi } from "vitest";
import { render, screen } from "@testing-library/react";

const mockUseParams = vi.fn(() => ({ siteId: "1" }));

vi.mock("next/navigation", () => ({
  useParams: () => mockUseParams(),
}));

vi.mock("@/components/keywords/KeywordCreateForm", () => ({
  __esModule: true,
  default: ({ siteId }: { siteId: number }) => (
    <div data-testid="keyword-create-form">Form for {siteId}</div>
  ),
}));

import NewKeywordPage from "@/app/sites/[siteId]/keywords/new/page";

describe("NewKeywordPage", () => {
  it("renders the add keyword page", () => {
    render(<NewKeywordPage />);

    expect(screen.getByText("Add a new keyword")).toBeInTheDocument();
    expect(screen.getByTestId("keyword-create-form")).toHaveTextContent("Form for 1");
  });
});
