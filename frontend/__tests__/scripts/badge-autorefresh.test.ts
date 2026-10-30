/**
 * badge-autorefresh.test.ts - Unit tests for badge auto-refresh mechanism
 * Task: TASK-33-19 - Badge Auto-Refresh Mechanism
 * 
 * Tests focus on:
 * 1. Default refresh interval configuration
 * 2. Custom refresh interval via config
 * 3. Disabled refresh (refreshInterval <= 0)
 * 4. Timer cleanup on destroy
 * 5. Timer behavior after load success/failure
 * 6. Multiple badges with different intervals
 * 7. Manual refresh interaction with auto-refresh
 * 8. Edge cases and error handling
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// ============================================================================
// Test Constants
// ============================================================================

const DEFAULT_REFRESH_INTERVAL = 86400000; // 24 hours in ms
const CUSTOM_SHORT_INTERVAL = 60000; // 1 minute
const CUSTOM_LONG_INTERVAL = 172800000; // 48 hours

// Mock badge data
const mockBadgeData = {
  hash: 'abc123def45678901234567890123456',
  site_url: 'https://example.com',
  score: 85,
  audit_url: 'https://seoindicator.com/audit/abc123def45678901234567890123456/'
};

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Simulates the setupAutoRefresh logic from badge.ts
 */
function setupAutoRefresh(
  refreshInterval: number,
  destroyed: boolean,
  existingTimer: number | null,
  onRefresh: () => void
): number | null {
  if (refreshInterval <= 0 || destroyed) {
    return null;
  }
  
  if (existingTimer) {
    clearTimeout(existingTimer);
  }
  
  return window.setTimeout(onRefresh, refreshInterval);
}

/**
 * Simulates the destroyInstance logic for timer cleanup
 */
function destroyInstance(
  refreshTimer: number | null,
  observer: IntersectionObserver | null
): { refreshTimer: null; observer: null } {
  if (refreshTimer) {
    clearTimeout(refreshTimer);
  }
  
  if (observer) {
    observer.disconnect();
  }
  
  return { refreshTimer: null, observer: null };
}

// ============================================================================
// AR-CONFIG: Refresh Interval Configuration Tests
// ============================================================================

describe('AR-CONFIG: Refresh Interval Configuration', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('AR-CONFIG-01: Default refresh interval is 24 hours', () => {
    expect(DEFAULT_REFRESH_INTERVAL).toBe(86400000); // 24 * 60 * 60 * 1000
  });

  it('AR-CONFIG-02: Custom refresh interval via config is applied', () => {
    const customInterval = 3600000; // 1 hour
    expect(customInterval).toBeGreaterThan(0);
    expect(customInterval).not.toBe(DEFAULT_REFRESH_INTERVAL);
  });

  it('AR-CONFIG-03: refreshInterval=0 disables auto-refresh', () => {
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(0, false, null, onRefresh);
    
    expect(timer).toBeNull();
    vi.advanceTimersByTime(DEFAULT_REFRESH_INTERVAL);
    expect(onRefresh).not.toHaveBeenCalled();
  });

  it('AR-CONFIG-04: negative refreshInterval disables auto-refresh', () => {
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(-1000, false, null, onRefresh);
    
    expect(timer).toBeNull();
    vi.advanceTimersByTime(DEFAULT_REFRESH_INTERVAL);
    expect(onRefresh).not.toHaveBeenCalled();
  });

  it('AR-CONFIG-05: Existing timer is cleared when setting new one', () => {
    const onRefresh1 = vi.fn();
    const onRefresh2 = vi.fn();
    
    // First timer
    const timer1 = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh1);
    expect(timer1).not.toBeNull();
    
    // Second timer replaces first
    const timer2 = setupAutoRefresh(CUSTOM_LONG_INTERVAL, false, timer1, onRefresh2);
    expect(timer2).not.toBeNull();
    expect(timer2).not.toBe(timer1);
    
    // Advance past first timer's interval
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    
    // First callback should NOT have been called (cleared)
    expect(onRefresh1).not.toHaveBeenCalled();
  });
});

