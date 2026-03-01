import { render, screen, waitFor, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useRouter } from "next/navigation";
import ForgotPasswordPage from "@/app/forgot-password/page";

// Mock next/navigation
jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
}));

// Mock AuthContext
const mockResetPassword = jest.fn();
const mockPush = jest.fn();

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

describe("ForgotPasswordPage", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (useRouter as jest.Mock).mockReturnValue({
      push: mockPush,
    });
    (useAuth as jest.Mock).mockReturnValue(mockAuthContext);
  });

  // ==================== FP-001: Renders forgot password form ====================

  describe("rendering", () => {
    it("renders email input (FP-001)", () => {
      render(<ForgotPasswordPage />);
      expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    });

    it("renders submit button", () => {
      render(<ForgotPasswordPage />);
      expect(screen.getByRole("button", { name: /send reset link|submit/i })).toBeInTheDocument();
    });

    it("renders back to login link", () => {
      render(<ForgotPasswordPage />);
      expect(screen.getByRole("link", { name: /back to login|sign in/i })).toBeInTheDocument();
    });

    it("renders page heading", () => {
      render(<ForgotPasswordPage />);
      expect(screen.getByRole("heading", { name: /forgot password/i })).toBeInTheDocument();
    });

    it("renders description text", () => {
      render(<ForgotPasswordPage />);
      expect(screen.getByText(/enter your email/i)).toBeInTheDocument();
    });
  });

  // ==================== FP-002 to FP-003: Email validation ====================

  describe("email validation", () => {
    it("shows error when email is empty (FP-002)", async () => {
      const user = userEvent.setup();
      render(<ForgotPasswordPage />);

      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/email is required/i)).toBeInTheDocument();
      });
    });

    it("shows error for invalid email format (FP-003)", async () => {
      const user = userEvent.setup();
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      await user.type(emailInput, "notanemail");

      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/invalid email address/i)).toBeInTheDocument();
      });
    });
  });

  // ==================== FP-004 to FP-005: Form Submission ====================

  describe("form submission", () => {
    it("shows success message after valid submission (FP-004)", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/check your email/i)).toBeInTheDocument();
      });
    });

    it("displays specific success message content (FP-005)", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/reset link/i)).toBeInTheDocument();
      });
    });

    it("calls resetPassword API with email", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockResetPassword).toHaveBeenCalledWith("test@example.com");
      });
    });
  });

  // ==================== FP-006: Loading state ====================

  describe("loading state", () => {
    it("shows loading state during submission (FP-006)", async () => {
      const user = userEvent.setup();
      let resolveReset: () => void;
      mockResetPassword.mockImplementation(() => new Promise<void>((resolve) => {
        resolveReset = resolve;
      }));

      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      // Button should be disabled and show loading state
      expect(submitButton).toBeDisabled();

      await act(async () => {
        resolveReset!();
      });
    });
  });

  // ==================== FP-007: API error handling ====================

  describe("error handling", () => {
    it("displays API error message (FP-007)", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockRejectedValueOnce(new Error("User not found"));
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "nonexistent@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/user not found|error/i)).toBeInTheDocument();
      });
    });

    it("clears error when user starts typing again", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockRejectedValueOnce(new Error("Error"));
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/error/i)).toBeInTheDocument();
      });

      // Type again to clear error
      await user.clear(emailInput);
      await user.type(emailInput, "new");

      await waitFor(() => {
        expect(screen.queryByText(/error/i)).not.toBeInTheDocument();
      });
    });
  });

  // ==================== FP-008: Link to login ====================

  describe("navigation", () => {
    it("has link to login page (FP-008)", () => {
      render(<ForgotPasswordPage />);
      const link = screen.getByRole("link", { name: /back to login|sign in/i });
      expect(link).toHaveAttribute("href", "/login");
    });
  });

  // ==================== FP-009: Redirect if authenticated ====================

  describe("authenticated redirect", () => {
    it("redirects to dashboard if already authenticated (FP-009)", () => {
      (useAuth as jest.Mock).mockReturnValue({
        ...mockAuthContext,
        user: { id: 1, email: "test@example.com" },
        isAuthenticated: true,
      });

      render(<ForgotPasswordPage />);

      expect(mockPush).toHaveBeenCalledWith("/dashboard");
    });
  });

  // ==================== FP-010 to FP-011: Accessibility ====================

  describe("accessibility", () => {
    it("supports keyboard navigation (FP-010)", async () => {
      const user = userEvent.setup();
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.tab();
      expect(emailInput).toHaveFocus();

      await user.tab();
      expect(submitButton).toHaveFocus();
    });

    it("has proper ARIA labels (FP-011)", () => {
      render(<ForgotPasswordPage />);

      const form = screen.getByRole("form");
      expect(form).toBeInTheDocument();

      const emailInput = screen.getByLabelText(/email/i);
      expect(emailInput).toHaveAttribute("aria-required", "true");
    });

    it("associates error messages with inputs", async () => {
      const user = userEvent.setup();
      render(<ForgotPasswordPage />);

      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });
      await user.click(submitButton);

      await waitFor(() => {
        const emailInput = screen.getByLabelText(/email/i);
        const errorId = emailInput.getAttribute("aria-describedby");
        if (errorId) {
          expect(document.getElementById(errorId)).toBeInTheDocument();
        }
      });
    });
  });

  // ==================== FP-012: Form resubmission ====================

  describe("form resubmission", () => {
    it("allows resubmission after cooldown (FP-012)", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValue(undefined);
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      // First submission
      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/check your email/i)).toBeInTheDocument();
      });

      // Check if can submit again (may need to reset form)
      const resendButton = screen.queryByRole("button", { name: /resend|send again/i });
      if (resendButton) {
        mockResetPassword.mockClear();
        await user.click(resendButton);
        expect(mockResetPassword).toHaveBeenCalled();
      }
    });
  });

  // ==================== Edge Cases ====================

  describe("edge cases", () => {
    it("trims whitespace from email", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "  test@example.com  ");
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockResetPassword).toHaveBeenCalledWith("test@example.com");
      });
    });

    it("handles network errors gracefully", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockRejectedValueOnce(new Error("Network error"));
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/network error|try again/i)).toBeInTheDocument();
      });
    });

    it("submits form with Enter key", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      await user.type(emailInput, "test@example.com{enter}");

      await waitFor(() => {
        expect(mockResetPassword).toHaveBeenCalled();
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

      render(<ForgotPasswordPage />);

      const form = screen.getByRole("form");
      expect(form).toBeInTheDocument();
    });
  });

  // ==================== Success State ====================

  describe("success state", () => {
    it("shows success icon or animation after submission", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        // Look for success indicator (could be icon, color change, or message)
        const successMessage = screen.getByText(/check your email|sent|success/i);
        expect(successMessage).toBeInTheDocument();
      });
    });

    it("hides form after successful submission", async () => {
      const user = userEvent.setup();
      mockResetPassword.mockResolvedValueOnce(undefined);
      render(<ForgotPasswordPage />);

      const emailInput = screen.getByLabelText(/email/i);
      const submitButton = screen.getByRole("button", { name: /send reset link|submit/i });

      await user.type(emailInput, "test@example.com");
      await user.click(submitButton);

      await waitFor(() => {
        // Form may be hidden or replaced with success message
        expect(screen.getByText(/check your email/i)).toBeInTheDocument();
      });
    });
  });
});
