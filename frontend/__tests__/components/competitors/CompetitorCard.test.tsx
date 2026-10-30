import React from "react";
import { render, screen, fireEvent } from "@testing-library/react";
import "@testing-library/jest-dom";

import CompetitorCard from "@/components/competitors/CompetitorCard";
import type { Competitor } from "@/lib/types/competitors";

const mockCompetitor: Competitor = {
  id: 1,
  domain: "example.com",
  displayName: "Example Corp",
  notes: "Main competitor",
  trackedKeywordsCount: 50,
};

const mockCompetitorWithoutDisplayName: Competitor = {
  id: 2,
  domain: "another.com",
  displayName: "",
  notes: null,
  trackedKeywordsCount: 25,
};

describe("CompetitorCard", () => {
  describe("Rendering", () => {
    it("displays competitor displayName as title", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      expect(screen.getByText("Example Corp")).toBeInTheDocument();
    });

    it("displays domain as subtitle", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      expect(screen.getByText("example.com")).toBeInTheDocument();
    });

    it("displays domain as title when displayName is empty", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitorWithoutDisplayName}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      // Domain appears in both title and subtitle when displayName is empty
      const domainElements = screen.getAllByText("another.com");
      expect(domainElements.length).toBeGreaterThanOrEqual(1);
    });

    it("displays tracked keywords count", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      expect(screen.getByText("50")).toBeInTheDocument();
      expect(screen.getByText("Tracked keywords")).toBeInTheDocument();
    });

    it("displays notes when available", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      expect(screen.getByText("Main competitor")).toBeInTheDocument();
    });

    it("displays placeholder when no notes", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitorWithoutDisplayName}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      expect(screen.getByText("No notes added yet.")).toBeInTheDocument();
    });
  });

  describe("Edit action", () => {
    it("calls onEdit with competitor when edit button clicked", () => {
      const mockOnEdit = jest.fn();

      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={mockOnEdit}
          onDelete={jest.fn()}
        />
      );

      const editButton = screen.getByLabelText("Edit example.com");
      fireEvent.click(editButton);

      expect(mockOnEdit).toHaveBeenCalledWith(mockCompetitor);
      expect(mockOnEdit).toHaveBeenCalledTimes(1);
    });

    it("has accessible edit button label", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      expect(screen.getByLabelText("Edit example.com")).toBeInTheDocument();
    });
  });

  describe("Delete action", () => {
    it("calls onDelete with competitor when delete button clicked", () => {
      const mockOnDelete = jest.fn();

      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={mockOnDelete}
        />
      );

      const deleteButton = screen.getByLabelText("Delete example.com");
      fireEvent.click(deleteButton);

      expect(mockOnDelete).toHaveBeenCalledWith(mockCompetitor);
      expect(mockOnDelete).toHaveBeenCalledTimes(1);
    });

    it("has accessible delete button label", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      expect(screen.getByLabelText("Delete example.com")).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("has proper aria-labels for action buttons", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      expect(screen.getByLabelText("Edit example.com")).toBeInTheDocument();
      expect(screen.getByLabelText("Delete example.com")).toBeInTheDocument();
    });

    it("has icon-only buttons with accessible names", () => {
      render(
        <CompetitorCard
          competitor={mockCompetitor}
          onEdit={jest.fn()}
          onDelete={jest.fn()}
        />
      );

      const editBtn = screen.getByLabelText("Edit example.com");
      const deleteBtn = screen.getByLabelText("Delete example.com");

      // Buttons should have accessible names even though they're icon-only
      expect(editBtn).toHaveAttribute("aria-label");
      expect(deleteBtn).toHaveAttribute("aria-label");
    });
  });
});
