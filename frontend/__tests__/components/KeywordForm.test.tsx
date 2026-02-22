import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

import KeywordCreateForm from "@/components/keywords/KeywordCreateForm";
import KeywordEditForm from "@/components/keywords/KeywordEditForm";

jest.mock("next/navigation", () => ({
  useRouter: () => ({ push: jest.fn() }),
}));

const createKeyword = jest.fn();
const updateKeyword = jest.fn();
const useKeywords = jest.fn();
const useKeyword = jest.fn();

jest.mock("@/lib/api/keywords", () => ({
  createKeyword: (...args: unknown[]) => createKeyword(...args),
  updateKeyword: (...args: unknown[]) => updateKeyword(...args),
  useKeywords: (...args: unknown[]) => useKeywords(...args),
  useKeyword: (...args: unknown[]) => useKeyword(...args),
}));

describe("KeywordCreateForm", () => {
  beforeEach(() => {
    createKeyword.mockResolvedValue({});
    useKeywords.mockReturnValue({ keywords: [] });
  });

  it("renders required fields", () => {
    render(<KeywordCreateForm siteId={1} />);

    expect(screen.getByLabelText("Keyword")).toBeInTheDocument();
    expect(screen.getByLabelText("Target URL")).toBeInTheDocument();
    expect(screen.getByLabelText("Search engine")).toBeInTheDocument();
    expect(screen.getByLabelText("Location")).toBeInTheDocument();
    expect(screen.getByLabelText("Device")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Add keyword" })).toBeInTheDocument();
  });

  it("validates required keyword and URL format", async () => {
    const user = userEvent.setup();
    render(<KeywordCreateForm siteId={1} />);

    await user.click(screen.getByRole("button", { name: "Add keyword" }));
    expect(await screen.findByText("Keyword is required")).toBeInTheDocument();

    await user.type(screen.getByLabelText("Keyword"), "seo audit");
    await user.type(screen.getByLabelText("Target URL"), "not-a-url");
    await user.click(screen.getByRole("button", { name: "Add keyword" }));

    expect(await screen.findByText("Enter a valid URL")).toBeInTheDocument();
  });

  it("shows a duplicate warning", async () => {
    const user = userEvent.setup();
    useKeywords.mockReturnValue({
      keywords: [
        {
          id: 1,
          keyword: "seo audit",
          searchEngine: "google",
          location: "us",
          device: "desktop",
        },
      ],
    });

    render(<KeywordCreateForm siteId={1} />);

    await user.type(screen.getByLabelText("Keyword"), "SEO Audit");
    expect(await screen.findByText("Potential duplicate keyword found.")).toBeInTheDocument();
  });

  it("submits successfully and resets", async () => {
    const user = userEvent.setup();
    render(<KeywordCreateForm siteId={1} />);

    await user.type(screen.getByLabelText("Keyword"), "seo audit");
    await user.type(screen.getByLabelText("Target URL"), "https://example.com");
    await user.click(screen.getByRole("button", { name: "Add keyword" }));

    expect(createKeyword).toHaveBeenCalled();
    expect(screen.getByLabelText("Keyword")).toHaveValue("");
  });
});

describe("KeywordEditForm", () => {
  beforeEach(() => {
    useKeyword.mockReturnValue({
      keyword: {
        id: 10,
        keyword: "SEO audit",
        targetUrl: "https://example.com",
        status: "paused",
      },
      isLoading: false,
    });
    updateKeyword.mockResolvedValue({});
  });

  it("renders pre-populated fields", async () => {
    render(<KeywordEditForm siteId={1} keywordId={10} />);

    expect(await screen.findByDisplayValue("SEO audit")).toBeDisabled();
    expect(await screen.findByDisplayValue("https://example.com")).toBeInTheDocument();
    expect(screen.getByLabelText("Status")).toHaveValue("paused");
  });

  it("submits changes", async () => {
    const user = userEvent.setup();
    render(<KeywordEditForm siteId={1} keywordId={10} />);

    await user.clear(screen.getByLabelText("Target URL"));
    await user.type(screen.getByLabelText("Target URL"), "https://updated.com");
    await user.click(screen.getByRole("button", { name: "Save changes" }));

    expect(updateKeyword).toHaveBeenCalledWith(10, {
      targetUrl: "https://updated.com",
      status: "paused",
    });
  });
});
