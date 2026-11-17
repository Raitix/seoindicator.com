/**
 * EmptyState Component Tests - TASK-25-20
 *
 * The history page renders the empty state instead of the table when
 * there are no analyses, so the empty state is covered here.
 *
 * @see SPEC-025
 * @see TASK-25-20
 */

import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import EmptyState from "@/components/history/EmptyState";

const mockOnClearFilters = vi.fn();

beforeEach(() => {
  vi.clearAllMocks();
});

describe("EmptyState", () => {
  it("shows empty message when no data", () => {
    render(<EmptyState hasFilters={false} />);

    expect(screen.getByText(/no analyses yet/i)).toBeInTheDocument();
  });

  it("shows different message when filters applied", () => {
    render(<EmptyState hasFilters={true} />);

    expect(screen.getByText(/no analyses match your filters/i)).toBeInTheDocument();
  });

  it("offers to clear the filters when filters are applied", async () => {
    const user = userEvent.setup();
    render(<EmptyState hasFilters={true} onClearFilters={mockOnClearFilters} />);

    await user.click(screen.getByRole("button", { name: /clear filters/i }));

    expect(mockOnClearFilters).toHaveBeenCalled();
  });

  it("offers to run the first analysis when there is no data", () => {
    render(<EmptyState hasFilters={false} />);

    expect(
      screen.getByRole("button", { name: /run your first analysis/i })
    ).toBeInTheDocument();
  });
});
