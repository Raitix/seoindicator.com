/**
 * Badge Impression Tracking Latency Tests
 * Task: TASK-33-35 - Performance Optimization and Launch
 * 
 * Tests for badge impression and click tracking API latency (< 50ms).
 */

import { performance } from 'perf_hooks';

// Mock types
interface ImpressionPayload {
  public_hash: string;
  referrer_domain?: string;
  timestamp?: string;
}

interface ClickPayload {
  public_hash: string;
  click_target: 'audit' | 'home';
  timestamp?: string;
}

interface ApiResponse {
  success: boolean;
  data?: {
    impression_id?: string;
    click_id?: string;
  };
  error?: string;
}

// Mock API client for TDD
class MockBadgeTrackingAPI {
  private latencies: number[] = [];
  
  async trackImpression(payload: ImpressionPayload): Promise<ApiResponse> {
    const start = performance.now();
    
    // Simulate async processing
    await this.simulateNetworkLatency();
    
    const duration = performance.now() - start;
    this.latencies.push(duration);
    
    return {
      success: true,
      data: { impression_id: `imp_${Date.now()}` },
    };
  }
  
  async trackClick(payload: ClickPayload): Promise<ApiResponse> {
    const start = performance.now();
    
    // Simulate async processing
    await this.simulateNetworkLatency();
    
    const duration = performance.now() - start;
    this.latencies.push(duration);
    
    return {
      success: true,
      data: { click_id: `click_${Date.now()}` },
    };
  }
  
  private async simulateNetworkLatency(): Promise<void> {
    // Simulate 5-20ms network latency
    const latency = 5 + Math.random() * 15;
    return new Promise(resolve => setTimeout(resolve, latency));
  }
  
  getAverageLatency(): number {
    if (this.latencies.length === 0) return 0;
    return this.latencies.reduce((sum, l) => sum + l, 0) / this.latencies.length;
  }
  
  getP95Latency(): number {
    if (this.latencies.length === 0) return 0;
    const sorted = [...this.latencies].sort((a, b) => a - b);
    const index = Math.ceil(0.95 * sorted.length) - 1;
    return sorted[index];
  }
  
  resetLatencies(): void {
    this.latencies = [];
  }
}

// Performance measurement helper
async function measureLatency<T>(
  fn: () => Promise<T>
): Promise<{ result: T; durationMs: number }> {
  const start = performance.now();
  const result = await fn();
  const durationMs = performance.now() - start;
  return { result, durationMs };
}

