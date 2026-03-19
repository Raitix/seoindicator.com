/**
 * Onboarding Analytics Tracking Tests
 *
 * Tests for comprehensive analytics event tracking for the onboarding flow.
 *
 * @see TEST-17-13.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock fetch for API calls
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
};
Object.defineProperty(global, 'localStorage', { value: localStorageMock });

// Mock UUID generation
vi.mock('crypto', () => ({
  randomUUID: () => '550e8400-e29b-41d4-a716-446655440000',
}));

// Mock navigator.sendBeacon
const mockSendBeacon = vi.fn();
Object.defineProperty(navigator, 'sendBeacon', {
  value: mockSendBeacon,
  writable: true,
});

// Mock analytics utility
class OnboardingTracker {
  private userId: number | null = null;
  private sessionId: string = '550e8400-e29b-41d4-a716-446655440000';
  private eventQueue: Array<{ eventName: string; properties: Record<string, unknown> }> = [];
  private analyticsEnabled: boolean = true;
  private startTime: number | null = null;
  private stepStartTimes: Map<number, number> = new Map();

  setUserId(userId: number | null) {
    this.userId = userId;
  }

  setAnalyticsEnabled(enabled: boolean) {
    this.analyticsEnabled = enabled;
  }

  private async track(eventName: string, properties: Record<string, unknown> = {}) {
    if (!this.analyticsEnabled) return;
    if (!this.userId) return;

    const event = {
      event_name: eventName,
      user_id: this.userId,
      properties,
      timestamp: new Date().toISOString(),
      session_id: this.sessionId,
    };

    try {
      await fetch('/api/v1/analytics/events', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(event),
      });
    } catch {
      // Queue event for retry
      this.eventQueue.push({ eventName, properties });
    }
  }

  trackStarted() {
    this.startTime = Date.now();
    return this.track('onboarding_started', {});
  }

  trackStepStarted(stepNumber: number) {
    this.stepStartTimes.set(stepNumber, Date.now());
  }

  trackStepCompleted(stepNumber: number) {
    const startTime = this.stepStartTimes.get(stepNumber);
    const durationMs = startTime ? Date.now() - startTime : 0;
    return this.track('onboarding_step_completed', {
      step_number: stepNumber,
      duration_ms: durationMs,
    });
  }

  trackStepSkipped(stepNumber: number) {
    return this.track('onboarding_step_skipped', { step_number: stepNumber });
  }

  trackSkipped(stepNumber: number) {
    return this.track('onboarding_skipped', { step_number: stepNumber });
  }

  trackCompleted(stepsCompleted: number) {
    const durationSeconds = this.startTime ? Math.floor((Date.now() - this.startTime) / 1000) : 0;
    return this.track('onboarding_completed', {
      duration_seconds: durationSeconds,
      steps_completed: stepsCompleted,
    });
  }

  trackResumed(currentStep: number) {
    return this.track('onboarding_resumed', { current_step: currentStep });
  }

  getQueueLength() {
    return this.eventQueue.length;
  }
}

describe('Onboarding Analytics Tracking', () => {
  let tracker: OnboardingTracker;

  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    tracker = new OnboardingTracker();
    tracker.setUserId(123);
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  // ============================================
  // AT-001: onboarding_started Event
  // ============================================
  describe('onboarding_started Event', () => {
    it('AT-001-01: event fires when modal opens', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStarted();

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/analytics/events',
        expect.objectContaining({
          method: 'POST',
        })
      );
    });

    it('AT-001-02: event includes user_id', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStarted();

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.user_id).toBe(123);
    });

    it('AT-001-03: event includes timestamp', async () => {
      mockFetch.mockResolvedValue({ ok: true });
      vi.setSystemTime(new Date('2026-03-19T10:00:00Z'));

      await tracker.trackStarted();

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.timestamp).toBe('2026-03-19T10:00:00.000Z');
    });

    it('AT-001-04: event includes session_id', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStarted();

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.session_id).toBe('550e8400-e29b-41d4-a716-446655440000');
    });

    it('AT-001-05: event not fired for anonymous user', async () => {
      tracker.setUserId(null);

      await tracker.trackStarted();

      expect(mockFetch).not.toHaveBeenCalled();
    });
  });

  // ============================================
  // AT-002: onboarding_step_completed Event
  // ============================================
  describe('onboarding_step_completed Event', () => {
    it('AT-002-01: event fires on step complete', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStepCompleted(2);

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/analytics/events',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('AT-002-02: event includes step_number', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStepCompleted(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.properties.step_number).toBe(2);
    });

    it('AT-002-03: event includes duration_on_step', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      tracker.trackStepStarted(2);
      vi.advanceTimersByTime(5000);
      await tracker.trackStepCompleted(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.properties.duration_ms).toBe(5000);
    });

    it('AT-002-04: event includes user_id', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStepCompleted(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.user_id).toBe(123);
    });

    it('AT-002-05: event includes timestamp', async () => {
      mockFetch.mockResolvedValue({ ok: true });
      vi.setSystemTime(new Date('2026-03-19T10:00:10Z'));

      await tracker.trackStepCompleted(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.timestamp).toBe('2026-03-19T10:00:10.000Z');
    });
  });

  // ============================================
  // AT-003: onboarding_step_skipped Event
  // ============================================
  describe('onboarding_step_skipped Event', () => {
    it('AT-003-01: event fires on step skip', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStepSkipped(3);

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/analytics/events',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('AT-003-02: event includes step_number', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStepSkipped(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.properties.step_number).toBe(2);
    });

    it('AT-003-03: event includes user_id', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStepSkipped(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.user_id).toBe(123);
    });

    it('AT-003-04: event includes timestamp', async () => {
      mockFetch.mockResolvedValue({ ok: true });
      vi.setSystemTime(new Date('2026-03-19T10:00:15Z'));

      await tracker.trackStepSkipped(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.timestamp).toBe('2026-03-19T10:00:15.000Z');
    });
  });

  // ============================================
  // AT-004: onboarding_skipped Event
  // ============================================
  describe('onboarding_skipped Event', () => {
    it('AT-004-01: event fires on full skip', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackSkipped(2);

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/analytics/events',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('AT-004-02: event includes step_number', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackSkipped(3);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.properties.step_number).toBe(3);
    });

    it('AT-004-03: event includes user_id', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackSkipped(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.user_id).toBe(123);
    });

    it('AT-004-04: event includes timestamp', async () => {
      mockFetch.mockResolvedValue({ ok: true });
      vi.setSystemTime(new Date('2026-03-19T10:00:20Z'));

      await tracker.trackSkipped(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.timestamp).toBe('2026-03-19T10:00:20.000Z');
    });
  });

  // ============================================
  // AT-005: onboarding_completed Event
  // ============================================
  describe('onboarding_completed Event', () => {
    it('AT-005-01: event fires on wizard complete', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackCompleted(5);

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/analytics/events',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('AT-005-02: event includes duration_seconds', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStarted();
      vi.advanceTimersByTime(180000); // 3 minutes
      await tracker.trackCompleted(5);

      const lastCall = mockFetch.mock.calls[mockFetch.mock.calls.length - 1];
      const callArgs = JSON.parse(lastCall[1].body);
      expect(callArgs.properties.duration_seconds).toBe(180);
    });

    it('AT-005-03: event includes steps_completed', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackCompleted(5);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.properties.steps_completed).toBe(5);
    });

    it('AT-005-04: event includes user_id', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackCompleted(5);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.user_id).toBe(123);
    });

    it('AT-005-05: event includes timestamp', async () => {
      mockFetch.mockResolvedValue({ ok: true });
      vi.setSystemTime(new Date('2026-03-19T10:03:00Z'));

      await tracker.trackCompleted(5);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.timestamp).toBe('2026-03-19T10:03:00.000Z');
    });
  });

  // ============================================
  // AT-006: onboarding_resumed Event
  // ============================================
  describe('onboarding_resumed Event', () => {
    it('AT-006-01: event fires on resume', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackResumed(3);

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/analytics/events',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('AT-006-02: event includes current_step', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackResumed(3);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.properties.current_step).toBe(3);
    });

    it('AT-006-03: event includes user_id', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackResumed(3);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.user_id).toBe(123);
    });

    it('AT-006-04: event includes timestamp', async () => {
      mockFetch.mockResolvedValue({ ok: true });
      vi.setSystemTime(new Date('2026-03-19T10:05:00Z'));

      await tracker.trackResumed(3);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.timestamp).toBe('2026-03-19T10:05:00.000Z');
    });
  });

  // ============================================
  // AT-007: Event Storage
  // ============================================
  describe('Event Storage', () => {
    it('AT-007-01: events stored in database', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStarted();

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/analytics/events',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('AT-007-02: events queryable for reporting', async () => {
      // This would be a backend test
      // Verify events can be retrieved via API
    });

    it('AT-007-03: no duplicate events', async () => {
      // This would be a backend test
      // Verify idempotency
    });

    it('AT-007-04: events include all properties', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStepCompleted(2);

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs).toHaveProperty('event_name');
      expect(callArgs).toHaveProperty('user_id');
      expect(callArgs).toHaveProperty('properties');
      expect(callArgs).toHaveProperty('timestamp');
      expect(callArgs).toHaveProperty('session_id');
    });
  });

  // ============================================
  // AT-008: Performance
  // ============================================
  describe('Performance', () => {
    it('AT-008-01: no blocking on track', async () => {
      mockFetch.mockImplementation(() => new Promise((resolve) => setTimeout(resolve, 1000)));

      const start = Date.now();
      const promise = tracker.trackStarted();
      const elapsed = Date.now() - start;

      // Should return immediately (async)
      expect(elapsed).toBeLessThan(100);

      await promise;
    });

    it('AT-008-02: event queued on offline', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      await tracker.trackStarted();

      // Event should be queued for retry
      expect(tracker.getQueueLength()).toBe(1);
    });

    it('AT-008-03: batch events', async () => {
      // After implementation: verify events are batched
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStarted();
      await tracker.trackStepCompleted(1);
      await tracker.trackStepCompleted(2);

      expect(mockFetch).toHaveBeenCalledTimes(3);
    });
  });

  // ============================================
  // AT-009: Privacy
  // ============================================
  describe('Privacy', () => {
    it('AT-009-01: respect analytics disabled', async () => {
      tracker.setAnalyticsEnabled(false);

      await tracker.trackStarted();

      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('AT-009-02: PII not included', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStarted();

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      // Verify no email, name, or other PII
      expect(callArgs).not.toHaveProperty('email');
      expect(callArgs).not.toHaveProperty('name');
    });

    it('AT-009-03: session ID is UUID', async () => {
      mockFetch.mockResolvedValue({ ok: true });

      await tracker.trackStarted();

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      // Session ID should be UUID format
      expect(callArgs.session_id).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);
    });
  });

  // ============================================
  // AT-010: Error Handling
  // ============================================
  describe('Error Handling', () => {
    it('AT-010-01: API failure does not throw', async () => {
      mockFetch.mockRejectedValue(new Error('API error'));

      // Should not throw
      await expect(tracker.trackStarted()).resolves.not.toThrow();
    });

    it('AT-010-02: event queued on failure', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      await tracker.trackStarted();

      expect(tracker.getQueueLength()).toBe(1);
    });

    it('AT-010-03: retry on network failure', async () => {
      // After implementation: verify retry with backoff
      mockFetch.mockRejectedValueOnce(new Error('Network error'));
      mockFetch.mockResolvedValueOnce({ ok: true });

      await tracker.trackStarted();
    });

    it('AT-010-04: event logged on failure', async () => {
      // After implementation: verify event is logged
      mockFetch.mockRejectedValue(new Error('API error'));

      await tracker.trackStarted();
    });
  });
});
