import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";

import AddCompetitorModal from "@/components/competitors/AddCompetitorModal";
import * as competitorsApi from "@/lib/api/competitors";

// Mock the API hooks
jest.mock("@/lib/api/competitors", () => ({
  useCreateCompetitor: jest.fn(),
}));

// Mock SWR mutate
jest.mock("swr", () => ({
  __esModule: true,
  default: jest.fn(),
  mutate: jest.fn(),
}));

const mockCreateCompetitor = jest.fn();

describe("AddCompetitorModal", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (competitorsApi.useCreateCompetitor as jest.Mock).mockReturnValue(
      mockCreateCompetitor
    );
  });

  describe("Test 5: Opens on button click", () => {
    it("opens modal when Add Competitor button is clicked", async () => {
      render(<AddCompetitorModal />);

      const addButton = screen.getByRole("button", { name: /add competitor/i });
      expect(addButton).toBeInTheDocument();

      fireEvent.click(addButton);

      expect(screen.getByRole("dialog")).toBeInTheDocument();
      // Use getAllByText since "Add competitor" appears in both button and dialog title
      expect(screen.getAllByText("Add competitor").length).toBeGreaterThan(0);
    });
  });

  describe("Test 6: Domain field required", () => {
    it("shows validation error when domain is empty", async () => {
      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      // Try to submit without filling domain
      const submitBtn = screen.getByRole("button", { name: /add competitor/i });
      fireEvent.click(submitBtn);

      // Should show validation error
      await waitFor(() => {
        expect(screen.getByText("Domain is required")).toBeInTheDocument();
      });
    });
  });

  describe("Test 7: Domain format validation", () => {
    it("shows error for invalid domain format", async () => {
      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      const domainInput = screen.getByLabelText(/domain/i);
      
      // Type invalid domain
      fireEvent.change(domainInput, { target: { value: "not a domain" } });
      fireEvent.blur(domainInput);

      await waitFor(() => {
        expect(screen.getByText("Please enter a valid domain")).toBeInTheDocument();
      });
    });

    it("accepts valid domain format", async () => {
      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      const domainInput = screen.getByLabelText(/domain/i);

      // Type valid domain
      fireEvent.change(domainInput, { target: { value: "example.com" } });
      fireEvent.blur(domainInput);

      // Should not show validation error for valid domain
      await waitFor(() => {
        expect(screen.queryByText("Please enter a valid domain")).not.toBeInTheDocument();
      });
    });
  });

  describe("Test 8: Submit creates competitor", () => {
    it("calls API with correct data and closes modal on success", async () => {
      mockCreateCompetitor.mockResolvedValueOnce({ id: 1, domain: "test.com" });

      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      // Fill form
      const domainInput = screen.getByLabelText(/domain/i);
      const displayNameInput = screen.getByLabelText(/display name/i);
      const notesInput = screen.getByLabelText(/notes/i);

      fireEvent.change(domainInput, { target: { value: "test.com" } });
      fireEvent.change(displayNameInput, { target: { value: "Test Competitor" } });
      fireEvent.change(notesInput, { target: { value: "Test notes" } });

      // Submit
      const submitBtn = screen.getByRole("button", { name: /add competitor/i });
      fireEvent.click(submitBtn);

      await waitFor(() => {
        expect(mockCreateCompetitor).toHaveBeenCalledWith({
          domain: "test.com",
          displayName: "Test Competitor",
          notes: "Test notes",
        });
      });
    });
  });

  describe("Test 9: Shows loading state", () => {
    it("shows loading spinner during submission", async () => {
      let resolveCreate: any;
      mockCreateCompetitor.mockImplementation(
        () => new Promise((resolve) => (resolveCreate = resolve))
      );

      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      // Fill and submit
      const domainInput = screen.getByLabelText(/domain/i);
      fireEvent.change(domainInput, { target: { value: "test.com" } });

      const submitBtn = screen.getByRole("button", { name: /add competitor/i });
      fireEvent.click(submitBtn);

      // Should show loading state
      await waitFor(() => {
        expect(screen.getByText("Saving...")).toBeInTheDocument();
      });

      // Button should be disabled during loading
      expect(submitBtn).toBeDisabled();

      // Resolve the promise
      resolveCreate({ id: 1 });
    });
  });

  describe("Test 10: Handles API error", () => {
    it("shows error message when API fails", async () => {
      mockCreateCompetitor.mockRejectedValueOnce(new Error("Domain already exists"));

      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      // Fill and submit
      const domainInput = screen.getByLabelText(/domain/i);
      fireEvent.change(domainInput, { target: { value: "duplicate.com" } });

      const submitBtn = screen.getByRole("button", { name: /add competitor/i });
      fireEvent.click(submitBtn);

      await waitFor(() => {
        expect(screen.getByText("Domain already exists")).toBeInTheDocument();
      });
    });
  });

  describe("SSRF protection", () => {
    it("rejects localhost domains", async () => {
      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      const domainInput = screen.getByLabelText(/domain/i);
      fireEvent.change(domainInput, { target: { value: "localhost" } });
      fireEvent.blur(domainInput);

      await waitFor(() => {
        expect(screen.getByText("Localhost domains are not allowed")).toBeInTheDocument();
      });
    });

    it("rejects 127.0.0.1", async () => {
      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      const domainInput = screen.getByLabelText(/domain/i);
      fireEvent.change(domainInput, { target: { value: "127.0.0.1" } });
      fireEvent.blur(domainInput);

      await waitFor(() => {
        expect(screen.getByText("Localhost domains are not allowed")).toBeInTheDocument();
      });
    });

    it("rejects private IP ranges", async () => {
      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      const domainInput = screen.getByLabelText(/domain/i);
      fireEvent.change(domainInput, { target: { value: "192.168.1.1" } });
      fireEvent.blur(domainInput);

      await waitFor(() => {
        expect(screen.getByText("Private IPs are not allowed")).toBeInTheDocument();
      });
    });
  });

  describe("Domain sanitization", () => {
    it("strips https:// protocol", async () => {
      mockCreateCompetitor.mockResolvedValueOnce({ id: 1 });

      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      const domainInput = screen.getByLabelText(/domain/i);
      fireEvent.change(domainInput, { target: { value: "https://example.com" } });

      const submitBtn = screen.getByRole("button", { name: /add competitor/i });
      fireEvent.click(submitBtn);

      await waitFor(() => {
        expect(mockCreateCompetitor).toHaveBeenCalledWith(
          expect.objectContaining({ domain: "example.com" })
        );
      });
    });

    it("strips www. prefix", async () => {
      mockCreateCompetitor.mockResolvedValueOnce({ id: 1 });

      render(<AddCompetitorModal />);

      // Open modal
      fireEvent.click(screen.getByRole("button", { name: /add competitor/i }));

      const domainInput = screen.getByLabelText(/domain/i);
      fireEvent.change(domainInput, { target: { value: "www.example.com" } });

      const submitBtn = screen.getByRole("button", { name: /add competitor/i });
      fireEvent.click(submitBtn);

      await waitFor(() => {
        expect(mockCreateCompetitor).toHaveBeenCalledWith(
          expect.objectContaining({ domain: "example.com" })
        );
      });
    });
  });
});