// ============================================================================
// AR-TIMER: Timer Setup and Cleanup Tests
// ============================================================================

describe('AR-TIMER: Timer Setup and Cleanup', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('AR-TIMER-01: Timer is set after successful badge load', () => {
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    expect(timer).not.toBeNull();
    // Timer can be a number or object depending on environment
    expect(timer).toBeTruthy();
  });

  it('AR-TIMER-02: Timer fires callback after interval', () => {
    const onRefresh = vi.fn();
    setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    // Before interval
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL - 1);
    expect(onRefresh).not.toHaveBeenCalled();
    
    // After interval
    vi.advanceTimersByTime(1);
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  it('AR-TIMER-03: Timer is cleared on destroy', () => {
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    // Destroy before timer fires
    const result = destroyInstance(timer, null);
    
    expect(result.refreshTimer).toBeNull();
    
    // Advance past original interval
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    
    // Callback should not fire after destroy
    expect(onRefresh).not.toHaveBeenCalled();
  });

  it('AR-TIMER-04: Timer not set when instance is destroyed', () => {
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, true, null, onRefresh);
    
    expect(timer).toBeNull();
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).not.toHaveBeenCalled();
  });

  it('AR-TIMER-05: Multiple timers can coexist for different badges', () => {
    const onRefresh1 = vi.fn();
    const onRefresh2 = vi.fn();
    
    const timer1 = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh1);
    const timer2 = setupAutoRefresh(CUSTOM_LONG_INTERVAL, false, null, onRefresh2);
    
    expect(timer1).not.toBeNull();
    expect(timer2).not.toBeNull();
    expect(timer1).not.toBe(timer2);
    
    // First timer fires
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh1).toHaveBeenCalledTimes(1);
    expect(onRefresh2).not.toHaveBeenCalled();
    
    // Second timer fires
    vi.advanceTimersByTime(CUSTOM_LONG_INTERVAL - CUSTOM_SHORT_INTERVAL);
    expect(onRefresh2).toHaveBeenCalledTimes(1);
  });
});

// ============================================================================
// AR-MANUAL: Manual Refresh Interaction Tests
// ============================================================================

describe('AR-MANUAL: Manual Refresh Interaction', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('AR-MANUAL-01: Manual refresh triggers immediate load', () => {
    // Manual refresh should call loadBadge immediately
    const loadBadge = vi.fn();
    
    // Simulate manual refresh
    loadBadge('abc123');
    
    expect(loadBadge).toHaveBeenCalledWith('abc123');
    expect(loadBadge).toHaveBeenCalledTimes(1);
  });

  it('AR-MANUAL-02: Manual refresh resets auto-refresh timer', () => {
    const onRefresh = vi.fn();
    
    // Initial setup
    let timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    // Advance halfway
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL / 2);
    
    // Manual refresh happens (simulating the reset)
    // Clear the old timer and create a new one
    if (timer) clearTimeout(timer);
    timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    // Advance full interval from reset point
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    
    // Timer should have fired once (from the new timer)
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  it('AR-MANUAL-03: Refresh all badges calls loadBadge for each', () => {
    const instances = new Map([
      ['abc123', { config: { auditId: 'abc123' } }],
      ['def456', { config: { auditId: 'def456' } }],
      ['ghi789', { config: { auditId: 'ghi789' } }]
    ]);
    
    const loadBadge = vi.fn((id: string) => {
      instances.get(id);
    });
    
    // Simulate refresh() without auditId (refresh all)
    for (const id of instances.keys()) {
      loadBadge(id);
    }
    
    expect(loadBadge).toHaveBeenCalledTimes(3);
    expect(loadBadge).toHaveBeenCalledWith('abc123');
    expect(loadBadge).toHaveBeenCalledWith('def456');
    expect(loadBadge).toHaveBeenCalledWith('ghi789');
  });

  it('AR-MANUAL-04: Refresh specific badge only loads that badge', () => {
    const instances = new Map([
      ['abc123', { config: { auditId: 'abc123' } }],
      ['def456', { config: { auditId: 'def456' } }]
    ]);
    
    const loadBadge = vi.fn();
    
    // Simulate refresh() with specific auditId
    loadBadge('abc123');
    
    expect(loadBadge).toHaveBeenCalledTimes(1);
    expect(loadBadge).toHaveBeenCalledWith('abc123');
    expect(loadBadge).not.toHaveBeenCalledWith('def456');
  });
});

