/**
 * Unit tests for the New Keyword Page
 * Route: /sites/[siteId]/keywords/new
 *
 * @see SPEC-002
 */

import React from "react";
import { describe, expect, it, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import NewKeywordPage from "@/app/sites/[siteId]/keywords/new/page";
import { createKeyword, useKeywords } from "@/lib/api/keywords";

const mockPush = vi.fn();
const mockCreateKeyword = vi.fn();
const mockUseKeywords = vi.fn();

vi.mock("next/navigation", () => ({
  useParams: () => ({ siteId: "7" }),
  useRouter: () => ({
    push: mockPush,
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
  createKeyword: (...args: unknown[]) => mockCreateKeyword(...args),
  useKeywords: (...args: unknown[]) => mockUseKeywords(...args),
}));

describe("NewKeywordPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseKeywords.mockReturnValue({ keywords: [] });
    mockCreateKeyword.mockResolvedValue({});
  });

  describe("Page Rendering", () => {
    it("renders the page heading", () => {
      render(<NewKeywordPage />);
      expect(screen.getByRole("heading", { name: /add a new keyword/i, level: 1 })).toBeInTheDocument();
    });

    it("renders the page description", () => {
      render(<NewKeywordPage />);
      expect(
        screen.getByText(/define the keyword you want to monitor/i)
      ).toBeInTheDocument();
    });

    it("renders the keyword tracking eyebrow", () => {
      render(<NewKeywordPage />);
      expect(screen.getByText("Keyword Tracking")).toBeInTheDocument();
    });
  });

  describe("Create Form", () => {
    it("renders the keyword create form", () => {
      render(<NewKeywordPage />);
      expect(screen.getByRole("button", { name: "Add keyword" })).toBeInTheDocument();
    });

    it("renders the keyword field", () => {
      render(<NewKeywordPage />);
      expect(screen.getByLabelText("Keyword")).toBeInTheDocument();
    });

    it("renders the target url field", () => {
      render(<NewKeywordPage />);
      expect(screen.getByLabelText("Target URL")).toBeInTheDocument();
    });

    it("renders the targeting selectors", () => {
      render(<NewKeywordPage />);
      expect(screen.getByLabelText("Search engine")).toBeInTheDocument();
      expect(screen.getByLabelText("Location")).toBeInTheDocument();
      expect(screen.getByLabelText("Device")).toBeInTheDocument();
    });

    it("renders a link back to the keyword list", () => {
      render(<NewKeywordPage />);
      expect(screen.getByRole("link", { name: /back to keyword list/i })).toHaveAttribute(
        "href",
        "/sites/7/keywords"
      );
    });
  });

  describe("Form Submission", () => {
    it("creates the keyword with the entered values", async () => {
      const user = userEvent.setup();
      render(<NewKeywordPage />);

      await user.type(screen.getByLabelText("Keyword"), "technical seo audit");
      await user.click(screen.getByRole("button", { name: "Add keyword" }));

      expect(mockCreateKeyword).toHaveBeenCalledWith(7, {
        keyword: "technical seo audit",
        // The schema transform drops an empty target URL.
        targetUrl: undefined,
        searchEngine: "google",
        location: "us",
        device: "desktop",
      });
    });

    it("warns about a potential duplicate keyword", async () => {
      mockUseKeywords.mockReturnValue({
        keywords: [
          {
            id: 1,
            keyword: "technical seo audit",
            status: "active",
            currentPosition: null,
            previousPosition: null,
            positionChange: null,
            lastCheckedAt: null,
            targetUrl: null,
            searchEngine: "google",
            location: "us",
            device: "desktop",
          },
        ],
      });

      const user = userEvent.setup();
      render(<NewKeywordPage />);

      await user.type(screen.getByLabelText("Keyword"), "technical seo audit");

      expect(screen.getByText(/potential duplicate keyword found/i)).toBeInTheDocument();
    });
  });
});
