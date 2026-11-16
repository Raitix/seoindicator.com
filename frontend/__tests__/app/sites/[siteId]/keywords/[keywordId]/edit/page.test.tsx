/**
 * Unit tests for the Keyword Edit Page
 * Route: /sites/[siteId]/keywords/[keywordId]/edit
 *
 * @see SPEC-002
 */

import React from "react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import EditKeywordPage from "@/app/sites/[siteId]/keywords/[keywordId]/edit/page";

const mockPush = vi.fn();

vi.mock("next/navigation", () => ({
  useParams: () => ({ siteId: "7", keywordId: "42" }),
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

// The form has its own test file; stub it here so this suite stays focused
// on the page shell.
vi.mock("@/components/keywords/KeywordEditForm", () => ({
  __esModule: true,
  default: ({ siteId, keywordId }: { siteId: number; keywordId: number }) => (
    <div data-testid="keyword-edit-form">
      Form for {siteId} / {keywordId}
    </div>
  ),
}));

describe("EditKeywordPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("Page Rendering", () => {
    it("renders the page heading", () => {
      render(<EditKeywordPage />);
      expect(screen.getByRole("heading", { name: /edit keyword/i, level: 1 })).toBeInTheDocument();
    });

    it("renders the page description", () => {
      render(<EditKeywordPage />);
      expect(
        screen.getByText(/update the target url or pause keywords/i)
      ).toBeInTheDocument();
    });

    it("renders the keyword tracking eyebrow", () => {
      render(<EditKeywordPage />);
      expect(screen.getByText("Keyword Tracking")).toBeInTheDocument();
    });
  });

  describe("Edit Form", () => {
    it("renders the keyword edit form", () => {
      render(<EditKeywordPage />);
      expect(screen.getByTestId("keyword-edit-form")).toBeInTheDocument();
    });

    it("passes the site and keyword ids to the form", () => {
      render(<EditKeywordPage />);
      expect(screen.getByTestId("keyword-edit-form")).toHaveTextContent("Form for 7 / 42");
    });
  });
});
