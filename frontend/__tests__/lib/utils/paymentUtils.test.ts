/**
 * Payment Utility Tests
 * TASK-26-23: Stripe Payment Flow
 * 
 * Tests for payment-related utility functions.
 */

import {
  formatCurrency,
  parseErrorType,
  getErrorMessage,
  calculateBonus,
  isValidCardNumber,
  maskCardNumber,
  getCardBrand,
  formatExpiry
} from '@/lib/utils/paymentUtils';

describe('Payment Utilities', () => {
  describe('UT-001 to UT-003: formatCurrency', () => {
    it('formats currency correctly', () => {
      expect(formatCurrency(10)).toBe('$10.00');
      expect(formatCurrency(0)).toBe('$0.00');
      expect(formatCurrency(0.99)).toBe('$0.99');
    });

    it('formats currency with decimals', () => {
      expect(formatCurrency(10.5)).toBe('$10.50');
      expect(formatCurrency(10.99)).toBe('$10.99');
      expect(formatCurrency(10.1)).toBe('$10.10');
    });

    it('formats large amounts with commas', () => {
      expect(formatCurrency(1000)).toBe('$1,000.00');
      expect(formatCurrency(1000000)).toBe('$1,000,000.00');
      expect(formatCurrency(12345.67)).toBe('$12,345.67');
    });
  });

  describe('UT-004 to UT-005: Error Handling', () => {
    it('parses error type from Stripe error', () => {
      expect(parseErrorType({ type: 'card_error', code: 'card_declined' })).toBe('card_declined');
      expect(parseErrorType({ type: 'card_error', code: 'expired_card' })).toBe('expired_card');
      expect(parseErrorType({ type: 'api_connection_error' })).toBe('network_error');
      expect(parseErrorType({ type: 'unknown' })).toBe('unknown');
    });

    it('gets user-friendly error message', () => {
      expect(getErrorMessage('card_declined')).toContain('declined');
      expect(getErrorMessage('insufficient_funds')).toContain('insufficient funds');
      expect(getErrorMessage('expired_card')).toContain('expired');
      expect(getErrorMessage('incorrect_cvc')).toContain('security code');
      expect(getErrorMessage('network_error')).toContain('network error');
      expect(getErrorMessage('unknown')).toContain('error occurred');
    });
  });

  describe('UT-006: calculateBonus', () => {
    it('calculates bonus credits correctly', () => {
      expect(calculateBonus(100, 10)).toBe(110); // 10% bonus
      expect(calculateBonus(100, 15)).toBe(115); // 15% bonus
      expect(calculateBonus(100, 0)).toBe(100);  // No bonus
      expect(calculateBonus(50, 10)).toBe(55);   // 10% of 50 = 5
    });

    it('rounds bonus to whole credits', () => {
      expect(calculateBonus(99, 10)).toBe(109); // 99 + 9.9 ≈ 109
    });
  });

  describe('UT-007 to UT-010: Card Utilities', () => {
    it('validates card number with Luhn check', () => {
      // Valid test card numbers
      expect(isValidCardNumber('4242424242424242')).toBe(true); // Visa
      expect(isValidCardNumber('5555555555554444')).toBe(true); // Mastercard
      expect(isValidCardNumber('378282246310005')).toBe(true);  // Amex
      
      // Invalid numbers
      expect(isValidCardNumber('4242424242424241')).toBe(false);
      expect(isValidCardNumber('1234567890123456')).toBe(false);
      expect(isValidCardNumber('0000000000000000')).toBe(false);
    });

    it('masks card number correctly', () => {
      expect(maskCardNumber('4242424242424242')).toBe('**** **** **** 4242');
      expect(maskCardNumber('5555555555554444')).toBe('**** **** **** 4444');
    });

    it('gets card brand from number', () => {
      expect(getCardBrand('4242424242424242')).toBe('visa');
      expect(getCardBrand('5555555555554444')).toBe('mastercard');
      expect(getCardBrand('378282246310005')).toBe('amex');
      expect(getCardBrand('6011111111111117')).toBe('discover');
      expect(getCardBrand('1234567890123456')).toBe('unknown');
    });

    it('formats expiry date', () => {
      expect(formatExpiry('12', '25')).toBe('12/25');
      expect(formatExpiry('1', '26')).toBe('01/26');
      expect(formatExpiry('01', '2026')).toBe('01/26');
    });
  });
});
