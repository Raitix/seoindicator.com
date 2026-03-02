/**
 * Tests for TypeScript Types
 * Task: TASK-23-13 - Backlinks Dashboard Components
 *
 * Runtime type validation tests for backlink-related TypeScript interfaces.
 */

import '@testing-library/jest-dom';

// ============================================================================
// Type Definitions (will be imported from types.ts)
// ============================================================================

interface BacklinkSummary {
  totalBacklinks: number;
  referringDomains: number;
  dofollowRatio: number;
  avgDomainTrust: number;
  growthRate: number;
  profileScore: number;
}

interface ReferringDomain {
  domain: string;
  backlinks_count: number;
  domain_trust_score: number;
  first_seen_at: string;
  last_seen_at: string | null;
}

interface AnchorText {
  anchor_text: string;
  backlinks_count: number;
  percentage: number;
  is_branded: boolean;
  is_exact_match: boolean;
}

interface BacklinkHistory {
  date: string;
  backlinks: number;
  domains: number;
}

interface BacklinksOverviewResponse {
  success: boolean;
  data: {
    summary: BacklinkSummary;
    recentBacklinks: ReferringDomain[];
    topReferringDomains: ReferringDomain[];
  };
}

interface DomainsResponse {
  data: ReferringDomain[];
  meta: {
    total: number;
    page: number;
    perPage: number;
    totalPages: number;
  };
}

interface AnchorsResponse {
  data: AnchorText[];
}

interface HistoryResponse {
  data: BacklinkHistory[];
  meta?: {
    from: string;
    to: string;
  };
}

interface SyncResponse {
  jobId: string;
  status: 'queued' | 'running' | 'completed' | 'failed';
  estimatedTime?: number;
}

// ============================================================================
// Type Guard Functions
// ============================================================================

function isValidBacklinkSummary(obj: unknown): obj is BacklinkSummary {
  if (typeof obj !== 'object' || obj === null) return false;
  const summary = obj as Record<string, unknown>;
  return (
    typeof summary.totalBacklinks === 'number' &&
    typeof summary.referringDomains === 'number' &&
    typeof summary.dofollowRatio === 'number' &&
    typeof summary.avgDomainTrust === 'number' &&
    typeof summary.growthRate === 'number' &&
    typeof summary.profileScore === 'number'
  );
}

function isValidReferringDomain(obj: unknown): obj is ReferringDomain {
  if (typeof obj !== 'object' || obj === null) return false;
  const domain = obj as Record<string, unknown>;
  return (
    typeof domain.domain === 'string' &&
    typeof domain.backlinks_count === 'number' &&
    typeof domain.domain_trust_score === 'number' &&
    typeof domain.first_seen_at === 'string' &&
    (domain.last_seen_at === null || typeof domain.last_seen_at === 'string')
  );
}

function isValidAnchorText(obj: unknown): obj is AnchorText {
  if (typeof obj !== 'object' || obj === null) return false;
  const anchor = obj as Record<string, unknown>;
  return (
    typeof anchor.anchor_text === 'string' &&
    typeof anchor.backlinks_count === 'number' &&
    typeof anchor.percentage === 'number' &&
    typeof anchor.is_branded === 'boolean' &&
    typeof anchor.is_exact_match === 'boolean'
  );
}

function isValidBacklinkHistory(obj: unknown): obj is BacklinkHistory {
  if (typeof obj !== 'object' || obj === null) return false;
  const history = obj as Record<string, unknown>;
  return (
    typeof history.date === 'string' &&
    typeof history.backlinks === 'number' &&
    typeof history.domains === 'number'
  );
}

// ============================================================================
// Tests
// ============================================================================

