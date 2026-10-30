/**
 * Payment Types
 * TASK-26-23: Stripe Payment Flow
 * 
 * Types for credit packages, payment intents, transactions, and payment methods.
 */

// ==================== Credit Packages ====================

export interface CreditPackage {
  id: string | number;
  name: string;
  credits: number;
  price: number;
  currency?: string;
  bonus_credits?: number;
  bonus_percent?: number;
  popular?: boolean;
  is_popular?: boolean;
  type?: 'one-time' | 'subscription';
  description?: string;
}

// ==================== Payment Intent ====================

export interface PaymentIntent {
  id: string;
  client_secret: string;
  amount: number;
  currency: string;
  status: PaymentIntentStatus;
  metadata: Record<string, string>;
  payment_intent_id?: string;
  credits?: number;
}

export type PaymentIntentStatus =
  | 'requires_payment_method'
  | 'requires_confirmation'
  | 'requires_action'
  | 'processing'
  | 'requires_capture'
  | 'canceled'
  | 'succeeded';

// ==================== Payment Methods ====================

export interface PaymentMethod {
  id: string;
  type: 'card' | 'paypal' | 'apple_pay' | 'google_pay';
  card?: CardDetails;
  paypal?: PayPalDetails;
  is_default: boolean;
  created_at?: string;
}

export interface CardDetails {
  brand: CardBrand;
  last4: string;
  exp_month: number;
  exp_year: number;
  funding?: 'credit' | 'debit' | 'prepaid';
  country?: string;
}

export type CardBrand =
  | 'visa'
  | 'mastercard'
  | 'amex'
  | 'discover'
  | 'diners'
  | 'jcb'
  | 'unionpay'
  | 'unknown';

export interface PayPalDetails {
  email: string;
}

// ==================== Transactions ====================

export interface Transaction {
  id: string;
  amount: number;
  currency: string;
  credits: number;
  status: TransactionStatus;
  payment_method: string;
  payment_method_id?: string;
  created_at: string;
  updated_at?: string;
  receipt_url?: string;
  description?: string;
  package_name?: string;
  balance_after?: number;
  refund_amount?: number;
  refund_reason?: string;
}

export type TransactionStatus =
  | 'pending'
  | 'completed'
  | 'failed'
  | 'cancelled'
  | 'refunded'
  | 'partial_refund';

// ==================== Payment Result ====================

export interface PaymentResult {
  success: boolean;
  paymentIntentId?: string;
  transactionId?: string;
  amount?: number;
  credits?: number;
  balance?: number;
  savedCard?: PaymentMethod;
  redirect?: string;
  error?: PaymentError;
}

export interface PaymentError {
  type: PaymentErrorType;
  code?: string;
  message: string;
  decline_type?: string;
  retry_after?: number;
}

export type PaymentErrorType =
  | 'card_error'
  | 'validation_error'
  | 'authentication_error'
  | 'rate_limit_error'
  | 'api_connection_error'
  | 'api_error'
  | 'network_error';

// ==================== Payment Flow State ====================

export interface PaymentState {
  step: PaymentStep;
  package?: CreditPackage;
  paymentIntent?: PaymentIntent;
  selectedMethod?: PaymentMethod;
  error?: PaymentError;
}

export type PaymentStep =
  | 'package'
  | 'payment'
  | 'processing'
  | 'confirmation'
  | 'success'
  | 'error';

// ==================== Confirmation State ====================

export interface PaymentConfirmationState {
  status: 'success' | 'error' | 'pending' | 'cancelled';
  transaction?: Transaction;
  error?: string;
  errorCode?: PaymentErrorCode;
  errorDetails?: string;
  errorReference?: string;
  retryCount?: number;
  retryAfter?: number;
  newBalance?: number;
  emailSent?: boolean;
}

export type PaymentErrorCode =
  | 'card_declined'
  | 'insufficient_funds'
  | 'expired_card'
  | 'incorrect_cvc'
  | 'processing_error'
  | 'network'
  | 'rate_limit'
  | 'timeout'
  | 'duplicate'
  | 'fraud'
  | 'currency_not_supported'
  | 'amount_too_large';

// ==================== API Request/Response Types ====================

export interface CreatePaymentIntentRequest {
  packageId: string | number;
  email?: string;
  name?: string;
  savedCardId?: string;
  couponCode?: string;
}

export interface CreatePaymentIntentResponse {
  client_secret: string;
  payment_intent_id: string;
  amount: number;
  credits: number;
}

