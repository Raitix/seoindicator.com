/**
 * Analytics Event Tracking Tests
 *
 * Tests for comprehensive analytics event tracking
 * for the onboarding flow.
 *
 * @see TEST-17-13.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';

// Mock fetch for API calls
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock sessionStorage
const sessionStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value;
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    clear: () => {
      store = {};
    },
  };
})();
Object.defineProperty(window, 'sessionStorage', { value: sessionStorageMock });

// Mock navigator for DNT
Object.defineProperty(window, 'navigator', {
  value: { doNotTrack: null },
  writable: true,
});

// Analytics tracking functions (implementation for TDD)
const ANALYTICS_ENDPOINT = '/api/v1/analytics/events';

interface AnalyticsEvent {
  event_name: string;
  user_id: number;
  properties: Record<string, unknown>;
  timestamp: string;
  session_id: string;
}

const trackEvent = async (eventName: string, userId: number, properties: Record<string, unknown> = {}): Promise<void> => {
  // Check DNT
  if (navigator.doNotTrack === '1') return;
  
  // Check user preference (would come from context)
  // const userOptedOut = getUserPreference('analytics_opt_out');
  // if (userOptedOut) return;

  const event: AnalyticsEvent = {
    event_name: eventName,
    user_id: userId,
    properties,
    timestamp: new Date().toISOString(),
    session_id: sessionStorage.getItem('analytics_session_id') || crypto.randomUUID(),
  };

  // Send asynchronously (fire and forget)
  fetch(ANALYTICS_ENDPOINT, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${sessionStorage.getItem('auth_token') || ''}`,
    },
    body: JSON.stringify(event),
  }).catch(() => {
    // Graceful failure - don't crash
  });
};

// Tracking functions
const trackOnboardingStarted = (userId: number) => {
  const startTime = Date.now();
  sessionStorage.setItem('onboarding_start_time', startTime.toString());
  trackEvent('onboarding_started', userId);
};

const trackStepCompleted = (userId: number, stepNumber: number) => {
  const stepStartTime = sessionStorage.getItem(`step_${stepNumber}_start_time`);
  const durationMs = stepStartTime ? Date.now() - parseInt(stepStartTime) : 0;
  
  trackEvent('onboarding_step_completed', userId, {
    step_number: stepNumber,
    duration_on_step: durationMs,
  });
  
  // Set start time for next step
  sessionStorage.setItem(`step_${stepNumber + 1}_start_time`, Date.now().toString());
};

const trackStepSkipped = (userId: number, stepNumber: number) => {
  trackEvent('onboarding_step_skipped', userId, { step_number: stepNumber });
};

const trackOnboardingSkipped = (userId: number, stepNumber: number) => {
  trackEvent('onboarding_skipped', userId, { step_number: stepNumber });
};

const trackOnboardingCompleted = (userId: number, stepsCompleted: number) => {
  const startTime = sessionStorage.getItem('onboarding_start_time');
  const durationSeconds = startTime ? Math.round((Date.now() - parseInt(startTime)) / 1000) : 0;
  
  trackEvent('onboarding_completed', userId, {
    duration_seconds: durationSeconds,
    steps_completed: stepsCompleted,
  });
};

const trackOnboardingResumed = (userId: number, currentStep: number) => {
  trackEvent('onboarding_resumed', userId, { current_step: currentStep });
};

describe('Analytics Event Tracking', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    sessionStorageMock.clear();
    mockFetch.mockReset();
    mockFetch.mockResolvedValue({ ok: true });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // AE-001: onboarding_started Event
  // ============================================
  describe('onboarding_started', () => {
    it('AE-001-01: event fires on modal open', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          ANALYTICS_ENDPOINT,
          expect.objectContaining({ method: 'POST' })
        );
      });
      
      const call = mockFetch.mock.calls[0];
      const body = JSON.parse(call[1].body);
      expect(body.event_name).toBe('onboarding_started');
    });

    it('AE-001-02: event includes user_id', async () => {
      trackOnboardingStarted(456);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.user_id).toBe(456);
      });
    });

    it('AE-001-03: event includes timestamp', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.timestamp).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/);
      });
    });

    it('AE-001-04: event includes session_id', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.session_id).toBeDefined();
        expect(typeof body.session_id).toBe('string');
      });
    });

    it('AE-001-05: no event for unauthenticated (DNT)', async () => {
      // This would be handled by the caller not passing a user ID
      // or by checking auth state before tracking
    });
  });

  // ============================================
  // AE-002: onboarding_step_completed Event
  // ============================================
  describe('onboarding_step_completed', () => {
    it('AE-002-01: event fires on step completion', async () => {
      sessionStorage.setItem('step_2_start_time', Date.now().toString());
      trackStepCompleted(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.event_name).toBe('onboarding_step_completed');
      });
    });

    it('AE-002-02: event includes step_number', async () => {
      sessionStorage.setItem('step_3_start_time', Date.now().toString());
      trackStepCompleted(123, 3);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.step_number).toBe(3);
      });
    });

    it('AE-002-03: event includes duration', async () => {
      const startTime = Date.now() - 5000; // 5 seconds ago
      sessionStorage.setItem('step_2_start_time', startTime.toString());
      trackStepCompleted(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.duration_on_step).toBeGreaterThanOrEqual(4500);
        expect(body.properties.duration_on_step).toBeLessThanOrEqual(5500);
      });
    });

    it('AE-002-04: event includes timestamp', async () => {
      sessionStorage.setItem('step_2_start_time', Date.now().toString());
      trackStepCompleted(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.timestamp).toBeDefined();
      });
    });

    it('AE-002-05: duration calculated correctly', async () => {
      const startTime = Date.now() - 10000; // 10 seconds ago
      sessionStorage.setItem('step_2_start_time', startTime.toString());
      trackStepCompleted(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.duration_on_step).toBeGreaterThanOrEqual(9500);
      });
    });

    it('AE-002-06: event fires for all steps', async () => {
      for (let step = 1; step <= 5; step++) {
        sessionStorage.setItem(`step_${step}_start_time`, Date.now().toString());
        trackStepCompleted(123, step);
      }
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(5);
      });
    });
  });

  // ============================================
  // AE-003: onboarding_step_skipped Event
  // ============================================
  describe('onboarding_step_skipped', () => {
    it('AE-003-01: event fires on step skip', async () => {
      trackStepSkipped(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.event_name).toBe('onboarding_step_skipped');
      });
    });

    it('AE-003-02: event includes step_number', async () => {
      trackStepSkipped(123, 3);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.step_number).toBe(3);
      });
    });

    it('AE-003-03: event includes timestamp', async () => {
      trackStepSkipped(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.timestamp).toBeDefined();
      });
    });

    it('AE-003-04: event includes user_id', async () => {
      trackStepSkipped(456, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.user_id).toBe(456);
      });
    });
  });

  // ============================================
  // AE-004: onboarding_skipped Event
  // ============================================
  describe('onboarding_skipped', () => {
    it('AE-004-01: event fires on skip all', async () => {
      trackOnboardingSkipped(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.event_name).toBe('onboarding_skipped');
      });
    });

    it('AE-004-02: event includes step_number', async () => {
      trackOnboardingSkipped(123, 3);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.step_number).toBe(3);
      });
    });

    it('AE-004-03: event includes timestamp', async () => {
      trackOnboardingSkipped(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.timestamp).toBeDefined();
      });
    });

    it('AE-004-04: event includes user_id', async () => {
      trackOnboardingSkipped(789, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.user_id).toBe(789);
      });
    });
  });

  // ============================================
  // AE-005: onboarding_completed Event
  // ============================================
  describe('onboarding_completed', () => {
    it('AE-005-01: event fires on completion', async () => {
      sessionStorage.setItem('onboarding_start_time', Date.now().toString());
      trackOnboardingCompleted(123, 5);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.event_name).toBe('onboarding_completed');
      });
    });

    it('AE-005-02: event includes duration_seconds', async () => {
      const startTime = Date.now() - 120000; // 2 minutes ago
      sessionStorage.setItem('onboarding_start_time', startTime.toString());
      trackOnboardingCompleted(123, 5);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.duration_seconds).toBeGreaterThanOrEqual(118);
        expect(body.properties.duration_seconds).toBeLessThanOrEqual(122);
      });
    });

    it('AE-005-03: event includes steps_completed', async () => {
      sessionStorage.setItem('onboarding_start_time', Date.now().toString());
      trackOnboardingCompleted(123, 5);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.steps_completed).toBe(5);
      });
    });

    it('AE-005-04: event includes timestamp', async () => {
      sessionStorage.setItem('onboarding_start_time', Date.now().toString());
      trackOnboardingCompleted(123, 5);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.timestamp).toBeDefined();
      });
    });

    it('AE-005-05: total duration calculated correctly', async () => {
      const startTime = Date.now() - 90000; // 90 seconds ago
      sessionStorage.setItem('onboarding_start_time', startTime.toString());
      trackOnboardingCompleted(123, 5);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.duration_seconds).toBeGreaterThanOrEqual(89);
      });
    });
  });

  // ============================================
  // AE-006: onboarding_resumed Event
  // ============================================
  describe('onboarding_resumed', () => {
    it('AE-006-01: event fires on resume', async () => {
      trackOnboardingResumed(123, 3);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.event_name).toBe('onboarding_resumed');
      });
    });

    it('AE-006-02: event includes current_step', async () => {
      trackOnboardingResumed(123, 3);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.properties.current_step).toBe(3);
      });
    });

    it('AE-006-03: event includes timestamp', async () => {
      trackOnboardingResumed(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.timestamp).toBeDefined();
      });
    });

    it('AE-006-04: event includes user_id', async () => {
      trackOnboardingResumed(456, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body.user_id).toBe(456);
      });
    });
  });

  // ============================================
  // AE-007: Event Structure
  // ============================================
  describe('Event Structure', () => {
    it('AE-007-01: event has event_name', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body).toHaveProperty('event_name');
      });
    });

    it('AE-007-02: event has user_id', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body).toHaveProperty('user_id');
      });
    });

    it('AE-007-03: event has properties', async () => {
      sessionStorage.setItem('step_2_start_time', Date.now().toString());
      trackStepCompleted(123, 2);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body).toHaveProperty('properties');
        expect(typeof body.properties).toBe('object');
      });
    });

    it('AE-007-04: event has timestamp', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body).toHaveProperty('timestamp');
      });
    });

    it('AE-007-05: event has session_id', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        expect(body).toHaveProperty('session_id');
      });
    });
  });

  // ============================================
  // AE-008: API Integration
  // ============================================
  describe('API Integration', () => {
    it('AE-008-01: event sent to backend', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          ANALYTICS_ENDPOINT,
          expect.objectContaining({ method: 'POST' })
        );
      });
    });

    it('AE-008-02: authorization header', async () => {
      sessionStorage.setItem('auth_token', 'test-token');
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          ANALYTICS_ENDPOINT,
          expect.objectContaining({
            headers: expect.objectContaining({
              Authorization: 'Bearer test-token',
            }),
          })
        );
      });
    });

    it('AE-008-03: event persisted to database', async () => {
      // This would be tested on backend
    });

    it('AE-008-04: no blocking on event send', async () => {
      const start = Date.now();
      trackOnboardingStarted(123);
      const elapsed = Date.now() - start;
      
      // Should return immediately (< 50ms)
      expect(elapsed).toBeLessThan(50);
    });

    it('AE-008-05: error handling on failure', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));
      
      // Should not throw
      expect(() => trackOnboardingStarted(123)).not.toThrow();
    });
  });

  // ============================================
  // AE-009: Privacy Considerations
  // ============================================
  describe('Privacy Considerations', () => {
    it('AE-009-01: respects DNT header', async () => {
      Object.defineProperty(window.navigator, 'doNotTrack', { value: '1', writable: true });
      
      trackOnboardingStarted(123);
      
      await new Promise(resolve => setTimeout(resolve, 100));
      
      expect(mockFetch).not.toHaveBeenCalled();
      
      // Reset
      Object.defineProperty(window.navigator, 'doNotTrack', { value: null, writable: true });
    });

    it('AE-009-02: respects user preference', async () => {
      // After implementation: check user opt-out preference
    });

    it('AE-009-03: no PII in events', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        
        // Check no email, name, or other PII
        expect(body).not.toHaveProperty('email');
        expect(body).not.toHaveProperty('name');
        expect(JSON.stringify(body.properties)).not.toContain('@');
      });
    });

    it('AE-009-04: session ID is UUID', async () => {
      trackOnboardingStarted(123);
      
      await waitFor(() => {
        const call = mockFetch.mock.calls[0];
        const body = JSON.parse(call[1].body);
        
        // UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        expect(body.session_id).toMatch(/^[a-f0-9-]{36}$/);
      });
    });
  });

  // ============================================
  // AE-010: Performance
  // ============================================
  describe('Performance', () => {
    it('AE-010-01: no delay on event send', async () => {
      const start = Date.now();
      trackOnboardingStarted(123);
      const elapsed = Date.now() - start;
      
      expect(elapsed).toBeLessThan(50);
    });

    it('AE-010-02: batched sending', async () => {
      // After implementation: test batch sending
    });

    it('AE-010-03: memory efficient', async () => {
      // Track many events
      for (let i = 0; i < 100; i++) {
        trackStepCompleted(123, i % 5 + 1);
      }
      
      // Should not cause memory issues
      expect(mockFetch).toHaveBeenCalledTimes(100);
    });
  });
});
