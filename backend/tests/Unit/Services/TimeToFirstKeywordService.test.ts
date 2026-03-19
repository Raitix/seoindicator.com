/**
 * Time to First Keyword Service Tests
 *
 * Tests for the metric measuring time from registration to first keyword.
 *
 * @see TEST-17-16.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Time bucket enum
enum TimeToFirstKeywordBucket {
  SAME_SESSION = 'same_session',
  WITHIN_HOUR = 'within_hour',
  WITHIN_DAY = 'within_day',
  WITHIN_WEEK = 'within_week',
  NEVER = 'never',
}

// Mock service implementation
class TimeToFirstKeywordService {
  /**
   * Calculate time difference in seconds
   */
  calculateTimeDifference(
    registrationTime: Date,
    keywordTime: Date | null
  ): number | null {
    if (!keywordTime) return null;
    const diff = Math.floor((keywordTime.getTime() - registrationTime.getTime()) / 1000);
    return diff >= 0 ? diff : null;
  }

  /**
   * Convert seconds to minutes
   */
  toMinutes(seconds: number): number {
    return Math.floor(seconds / 60);
  }

  /**
   * Convert seconds to hours
   */
  toHours(seconds: number): number {
    return Math.floor(seconds / 3600);
  }

  /**
   * Classify time into bucket
   */
  classifyBucket(minutes: number | null): TimeToFirstKeywordBucket {
    if (minutes === null) return TimeToFirstKeywordBucket.NEVER;
    if (minutes < 30) return TimeToFirstKeywordBucket.SAME_SESSION;
    if (minutes < 60) return TimeToFirstKeywordBucket.WITHIN_HOUR;
    if (minutes < 1440) return TimeToFirstKeywordBucket.WITHIN_DAY; // 24 * 60
    if (minutes < 10080) return TimeToFirstKeywordBucket.WITHIN_WEEK; // 7 * 24 * 60
    return TimeToFirstKeywordBucket.WITHIN_WEEK;
  }

  /**
   * Calculate average from array
   */
  calculateAverage(values: (number | null)[]): number {
    const validValues = values.filter((v): v is number => v !== null);
    if (validValues.length === 0) return 0;
    return validValues.reduce((sum, v) => sum + v, 0) / validValues.length;
  }

  /**
   * Calculate median from array
   */
  calculateMedian(values: (number | null)[]): number {
    const validValues = values.filter((v): v is number => v !== null);
    if (validValues.length === 0) return 0;
    
    const sorted = [...validValues].sort((a, b) => a - b);
    const mid = Math.floor(sorted.length / 2);
    
    if (sorted.length % 2 === 0) {
      return (sorted[mid - 1] + sorted[mid]) / 2;
    }
    return sorted[mid];
  }

  /**
   * Check if alert should be triggered
   */
  shouldAlert(medianMinutes: number): boolean {
    return medianMinutes > 10;
  }

  /**
   * Calculate full metrics
   */
  calculateMetrics(
    userData: Array<{ registeredAt: Date; firstKeywordAt: Date | null }>
  ) {
    const times = userData.map((u) => {
      const seconds = this.calculateTimeDifference(u.registeredAt, u.firstKeywordAt);
      return seconds ? this.toMinutes(seconds) : null;
    });

    const averageMinutes = this.calculateAverage(times);
    const medianMinutes = this.calculateMedian(times);

    const bucketDistribution = {
      [TimeToFirstKeywordBucket.SAME_SESSION]: 0,
      [TimeToFirstKeywordBucket.WITHIN_HOUR]: 0,
      [TimeToFirstKeywordBucket.WITHIN_DAY]: 0,
      [TimeToFirstKeywordBucket.WITHIN_WEEK]: 0,
      [TimeToFirstKeywordBucket.NEVER]: 0,
    };

    times.forEach((minutes) => {
      const bucket = this.classifyBucket(minutes);
      bucketDistribution[bucket]++;
    });

    const total = userData.length;
    const percentageDistribution = {
      [TimeToFirstKeywordBucket.SAME_SESSION]: (bucketDistribution[TimeToFirstKeywordBucket.SAME_SESSION] / total) * 100,
      [TimeToFirstKeywordBucket.WITHIN_HOUR]: (bucketDistribution[TimeToFirstKeywordBucket.WITHIN_HOUR] / total) * 100,
      [TimeToFirstKeywordBucket.WITHIN_DAY]: (bucketDistribution[TimeToFirstKeywordBucket.WITHIN_DAY] / total) * 100,
      [TimeToFirstKeywordBucket.WITHIN_WEEK]: (bucketDistribution[TimeToFirstKeywordBucket.WITHIN_WEEK] / total) * 100,
      [TimeToFirstKeywordBucket.NEVER]: (bucketDistribution[TimeToFirstKeywordBucket.NEVER] / total) * 100,
    };

    return {
      average_minutes: Math.round(averageMinutes),
      median_minutes: Math.round(medianMinutes),
      bucket_distribution: bucketDistribution,
      percentage_distribution: percentageDistribution,
      alert: this.shouldAlert(medianMinutes),
      alert_message: this.shouldAlert(medianMinutes)
        ? 'Median time to first keyword exceeds 10 minutes. Consider optimizing onboarding.'
        : undefined,
    };
  }
}

