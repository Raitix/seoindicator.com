import * as React from "react";
import { describe, it, expect, beforeEach, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";

import { LocationManagementPage } from "@/components/local/LocationManagementPage";

const mockPush = vi.fn();
const mockMutate = vi.fn();
const mockCreateLocation = vi.fn();
const mockUpdateLocation = vi.fn();
const mockDeleteLocation = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ push: mockPush }),
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

const mockLocations = [
  {
    id: 1,
    siteId: 1,
    name: "Main Office",
    address: "123 Main Street, New York, NY 10001",
    phone: "+1 (555) 123-4567",
    website: "https://example.com",
    category: "Restaurant",
    latitude: 40.7128,
    longitude: -74.006,
    isGbpConnected: true,
    gbpLocationId: "abc",
    isActive: true,
    createdAt: "2026-03-01T00:00:00Z",
    updatedAt: "2026-03-08T00:00:00Z",
    reviewStats: null,
    insightsSummary: null,
    rating: 4.5,
    reviewCount: 120,
    packPosition: 3,
  },
  {
    id: 2,
    siteId: 1,
    name: "Downtown Branch",
    address: "456 Oak Avenue, New York, NY 10002",
    phone: null,
    website: null,
    category: "Restaurant",
    latitude: null,
    longitude: null,
    isGbpConnected: false,
    gbpLocationId: null,
    isActive: true,
    createdAt: "2026-03-02T00:00:00Z",
    updatedAt: "2026-03-09T00:00:00Z",
    reviewStats: null,
    insightsSummary: null,
    rating: null,
    reviewCount: null,
    packPosition: null,
  },
];

vi.mock("@/lib/hooks/useLocations", () => ({
  useLocations: () => ({
    locations: mockLocations,
    meta: {
      current_page: 1,
      per_page: 15,
      total: 2,
      last_page: 1,
    },
    isLoading: false,
    error: null,
    mutate: mockMutate,
  }),
  useCreateLocation: () => ({ createLocation: mockCreateLocation }),
  useUpdateLocation: () => ({ updateLocation: mockUpdateLocation }),
  useDeleteLocation: () => ({ deleteLocation: mockDeleteLocation }),
}));

describe("LocationManagementPage (real)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockCreateLocation.mockResolvedValue({ id: 3 });
    mockUpdateLocation.mockResolvedValue({ id: 1 });
    mockDeleteLocation.mockResolvedValue(undefined);
  });

  it("renders locations and summary cards", () => {
    render(<LocationManagementPage siteId="1" />);

    expect(screen.getByRole("heading", { name: "Location management" })).toBeInTheDocument();
    expect(screen.getByText("Main Office")).toBeInTheDocument();
    expect(screen.getByText("Downtown Branch")).toBeInTheDocument();
    expect(screen.getByText("Tracked locations")).toBeInTheDocument();
    expect(screen.getByText("GBP connected")).toBeInTheDocument();
  });

  it("opens the add location modal", async () => {
    const user = userEvent.setup();
    render(<LocationManagementPage siteId="1" />);

    await user.click(screen.getByRole("button", { name: /add location/i }));

    expect(screen.getByRole("dialog")).toBeInTheDocument();
    expect(screen.getByText("Add new location")).toBeInTheDocument();
  });

  it("opens the edit modal with location data", async () => {
    const user = userEvent.setup();
    render(<LocationManagementPage siteId="1" />);

    await user.click(screen.getByRole("button", { name: "Edit Main Office" }));

    expect(screen.getByText("Edit location")).toBeInTheDocument();
    expect(screen.getByDisplayValue("Main Office")).toBeInTheDocument();
    expect(screen.getByDisplayValue("123 Main Street, New York, NY 10001")).toBeInTheDocument();
  });

  it("opens the delete confirmation dialog", async () => {
    const user = userEvent.setup();
    render(<LocationManagementPage siteId="1" />);

    await user.click(screen.getByRole("button", { name: "Delete Main Office" }));

    expect(screen.getByRole("alertdialog")).toBeInTheDocument();
    expect(screen.getByText("Delete location?")).toBeInTheDocument();
    expect(screen.getByText(/permanently remove/i)).toBeInTheDocument();
  });
});