export interface ConfirmPaymentRequest {
  paymentIntentId: string;
  paymentMethodId?: string;
  saveCard?: boolean;
}

export interface ConfirmPaymentResponse {
  success: boolean;
  credits?: number;
  balance?: number;
  receipt_sent?: boolean;
  transaction_id?: string;
  saved_card?: PaymentMethod;
}

export interface GetPaymentMethodsResponse {
  methods: PaymentMethod[];
  default_method?: string;
}

export interface AddPaymentMethodRequest {
  paymentMethodId: string;
  setDefault?: boolean;
}

export interface GetTransactionsRequest {
  page?: number;
  limit?: number;
  status?: TransactionStatus;
  startDate?: string;
  endDate?: string;
  type?: 'purchase' | 'refund';
  minAmount?: number;
  maxAmount?: number;
  search?: string;
}

export interface GetTransactionsResponse {
  transactions: Transaction[];
  total: number;
  page: number;
  limit: number;
  total_pages: number;
}

// ==================== Utility Types ====================

export interface CardElementOptions {
  disabled?: boolean;
  hidePostalCode?: boolean;
  style?: {
    base?: React.CSSProperties;
    invalid?: React.CSSProperties;
    complete?: React.CSSProperties;
    empty?: React.CSSProperties;
  };
}

export interface PaymentFormValidation {
  email?: string;
  name?: string;
  address?: string;
  country?: string;
  postalCode?: string;
  card?: string;
  terms?: string;
}

export interface SavedCard {
  id: string;
  last4: string;
  brand: CardBrand;
  expiry: string;
  isDefault?: boolean;
  isExpired?: boolean;
  expiringSoon?: boolean;
}

// ==================== Component Props ====================

export interface PaymentFormProps {
  selectedPackage: CreditPackage;
  onSuccess: (result: PaymentResult) => void;
  onCancel: () => void;
  isProcessing?: boolean;
  isLoading?: boolean;
  cardError?: string | null;
  hasSavedCard?: boolean;
  savedCardId?: string;
  termsRequired?: boolean;
  requireEmail?: boolean;
  requireName?: boolean;
  requireAddress?: boolean;
  csrfToken?: string;
  currency?: string;
  className?: string;
  isModal?: boolean;
  saveCard?: boolean;
}

export interface StripeCheckoutProps {
  creditPackage: CreditPackage;
  paymentIntent: CreatePaymentIntentResponse;
  onSuccess: (data: { paymentIntentId: string; amount: number; credits: number }) => void;
  onError: (error: PaymentError) => void;
  onCancel: () => void;
  isProcessing?: boolean;
  isLoading?: boolean;
  loadError?: boolean;
  hasValidationError?: boolean;
}

export interface PaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: (data: PaymentResult) => void;
  creditPackage: CreditPackage;
  isProcessing?: boolean;
  isLoading?: boolean;
  currentStep?: number;
}

export interface PaymentConfirmationProps {
  status: 'success' | 'error' | 'pending' | 'cancelled';
  transaction?: Transaction;
  error?: string;
  errorCode?: PaymentErrorCode;
  errorDetails?: string;
  errorReference?: string;
  onRetry?: (data?: { packageId?: string }) => void;
  onBackToDashboard?: () => void;
  emailSent?: boolean;
  newBalance?: number;
  showShare?: boolean;
  allowNewCard?: boolean;
  retryCount?: number;
  maxRetries?: number;
  retryAfter?: number;
  packageId?: string;
  savedCards?: SavedCard[];
  trackEvent?: (event: string, data: Record<string, unknown>) => void;
}

export interface PaymentSuccessProps {
  credits: number;
  newBalance: number;
  transactionId: string;
  onDone: () => void;
  autoCloseDelay?: number;
}

export interface PaymentErrorProps {
  error: string;
  errorCode?: PaymentErrorCode;
  onRetry?: () => void;
  onDismiss?: () => void;
  retryAfter?: number;
  showContactSupport?: boolean;
}

export interface PaymentMethodSelectorProps {
  methods: PaymentMethod[];
  selectedId?: string;
  onMethodSelect: (method: PaymentMethod) => void;
  onAddNew: () => void;
  isLoading?: boolean;
  error?: string;
  compact?: boolean;
  isModal?: boolean;
}

export interface PaymentHistoryProps {
  transactions?: Transaction[];
  isLoading?: boolean;
  error?: string;
  page?: number;
  limit?: number;
  total?: number;
  onPageChange?: (page: number) => void;
  onFilterChange?: (filters: GetTransactionsRequest) => void;
  onRefresh?: () => void;
}
