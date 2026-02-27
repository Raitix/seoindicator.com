import React from "react";
import { render, screen } from "@testing-library/react";

import NewKeywordPage from "@/app/sites/[siteId]/keywords/new/page";

jest.mock("next/navigation", () => ({
  useParams: () => ({ siteId: "1" }),
}));

jest.mock("@/components/keywords/KeywordCreateForm", () => ({
  __esModule: true,
  default: ({ siteId }: { siteId: number }) => (
    <div data-testid="keyword-create-form">Form for {siteId}</div>
  ),
}));

describe("NewKeywordPage", () => {
  it("renders the add keyword page", () => {
    render(<NewKeywordPage />);

    expect(screen.getByText("Add a new keyword")).toBeInTheDocument();
    expect(screen.getByTestId("keyword-create-form")).toBeInTheDocument();
  });
});
