/**
 * useAnalysisProgress Hook Tests - TASK-19-11
 *
 * Unit tests for the useAnalysisProgress hook.
 *
 * @see TASK-19-11
 * @see TEST-19-11
 */

import { renderHook, act, waitFor } from '@testing-library/react';
import { useAnalysisProgress } from '@/lib/hooks/useAnalysisProgress';

// Mock API functions
const mockGetJobProgress = jest.fn();
const mockCancelAnalysisJob = jest.fn();
const mockCreateEventSource = jest.fn();

jest.mock('@/lib/api/analysis-job', () => ({
  getJobProgress: (...args: unknown[]) => mockGetJobProgress(...args),
  cancelAnalysisJob: (...args: unknown[]) => mockCancelAnalysisJob(...args),
  createEventSource: (...args: unknown[]) => mockCreateEventSource(...args),
  isJobTerminal: (status: string) => ['completed', 'cancelled', 'failed'].includes(status),
}));

// Mock EventSource
class MockEventSource {
  url: string;
  onopen: ((this: MockEventSource, ev: Event) => void) | null = null;
  onerror: ((this: MockEventSource, ev: Event) => void) | null = null;
  onmessage: ((this: MockEventSource, ev: MessageEvent) => void) | null = null;
  readyState: number = 0;
  private listeners: Map<string, EventListener[]> = new Map();

  constructor(url: string) {
    this.url = url;
  }

  addEventListener(type: string, listener: EventListener) {
    if (!this.listeners.has(type)) {
      this.listeners.set(type, []);
    }
    this.listeners.get(type)!.push(listener);
  }

  removeEventListener(type: string, listener: EventListener) {
    const arr = this.listeners.get(type);
    if (arr) {
      const idx = arr.indexOf(listener);
      if (idx !== -1) arr.splice(idx, 1);
    }
  }

  close() {
    this.readyState = 2;
  }

  simulateOpen() {
    this.readyState = 1;
    // Dispatch to addEventListener listeners
    const listeners = this.listeners.get('open');
    if (listeners) {
      const event = new Event('open');
      listeners.forEach((l) => l(event));
    }
    // Also call onopen if set
    if (this.onopen) {
      this.onopen.call(this, new Event('open'));
    }
  }

  simulateEvent(type: string, data: unknown) {
    const listeners = this.listeners.get(type);
    if (listeners) {
      const event = new MessageEvent(type, { data: JSON.stringify(data) });
      listeners.forEach((l) => l(event));
    }
  }

  simulateError() {
    if (this.onerror) {
      this.onerror.call(this, new Event('error'));
    }
  }
}

// Helper to create mock job progress
function createMockJobProgress(overrides = {}) {
  return {
    id: 1,
    status: 'running',
    total_indicators: 10,
    completed_indicators: 5,
    progress_percent: 50,
    results: [],
    ...overrides,
  };
}

