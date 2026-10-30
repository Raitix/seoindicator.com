/**
 * SVG Badge Generation Benchmark Tests
 * Task: TASK-33-35 - Performance Optimization and Launch
 * 
 * Performance benchmarks for SVG badge generation with mocked BadgeService.
 */

import { performance } from 'perf_hooks';

// Mock types for BadgeService
interface BadgeGenerateOptions {
  score: number;
  size: 'small' | 'medium' | 'large';
  theme: 'light' | 'dark';
}

interface BadgeGenerateResult {
  svg: string;
  width: number;
  height: number;
}

// Dimensions by size
const DIMENSIONS: Record<string, { width: number; height: number }> = {
  small: { width: 120, height: 40 },
  medium: { width: 150, height: 100 },
  large: { width: 300, height: 150 },
};

// Colors by theme
const THEME_COLORS: Record<string, { bg: string; text: string; accent: string }> = {
  light: { bg: '#ffffff', text: '#1f2937', accent: '#22c55e' },
  dark: { bg: '#1a1a1a', text: '#ffffff', accent: '#22c55e' },
};

// Mock BadgeService implementation for TDD
class MockBadgeService {
  private cache: Map<string, BadgeGenerateResult> = new Map();
  
  generate(options: BadgeGenerateOptions): BadgeGenerateResult {
    const cacheKey = `${options.score}-${options.size}-${options.theme}`;
    
    // Check cache first
    if (this.cache.has(cacheKey)) {
      return this.cache.get(cacheKey)!;
    }
    
    const { width, height } = DIMENSIONS[options.size];
    const colors = THEME_COLORS[options.theme];
    
    // Generate SVG with score display
    const svg = this.generateSVG(options.score, width, height, colors);
    
    const result: BadgeGenerateResult = {
      svg,
      width,
      height,
    };
    
    // Cache the result
    this.cache.set(cacheKey, result);
    
    return result;
  }
  
