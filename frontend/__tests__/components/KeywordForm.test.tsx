import React from "react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

import KeywordCreateForm from "@/components/keywords/KeywordCreateForm";
import KeywordEditForm from "@/components/keywords/KeywordEditForm";

const mockPush = vi.fn();
const createKeyword = vi.fn();
const updateKeyword = vi.fn();
const useKeywords = vi.fn();
const useKeyword = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: mockPush,
    replace: vi.fn(),
    back: vi.fn(),
    forward: vi.fn(),
    refresh: vi.fn(),
    prefetch: vi.fn(),
  }),
}));

vi.mock("next/link", () => ({
  default: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}));

vi.mock("@/lib/api/keywords", () => ({
  createKeyword: (...args: unknown[]) => createKeyword(...args),
  updateKeyword: (...args: unknown[]) => updateKeyword(...args),
  useKeywords: (...args: unknown[]) => useKeywords(...args),
  useKeyword: (...args: unknown[]) => useKeyword(...args),
}));

describe("KeywordCreateForm", () => {
  beforeEach(() => {
    vi.clearAllMocks();
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

    expect(createKeyword).toHaveBeenCalledWith(1, {
      keyword: "seo audit",
      targetUrl: "https://example.com",
      searchEngine: "google",
      location: "us",
      device: "desktop",
    });
    expect(screen.getByLabelText("Keyword")).toHaveValue("");
    expect(screen.getByLabelText("Target URL")).toHaveValue("");
  });
});

describe("KeywordEditForm", () => {
  beforeEach(() => {
    vi.clearAllMocks();
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

    await user.clear(await screen.findByLabelText("Target URL"));
    await user.type(screen.getByLabelText("Target URL"), "https://updated.com");
    await user.click(screen.getByRole("button", { name: "Save changes" }));

    expect(updateKeyword).toHaveBeenCalledWith(10, {
      targetUrl: "https://updated.com",
      status: "paused",
    });
  });
});