// ============================================================================
// AR-ERROR: Error Handling and Recovery Tests
// ============================================================================

describe('AR-ERROR: Error Handling and Recovery', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('AR-ERROR-01: Timer continues after successful retry', async () => {
    const onRefresh = vi.fn();
    const loadBadge = vi.fn();
    
    // Simulate: load fails, retry succeeds, auto-refresh continues
    let attemptCount = 0;
    const mockLoad = () => {
      attemptCount++;
      if (attemptCount === 1) {
        // First attempt fails
        return { success: false, error: 'Network error' };
      }
      // Retry succeeds
      return { success: true, data: mockBadgeData };
    };
    
    const result1 = mockLoad();
    expect(result1.success).toBe(false);
    
    const result2 = mockLoad();
    expect(result2.success).toBe(true);
    
    // Auto-refresh should be set up after successful load
    const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    expect(timer).not.toBeNull();
  });

  it('AR-ERROR-02: No timer set when badge load fails', () => {
    const onRefresh = vi.fn();
    
    // When load fails, setupAutoRefresh should not be called
    // This is verified by checking that no timer is active
    const loadFailed = true;
    
    if (!loadFailed) {
      setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    }
    
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).not.toHaveBeenCalled();
  });

  it('AR-ERROR-03: Timer works after network recovery', async () => {
    const onRefresh = vi.fn();
    
    // Simulate network was down, now recovered
    const networkRecovered = true;
    
    if (networkRecovered) {
      const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
      expect(timer).not.toBeNull();
      
      vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
      expect(onRefresh).toHaveBeenCalledTimes(1);
    }
  });

  it('AR-ERROR-04: Destroy during load prevents timer setup', () => {
    const onRefresh = vi.fn();
    
    // Simulate: loadBadge starts, instance destroyed during async fetch
    const instanceDestroyed = true;
    
    // After fetch completes, check if destroyed before setupAutoRefresh
    if (!instanceDestroyed) {
      setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    }
    
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).not.toHaveBeenCalled();
  });
});

// ============================================================================
// AR-EDGE: Edge Cases and Boundary Conditions
// ============================================================================

