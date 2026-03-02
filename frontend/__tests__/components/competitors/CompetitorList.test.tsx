import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import "@testing-library/jest-dom";

import CompetitorList from "@/components/competitors/CompetitorList";
import * as competitorsApi from "@/lib/api/competitors";

// Mock the API hooks
jest.mock("@/lib/api/competitors", () => ({
  useCompetitors: jest.fn(),
}));

// Mock child components that have complex dependencies
jest.mock("@/components/competitors/AddCompetitorModal", () => {
  return function MockAddCompetitorModal() {
    return <button data-testid="add-competitor-btn">Add Competitor</button>;
  };
});

jest.mock("@/components/competitors/EditCompetitorModal", () => {
  return function MockEditCompetitorModal({
    competitor,
    open,
  }: {
    competitor: any;
    open: boolean;
  }) {
    return open ? <div data-testid="edit-modal">Edit {competitor?.domain}</div> : null;
  };
});

jest.mock("@/components/competitors/DeleteCompetitorDialog", () => {
  return function MockDeleteCompetitorDialog({
    competitor,
    open,
  }: {
    competitor: any;
    open: boolean;
  }) {
    return open ? (
      <div data-testid="delete-dialog">Delete {competitor?.domain}</div>
    ) : null;
  };
});

const mockCompetitors = [
  {
    id: 1,
    domain: "competitor1.com",
    displayName: "Competitor One",
    notes: "Main competitor",
    trackedKeywordsCount: 50,
  },
  {
    id: 2,
    domain: "competitor2.com",
    displayName: "Competitor Two",
    notes: null,
    trackedKeywordsCount: 25,
  },
];

describe("CompetitorList", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe("Test 1: Renders competitor list", () => {
    it("displays all competitors when data is loaded", async () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: mockCompetitors,
        meta: { limit: 3, used: 2 },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      expect(screen.getByText("competitor1.com")).toBeInTheDocument();
      expect(screen.getByText("competitor2.com")).toBeInTheDocument();
      expect(screen.getByText("Competitor One")).toBeInTheDocument();
      expect(screen.getByText("Competitor Two")).toBeInTheDocument();
    });
  });

  describe("Test 2: Shows empty state", () => {
    it("displays empty state message when no competitors", () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: [],
        meta: { limit: 3, used: 0 },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      expect(screen.getByText("No competitors added yet")).toBeInTheDocument();
      expect(
        screen.getByText("Add your first competitor to start tracking keyword positions.")
      ).toBeInTheDocument();
    });
  });

  describe("Test 3: Shows competitor limit indicator", () => {
    it("displays limit indicator with correct count", () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: mockCompetitors,
        meta: { limit: 3, used: 2 },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      expect(screen.getByText("2/3 competitors used")).toBeInTheDocument();
    });

    it("shows fallback limit when meta is missing", () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: [mockCompetitors[0]],
        meta: undefined,
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      // Falls back to length of competitors array for "used"
      expect(screen.getByText("1/3 competitors used")).toBeInTheDocument();
    });
  });

  describe("Test 4: Shows competitor stats", () => {
    it("displays tracked keywords count for each competitor", () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: mockCompetitors,
        meta: { limit: 3, used: 2 },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      expect(screen.getByText("50")).toBeInTheDocument();
      expect(screen.getByText("25")).toBeInTheDocument();
      expect(screen.getAllByText("Tracked keywords").length).toBe(2);
    });
  });

  describe("Loading state", () => {
    it("shows loading skeletons while loading", () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: [],
        meta: undefined,
        isLoading: true,
        error: null,
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      // Check for skeleton cards (animate-pulse class)
      const skeletons = document.querySelectorAll(".animate-pulse");
      expect(skeletons.length).toBeGreaterThan(0);
    });
  });

  describe("Error state", () => {
    it("shows error message when API fails", () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: [],
        meta: undefined,
        isLoading: false,
        error: { message: "Network error" },
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      expect(screen.getByText("Failed to load competitors")).toBeInTheDocument();
      expect(screen.getByText("Network error")).toBeInTheDocument();
    });

    it("shows retry button on error", () => {
      const mockMutate = jest.fn();
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: [],
        meta: undefined,
        isLoading: false,
        error: { message: "Network error" },
        mutate: mockMutate,
      });

      render(<CompetitorList />);

      const retryBtn = screen.getByText("Retry");
      expect(retryBtn).toBeInTheDocument();

      fireEvent.click(retryBtn);
      expect(mockMutate).toHaveBeenCalled();
    });
  });

  describe("Edit and Delete interactions", () => {
    it("opens edit modal when edit button clicked", async () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: mockCompetitors,
        meta: { limit: 3, used: 2 },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      const editButtons = screen.getAllByLabelText(/Edit /);
      fireEvent.click(editButtons[0]);

      expect(screen.getByTestId("edit-modal")).toBeInTheDocument();
      expect(screen.getByText("Edit competitor1.com")).toBeInTheDocument();
    });

    it("opens delete dialog when delete button clicked", async () => {
      (competitorsApi.useCompetitors as jest.Mock).mockReturnValue({
        competitors: mockCompetitors,
        meta: { limit: 3, used: 2 },
        isLoading: false,
        error: null,
        mutate: jest.fn(),
      });

      render(<CompetitorList />);

      const deleteButtons = screen.getAllByLabelText(/Delete /);
      fireEvent.click(deleteButtons[0]);

      expect(screen.getByTestId("delete-dialog")).toBeInTheDocument();
      expect(screen.getByText("Delete competitor1.com")).toBeInTheDocument();
    });
  });
});