describe('TypeScript Types Validation', () => {
  describe('BD-006a: BacklinkSummary Type', () => {
    const validSummary: BacklinkSummary = {
      totalBacklinks: 1250,
      referringDomains: 89,
      dofollowRatio: 0.72,
      avgDomainTrust: 45.2,
      growthRate: 12.5,
      profileScore: 68.5,
    };

    it('validates correct BacklinkSummary', () => {
      expect(isValidBacklinkSummary(validSummary)).toBe(true);
    });

    it('rejects missing totalBacklinks', () => {
      const invalid = { ...validSummary, totalBacklinks: undefined };
      expect(isValidBacklinkSummary(invalid)).toBe(false);
    });

    it('rejects missing referringDomains', () => {
      const invalid = { ...validSummary, referringDomains: undefined };
      expect(isValidBacklinkSummary(invalid)).toBe(false);
    });

    it('rejects missing dofollowRatio', () => {
      const invalid = { ...validSummary, dofollowRatio: undefined };
      expect(isValidBacklinkSummary(invalid)).toBe(false);
    });

    it('rejects missing avgDomainTrust', () => {
      const invalid = { ...validSummary, avgDomainTrust: undefined };
      expect(isValidBacklinkSummary(invalid)).toBe(false);
    });

    it('rejects missing growthRate', () => {
      const invalid = { ...validSummary, growthRate: undefined };
      expect(isValidBacklinkSummary(invalid)).toBe(false);
    });

    it('rejects missing profileScore', () => {
      const invalid = { ...validSummary, profileScore: undefined };
      expect(isValidBacklinkSummary(invalid)).toBe(false);
    });

    it('rejects wrong type for totalBacklinks', () => {
      const invalid = { ...validSummary, totalBacklinks: '1250' };
      expect(isValidBacklinkSummary(invalid)).toBe(false);
    });

    it('accepts zero values', () => {
      const zeroSummary = {
        totalBacklinks: 0,
        referringDomains: 0,
        dofollowRatio: 0,
        avgDomainTrust: 0,
        growthRate: 0,
        profileScore: 0,
      };
      expect(isValidBacklinkSummary(zeroSummary)).toBe(true);
    });

    it('accepts negative growthRate', () => {
      const negativeGrowth = { ...validSummary, growthRate: -15.5 };
      expect(isValidBacklinkSummary(negativeGrowth)).toBe(true);
    });

    it('rejects null', () => {
      expect(isValidBacklinkSummary(null)).toBe(false);
    });

    it('rejects undefined', () => {
      expect(isValidBacklinkSummary(undefined)).toBe(false);
    });

    it('rejects empty object', () => {
      expect(isValidBacklinkSummary({})).toBe(false);
    });
  });

  describe('BD-006b: ReferringDomain Type', () => {
    const validDomain: ReferringDomain = {
      domain: 'example.com',
      backlinks_count: 45,
      domain_trust_score: 72,
      first_seen_at: '2026-01-15T10:00:00Z',
      last_seen_at: '2026-02-28T15:30:00Z',
    };

    it('validates correct ReferringDomain', () => {
      expect(isValidReferringDomain(validDomain)).toBe(true);
    });

    it('accepts null last_seen_at', () => {
      const valid = { ...validDomain, last_seen_at: null };
      expect(isValidReferringDomain(valid)).toBe(true);
    });

    it('rejects missing domain', () => {
      const invalid = { ...validDomain, domain: undefined };
      expect(isValidReferringDomain(invalid)).toBe(false);
    });

    it('rejects missing backlinks_count', () => {
      const invalid = { ...validDomain, backlinks_count: undefined };
      expect(isValidReferringDomain(invalid)).toBe(false);
    });

    it('rejects missing domain_trust_score', () => {
      const invalid = { ...validDomain, domain_trust_score: undefined };
      expect(isValidReferringDomain(invalid)).toBe(false);
    });

    it('rejects missing first_seen_at', () => {
      const invalid = { ...validDomain, first_seen_at: undefined };
      expect(isValidReferringDomain(invalid)).toBe(false);
    });

    it('rejects wrong type for domain', () => {
      const invalid = { ...validDomain, domain: 123 };
      expect(isValidReferringDomain(invalid)).toBe(false);
    });

    it('rejects wrong type for backlinks_count', () => {
      const invalid = { ...validDomain, backlinks_count: '45' };
      expect(isValidReferringDomain(invalid)).toBe(false);
    });
  });

  describe('BD-006c: AnchorText Type', () => {
    const validAnchor: AnchorText = {
      anchor_text: 'Brand Name',
      backlinks_count: 280,
      percentage: 22.4,
      is_branded: true,
      is_exact_match: false,
    };

    it('validates correct AnchorText', () => {
      expect(isValidAnchorText(validAnchor)).toBe(true);
    });

    it('rejects missing anchor_text', () => {
      const invalid = { ...validAnchor, anchor_text: undefined };
      expect(isValidAnchorText(invalid)).toBe(false);
    });

    it('rejects missing backlinks_count', () => {
      const invalid = { ...validAnchor, backlinks_count: undefined };
      expect(isValidAnchorText(invalid)).toBe(false);
    });

    it('rejects missing percentage', () => {
      const invalid = { ...validAnchor, percentage: undefined };
      expect(isValidAnchorText(invalid)).toBe(false);
    });

    it('rejects missing is_branded', () => {
      const invalid = { ...validAnchor, is_branded: undefined };
      expect(isValidAnchorText(invalid)).toBe(false);
    });

    it('rejects missing is_exact_match', () => {
      const invalid = { ...validAnchor, is_exact_match: undefined };
      expect(isValidAnchorText(invalid)).toBe(false);
    });

    it('rejects wrong type for is_branded', () => {
      const invalid = { ...validAnchor, is_branded: 'true' };
      expect(isValidAnchorText(invalid)).toBe(false);
    });

    it('accepts false boolean values', () => {
      const allFalse = {
        anchor_text: 'click here',
        backlinks_count: 100,
        percentage: 8.0,
        is_branded: false,
        is_exact_match: false,
      };
      expect(isValidAnchorText(allFalse)).toBe(true);
    });
  });

  describe('BD-006d: BacklinkHistory Type', () => {
    const validHistory: BacklinkHistory = {
      date: '2026-01-15',
      backlinks: 1150,
      domains: 80,
    };

    it('validates correct BacklinkHistory', () => {
      expect(isValidBacklinkHistory(validHistory)).toBe(true);
    });

    it('rejects missing date', () => {
      const invalid = { ...validHistory, date: undefined };
      expect(isValidBacklinkHistory(invalid)).toBe(false);
    });

    it('rejects missing backlinks', () => {
      const invalid = { ...validHistory, backlinks: undefined };
      expect(isValidBacklinkHistory(invalid)).toBe(false);
    });

    it('rejects missing domains', () => {
      const invalid = { ...validHistory, domains: undefined };
      expect(isValidBacklinkHistory(invalid)).toBe(false);
    });

    it('rejects wrong type for date', () => {
      const invalid = { ...validHistory, date: 20260115 };
      expect(isValidBacklinkHistory(invalid)).toBe(false);
    });

    it('rejects wrong type for backlinks', () => {
      const invalid = { ...validHistory, backlinks: '1150' };
      expect(isValidBacklinkHistory(invalid)).toBe(false);
    });

    it('accepts zero values', () => {
      const zeroHistory = { date: '2026-01-01', backlinks: 0, domains: 0 };
      expect(isValidBacklinkHistory(zeroHistory)).toBe(true);
    });
  });

  describe('Array Validation', () => {
    it('validates array of ReferringDomain', () => {
      const domains: ReferringDomain[] = [
        { domain: 'a.com', backlinks_count: 10, domain_trust_score: 50, first_seen_at: '2026-01-01', last_seen_at: null },
        { domain: 'b.com', backlinks_count: 20, domain_trust_score: 60, first_seen_at: '2026-01-15', last_seen_at: '2026-02-01' },
      ];
      expect(domains.every(isValidReferringDomain)).toBe(true);
    });

    it('validates array of AnchorText', () => {
      const anchors: AnchorText[] = [
        { anchor_text: 'Brand', backlinks_count: 100, percentage: 50, is_branded: true, is_exact_match: false },
        { anchor_text: 'keyword', backlinks_count: 50, percentage: 25, is_branded: false, is_exact_match: true },
      ];
      expect(anchors.every(isValidAnchorText)).toBe(true);
    });

    it('validates array of BacklinkHistory', () => {
      const history: BacklinkHistory[] = [
        { date: '2026-01-01', backlinks: 1000, domains: 50 },
        { date: '2026-02-01', backlinks: 1100, domains: 55 },
      ];
      expect(history.every(isValidBacklinkHistory)).toBe(true);
    });
  });
});
