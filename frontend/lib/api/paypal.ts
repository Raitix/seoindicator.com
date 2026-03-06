/**
 * PayPal Payment API functions for SEOIndicator
 *
 * Handles PayPal Smart Buttons integration with backend order/capture endpoints.
 *
 * @see SPEC-026
 * @see TASK-26-24
 */

// ==================== Types ====================

export interface CreditPackage {
  id: number;
  name: string;
  credits: number;
  price: number;
  bonus_percent: number;
  is_popular: boolean;
}

export interface PayPalOrderRequest {
  package_id: number;
  return_url?: string;
  cancel_url?: string;
}

export interface PayPalOrderResponse {
  success: boolean;
  data?: {
    order_id: string;
    approval_url: string;
    amount: number;
    credits: number;
    package_id: number;
    package_name: string;
  };
  error?: {
    message: string;
    code?: string;
    status?: number;
  };
}

export interface PayPalCaptureRequest {
  order_id: string;
}

export interface PayPalCaptureResponse {
  success: boolean;
  data?: {
    capture_id: string;
    credits: number;
    new_balance: number;
    transaction_id: string;
    order_id: string;
  };
  error?: {
    message: string;
    code?: string;
    status?: number;
  };
}

export interface PayPalErrorResponse {
  success: false;
  error: {
    message: string;
    code?: string;
    status?: number;
  };
}

export class PayPalError extends Error {
  public status?: number;
  public code?: string;
  public retryAfter?: number;

  constructor(message: string, status?: number, code?: string) {
    super(message);
    this.name = "PayPalError";
    this.status = status;
    this.code = code;
  }
}

// ==================== Constants ====================

const API_BASE = "/api/v1";
const ORDER_ENDPOINT = `${API_BASE}/payments/paypal/order`;
const CAPTURE_ENDPOINT = `${API_BASE}/payments/paypal/capture`;

// PayPal Error Messages Mapping
export const PayPalErrorMessages: Record<string, string> = {
  PAYMENT_DENIED: "Your payment was declined. Please try a different payment method.",
  INSUFFICIENT_FUNDS: "Insufficient funds in your PayPal account.",
  INSTRUMENT_DECLINED: "The payment method was declined.",
  PAYER_ACCOUNT_LOCKED_OR_CLOSED: "Your PayPal account is locked or closed.",
  TRANSACTION_REFUSED: "This transaction cannot be processed.",
  POPUP_BLOCKED: "Popup blocked by browser. Please enable popups or use redirect flow.",
  NETWORK_ERROR: "Network error. Please check your connection and try again.",
  TIMEOUT: "Request timed out. Please try again.",
  UNAUTHORIZED: "Please sign in to continue.",
  RATE_LIMITED: "Too many requests. Please wait a moment and try again.",
  SERVER_ERROR: "Server error. Please try again later.",
  INVALID_PACKAGE: "Invalid package selected.",
  ORDER_NOT_FOUND: "Order not found or expired.",
  ORDER_ALREADY_CAPTURED: "This order has already been processed.",
};

// ==================== Helper Functions ====================

/**
 * Get user-friendly error message from PayPal error code
 */
export function getPayPalErrorMessage(error: PayPalError | Error): string {
  if (error instanceof PayPalError && error.code) {
    return PayPalErrorMessages[error.code] || error.message;
  }
  return error.message || "An unexpected error occurred. Please try again.";
}

/**
 * Get PayPal client ID from environment
 */
export function getPayPalClientId(): string | null {
  if (typeof window === "undefined") {
    return null;
  }
  return process.env.NEXT_PUBLIC_PAYPAL_CLIENT_ID || null;
}

/**
 * Check if PayPal SDK is loaded
 */
export function isPayPalSDKLoaded(): boolean {
  return typeof window !== "undefined" && typeof (window as any).paypal !== "undefined";
}

/**
 * Load PayPal SDK script dynamically
 */
