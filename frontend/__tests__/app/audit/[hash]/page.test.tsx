/**
 * Legacy Public Audit Route Tests
 * Tests the redirect from old URL format /audit/{hash}/ to new format /audit/?h={hash}
 * @module __tests__/app/audit/[hash]/page.test
 */

import React from 'react';
import { render, screen, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Mock next/navigation
const mockReplace = vi.fn();
vi.mock('next/navigation', () => ({
  useRouter: () => ({
    replace: mockReplace,
  }),
}));

// Import after mocks
import LegacyAuditRedirect from '@/app/audit/[hash]/LegacyAuditRedirect';

describe('Legacy Public Audit Route - Redirect Handler', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================================================
  // Route Structure Tests
  // ============================================================================
  describe('Route Structure', () => {
    it('renders without crashing', () => {
      render(<LegacyAuditRedirect hash="testhash" />);
      expect(screen.getByText('Redirecting...')).toBeInTheDocument();
    });

    it('has generateStaticParams function', async () => {
      const { generateStaticParams } = await import('@/app/audit/[hash]/page');
      expect(typeof generateStaticParams).toBe('function');
    });

    it('generateStaticParams returns placeholder for static export', async () => {
      const { generateStaticParams } = await import('@/app/audit/[hash]/page');
      const params = generateStaticParams();
      expect(params).toEqual([{ hash: '_' }]);
    });

    it('has dynamicParams = false for static export', async () => {
      const { dynamicParams } = await import('@/app/audit/[hash]/page');
      expect(dynamicParams).toBe(false);
    });
  });

  // ============================================================================
  // Redirect Behavior Tests
  // ============================================================================
  describe('Redirect Behavior', () => {
    it('shows redirecting state initially', () => {
      render(<LegacyAuditRedirect hash="testhash" />);
      expect(screen.getByText('Redirecting...')).toBeInTheDocument();
    });

    it('shows loading spinner during redirect', () => {
      render(<LegacyAuditRedirect hash="testhash" />);
      // Loader2 is rendered as an SVG with animate-spin class
      const spinner = document.querySelector('.animate-spin');
      expect(spinner).toBeInTheDocument();
    });

    it('uses router.replace for client-side redirect', async () => {
      render(<LegacyAuditRedirect hash="testhash" />);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/audit/?h=testhash');
      });
    });

    it('redirects with correct query parameter format', async () => {
      render(<LegacyAuditRedirect hash="abc123xyz" />);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/audit/?h=abc123xyz');
      });
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================
  describe('Edge Cases', () => {
    it('handles special characters in hash', async () => {
      render(<LegacyAuditRedirect hash="test-hash_123" />);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/audit/?h=test-hash_123');
      });
    });

    it('handles long hash values', async () => {
      const longHash = 'a'.repeat(64);
      render(<LegacyAuditRedirect hash={longHash} />);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith(`/audit/?h=${longHash}`);
      });
    });

    it('handles placeholder hash', async () => {
      render(<LegacyAuditRedirect hash="_" />);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/audit/?h=_');
      });
    });
  });

  // ============================================================================
  // Static Export Compatibility Tests
  // ============================================================================
  describe('Static Export Compatibility', () => {
    it('only generates placeholder for static export', async () => {
      const { generateStaticParams } = await import('@/app/audit/[hash]/page');
      const params = generateStaticParams();
      
      // Only the placeholder should be generated
      expect(params.length).toBe(1);
      expect(params[0].hash).toBe('_');
    });

    it('dynamicParams is disabled', async () => {
      const { dynamicParams } = await import('@/app/audit/[hash]/page');
      
      // This ensures other hashes won't be generated
      expect(dynamicParams).toBe(false);
    });
  });

  // ============================================================================
  // Acceptance Criteria Tests
  // ============================================================================
  describe('Acceptance Criteria', () => {
    it('AC-1: Route exists for backward compatibility', () => {
      render(<LegacyAuditRedirect hash="test" />);
      expect(screen.getByText('Redirecting...')).toBeInTheDocument();
    });

    it('AC-2: Redirects to new URL format', async () => {
      render(<LegacyAuditRedirect hash="abc123" />);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/audit/?h=abc123');
      });
    });

    it('AC-3: Static export compatible (only placeholder generated)', async () => {
      const { generateStaticParams, dynamicParams } = await import('@/app/audit/[hash]/page');
      
      expect(generateStaticParams()).toEqual([{ hash: '_' }]);
      expect(dynamicParams).toBe(false);
    });
  });
});