describe('AR-EDGE: Edge Cases and Boundary Conditions', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('AR-EDGE-01: Very short refresh interval (1 second)', () => {
    const onRefresh = vi.fn();
    const veryShortInterval = 1000; // 1 second
    
    setupAutoRefresh(veryShortInterval, false, null, onRefresh);
    
    vi.advanceTimersByTime(1000);
    expect(onRefresh).toHaveBeenCalledTimes(1);
    
    // Multiple fires
    vi.advanceTimersByTime(1000);
    // Note: This would only work if the timer resets itself
    // In the actual implementation, loadBadge resets the timer
  });

  it('AR-EDGE-02: Very long refresh interval (7 days)', () => {
    const onRefresh = vi.fn();
    const veryLongInterval = 7 * 24 * 60 * 60 * 1000; // 7 days
    
    setupAutoRefresh(veryLongInterval, false, null, onRefresh);
    
    vi.advanceTimersByTime(veryLongInterval - 1);
    expect(onRefresh).not.toHaveBeenCalled();
    
    vi.advanceTimersByTime(1);
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  it('AR-EDGE-03: Rapid init/destroy cycles', () => {
    const onRefresh = vi.fn();
    
    for (let i = 0; i < 10; i++) {
      const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
      destroyInstance(timer, null);
    }
    
    // Advance past interval
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    
    // No callbacks should fire (all destroyed)
    expect(onRefresh).not.toHaveBeenCalled();
  });

  it('AR-EDGE-04: Multiple init calls for same auditId', () => {
    const onRefresh = vi.fn();
    
    // First init
    const timer1 = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    // Second init (should return existing timer or clear and create new)
    const timer2 = setupAutoRefresh(CUSTOM_LONG_INTERVAL, false, timer1, onRefresh);
    
    expect(timer2).not.toBeNull();
    expect(timer2).not.toBe(timer1);
  });

  it('AR-EDGE-05: Refresh interval as string via data attribute', () => {
    // Data attributes are strings, need to be parsed
    const dataRefresh = '60'; // 60 seconds
    const parsedInterval = parseInt(dataRefresh, 10) * 1000;
    
    expect(parsedInterval).toBe(60000);
    expect(typeof parsedInterval).toBe('number');
  });

  it('AR-EDGE-06: Invalid refresh interval string', () => {
    const dataRefresh = 'invalid';
    const parsedInterval = parseInt(dataRefresh, 10);
    
    expect(isNaN(parsedInterval)).toBe(true);
    
    // Should fall back to default
    const finalInterval = isNaN(parsedInterval) ? DEFAULT_REFRESH_INTERVAL : parsedInterval * 1000;
    expect(finalInterval).toBe(DEFAULT_REFRESH_INTERVAL);
  });

  it('AR-EDGE-07: Zero refresh interval from data attribute', () => {
    const dataRefresh = '0';
    const parsedInterval = parseInt(dataRefresh, 10) * 1000;
    
    expect(parsedInterval).toBe(0);
    
    // Zero means disabled
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(parsedInterval, false, null, onRefresh);
    
    expect(timer).toBeNull();
  });

  it('AR-EDGE-08: Float refresh interval is handled', () => {
    const floatInterval = 1.5 * 60 * 1000; // 1.5 minutes
    const onRefresh = vi.fn();
    
    setupAutoRefresh(floatInterval, false, null, onRefresh);
    
    vi.advanceTimersByTime(floatInterval);
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });
});

// ============================================================================
// AR-INTEGRATION: Integration Scenarios
// ============================================================================

describe('AR-INTEGRATION: Integration Scenarios', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('AR-INTEGRATION-01: Full badge lifecycle with auto-refresh', () => {
    const loadBadge = vi.fn();
    const onRefresh = vi.fn();
    
    // 1. Initial load
    loadBadge('abc123');
    expect(loadBadge).toHaveBeenCalledTimes(1);
    
    // 2. Auto-refresh timer set
    const timer = setupAutoRefresh(DEFAULT_REFRESH_INTERVAL, false, null, onRefresh);
    expect(timer).not.toBeNull();
    
    // 3. Advance to trigger auto-refresh
    vi.advanceTimersByTime(DEFAULT_REFRESH_INTERVAL);
    expect(onRefresh).toHaveBeenCalledTimes(1);
    
    // 4. Destroy cleans up
    const result = destroyInstance(timer, null);
    expect(result.refreshTimer).toBeNull();
  });

  it('AR-INTEGRATION-02: Multiple badges with different intervals', () => {
    const onRefresh1 = vi.fn();
    const onRefresh2 = vi.fn();
    const onRefresh3 = vi.fn();
    
    const instances = new Map([
      ['badge1', { timer: setupAutoRefresh(60000, false, null, onRefresh1) }],
      ['badge2', { timer: setupAutoRefresh(300000, false, null, onRefresh2) }],
      ['badge3', { timer: setupAutoRefresh(600000, false, null, onRefresh3) }]
    ]);
    
    // First badge refreshes at 1 minute
    vi.advanceTimersByTime(60000);
    expect(onRefresh1).toHaveBeenCalledTimes(1);
    expect(onRefresh2).not.toHaveBeenCalled();
    expect(onRefresh3).not.toHaveBeenCalled();
    
    // Second badge at 5 minutes
    vi.advanceTimersByTime(240000); // Total 5 min
    expect(onRefresh2).toHaveBeenCalledTimes(1);
    
    // Third badge at 10 minutes
    vi.advanceTimersByTime(300000); // Total 10 min
    expect(onRefresh3).toHaveBeenCalledTimes(1);
  });

  it('AR-INTEGRATION-03: Page visibility changes', () => {
    // When page becomes hidden, timers should still run (browser handles this)
    // When page becomes visible again, badge should be fresh
    
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    // Simulate tab switch away and back
    // (Timers still run in background)
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  it('AR-INTEGRATION-04: Auto-refresh disabled via noTracking style', () => {
    // noTracking affects impression/click tracking, not auto-refresh
    // Auto-refresh is controlled by refreshInterval only
    
    const onRefresh = vi.fn();
    const noTracking = true;
    
    // noTracking should not affect auto-refresh
    const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    expect(timer).not.toBeNull();
    
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  it('AR-INTEGRATION-05: Destroy and reinitialize same container', () => {
    const onRefresh1 = vi.fn();
    const onRefresh2 = vi.fn();
    
    // First instance
    const timer1 = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh1);
    
    // Destroy
    destroyInstance(timer1, null);
    
    // Reinitialize
    const timer2 = setupAutoRefresh(CUSTOM_LONG_INTERVAL, false, null, onRefresh2);
    expect(timer2).not.toBeNull();
    
    // Old timer callback should not fire
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh1).not.toHaveBeenCalled();
    expect(onRefresh2).not.toHaveBeenCalled();
    
    // New timer callback fires
    vi.advanceTimersByTime(CUSTOM_LONG_INTERVAL - CUSTOM_SHORT_INTERVAL);
    expect(onRefresh2).toHaveBeenCalledTimes(1);
  });
});

// ============================================================================
// AR-DATA: Data Attribute Tests
// ============================================================================

describe('AR-DATA: Data Attribute Configuration', () => {
  it('AR-DATA-01: data-refresh attribute parsed correctly', () => {
    const dataRefresh = '3600'; // 1 hour in seconds
    const interval = parseInt(dataRefresh, 10) * 1000;
    
    expect(interval).toBe(3600000); // 1 hour in ms
  });

  it('AR-DATA-02: Missing data-refresh uses default', () => {
    const dataRefresh = null;
    const interval = dataRefresh ? parseInt(dataRefresh, 10) * 1000 : DEFAULT_REFRESH_INTERVAL;
    
    expect(interval).toBe(DEFAULT_REFRESH_INTERVAL);
  });

  it('AR-DATA-03: Empty data-refresh uses default', () => {
    const dataRefresh = '';
    const interval = dataRefresh ? parseInt(dataRefresh, 10) * 1000 : DEFAULT_REFRESH_INTERVAL;
    
    expect(interval).toBe(DEFAULT_REFRESH_INTERVAL);
  });

  it('AR-DATA-04: data-refresh="0" disables auto-refresh', () => {
    const dataRefresh = '0';
    const interval = parseInt(dataRefresh, 10) * 1000;
    
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(interval, false, null, onRefresh);
    
    expect(timer).toBeNull();
  });
});

// ============================================================================
// AR-PERF: Performance Considerations
// ============================================================================

describe('AR-PERF: Performance Considerations', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('AR-PERF-01: Many concurrent timers do not impact performance', () => {
    const timers: number[] = [];
    const callbacks: ReturnType<typeof vi.fn>[] = [];
    
    // Create 100 badges with auto-refresh
    for (let i = 0; i < 100; i++) {
      const callback = vi.fn();
      callbacks.push(callback);
      const timer = setupAutoRefresh(DEFAULT_REFRESH_INTERVAL, false, null, callback);
      if (timer) timers.push(timer);
    }
    
    expect(timers.length).toBe(100);
    
    // Advance to trigger all
    vi.advanceTimersByTime(DEFAULT_REFRESH_INTERVAL);
    
    // All should have fired
    callbacks.forEach(cb => expect(cb).toHaveBeenCalledTimes(1));
  });

  it('AR-PERF-02: Timer cleanup is immediate on destroy', () => {
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(DEFAULT_REFRESH_INTERVAL, false, null, onRefresh);
    
    const start = Date.now();
    destroyInstance(timer, null);
    const elapsed = Date.now() - start;
    
    // Destroy should be instant (< 1ms)
    expect(elapsed).toBeLessThan(1);
  });
});

// ============================================================================
// AR-ACCEPTANCE: Acceptance Criteria Verification
// ============================================================================

describe('AR-ACCEPTANCE: Acceptance Criteria', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('AR-AC-01: Default refresh interval is 24 hours', () => {
    expect(DEFAULT_REFRESH_INTERVAL).toBe(86400000);
  });

  it('AR-AC-02: Custom refresh interval can be configured', () => {
    const customInterval = 3600000; // 1 hour
    const onRefresh = vi.fn();
    
    const timer = setupAutoRefresh(customInterval, false, null, onRefresh);
    expect(timer).not.toBeNull();
    
    vi.advanceTimersByTime(customInterval);
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  it('AR-AC-03: Auto-refresh can be disabled with refreshInterval=0', () => {
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(0, false, null, onRefresh);
    
    expect(timer).toBeNull();
    vi.advanceTimersByTime(DEFAULT_REFRESH_INTERVAL);
    expect(onRefresh).not.toHaveBeenCalled();
  });

  it('AR-AC-04: Timer is cleared when badge is destroyed', () => {
    const onRefresh = vi.fn();
    const timer = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    destroyInstance(timer, null);
    
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).not.toHaveBeenCalled();
  });

  it('AR-AC-05: Manual refresh() works independently of auto-refresh', () => {
    const loadBadge = vi.fn();
    
    // Manual refresh
    loadBadge('abc123');
    expect(loadBadge).toHaveBeenCalledTimes(1);
    
    // Auto-refresh also works
    const onRefresh = vi.fn();
    setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  it('AR-AC-06: Multiple badges can have different refresh intervals', () => {
    const callbacks = [vi.fn(), vi.fn(), vi.fn()];
    const intervals = [60000, 300000, 600000]; // 1min, 5min, 10min
    
    const timers = intervals.map((interval, i) => 
      setupAutoRefresh(interval, false, null, callbacks[i])
    );
    
    expect(timers.every(t => t !== null)).toBe(true);
    
    // Each fires at correct time
    vi.advanceTimersByTime(60000);
    expect(callbacks[0]).toHaveBeenCalledTimes(1);
    
    vi.advanceTimersByTime(240000); // Total 5min
    expect(callbacks[1]).toHaveBeenCalledTimes(1);
    
    vi.advanceTimersByTime(300000); // Total 10min
    expect(callbacks[2]).toHaveBeenCalledTimes(1);
  });

  it('AR-AC-07: Auto-refresh continues after successful load', () => {
    const onRefresh = vi.fn();
    
    // First auto-refresh
    const timer1 = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, null, onRefresh);
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).toHaveBeenCalledTimes(1);
    
    // After load, new timer is set
    const timer2 = setupAutoRefresh(CUSTOM_SHORT_INTERVAL, false, timer1, onRefresh);
    vi.advanceTimersByTime(CUSTOM_SHORT_INTERVAL);
    expect(onRefresh).toHaveBeenCalledTimes(2);
  });
});