export function loadPayPalSDK(clientId: string): Promise<void> {
  return new Promise((resolve, reject) => {
    if (isPayPalSDKLoaded()) {
      resolve();
      return;
    }

    const script = document.createElement("script");
    script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=USD&intent=capture`;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new PayPalError("Failed to load PayPal SDK", 0, "SDK_LOAD_ERROR"));

    document.head.appendChild(script);
  });
}

// Non-nullable types for successful responses
export type PayPalOrderData = NonNullable<PayPalOrderResponse["data"]>;
export type PayPalCaptureData = NonNullable<PayPalCaptureResponse["data"]>;

// ==================== API Functions ====================

/**
 * Create a PayPal order
 */
export async function createPayPalOrder(
  packageId: number,
  returnUrl?: string,
  cancelUrl?: string
): Promise<PayPalOrderData> {
  const payload: PayPalOrderRequest = {
    package_id: packageId,
  };

  if (returnUrl) {
    payload.return_url = returnUrl;
  }

  if (cancelUrl) {
    payload.cancel_url = cancelUrl;
  }

  const response = await fetch(ORDER_ENDPOINT, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    credentials: "include",
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    await handleErrorResponse(response);
  }

  const data: PayPalOrderResponse = await response.json();

  if (!data.success || !data.data) {
    throw new PayPalError(
      data.error?.message || "Failed to create order",
      data.error?.status,
      data.error?.code
    );
  }

  return data.data;
}

/**
 * Capture a PayPal order
 */
export async function capturePayPalOrder(
  orderId: string
): Promise<PayPalCaptureData> {
  const payload: PayPalCaptureRequest = {
    order_id: orderId,
  };

  const response = await fetch(CAPTURE_ENDPOINT, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    credentials: "include",
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    await handleErrorResponse(response);
  }

  const data: PayPalCaptureResponse = await response.json();

  if (!data.success || !data.data) {
    throw new PayPalError(
      data.error?.message || "Capture failed",
      data.error?.status,
      data.error?.code || "CAPTURE_FAILED"
    );
  }

  return data.data;
}

/**
 * Handle error responses from the API
 */
async function handleErrorResponse(response: Response): Promise<never> {
  let errorMessage = "An error occurred";
  let errorCode: string | undefined;

  try {
    const errorData: PayPalErrorResponse = await response.json();
    errorMessage = errorData.error?.message || errorMessage;
    errorCode = errorData.error?.code;
  } catch {
    // Failed to parse error response
  }

  // Handle specific error statuses
  if (response.status === 401) {
    throw new PayPalError("Unauthorized. Please log in.", 401, "UNAUTHORIZED");
  }

  if (response.status === 429) {
    const retryAfter = response.headers.get("Retry-After");
    const error = new PayPalError(
      "Rate limit exceeded. Please try again later.",
      429,
      "RATE_LIMITED"
    );
    if (retryAfter) {
      error.retryAfter = parseInt(retryAfter, 10);
    }
    throw error;
  }

  if (response.status === 403) {
    throw new PayPalError("Access forbidden.", 403, "FORBIDDEN");
  }

  if (response.status === 404) {
    throw new PayPalError("Resource not found.", 404, "NOT_FOUND");
  }

  if (response.status >= 500) {
    throw new PayPalError("Server error. Please try again later.", response.status, "SERVER_ERROR");
  }

  throw new PayPalError(errorMessage, response.status, errorCode);
}

// ==================== URL Helpers ====================

/**
 * Get the return URL for redirect flow
 */
export function getReturnUrl(): string {
  if (typeof window === "undefined") return "";
  const baseUrl = window.location.origin;
  return `${baseUrl}/paypal/success`;
}

/**
 * Get the cancel URL for redirect flow
 */
export function getCancelUrl(): string {
  if (typeof window === "undefined") return "";
  const baseUrl = window.location.origin;
  return `${baseUrl}/paypal/cancel`;
}

/**
 * Parse PayPal return URL parameters
 */
export function parseReturnParams(search: string): {
  token: string | null;
  payerId: string | null;
} {
  const params = new URLSearchParams(search);
  return {
    token: params.get("token"),
    payerId: params.get("PayerID"),
  };
}

// ==================== Session Storage ====================

const ORDER_STORAGE_KEY = "seoindicator_paypal_order";

/**
 * Store order info for redirect flow validation
 */
export function storeOrderInfo(orderId: string, packageId: number): void {
  if (typeof window === "undefined") return;

  try {
    sessionStorage.setItem(
      ORDER_STORAGE_KEY,
      JSON.stringify({
        orderId,
        packageId,
        createdAt: Date.now(),
      })
    );
  } catch {
    // Ignore storage errors
  }
}

/**
 * Get stored order info
 */
export function getStoredOrderInfo(): {
  orderId: string;
  packageId: number;
  createdAt: number;
} | null {
  if (typeof window === "undefined") return null;

  try {
    const stored = sessionStorage.getItem(ORDER_STORAGE_KEY);
    if (stored) {
      return JSON.parse(stored);
    }
  } catch {
    // Ignore storage errors
  }

  return null;
}

/**
 * Clear stored order info
 */
export function clearStoredOrderInfo(): void {
  if (typeof window === "undefined") return;

  try {
    sessionStorage.removeItem(ORDER_STORAGE_KEY);
  } catch {
    // Ignore storage errors
  }
}
