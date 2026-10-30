import * as React from "react";
import { describe, it, expect, beforeEach, vi } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";

import { LocationForm } from "@/components/local/LocationForm";

const { mockGeocodeAddress, mockToast } = vi.hoisted(() => ({
  mockGeocodeAddress: vi.fn(),
  mockToast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

vi.mock("@/lib/api/geocode", () => ({
  geocodeAddress: (address: string) => mockGeocodeAddress(address),
}));

vi.mock("sonner", () => ({
  toast: mockToast,
}));

describe("LocationForm (real)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGeocodeAddress.mockResolvedValue({
      latitude: 40.7128,
      longitude: -74.006,
      displayName: "123 Main Street, New York, NY 10001",
    });
  });

  it("geocodes an address and shows coordinates", async () => {
    const user = userEvent.setup();
    render(
      <LocationForm
        open
        mode="add"
        onClose={vi.fn()}
        onSubmit={vi.fn()}
      />
    );

    await user.type(screen.getByTestId("address-input"), "123 Main Street, New York, NY 10001");
    await user.tab();

    await waitFor(() => {
      expect(mockGeocodeAddress).toHaveBeenCalledWith("123 Main Street, New York, NY 10001");
    });

    expect(screen.getByText("Coordinates confirmed")).toBeInTheDocument();
    expect(screen.getByText(/40\.712800, -74\.006000/)).toBeInTheDocument();
  });

  it("normalizes website values and submits coordinates", async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn().mockResolvedValue(undefined);

    render(
      <LocationForm
        open
        mode="add"
        onClose={vi.fn()}
        onSubmit={onSubmit}
      />
    );

    await user.type(screen.getByTestId("name-input"), "Main Office");
    await user.type(screen.getByTestId("address-input"), "123 Main Street, New York, NY 10001");
    await user.tab();

    await waitFor(() => {
      expect(mockGeocodeAddress).toHaveBeenCalled();
    });

    await user.type(screen.getByTestId("website-input"), "example.com");
    await user.selectOptions(screen.getByTestId("category-select"), "Restaurant");
    await user.click(screen.getByRole("button", { name: /add location/i }));

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          name: "Main Office",
          address: "123 Main Street, New York, NY 10001",
          website: "https://example.com",
          category: "Restaurant",
          latitude: 40.7128,
          longitude: -74.006,
        })
      );
    });
  });

  it("shows validation feedback for an invalid phone number", async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    render(
      <LocationForm
        open
        mode="add"
        onClose={vi.fn()}
        onSubmit={onSubmit}
      />
    );

    await user.type(screen.getByTestId("name-input"), "Main Office");
    await user.type(screen.getByTestId("address-input"), "123 Main Street, New York, NY 10001");
    await user.type(screen.getByTestId("phone-input"), "bad-phone");
    await user.click(screen.getByRole("button", { name: /add location/i }));

    expect(await screen.findByText("Please enter a valid phone number")).toBeInTheDocument();
    expect(onSubmit).not.toHaveBeenCalled();
  });
});