describe('useAnalysisProgress', () => {
  let mockEventSource: MockEventSource | null = null;

  beforeEach(() => {
    jest.clearAllMocks();
    mockEventSource = null;
    mockGetJobProgress.mockResolvedValue(createMockJobProgress());
    mockCancelAnalysisJob.mockResolvedValue({
      data: { id: 1, status: 'cancelled' },
    });
  });

  afterEach(() => {
    if (mockEventSource) {
      mockEventSource.close();
    }
  });

  // ==================== Hook Return Structure ====================

  describe('return structure', () => {
    it('returns all expected properties', () => {
      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      expect(result.current).toHaveProperty('job');
      expect(result.current).toHaveProperty('isConnecting');
      expect(result.current).toHaveProperty('isPolling');
      expect(result.current).toHaveProperty('error');
      expect(result.current).toHaveProperty('reconnectAttempts');
      expect(result.current).toHaveProperty('elapsedSeconds');
      expect(result.current).toHaveProperty('start');
      expect(result.current).toHaveProperty('stop');
      expect(result.current).toHaveProperty('cancel');
    });

    it('returns null job initially', () => {
      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      expect(result.current.job).toBeNull();
    });

    it('returns false for isConnecting initially', () => {
      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      expect(result.current.isConnecting).toBe(false);
    });

    it('returns 0 for elapsedSeconds initially', () => {
      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      expect(result.current.elapsedSeconds).toBe(0);
    });
  });

  // ==================== Start Function ====================

  describe('start function', () => {
    it('fetches initial job state', async () => {
      mockGetJobProgress.mockResolvedValue(createMockJobProgress());

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.start();
      });

      expect(mockGetJobProgress).toHaveBeenCalledWith(1);
    });

    it('sets job state after fetching', async () => {
      const mockProgress = createMockJobProgress();
      mockGetJobProgress.mockResolvedValue(mockProgress);

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.start();
      });

      expect(result.current.job).not.toBeNull();
    });

    it('sets error on fetch failure', async () => {
      mockGetJobProgress.mockRejectedValue(new Error('Network error'));

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.start();
      });

      expect(result.current.error).toBe('Network error');
    });
  });

  // ==================== Cancel Function ====================

  describe('cancel function', () => {
    it('calls cancelAnalysisJob', async () => {
      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.cancel();
      });

      expect(mockCancelAnalysisJob).toHaveBeenCalledWith(1);
    });

    it('updates job state after cancel', async () => {
      mockCancelAnalysisJob.mockResolvedValue({
        data: { id: 1, status: 'cancelled' },
      });

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.cancel();
      });

      expect(result.current.job?.status).toBe('cancelled');
    });

    it('sets error on cancel failure', async () => {
      mockCancelAnalysisJob.mockRejectedValue(new Error('Cancel failed'));

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.cancel();
      });

      expect(result.current.error).toBe('Cancel failed');
    });
  });

  // ==================== Stop Function ====================

  describe('stop function', () => {
    it('clears isConnecting', async () => {
      mockCreateEventSource.mockImplementation(() => {
        mockEventSource = new MockEventSource('/api/v1/analysis-jobs/1/stream');
        return mockEventSource;
      });

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.start();
      });

      act(() => {
        result.current.stop();
      });

      expect(result.current.isConnecting).toBe(false);
    });

    it('clears isPolling', async () => {
      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.start();
      });

      act(() => {
        result.current.stop();
      });

      expect(result.current.isPolling).toBe(false);
    });
  });

  // ==================== Elapsed Time ====================

  describe('elapsed time', () => {
    it('starts at 0', () => {
      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      expect(result.current.elapsedSeconds).toBe(0);
    });

    it('updates when job is running', async () => {
      const mockProgress = createMockJobProgress({
        status: 'running',
        started_at: new Date(Date.now() - 5000).toISOString(), // 5 seconds ago
      });
      mockGetJobProgress.mockResolvedValue(mockProgress);

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.start();
      });

      // Elapsed time should be calculated from started_at
      expect(result.current.elapsedSeconds).toBeGreaterThanOrEqual(5);
    });
  });

  // ==================== SSE Connection ====================

  describe('SSE connection', () => {
    it('creates EventSource with correct URL', async () => {
      const mockES = new MockEventSource('/api/v1/analysis-jobs/42/stream');
      mockCreateEventSource.mockReturnValue(mockES);
      mockGetJobProgress.mockResolvedValue(createMockJobProgress());

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 42 })
      );

      await act(async () => {
        await result.current.start();
      });

      expect(mockCreateEventSource).toHaveBeenCalledWith(42);
    });

    it('sets isConnecting when connecting', async () => {
      const mockES = new MockEventSource('/api/v1/analysis-jobs/1/stream');
      mockCreateEventSource.mockReturnValue(mockES);
      mockGetJobProgress.mockResolvedValue(createMockJobProgress());

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      // Start the async operation
      await act(async () => {
        await result.current.start();
      });

      // isConnecting should be true after SSE connection is initiated
      expect(result.current.isConnecting).toBe(true);
    });

    it('clears isConnecting when connected', async () => {
      const mockES = new MockEventSource('/api/v1/analysis-jobs/1/stream');
      mockCreateEventSource.mockReturnValue(mockES);
      mockGetJobProgress.mockResolvedValue(createMockJobProgress());

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.start();
      });

      // Verify connecting state
      expect(result.current.isConnecting).toBe(true);

      // Simulate open event
      act(() => {
        mockES.simulateOpen();
      });

      // Now wait for connecting to clear
      await waitFor(() => {
        expect(result.current.isConnecting).toBe(false);
      });
    });
  });

  // ==================== Reconnection ====================

  describe('reconnection', () => {
    it('increments reconnectAttempts on error', async () => {
      const mockES = new MockEventSource('/api/v1/analysis-jobs/1/stream');
      mockCreateEventSource.mockReturnValue(mockES);
      mockGetJobProgress.mockResolvedValue(createMockJobProgress());

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1, maxReconnectAttempts: 3 })
      );

      await act(async () => {
        await result.current.start();
      });

      act(() => {
        mockES.simulateError();
      });

      await waitFor(() => {
        expect(result.current.reconnectAttempts).toBe(1);
      });
    });

    it('falls back to polling after max attempts', async () => {
      const mockES = new MockEventSource('/api/v1/analysis-jobs/1/stream');
      mockCreateEventSource.mockReturnValue(mockES);
      mockGetJobProgress.mockResolvedValue(createMockJobProgress());

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1, maxReconnectAttempts: 3 })
      );

      await act(async () => {
        await result.current.start();
      });

      // Simulate 3 errors
      for (let i = 0; i < 3; i++) {
        act(() => {
          mockES.simulateError();
        });
        await new Promise(resolve => setTimeout(resolve, 0));
      }

      await waitFor(() => {
        expect(result.current.isPolling).toBe(true);
      });
    });
  });

  // ==================== Error Handling ====================

  describe('error handling', () => {
    it('sets error on fetch failure', async () => {
      mockGetJobProgress.mockRejectedValue(new Error('Network error'));

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.start();
      });

      expect(result.current.error).toBe('Network error');
    });

    it('sets error on cancel failure', async () => {
      mockCancelAnalysisJob.mockRejectedValue(new Error('Cancel failed'));

      const { result } = renderHook(() =>
        useAnalysisProgress({ jobId: 1 })
      );

      await act(async () => {
        await result.current.cancel();
      });

      expect(result.current.error).toBe('Cancel failed');
    });
  });
});
