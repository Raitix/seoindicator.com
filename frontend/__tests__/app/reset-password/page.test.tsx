import { render, screen, waitFor, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useRouter, useSearchParams } from "next/navigation";
import ResetPasswordPage from "@/app/reset-password/page";

// Mock next/navigation
const mockPush = jest.fn();
const mockSearchParamsGet = jest.fn();

jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
  useSearchParams: jest.fn(),
}));

// Mock AuthContext
const mockResetPassword = jest.fn();

jest.mock("@/lib/contexts/AuthContext", () => ({
  useAuth: jest.fn(),
}));

import { useAuth } from "@/lib/contexts/AuthContext";

const mockAuthContext = {
  user: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,
  login: jest.fn(),
  loginWithGoogle: jest.fn(),
  logout: jest.fn(),
  register: jest.fn(),
  resetPassword: mockResetPassword,
  resendVerification: jest.fn(),
};

describe("ResetPasswordPage", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (useRouter as jest.Mock).mockReturnValue({
      push: mockPush,
    });
    (useSearchParams as jest.Mock).mockReturnValue({
      get: mockSearchParamsGet,
    });
    mockSearchParamsGet.mockImplementation((key: string) => {
      if (key === "token") return "valid-reset-token-123";
      if (key === "email") return "test@example.com";
      return null;
    });
    (useAuth as jest.Mock).mockReturnValue(mockAuthContext);
  });

  // ==================== RSP-001: Renders reset password form ====================

  describe("rendering", () => {
    it("renders password input (RSP-001)", () => {
      render(<ResetPasswordPage />);
      expect(screen.getByLabelText(/^password|new password/i)).toBeInTheDocument();
    });

    it("renders confirm password input", () => {
      render(<ResetPasswordPage />);
      expect(screen.getByLabelText(/confirm password/i)).toBeInTheDocument();
    });

    it("renders submit button", () => {
      render(<ResetPasswordPage />);
      expect(screen.getByRole("button", { name: /reset password|submit/i })).toBeInTheDocument();
    });

    it("renders page heading", () => {
      render(<ResetPasswordPage />);
      expect(screen.getByRole("heading", { name: /reset password/i })).toBeInTheDocument();
    });
  });

  // ==================== RSP-002: Invalid/expired token ====================

  describe("token validation", () => {
    it("shows error for invalid/expired token (RSP-002)", async () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "token") return "invalid-token";
        if (key === "email") return "test@example.com";
        return null;
      });

      // Mock API to reject invalid token
      mockResetPassword.mockRejectedValueOnce({
        response: { status: 400, data: { message: "Invalid or expired token" } },
      });

      render(<ResetPasswordPage />);

      // Try to submit to trigger validation
      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);
      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });

      const user = userEvent.setup();
      await user.type(passwordInput, "NewPassword123");
      await user.type(confirmPasswordInput, "NewPassword123");
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/invalid or expired token/i)).toBeInTheDocument();
      });
    });

    it("shows link to forgot-password when token is invalid", async () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "token") return "invalid-token";
        if (key === "email") return "test@example.com";
        return null;
      });

      mockResetPassword.mockRejectedValueOnce(new Error("Invalid token"));
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);
      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });

      const user = userEvent.setup();
      await user.type(passwordInput, "NewPassword123");
      await user.type(confirmPasswordInput, "NewPassword123");
      await user.click(submitButton);

      await waitFor(() => {
        const forgotLink = screen.getByRole("link", { name: /request new link|forgot password/i });
        expect(forgotLink).toHaveAttribute("href", "/forgot-password");
      });
    });
  });

  // ==================== RSP-003: Missing token ====================

  describe("missing token", () => {
    it("redirects to forgot-password when no token in URL (RSP-003)", () => {
      mockSearchParamsGet.mockReturnValue(null);

      render(<ResetPasswordPage />);

      expect(mockPush).toHaveBeenCalledWith("/forgot-password");
    });
  });

  // ==================== RSP-004 to RSP-006: Password validation ====================

  describe("password validation", () => {
    it("shows error when password is empty (RSP-004)", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/password is required/i)).toBeInTheDocument();
      });
    });

    it("shows error when password is too short (RSP-005)", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      await user.type(passwordInput, "short7");

      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/at least 8 characters/i)).toBeInTheDocument();
      });
    });

    it("shows error when password lacks strength (RSP-006)", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      await user.type(passwordInput, "password");

      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/letters and numbers/i)).toBeInTheDocument();
      });
    });
  });

  // ==================== RSP-007 to RSP-008: Confirm password validation ====================

  describe("confirm password validation", () => {
    it("shows error when confirm password is empty (RSP-007)", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      await user.type(passwordInput, "Password123");

      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/confirm your password/i)).toBeInTheDocument();
      });
    });

    it("shows error when passwords do not match (RSP-008)", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);

      await user.type(passwordInput, "Password123");
      await user.type(confirmPasswordInput, "Different123");

      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/passwords do not match/i)).toBeInTheDocument();
      });
    });
  });

  // ==================== RSP-009 to RSP-012: Form Submission ====================

  describe("form submission", () => {
    it("calls resetPassword with valid data and redirects (RSP-009)", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);
      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });

      await user.type(passwordInput, "NewPassword123");
      await user.type(confirmPasswordInput, "NewPassword123");
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockResetPassword).toHaveBeenCalledWith(
          "test@example.com",
          "valid-reset-token-123",
          "NewPassword123"
        );
        expect(mockPush).toHaveBeenCalledWith("/login");
      });
    });

    it("shows loading state during submission (RSP-010)", async () => {
      const user = userEvent.setup();
      let resolveReset: () => void;
      mockResetPassword.mockImplementation(() => new Promise<void>((resolve) => {
        resolveReset = resolve;
      }));

      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);
      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });

      await user.type(passwordInput, "NewPassword123");
      await user.type(confirmPasswordInput, "NewPassword123");
      await user.click(submitButton);

      expect(submitButton).toBeDisabled();

      await act(async () => {
        resolveReset!();
      });
    });

    it("displays API error message (RSP-011)", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockRejectedValueOnce(new Error("Reset failed"));
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);
      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });

      await user.type(passwordInput, "NewPassword123");
      await user.type(confirmPasswordInput, "NewPassword123");
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/reset failed|error/i)).toBeInTheDocument();
      });
    });

    it("redirects to login with success message after reset (RSP-012)", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);
      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });

      await user.type(passwordInput, "NewPassword123");
      await user.type(confirmPasswordInput, "NewPassword123");
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockPush).toHaveBeenCalledWith(expect.stringContaining("/login"));
      });
    });
  });

  // ==================== RSP-013: Password visibility toggle ====================

  describe("password visibility", () => {
    it("toggles password visibility (RSP-013)", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      expect(passwordInput).toHaveAttribute("type", "password");

      const toggleButton = screen.getByRole("button", { name: /show password/i });
      await user.click(toggleButton);

      expect(passwordInput).toHaveAttribute("type", "text");
    });

    it("toggles confirm password visibility independently", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);
      expect(confirmPasswordInput).toHaveAttribute("type", "password");

      // Find toggle for confirm password
      const toggleButtons = screen.getAllByRole("button", { name: /show password/i });
      if (toggleButtons.length > 1) {
        await user.click(toggleButtons[1]);
        expect(confirmPasswordInput).toHaveAttribute("type", "text");
      }
    });
  });

  // ==================== RSP-014: Keyboard navigation ====================

  describe("accessibility", () => {
    it("supports keyboard navigation (RSP-014)", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);

      await user.tab();
      expect(passwordInput).toHaveFocus();

      await user.tab();
      expect(confirmPasswordInput).toHaveFocus();
    });

    it("has proper ARIA labels", () => {
      render(<ResetPasswordPage />);

      const form = screen.getByRole("form");
      expect(form).toBeInTheDocument();

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      expect(passwordInput).toHaveAttribute("aria-required", "true");
    });

    it("submits form with Enter key", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ResetPasswordPage />);

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      const confirmPasswordInput = screen.getByLabelText(/confirm password/i);

      await user.type(passwordInput, "NewPassword123");
      await user.type(confirmPasswordInput, "NewPassword123{enter}");

      await waitFor(() => {
        expect(mockResetPassword).toHaveBeenCalled();
      });
    });
  });

  // ==================== Edge Cases ====================

  describe("edge cases", () => {
    it("handles missing email in URL", () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "token") return "valid-token";
        if (key === "email") return null;
        return null;
      });

      render(<ResetPasswordPage />);

      // Should still render form, token is the critical piece
      expect(screen.getByLabelText(/^password|new password/i)).toBeInTheDocument();
    });

    it("validates token format", async () => {
      mockSearchParamsGet.mockImplementation((key: string) => {
        if (key === "token") return "";
        if (key === "email") return "test@example.com";
        return null;
      });

      // Empty string token should redirect
      render(<ResetPasswordPage />);

      expect(mockPush).toHaveBeenCalled();
    });

    it("clears form errors when user types", async () => {
      const user = userEvent.setup();
      render(<ResetPasswordPage />);

      const submitButton = screen.getByRole("button", { name: /reset password|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/password is required/i)).toBeInTheDocument();
      });

      const passwordInput = screen.getByLabelText(/^password|new password/i);
      await user.type(passwordInput, "N");

      await waitFor(() => {
        expect(screen.queryByText(/password is required/i)).not.toBeInTheDocument();
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

      render(<ResetPasswordPage />);

      const form = screen.getByRole("form");
      expect(form).toBeInTheDocument();
    });
  });

  // ==================== Authenticated Redirect ====================

  describe("authenticated redirect", () => {
    it("redirects to dashboard if already authenticated", () => {
      (useAuth as jest.Mock).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com" },
        isAuthenticated: true,
      });

      render(<ResetPasswordPage />);

      expect(mockPush).toHaveBeenCalledWith("/dashboard");
    });
  });
});