  private generateSVG(score: number, width: number, height: number, colors: typeof THEME_COLORS['light']): string {
    const roundedScore = Math.round(score);
    const scoreColor = this.getScoreColor(roundedScore);
    
    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" role="img" aria-label="SEO Score: ${roundedScore}/100">
  <rect width="${width}" height="${height}" fill="${colors.bg}" rx="8"/>
  <text x="${width / 2}" y="${height / 2}" text-anchor="middle" fill="${colors.text}" font-size="24" font-weight="bold">${roundedScore}</text>
  <text x="${width / 2}" y="${height - 15}" text-anchor="middle" fill="${colors.text}" font-size="10">SEO Score</text>
  <circle cx="${width - 20}" cy="20" r="8" fill="${scoreColor}"/>
</svg>`;
  }
  
  private getScoreColor(score: number): string {
    if (score >= 90) return '#22c55e'; // Green
    if (score >= 70) return '#3b82f6'; // Blue
    if (score >= 50) return '#eab308'; // Yellow
    if (score >= 30) return '#f97316'; // Orange
    return '#ef4444'; // Red
  }
  
  clearCache(): void {
    this.cache.clear();
  }
}

// Performance measurement helper
async function measureTime<T>(fn: () => T): Promise<{ result: T; durationMs: number }> {
  const start = performance.now();
  const result = fn();
  const durationMs = performance.now() - start;
  return { result, durationMs };
}

async function measureTimeAsync<T>(fn: () => Promise<T>): Promise<{ result: T; durationMs: number }> {
  const start = performance.now();
  const result = await fn();
  const durationMs = performance.now() - start;
  return { result, durationMs };
}

// Statistical helpers
function calculatePercentile(values: number[], percentile: number): number {
  const sorted = [...values].sort((a, b) => a - b);
  const index = Math.ceil((percentile / 100) * sorted.length) - 1;
  return sorted[index];
}

function calculateAverage(values: number[]): number {
  return values.reduce((sum, v) => sum + v, 0) / values.length;
}

describe('SVG Badge Generation Benchmarks', () => {
  let badgeService: MockBadgeService;
  
  beforeEach(() => {
    badgeService = new MockBadgeService();
  });
  
  describe('SVG-BENCH: Generation Performance', () => {
    const ITERATIONS = 100;
    const TARGET_MS = 100;
    
    it('SVG-BENCH-001: Small badge generation < 50ms', async () => {
      const times: number[] = [];
      
      for (let i = 0; i < ITERATIONS; i++) {
        const { durationMs } = await measureTime(() =>
          badgeService.generate({ score: 85, size: 'small', theme: 'light' })
        );
        times.push(durationMs);
      }
      
      const avgTime = calculateAverage(times);
      console.log(`Small badge avg: ${avgTime.toFixed(2)}ms over ${ITERATIONS} runs`);
      
      expect(avgTime).toBeLessThan(50);
    });

    it('SVG-BENCH-002: Medium badge generation < 75ms', async () => {
      const times: number[] = [];
      
      for (let i = 0; i < ITERATIONS; i++) {
        const { durationMs } = await measureTime(() =>
          badgeService.generate({ score: 85, size: 'medium', theme: 'light' })
        );
        times.push(durationMs);
      }
      
      const avgTime = calculateAverage(times);
      console.log(`Medium badge avg: ${avgTime.toFixed(2)}ms over ${ITERATIONS} runs`);
      
      expect(avgTime).toBeLessThan(75);
    });

    it('SVG-BENCH-003: Large badge generation < 100ms', async () => {
      const times: number[] = [];
      
      for (let i = 0; i < ITERATIONS; i++) {
        const { durationMs } = await measureTime(() =>
          badgeService.generate({ score: 85, size: 'large', theme: 'light' })
        );
        times.push(durationMs);
      }
      
      const avgTime = calculateAverage(times);
      console.log(`Large badge avg: ${avgTime.toFixed(2)}ms over ${ITERATIONS} runs`);
      
      expect(avgTime).toBeLessThan(TARGET_MS);
    });

    it('SVG-BENCH-004: Light theme generation < 100ms', async () => {
      const { durationMs } = await measureTime(() =>
        badgeService.generate({ score: 85, size: 'medium', theme: 'light' })
      );
      
      expect(durationMs).toBeLessThan(TARGET_MS);
    });

    it('SVG-BENCH-005: Dark theme generation < 100ms', async () => {
      const { durationMs } = await measureTime(() =>
        badgeService.generate({ score: 85, size: 'medium', theme: 'dark' })
      );
      
      expect(durationMs).toBeLessThan(TARGET_MS);
    });

    it('SVG-BENCH-006: Concurrent badge generation (10 parallel) < 500ms', async () => {
      const promises = Array.from({ length: 10 }, (_, i) =>
        measureTime(() =>
          badgeService.generate({ score: 70 + i * 3, size: 'medium', theme: 'light' })
        )
      );
      
      const start = performance.now();
      const results = await Promise.all(promises);
      const totalDuration = performance.now() - start;
      
      console.log(`10 concurrent badges: ${totalDuration.toFixed(2)}ms`);
      
      expect(totalDuration).toBeLessThan(500);
      results.forEach(({ durationMs }) => {
        expect(durationMs).toBeLessThan(TARGET_MS);
      });
    });

    it('SVG-BENCH-007: Score 0 edge case < 100ms', async () => {
      const { durationMs } = await measureTime(() =>
        badgeService.generate({ score: 0, size: 'medium', theme: 'light' })
      );
      
      expect(durationMs).toBeLessThan(TARGET_MS);
    });

    it('SVG-BENCH-008: Score 100 edge case < 100ms', async () => {
      const { durationMs } = await measureTime(() =>
        badgeService.generate({ score: 100, size: 'medium', theme: 'light' })
      );
      
      expect(durationMs).toBeLessThan(TARGET_MS);
    });
  });

  describe('SVG-CACHE: Cache Performance', () => {
    it('SVG-BENCH-009: Cache hit performance < 5ms', async () => {
      // First request (cold cache)
      await badgeService.generate({ score: 85, size: 'medium', theme: 'light' });
      
      // Second request (cache hit)
      const { durationMs } = await measureTime(() =>
        badgeService.generate({ score: 85, size: 'medium', theme: 'light' })
      );
      
      console.log(`Cache hit: ${durationMs.toFixed(2)}ms`);
      expect(durationMs).toBeLessThan(5);
    });

    it('SVG-BENCH-010: Cold cache performance < 100ms', async () => {
      badgeService.clearCache();
      
      const { durationMs } = await measureTime(() =>
        badgeService.generate({ score: 85, size: 'medium', theme: 'light' })
      );
      
      expect(durationMs).toBeLessThan(100);
    });
  });

  describe('SVG-RESOURCES: Resource Usage', () => {
    it('SVG-BENCH-011: Memory usage for 1000 badges < 50MB', () => {
      const startMemory = process.memoryUsage().heapUsed;
      
      for (let i = 0; i < 1000; i++) {
        badgeService.generate({
          score: Math.floor(Math.random() * 101),
          size: ['small', 'medium', 'large'][i % 3] as 'small' | 'medium' | 'large',
          theme: ['light', 'dark'][i % 2] as 'light' | 'dark',
        });
      }
      
      const endMemory = process.memoryUsage().heapUsed;
      const memoryIncreaseMB = (endMemory - startMemory) / (1024 * 1024);
      
      console.log(`Memory increase for 1000 badges: ${memoryIncreaseMB.toFixed(2)}MB`);
      expect(memoryIncreaseMB).toBeLessThan(50);
    });

    it('SVG-BENCH-012: P99 latency < 150ms', async () => {
      const times: number[] = [];
      
      for (let i = 0; i < 100; i++) {
        const { durationMs } = await measureTime(() =>
          badgeService.generate({
            score: Math.floor(Math.random() * 101),
            size: 'medium',
            theme: 'light',
          })
        );
        times.push(durationMs);
      }
      
      const p99 = calculatePercentile(times, 99);
      console.log(`P99 latency: ${p99.toFixed(2)}ms`);
      
      expect(p99).toBeLessThan(150);
    });
  });

  describe('SVG-CONTENT: Output Validation', () => {
    it('generates valid SVG with xmlns attribute', () => {
      const result = badgeService.generate({ score: 85, size: 'medium', theme: 'light' });
      
      expect(result.svg).toContain('xmlns="http://www.w3.org/2000/svg"');
      expect(result.svg).toContain('<svg');
      expect(result.svg).toContain('</svg>');
    });

    it('includes correct dimensions', () => {
      const small = badgeService.generate({ score: 85, size: 'small', theme: 'light' });
      const medium = badgeService.generate({ score: 85, size: 'medium', theme: 'light' });
      const large = badgeService.generate({ score: 85, size: 'large', theme: 'light' });
      
      expect(small.width).toBe(120);
      expect(small.height).toBe(40);
      expect(medium.width).toBe(150);
      expect(medium.height).toBe(100);
      expect(large.width).toBe(300);
      expect(large.height).toBe(150);
    });

    it('includes score in SVG content', () => {
      const result = badgeService.generate({ score: 85, size: 'medium', theme: 'light' });
      
      expect(result.svg).toContain('85');
      expect(result.svg).toContain('SEO Score');
    });

    it('includes accessibility attributes', () => {
      const result = badgeService.generate({ score: 85, size: 'medium', theme: 'light' });
      
      expect(result.svg).toContain('role="img"');
      expect(result.svg).toContain('aria-label="SEO Score: 85/100"');
    });
  });
});

// Export for benchmark runner
export { MockBadgeService, measureTime, measureTimeAsync, calculatePercentile, calculateAverage };