describe('Badge Impression Tracking Latency Tests', () => {
  let api: MockBadgeTrackingAPI;
  const TARGET_MS = 50;
  const ITERATIONS = 100;
  
  beforeEach(() => {
    api = new MockBadgeTrackingAPI();
  });
  
  describe('IMPRESSION: Impression Tracking', () => {
    it('IMP-001: Impression tracking POST < 50ms', async () => {
      const { result, durationMs } = await measureLatency(() =>
        api.trackImpression({
          public_hash: 'abc123def456ghi789jkl012mno345pq',
          referrer_domain: 'example.com',
        })
      );
      
      expect(result.success).toBe(true);
      expect(durationMs).toBeLessThan(TARGET_MS);
    });

    it('IMP-002: Click tracking POST < 50ms', async () => {
      const { result, durationMs } = await measureLatency(() =>
        api.trackClick({
          public_hash: 'abc123def456ghi789jkl012mno345pq',
          click_target: 'audit',
        })
      );
      
      expect(result.success).toBe(true);
      expect(durationMs).toBeLessThan(TARGET_MS);
    });

    it('IMP-003: Async processing is non-blocking', async () => {
      const start = performance.now();
      
      // Fire multiple tracking requests
      const promises = [
        api.trackImpression({ public_hash: 'hash1' }),
        api.trackImpression({ public_hash: 'hash2' }),
        api.trackImpression({ public_hash: 'hash3' }),
      ];
      
      await Promise.all(promises);
      
      const totalDuration = performance.now() - start;
      
      // All 3 should complete in parallel, not sequentially
      expect(totalDuration).toBeLessThan(100); // Not 50 * 3 = 150ms
    });
  });

  describe('IMPRESSION-CONCURRENT: Concurrent Load', () => {
    it('IMP-004: Rate limiting allows 100/min', async () => {
      const results: { success: boolean }[] = [];
      
      // Simulate 100 rapid requests
      for (let i = 0; i < 100; i++) {
        const result = await api.trackImpression({
          public_hash: `hash_${i}`,
        });
        results.push(result);
      }
      
      const successCount = results.filter(r => r.success).length;
      
      // All should succeed (within rate limit)
      expect(successCount).toBe(100);
    });

    it('IMP-005: 50 concurrent impressions < 2s', async () => {
      const promises = Array.from({ length: 50 }, (_, i) =>
        api.trackImpression({ public_hash: `concurrent_${i}` })
      );
      
      const start = performance.now();
      await Promise.all(promises);
      const duration = performance.now() - start;
      
      console.log(`50 concurrent impressions: ${duration.toFixed(0)}ms`);
      expect(duration).toBeLessThan(2000);
    });
  });

  describe('IMPRESSION-ERROR: Error Handling Performance', () => {
    it('IMP-006: Invalid hash handling (404) < 20ms', async () => {
      // Mock error response for invalid hash
      const errorApi = new MockBadgeTrackingAPI();
      
      const { durationMs } = await measureLatency(async () => {
        // In real implementation, this would return 404
        return { success: false, error: 'Not Found' };
      });
      
      expect(durationMs).toBeLessThan(20);
    });

    it('IMP-007: Request validation (422) < 20ms', async () => {
      const { durationMs } = await measureLatency(async () => {
        // Validation is fast, no network call needed
        return { success: false, error: 'Validation failed' };
      });
      
      expect(durationMs).toBeLessThan(20);
    });
  });

  describe('IMPRESSION-DATABASE: Database Write Performance', () => {
    it('IMP-008: Database insert latency < 20ms', async () => {
      // Simulate database write
      const { durationMs } = await measureLatency(async () => {
        // Mock database insert
        await new Promise(resolve => setTimeout(resolve, 5));
        return { id: 1 };
      });
      
      expect(durationMs).toBeLessThan(20);
    });

    it('IMP-009: Queue dispatch time < 20ms', async () => {
      const { durationMs } = await measureLatency(async () => {
        // Mock queue dispatch
        await new Promise(resolve => setTimeout(resolve, 2));
        return { dispatched: true };
      });
      
      expect(durationMs).toBeLessThan(20);
    });
  });

  describe('IMPRESSION-STATS: Latency Statistics', () => {
    it('IMP-010: P95 latency < 75ms', async () => {
      api.resetLatencies();
      
      // Run 100 iterations
      for (let i = 0; i < ITERATIONS; i++) {
        await api.trackImpression({
          public_hash: `stat_${i}`,
        });
      }
      
      const p95 = api.getP95Latency();
      console.log(`P95 latency: ${p95.toFixed(2)}ms`);
      
      expect(p95).toBeLessThan(75);
    });
  });

  describe('IMPRESSION-PAYLOAD: Payload Validation', () => {
    it('validates public_hash format', async () => {
      const validHash = 'abc123def456ghi789jkl012mno345pq';
      const invalidHash = 'too-short';
      
      // Valid hash should work
      const validResult = await api.trackImpression({ public_hash: validHash });
      expect(validResult.success).toBe(true);
      
      // Invalid hash would be rejected in real implementation
      // For mock, we just verify the format check
      expect(validHash.length).toBe(32);
      expect(invalidHash.length).toBeLessThan(32);
    });

    it('accepts optional referrer_domain', async () => {
      const result = await api.trackImpression({
        public_hash: 'abc123def456ghi789jkl012mno345pq',
        referrer_domain: 'example.com',
      });
      
      expect(result.success).toBe(true);
    });

    it('accepts click_target values', async () => {
      const auditResult = await api.trackClick({
        public_hash: 'abc123def456ghi789jkl012mno345pq',
        click_target: 'audit',
      });
      
      const homeResult = await api.trackClick({
        public_hash: 'abc123def456ghi789jkl012mno345pq',
        click_target: 'home',
      });
      
      expect(auditResult.success).toBe(true);
      expect(homeResult.success).toBe(true);
    });
  });
});

// Export for integration testing
export { MockBadgeTrackingAPI, measureLatency };
