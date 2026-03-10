import * as React from "react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";

import { LocationManagementPage } from "@/components/local/LocationManagementPage";

const mockPush = vi.fn();
const mockMutate = vi.fn();
const mockCreateLocation = vi.fn();
const mockUpdateLocation = vi.fn();
const mockDeleteLocation = vi.fn();

const hookState = vi.hoisted(() => ({
  locations: [] as Array<Record<string, unknown>>,
  meta: {
    current_page: 1,
    per_page: 15,
    total: 0,
    last_page: 1,
  },
  isLoading: false,
  error: null as Error | null,
}));

vi.mock("next/navigation", () => ({
  useRouter: () => ({ push: mockPush }),
  usePathname: () => "/sites/1/local",
  useSearchParams: () => new URLSearchParams(),
}));

vi.mock("next/link", () => ({
  default: ({ children, href }: { children: React.ReactNode; href: string }) => <a href={href}>{children}</a>,
}));

vi.mock("sonner", () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

vi.mock("@/lib/hooks/useLocations", () => ({
  useLocations: () => ({
    locations: hookState.locations,
    meta: hookState.meta,
    isLoading: hookState.isLoading,
    error: hookState.error,
    mutate: mockMutate,
  }),
  useLocation: () => ({
    location: null,
    isLoading: false,
    error: null,
    mutate: mockMutate,
  }),
  useCreateLocation: () => ({ createLocation: mockCreateLocation }),
  useUpdateLocation: () => ({ updateLocation: mockUpdateLocation }),
  useDeleteLocation: () => ({ deleteLocation: mockDeleteLocation }),
}));

describe("LocationManagementPage endpoint states", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    hookState.locations = [];
    hookState.meta = {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1,
    };
    hookState.isLoading = false;
    hookState.error = null;
  });

  it("renders the production empty-state when the locations endpoint returns no rows", () => {
    render(<LocationManagementPage siteId="1" />);

    expect(screen.getByRole("heading", { name: "No locations yet" })).toBeInTheDocument();
    expect(screen.getByText(/Add your first business location/i)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /Add your first location/i })).toBeInTheDocument();
  });

  it("renders the endpoint error state and lets the user retry", async () => {
    const user = userEvent.setup();
    hookState.error = new Error("Please sign in to manage locations");

    render(<LocationManagementPage siteId="1" />);

    expect(screen.getByText("Failed to load locations")).toBeInTheDocument();
    expect(screen.getByText("Please sign in to manage locations")).toBeInTheDocument();

    await user.click(screen.getByRole("button", { name: /Try again/i }));

    expect(mockMutate).toHaveBeenCalledTimes(1);
  });
});
