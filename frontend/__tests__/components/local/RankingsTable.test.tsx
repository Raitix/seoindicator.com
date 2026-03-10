import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, expect, it, vi } from "vitest";

import { RankingsTable } from "@/components/local/RankingsTable";

const rows = [
  {
    keywordId: 1,
    keyword: "best pizza near me",
    localPackPosition: 2,
    localOrganicPosition: 5,
    packChange: 2,
    organicChange: 1,
    lastChecked: "2026-03-10T10:00:00Z",
    competitorsInPackCount: 3,
  },
  {
    keywordId: 2,
    keyword: "pizza delivery",
    localPackPosition: null,
    localOrganicPosition: 12,
    packChange: -3,
    organicChange: -2,
    lastChecked: "2026-03-09T10:00:00Z",
    competitorsInPackCount: 0,
  },
];

describe("RankingsTable", () => {
  it("calls onSortChange when a sortable header is clicked", async () => {
    const user = userEvent.setup();
    const onSortChange = vi.fn();

    render(
      <RankingsTable
        rows={rows}
        selectedKeywordId={1}
        sortKey="localPackPosition"
        sortDirection="asc"
        onSelectKeyword={vi.fn()}
        onSortChange={onSortChange}
      />
    );

    await user.click(screen.getByRole("button", { name: /keyword/i }));

    expect(onSortChange).toHaveBeenCalledWith("keyword");
  });

  it("selects a keyword when a row is clicked", async () => {
    const user = userEvent.setup();
    const onSelectKeyword = vi.fn();

    render(
      <RankingsTable
        rows={rows}
        selectedKeywordId={1}
        sortKey="localPackPosition"
        sortDirection="asc"
        onSelectKeyword={onSelectKeyword}
        onSortChange={vi.fn()}
      />
    );

    await user.click(screen.getByText("pizza delivery"));

    expect(onSelectKeyword).toHaveBeenCalledWith(2);
  });

  it("triggers a single-keyword check from the actions column", async () => {
    const user = userEvent.setup();
    const onCheckKeyword = vi.fn();

    render(
      <RankingsTable
        rows={rows}
        selectedKeywordId={1}
        sortKey="localPackPosition"
        sortDirection="asc"
        onSelectKeyword={vi.fn()}
        onSortChange={vi.fn()}
        onCheckKeyword={onCheckKeyword}
      />
    );

    await user.click(screen.getAllByRole("button", { name: /^check$/i })[1]);

    expect(onCheckKeyword).toHaveBeenCalledWith(2);
  });
});
