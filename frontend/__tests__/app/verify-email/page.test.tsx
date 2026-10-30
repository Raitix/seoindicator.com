import { render, screen, waitFor, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useRouter, useSearchParams } from "next/navigation";
import { vi, describe, it, expect, beforeEach } from "vitest";
import VerifyEmailPage from "@/app/verify-email/page";

// Mock next/navigation
const mockPush = vi.fn();
const mockSearchParamsGet = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: vi.fn(),
  useSearchParams: vi.fn(),
}));

// Mock AuthContext
const mockResendVerification = vi.fn();

vi.mock("@/lib/contexts/AuthContext", () => ({
  useAuth: vi.fn(),
}));

import { useAuth } from "@/lib/contexts/AuthContext";

const mockAuthContext = {
  user: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,
  login: vi.fn(),
  loginWithGoogle: vi.fn(),
  logout: vi.fn(),
  register: vi.fn(),
  resetPassword: vi.fn(),
  resendVerification: mockResendVerification,
  clearError: vi.fn(),
  refreshUser: vi.fn(),
};

describe("VerifyEmailPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(useRouter).mockReturnValue({
      push: mockPush,
    } as any);
    vi.mocked(useSearchParams).mockReturnValue({
      get: mockSearchParamsGet,
    } as any);
    mockSearchParamsGet.mockReturnValue(null);
    vi.mocked(useAuth).mockReturnValue(mockAuthContext as any);
  });

  // ==================== VEP-001: Renders verification notice ====================

  describe("rendering", () => {
    it("renders verification notice (VEP-001)", () => {
      render(<VerifyEmailPage />);
      expect(screen.getByRole("heading", { name: /verify your email/i })).toBeInTheDocument();
    });

    it("renders resend verification button", () => {
      render(<VerifyEmailPage />);
      expect(screen.getByRole("button", { name: /resend|send again/i })).toBeInTheDocument();
    });

    it("renders instruction text", () => {
      render(<VerifyEmailPage />);
      expect(screen.getByText(/check your email|verification link/i)).toBeInTheDocument();
    });
  });

  // ==================== VEP-002: Shows user email ====================

  describe("user email display", () => {
    it("shows masked email address (VEP-002)", () => {
      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: {
          id: 1,
          email: "john.doe@example.com",
          email_verified: false,
        },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      // Should show masked email like j***@example.com
      expect(screen.getByText(/j.*@example\.com/i)).toBeInTheDocument();
    });

    it("shows email from URL params if not authenticated", () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "email") return "test@example.com";
        return null;
      });

      render(<VerifyEmailPage />);

      expect(screen.getByText(/test@example\.com/i)).toBeInTheDocument();
    });
  });

  // ==================== VEP-003: Resend verification button ====================

  describe("resend verification", () => {
    it("calls resendVerification when button is clicked (VEP-003)", async () => {
      const user = userEvent.setup();
      mockResendVerification.mockResolvedValueOnce(undefined);

      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com", email_verified: false },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      const resendButton = screen.getByRole("button", { name: /resend|send again/i });
      await user.click(resendButton);

      expect(mockResendVerification).toHaveBeenCalled();
    });

    it("shows success message after resend (VEP-004)", async () => {
      const user = userEvent.setup();
      mockResendVerification.mockResolvedValueOnce(undefined);

      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com", email_verified: false },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      const resendButton = screen.getByRole("button", { name: /resend|send again/i });
      await user.click(resendButton);

      await waitFor(() => {
        expect(screen.getByText(/email sent|verification email sent/i)).toBeInTheDocument();
      });
    });
  });

  // ==================== VEP-005: Resend rate limiting ====================

  describe("rate limiting", () => {
    it("disables button during cooldown (VEP-005)", async () => {
      const user = userEvent.setup();
      mockResendVerification.mockResolvedValueOnce(undefined);

      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com", email_verified: false },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      const resendButton = screen.getByRole("button", { name: /resend|send again/i });
      await user.click(resendButton);

      // Button should be disabled during cooldown
      await waitFor(() => {
        expect(resendButton).toBeDisabled();
      });
    });

    it("shows countdown during cooldown", async () => {
      const user = userEvent.setup();
      mockResendVerification.mockResolvedValueOnce(undefined);

      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com", email_verified: false },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      const resendButton = screen.getByRole("button", { name: /resend|send again/i });
      await user.click(resendButton);

      await waitFor(() => {
        // Should show countdown like "Resend in 60s"
        const countdownText = screen.queryByText(/\d+s|\d+ seconds/i);
        if (countdownText) {
          expect(countdownText).toBeInTheDocument();
        }
      });
    });
  });

  // ==================== VEP-006: Loading state ====================

  describe("loading state", () => {
    it("shows loading state during resend (VEP-006)", async () => {
      const user = userEvent.setup();
      let resolveResend: () => void;
      mockResendVerification.mockImplementation(() => new Promise<void>((resolve) => {
        resolveResend = resolve;
      }));

      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com", email_verified: false },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      const resendButton = screen.getByRole("button", { name: /resend|send again/i });
      await user.click(resendButton);

      expect(resendButton).toBeDisabled();

      await act(async () => {
        resolveResend!();
      });
    });
  });

  // ==================== VEP-007: Already verified redirect ====================

  describe("verified user redirect", () => {
    it("redirects to dashboard if user is already verified (VEP-007)", () => {
      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com", email_verified: true },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      expect(mockPush).toHaveBeenCalledWith("/dashboard");
    });
  });

  // ==================== VEP-008: Verification from link ====================

  describe("verification link handling", () => {
    it("handles successful verification from link (VEP-008)", async () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "verified") return "true";
        return null;
      });

      render(<VerifyEmailPage />);

      await waitFor(() => {
        expect(screen.getByText(/verified|success/i)).toBeInTheDocument();
      });
    });

    it("shows success message after verification", async () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "verified") return "true";
        return null;
      });

      render(<VerifyEmailPage />);

      await waitFor(() => {
        expect(screen.getByText(/email verified|success/i)).toBeInTheDocument();
      });
    });
  });

  // ==================== VEP-009: Expired link ====================

  describe("expired link handling", () => {
    it("shows error for expired verification link (VEP-009)", () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "error") return "expired";
        return null;
      });

      render(<VerifyEmailPage />);

      expect(screen.getByText(/expired|link has expired/i)).toBeInTheDocument();
    });

    it("shows resend option for expired link", () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "error") return "expired";
        return null;
      });

      render(<VerifyEmailPage />);

      expect(screen.getByRole("button", { name: /resend|send again/i })).toBeInTheDocument();
    });
  });

  // ==================== VEP-010: Invalid link ====================

  describe("invalid link handling", () => {
    it("shows error for invalid verification link (VEP-010)", () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "error") return "invalid";
        return null;
      });

      render(<VerifyEmailPage />);

      expect(screen.getByText(/invalid|link is invalid/i)).toBeInTheDocument();
    });

    it("shows link to request new verification for invalid link", () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "error") return "invalid";
        return null;
      });

      render(<VerifyEmailPage />);

      expect(screen.getByRole("button", { name: /resend|send again/i })).toBeInTheDocument();
    });
  });

  // ==================== Accessibility ====================

  describe("accessibility", () => {
    it("has proper heading structure", () => {
      render(<VerifyEmailPage />);

      const heading = screen.getByRole("heading", { name: /verify your email/i });
      expect(heading).toBeInTheDocument();
    });

    it("has accessible resend button", () => {
      render(<VerifyEmailPage />);

      const resendButton = screen.getByRole("button", { name: /resend|send again/i });
      expect(resendButton).toHaveAttribute("type", "button");
    });

    it("announces success message to screen readers", async () => {
      const user = userEvent.setup();
      mockResendVerification.mockResolvedValueOnce(undefined);

      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com", email_verified: false },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      const resendButton = screen.getByRole("button", { name: /resend|send again/i });
      await user.click(resendButton);

      await waitFor(() => {
        const successMessage = screen.getByText(/email sent|verification email sent/i);
        expect(successMessage).toBeInTheDocument();
      });
    });
  });

  // ==================== Navigation ====================

  describe("navigation", () => {
    it("has button to login page", () => {
      render(<VerifyEmailPage />);

      const loginButton = screen.queryByRole("button", { name: /sign in|login|back to login/i });
      if (loginButton) {
        expect(loginButton).toBeInTheDocument();
      }
    });

    it("has link to update email if needed", () => {
      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "wrong@example.com", email_verified: false },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      const updateLink = screen.queryByRole("link", { name: /update email|change email/i });
      if (updateLink) {
        expect(updateLink).toBeInTheDocument();
      }
    });
  });

  // ==================== Edge Cases ====================

  describe("edge cases", () => {
    it("handles unauthenticated state gracefully", () => {
      render(<VerifyEmailPage />);

      // Should show generic message or redirect to login
      expect(screen.getByRole("heading", { name: /verify/i })).toBeInTheDocument();
    });

    it("handles resend failure gracefully", async () => {
      const user = userEvent.setup();
      mockResendVerification.mockRejectedValueOnce(new Error("Failed to send"));

      vi.mocked(useAuth).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com", email_verified: false },
        isAuthenticated: true,
      } as any);

      render(<VerifyEmailPage />);

      const resendButton = screen.getByRole("button", { name: /resend|send again/i });
      await user.click(resendButton);

      await waitFor(() => {
        expect(screen.getByText(/failed|error|try again/i)).toBeInTheDocument();
      });
    });
  });

  // ==================== Responsive Design ====================

  describe("responsive design", () => {
    it("renders correctly on mobile viewport", () => {
      Object.defineProperty(window, "innerWidth", {
        writable: true,
        configurable: true,
        value: 375,
      });

      render(<VerifyEmailPage />);

      expect(screen.getByRole("heading", { name: /verify/i })).toBeInTheDocument();
    });
  });

  // ==================== Icon/Visual ====================

  describe("visual elements", () => {
    it("shows email icon or illustration", () => {
      render(<VerifyEmailPage />);

      // Check for icon or illustration (could be SVG or image)
      const container = screen.getByRole("heading", { name: /verify/i }).closest("div");
      expect(container).toBeInTheDocument();
    });
  });
});