describe('Time to First Keyword Service', () => {
  let service: TimeToFirstKeywordService;

  beforeEach(() => {
    service = new TimeToFirstKeywordService();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // TF-001: Time Calculation
  // ============================================
  describe('Time Calculation', () => {
    it('TF-001-01: calculate time difference', () => {
      const registration = new Date('2026-03-19T10:00:00Z');
      const keyword = new Date('2026-03-19T10:15:00Z');

      const result = service.calculateTimeDifference(registration, keyword);

      expect(result).toBe(900); // 15 minutes in seconds
    });

    it('TF-001-02: handle same minute', () => {
      const registration = new Date('2026-03-19T10:00:30Z');
      const keyword = new Date('2026-03-19T10:00:45Z');

      const result = service.calculateTimeDifference(registration, keyword);

      expect(result).toBe(15);
    });

    it('TF-001-03: handle days difference', () => {
      const registration = new Date('2026-03-17T10:00:00Z');
      const keyword = new Date('2026-03-19T10:00:00Z');

      const result = service.calculateTimeDifference(registration, keyword);

      expect(result).toBe(172800); // 2 days in seconds
    });

    it('TF-001-04: handle null keyword timestamp', () => {
      const registration = new Date('2026-03-19T10:00:00Z');

      const result = service.calculateTimeDifference(registration, null);

      expect(result).toBeNull();
    });

    it('TF-001-05: calculate in minutes', () => {
      const result = service.toMinutes(900);
      expect(result).toBe(15);
    });

    it('TF-001-06: calculate in hours', () => {
      const result = service.toHours(7200);
      expect(result).toBe(2);
    });
  });

  // ============================================
  // TF-002: Bucket Classification
  // ============================================
  describe('Bucket Classification', () => {
    it('TF-002-01: same session bucket', () => {
      const result = service.classifyBucket(15);
      expect(result).toBe(TimeToFirstKeywordBucket.SAME_SESSION);
    });

    it('TF-002-02: within hour bucket', () => {
      const result = service.classifyBucket(45);
      expect(result).toBe(TimeToFirstKeywordBucket.WITHIN_HOUR);
    });

    it('TF-002-03: within day bucket', () => {
      const result = service.classifyBucket(300); // 5 hours
      expect(result).toBe(TimeToFirstKeywordBucket.WITHIN_DAY);
    });

    it('TF-002-04: within week bucket', () => {
      const result = service.classifyBucket(4320); // 3 days
      expect(result).toBe(TimeToFirstKeywordBucket.WITHIN_WEEK);
    });

    it('TF-002-05: never bucket', () => {
      const result = service.classifyBucket(null);
      expect(result).toBe(TimeToFirstKeywordBucket.NEVER);
    });

    it('TF-002-06: boundary: 30 minutes', () => {
      const result = service.classifyBucket(30);
      expect(result).toBe(TimeToFirstKeywordBucket.WITHIN_HOUR);
    });

    it('TF-002-07: boundary: 1 hour', () => {
      const result = service.classifyBucket(60);
      expect(result).toBe(TimeToFirstKeywordBucket.WITHIN_DAY);
    });

    it('TF-002-08: boundary: 24 hours', () => {
      const result = service.classifyBucket(1440);
      expect(result).toBe(TimeToFirstKeywordBucket.WITHIN_WEEK);
    });
  });

  // ============================================
  // TF-003: Average Calculation
  // ============================================
  describe('Average Calculation', () => {
    it('TF-003-01: calculate average', () => {
      const result = service.calculateAverage([10, 20, 30]);
      expect(result).toBe(20);
    });

    it('TF-003-02: handle null values', () => {
      const result = service.calculateAverage([10, null, 20]);
      expect(result).toBe(15);
    });

    it('TF-003-03: handle empty array', () => {
      const result = service.calculateAverage([]);
      expect(result).toBe(0);
    });

    it('TF-003-04: all null values', () => {
      const result = service.calculateAverage([null, null, null]);
      expect(result).toBe(0);
    });
  });

  // ============================================
  // TF-004: Median Calculation
  // ============================================
  describe('Median Calculation', () => {
    it('TF-004-01: calculate median odd', () => {
      const result = service.calculateMedian([10, 20, 30]);
      expect(result).toBe(20);
    });

    it('TF-004-02: calculate median even', () => {
      const result = service.calculateMedian([10, 20, 30, 40]);
      expect(result).toBe(25);
    });

    it('TF-004-03: alert if median > 10 min', () => {
      const result = service.shouldAlert(15);
      expect(result).toBe(true);
    });

    it('TF-004-04: no alert if median <= 10 min', () => {
      const result = service.shouldAlert(8);
      expect(result).toBe(false);
    });

    it('TF-004-05: alert at exactly 10 min', () => {
      const result = service.shouldAlert(10);
      expect(result).toBe(false);
    });
  });

  // ============================================
  // TF-005: Full Metrics Calculation
  // ============================================
  describe('Full Metrics Calculation', () => {
    it('TF-005-01: calculates all metrics correctly', () => {
      const userData = [
        { registeredAt: new Date('2026-03-19T10:00:00Z'), firstKeywordAt: new Date('2026-03-19T10:10:00Z') }, // 10 min
        { registeredAt: new Date('2026-03-19T11:00:00Z'), firstKeywordAt: new Date('2026-03-19T11:20:00Z') }, // 20 min
        { registeredAt: new Date('2026-03-19T12:00:00Z'), firstKeywordAt: new Date('2026-03-19T12:30:00Z') }, // 30 min
        { registeredAt: new Date('2026-03-19T13:00:00Z'), firstKeywordAt: null }, // never
      ];

      const result = service.calculateMetrics(userData);

      expect(result.average_minutes).toBe(20); // (10 + 20 + 30) / 3
      expect(result.median_minutes).toBe(20);
      expect(result.alert).toBe(true);
      expect(result.bucket_distribution[TimeToFirstKeywordBucket.SAME_SESSION]).toBe(2);
      expect(result.bucket_distribution[TimeToFirstKeywordBucket.WITHIN_HOUR]).toBe(1);
      expect(result.bucket_distribution[TimeToFirstKeywordBucket.NEVER]).toBe(1);
    });

    it('TF-005-02: no alert when median < 10', () => {
      const userData = [
        { registeredAt: new Date('2026-03-19T10:00:00Z'), firstKeywordAt: new Date('2026-03-19T10:05:00Z') }, // 5 min
        { registeredAt: new Date('2026-03-19T11:00:00Z'), firstKeywordAt: new Date('2026-03-19T11:08:00Z') }, // 8 min
        { registeredAt: new Date('2026-03-19T12:00:00Z'), firstKeywordAt: new Date('2026-03-19T12:10:00Z') }, // 10 min
      ];

      const result = service.calculateMetrics(userData);

      expect(result.median_minutes).toBe(8);
      expect(result.alert).toBe(false);
    });
  });

  // ============================================
  // TF-009: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('TF-009-01: keyword before registration returns null', () => {
      const registration = new Date('2026-03-19T10:00:00Z');
      const keyword = new Date('2026-03-19T09:00:00Z'); // Before registration

      const result = service.calculateTimeDifference(registration, keyword);

      expect(result).toBeNull();
    });

    it('TF-009-02: very large time span', () => {
      const registration = new Date('2026-01-01T00:00:00Z');
      const keyword = new Date('2026-12-31T00:00:00Z'); // ~365 days

      const result = service.calculateTimeDifference(registration, keyword);

      expect(result).toBeGreaterThan(30000000); // Many seconds
    });

    it('TF-009-03: empty user data', () => {
      const result = service.calculateMetrics([]);
      
      expect(result.average_minutes).toBe(0);
      expect(result.median_minutes).toBe(0);
    });
  });
});
