/**
 * Legacy Public Audit Route Tests
 * Tests the redirect from old URL format /audit/{hash}/ to new format /audit/?h={hash}
 * @module __tests__/app/audit/[hash]/page.test
 */

import React from 'react';
import { render, screen, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock next/navigation
const mockUseRouter = jest.fn();
const mockReplace = jest.fn();
jest.mock('next/navigation', () => ({
  useRouter: () => mockUseRouter(),
  redirect: jest.fn(),
}));

// Mock fetch - not used in redirect component
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Import after mocks
import Page from '@/app/audit/[hash]/page';

// Test data
const mockAuditData = {
  id: 1,
  hash: 'testhash123',
  siteUrl: 'https://example.com',
  overallScore: 85,
  seoScore: 88,
  performanceScore: 82,
  accessibilityScore: 90,
  bestPracticesScore: 85,
  createdAt: '2024-01-01T00:00:00Z',
  expiresAt: '2025-01-01T00:00:00Z',
  isPublished: true,
};

describe('Legacy Public Audit Route - Redirect Handler', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockUseRouter.mockReturnValue({
      replace: mockReplace,
    });
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  // ============================================================================
  // Route Structure Tests
  // ============================================================================
  describe('Route Structure', () => {
    it('renders without crashing', async () => {
      const page = await Page({ params: Promise.resolve({ hash: 'testhash' }) });
      render(page);
      
      expect(screen.getByText('Redirecting...')).toBeInTheDocument();
    });

    it('has generateStaticParams function', () => {
      const { generateStaticParams } = require('@/app/audit/[hash]/page');
      expect(typeof generateStaticParams).toBe('function');
    });

    it('generateStaticParams returns placeholder for static export', () => {
      const { generateStaticParams } = require('@/app/audit/[hash]/page');
      const params = generateStaticParams();
      expect(params).toEqual([{ hash: '_' }]);
    });

    it('has dynamicParams = false for static export', () => {
      const { dynamicParams } = require('@/app/audit/[hash]/page');
      expect(dynamicParams).toBe(false);
    });
  });

  // ============================================================================
  // Redirect Behavior Tests
  // ============================================================================
  describe('Redirect Behavior', () => {
    it('shows redirecting state initially', async () => {
      const page = await Page({ params: Promise.resolve({ hash: 'testhash' }) });
      render(page);
      
      expect(screen.getByText('Redirecting...')).toBeInTheDocument();
    });

    it('shows loading spinner during redirect', async () => {
      const page = await Page({ params: Promise.resolve({ hash: 'testhash' }) });
      render(page);
      
      expect(screen.getByRole('img', { hidden: true })).toBeInTheDocument();
    });

    it('uses router.replace for client-side redirect', async () => {
      const page = await Page({ params: Promise.resolve({ hash: 'testhash' }) });
      render(page);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/audit/?h=testhash');
      });
    });

    it('redirects with correct query parameter format', async () => {
      const page = await Page({ params: Promise.resolve({ hash: 'abc123xyz' }) });
      render(page);
      
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
      const page = await Page({ params: Promise.resolve({ hash: 'test-hash_123' }) });
      render(page);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/audit/?h=test-hash_123');
      });
    });

    it('handles long hash values', async () => {
      const longHash = 'a'.repeat(64);
      const page = await Page({ params: Promise.resolve({ hash: longHash }) });
      render(page);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith(`/audit/?h=${longHash}`);
      });
    });

    it('handles placeholder hash', async () => {
      const page = await Page({ params: Promise.resolve({ hash: '_' }) });
      render(page);
      
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
      const { generateStaticParams } = require('@/app/audit/[hash]/page');
      const params = generateStaticParams();
      
      // Only the placeholder should be generated
      expect(params.length).toBe(1);
      expect(params[0].hash).toBe('_');
    });

    it('dynamicParams is disabled', async () => {
      const { dynamicParams } = require('@/app/audit/[hash]/page');
      
      // This ensures other hashes won't be generated
      expect(dynamicParams).toBe(false);
    });
  });

  // ============================================================================
  // Acceptance Criteria Tests
  // ============================================================================
  describe('Acceptance Criteria', () => {
    it('AC-1: Route exists for backward compatibility', async () => {
      const page = await Page({ params: Promise.resolve({ hash: 'test' }) });
      render(page);
      
      expect(screen.getByText('Redirecting...')).toBeInTheDocument();
    });

    it('AC-2: Redirects to new URL format', async () => {
      const page = await Page({ params: Promise.resolve({ hash: 'abc123' }) });
      render(page);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/audit/?h=abc123');
      });
    });

    it('AC-3: Static export compatible (only placeholder generated)', async () => {
      const { generateStaticParams, dynamicParams } = require('@/app/audit/[hash]/page');
      
      expect(generateStaticParams()).toEqual([{ hash: '_' }]);
      expect(dynamicParams).toBe(false);
    });
  });
});
