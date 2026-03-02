import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import "@testing-library/jest-dom";

import DeleteCompetitorDialog from "@/components/competitors/DeleteCompetitorDialog";
import * as competitorsApi from "@/lib/api/competitors";
import type { Competitor } from "@/lib/types/competitors";

// Mock the API hooks
jest.mock("@/lib/api/competitors", () => ({
  useDeleteCompetitor: jest.fn(),
}));

// Mock SWR mutate
jest.mock("swr", () => ({
  __esModule: true,
  default: jest.fn(),
  mutate: jest.fn(),
}));

const mockDeleteCompetitor = jest.fn();

const mockCompetitor: Competitor = {
  id: 1,
  domain: "example.com",
  displayName: "Example Corp",
  notes: "Main competitor",
  trackedKeywordsCount: 50,
};

describe("DeleteCompetitorDialog", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (competitorsApi.useDeleteCompetitor as jest.Mock).mockReturnValue(
      mockDeleteCompetitor
    );
  });

  describe("Test 14: Shows confirmation dialog", () => {
    it("displays confirmation dialog with competitor domain", () => {
      render(
        <DeleteCompetitorDialog
          competitor={mockCompetitor}
          open={true}
          onClose={jest.fn()}
        />
      );

      expect(screen.getByRole("dialog")).toBeInTheDocument();
      expect(screen.getByText("Delete competitor")).toBeInTheDocument();
      expect(screen.getByText(/example.com/)).toBeInTheDocument();
    });

    it("shows warning message about deletion", () => {
      render(
        <DeleteCompetitorDialog
          competitor={mockCompetitor}
          open={true}
          onClose={jest.fn()}
        />
      );

      expect(
        screen.getByText(/This will remove example.com from your tracked competitors/)
      ).toBeInTheDocument();
    });

    it("does not render when open is false", () => {
      render(
        <DeleteCompetitorDialog
          competitor={mockCompetitor}
          open={false}
          onClose={jest.fn()}
        />
      );

      expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    });
  });

  describe("Test 15: Cancel closes dialog", () => {
    it("closes dialog without deletion when Cancel is clicked", () => {
      const mockOnClose = jest.fn();

      render(
        <DeleteCompetitorDialog
          competitor={mockCompetitor}
          open={true}
          onClose={mockOnClose}
        />
      );

      const cancelButton = screen.getByRole("button", { name: /cancel/i });
      fireEvent.click(cancelButton);

      expect(mockOnClose).toHaveBeenCalled();
      expect(mockDeleteCompetitor).not.toHaveBeenCalled();
    });
  });

  describe("Test 16: Confirm deletes competitor", () => {
    it("calls DELETE API when confirm is clicked", async () => {
      mockDeleteCompetitor.mockResolvedValueOnce({ success: true });
      const mockOnClose = jest.fn();

      render(
        <DeleteCompetitorDialog
          competitor={mockCompetitor}
          open={true}
          onClose={mockOnClose}
        />
      );

      const deleteButton = screen.getByRole("button", { name: /delete/i });
      fireEvent.click(deleteButton);

      await waitFor(() => {
        expect(mockDeleteCompetitor).toHaveBeenCalledWith(1);
      });

      // Dialog should close on success
      expect(mockOnClose).toHaveBeenCalled();
    });

    it("shows loading state during deletion", async () => {
      let resolveDelete: any;
      mockDeleteCompetitor.mockImplementation(
        () => new Promise((resolve) => (resolveDelete = resolve))
      );

      render(
        <DeleteCompetitorDialog
          competitor={mockCompetitor}
          open={true}
          onClose={jest.fn()}
        />
      );

      const deleteButton = screen.getByRole("button", { name: /delete/i });
      fireEvent.click(deleteButton);

      await waitFor(() => {
        expect(screen.getByText("Deleting...")).toBeInTheDocument();
      });

      // Buttons should be disabled during deletion
      expect(deleteButton).toBeDisabled();

      resolveDelete({ success: true });
    });

    it("shows error message on API failure", async () => {
      mockDeleteCompetitor.mockRejectedValueOnce(new Error("Cannot delete"));
      const mockOnClose = jest.fn();

      render(
        <DeleteCompetitorDialog
          competitor={mockCompetitor}
          open={true}
          onClose={mockOnClose}
        />
      );

      const deleteButton = screen.getByRole("button", { name: /delete/i });
      fireEvent.click(deleteButton);

      await waitFor(() => {
        expect(screen.getByText("Cannot delete")).toBeInTheDocument();
      });

      // Dialog should NOT close on error
      expect(mockOnClose).not.toHaveBeenCalled();
    });
  });

  describe("Button states", () => {
    it("disables both buttons during deletion", async () => {
      let resolveDelete: any;
      mockDeleteCompetitor.mockImplementation(
        () => new Promise((resolve) => (resolveDelete = resolve))
      );

      render(
        <DeleteCompetitorDialog
          competitor={mockCompetitor}
          open={true}
          onClose={jest.fn()}
        />
      );

      const deleteButton = screen.getByRole("button", { name: /delete/i });
      const cancelButton = screen.getByRole("button", { name: /cancel/i });

      fireEvent.click(deleteButton);

      await waitFor(() => {
        expect(deleteButton).toBeDisabled();
        expect(cancelButton).toBeDisabled();
      });

      resolveDelete({ success: true });
    });
  });

  describe("Null competitor handling", () => {
    it("handles null competitor gracefully", () => {
      render(
        <DeleteCompetitorDialog
          competitor={null}
          open={true}
          onClose={jest.fn()}
        />
      );

      // Should render but may show undefined domain text
    });
  });
});
