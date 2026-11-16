/**
 * Payment utility functions.
 * Task: TASK-26-23 - Stripe Payment Flow.
 *
 * Pure helpers for the payment flow: formatting money, turning a Stripe
 * error into something a person can read, and validating the card
 * details the browser hands us.
 */

/**
 * Format an amount as US dollars.
 */
export function formatCurrency(amount: number, currency = 'USD'): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
  }).format(amount);
}

/**
 * Pull the machine-readable code out of a Stripe error.
 *
 * A Stripe error carries a `type` and usually a `code`; the code is
 * what distinguishes a declined card from an expired one.
 */
export function parseErrorType(error: unknown): string {
  if (!error || typeof error !== 'object') {
    return 'unknown';
  }

  const obj = error as { type?: unknown; code?: unknown };

  if (typeof obj.code === 'string' && obj.code) {
    return obj.code;
  }

  if (obj.type === 'card_error') {
    return 'card_error';
  }
  if (obj.type === 'api_connection_error') {
    return 'network_error';
  }
  if (typeof obj.type === 'string' && obj.type) {
    return obj.type;
  }

  return 'unknown';
}

/**
 * Turn a Stripe error code into a message that can be shown to a user.
 */
export function getErrorMessage(code: string): string {
  const messages: Record<string, string> = {
    card_declined: 'Your card was declined. Please try another payment method.',
    insufficient_funds: 'Your card has insufficient funds.',
    expired_card: 'Your card has expired.',
    incorrect_cvc: 'The security code is incorrect.',
    incorrect_number: 'The card number is incorrect.',
    processing_error: 'An error occurred while processing the payment. Please try again.',
    network_error: 'A network error occurred. Please check your connection and try again.',
    authentication_required: 'Your card requires authentication. Please try again.',
    unknown: 'An error occurred. Please try again.',
  };

  return messages[code] ?? messages.unknown;
}

/**
 * Work out how many credits a purchase yields, including the bonus.
 *
 * The bonus is a percentage of the base credits and the result is
 * rounded to whole credits.
 */
export function calculateBonus(baseCredits: number, bonusPercent: number): number {
  return Math.round(baseCredits * (1 + bonusPercent / 100));
}

/**
 * Validate a card number with the Luhn checksum.
 */
export function isValidCardNumber(cardNumber: string): boolean {
  const digits = cardNumber.replace(/[\s-]/g, '');

  if (!/^\d{12,19}$/.test(digits)) {
    return false;
  }

  // An all-zero number passes the Luhn checksum, because its digit sum
  // is zero, but it is not a real card.
  if (/^0+$/.test(digits)) {
    return false;
  }

  let sum = 0;
  let double = false;

  for (let i = digits.length - 1; i >= 0; i--) {
    let digit = digits.charCodeAt(i) - 48;
    if (double) {
      digit *= 2;
      if (digit > 9) digit -= 9;
    }
    sum += digit;
    double = !double;
  }

  return sum % 10 === 0;
}

/**
 * Mask a card number, keeping only the last four digits visible.
 */
export function maskCardNumber(cardNumber: string): string {
  const digits = cardNumber.replace(/[\s-]/g, '');

  if (digits.length < 4) {
    return '****';
  }

  return `**** **** **** ${digits.slice(-4)}`;
}

/**
 * Identify the card brand from the leading digits.
 */
export function getCardBrand(cardNumber: string): string {
  const digits = cardNumber.replace(/[\s-]/g, '');

  if (/^4/.test(digits)) return 'visa';
  if (/^(5[1-5]|2[2-7])/.test(digits)) return 'mastercard';
  if (/^3[47]/.test(digits)) return 'amex';
  if (/^6(?:011|5)/.test(digits)) return 'discover';
  if (/^(6011|65)/.test(digits)) return 'discover';
  if (/^3(?:0[0-5]|[68])/.test(digits)) return 'diners';

  return 'unknown';
}

/**
 * Format an expiry month and year as MM/YY.
 */
export function formatExpiry(month: string, year: string): string {
  const cleanMonth = month.replace(/\D/g, '');
  const cleanYear = year.replace(/\D/g, '');

  const normalizedMonth = cleanMonth.padStart(2, '0');

  // A four-digit year is shortened to its last two digits.
  const normalizedYear =
    cleanYear.length > 2 ? cleanYear.slice(-2) : cleanYear.padStart(2, '0');

  return `${normalizedMonth}/${normalizedYear}`;
}
