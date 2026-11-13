/**
 * Vitest setup file
 * Configures test environment with global mocks
 */

import React from 'react';
import { vi, afterEach } from 'vitest';
import '@testing-library/jest-dom';

// Provide jest as alias for vi (for tests using Jest syntax)
// @ts-expect-error Jest compatibility
global.jest = vi;

// Note: Stripe modules are mocked at the test file level for better control
// These are fallback mocks that only apply if no test-specific mock exists
vi.mock('@stripe/stripe-js', () => ({
  loadStripe: vi.fn(() => Promise.resolve({
    confirmPayment: vi.fn().mockResolvedValue({ paymentIntent: { status: 'succeeded' } }),
    createPaymentMethod: vi.fn(),
  })),
}));

vi.mock('@stripe/react-stripe-js', () => ({
  Elements: ({ children }: { children: React.ReactNode }) => React.createElement('div', { 'data-testid': 'stripe-elements' }, children),
  PaymentElement: () => React.createElement('div', { 'data-testid': 'payment-element' }, 'Payment Element'),
  CardElement: ({ onChange, options }: any) => React.createElement('div', { 'data-testid': 'card-element', 'data-complete': options?.disabled ? 'false' : 'true' },
    React.createElement('input', {
      'data-testid': 'card-number-input',
      placeholder: 'Card number',
      onChange: (e: any) => onChange?.({ 
        complete: e.target.value.length === 16,
        error: null,
        empty: e.target.value.length === 0,
      }),
    })
  ),
  useStripe: () => ({
    confirmPayment: vi.fn().mockResolvedValue({ paymentIntent: { status: 'succeeded' } }),
    confirmCardPayment: vi.fn().mockResolvedValue({ paymentIntent: { status: 'succeeded' } }),
    createPaymentMethod: vi.fn(),
  }),
  useElements: () => ({
    getElement: vi.fn(),
    submit: vi.fn().mockResolvedValue({}),
  }),
}));

// Mock IntersectionObserver
class MockIntersectionObserver {
  readonly root: Element | null = null;
  readonly rootMargin: string = '';
  readonly thresholds: ReadonlyArray<number> = [];
  
  observe = vi.fn();
  unobserve = vi.fn();
  disconnect = vi.fn();
  takeRecords = vi.fn().mockReturnValue([]);
}

// @ts-expect-error Mocking global
global.IntersectionObserver = MockIntersectionObserver;

// Mock navigator.sendBeacon
Object.defineProperty(navigator, 'sendBeacon', {
  value: vi.fn().mockReturnValue(true),
  writable: true
});

// Mock ResizeObserver
class MockResizeObserver {
  observe = vi.fn();
  unobserve = vi.fn();
  disconnect = vi.fn();
}

// @ts-expect-error Mocking global
global.ResizeObserver = MockResizeObserver;

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock scrollTo
window.scrollTo = vi.fn();

// Mock scrollIntoView
Element.prototype.scrollIntoView = vi.fn();

// Set test environment flag for components to detect
(window as any).__TEST__ = true;
(globalThis as any).__TEST__ = true;

// Clean up after each test
//
// Timers are cleared too. Several hooks use SWR with `refreshInterval:
// 60000`, and a pending interval keeps Node's event loop alive, so the
// vitest worker never exits and the run appears to hang instead of
// finishing. The timers are tracked here and cleared after every test.
//
// This is a test-harness concern only: the intervals are wanted in
// production, where the page stays open and the poll is the point.
const pendingTimers = new Set<ReturnType<typeof setTimeout>>();

const realSetTimeout = global.setTimeout;
const realSetInterval = global.setInterval;
const realClearTimeout = global.clearTimeout;
const realClearInterval = global.clearInterval;

global.setTimeout = ((handler: TimerHandler, timeout?: number, ...args: any[]) => {
  const id = realSetTimeout(handler as any, timeout, ...args);
  pendingTimers.add(id);
  return id;
}) as typeof global.setTimeout;

global.setInterval = ((handler: TimerHandler, timeout?: number, ...args: any[]) => {
  const id = realSetInterval(handler as any, timeout, ...args);
  pendingTimers.add(id);
  return id;
}) as typeof global.setInterval;

global.clearTimeout = ((id: any) => {
  pendingTimers.delete(id);
  return realClearTimeout(id);
}) as typeof global.clearTimeout;

global.clearInterval = ((id: any) => {
  pendingTimers.delete(id);
  return realClearInterval(id);
}) as typeof global.clearInterval;

afterEach(() => {
  document.body.innerHTML = '';
  document.head.innerHTML = '';

  for (const id of pendingTimers) {
    realClearTimeout(id);
    realClearInterval(id);
  }
  pendingTimers.clear();
});
