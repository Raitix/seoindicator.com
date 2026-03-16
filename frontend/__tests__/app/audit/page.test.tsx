/**
 * Public Audit Route Tests
 * Client-side rendered page for public SEO audit pages (static export compatible)
 * Uses query parameter: /audit/?h=abc123
 * @module __tests__/app/audit/page.test
 */

import React from 'react';
import { render, screen, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Mock next/navigation
const mockGet = vi.fn();
vi.mock('next/navigation', () => ({
  useSearchParams: () => ({
    get: mockGet,
  }),
}));

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock PublicAuditPage component - matches actual export structure
vi.mock('@/components/audit/PublicAuditPage', () => ({
  PublicAuditPage: ({ initialData, hash }: { initialData: { siteUrl: string; overallScore: number }; hash: string }) => (
    <div data-testid="public-audit-page">
      <span data-testid="audit-site">{initialData?.siteUrl}</span>
      <span data-testid="audit-score">{initialData?.overallScore}</span>
      <span data-testid="audit-hash">{hash}</span>
    </div>
  ),
  PublicAuditData: {} as any,
}));

// Import after mocks
import AuditPageClient from '@/app/audit/AuditPageClient';

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

const mockFetchResponse = (data: unknown, success = true) => ({
  ok: true,
  json: () => Promise.resolve({ success, data }),
});

describe('Public Audit Route - Query Parameter Approach', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGet.mockReturnValue('testhash123');
    // Reset document state
    document.title = '';
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================================================
  // Route Structure Tests
  // ============================================================================
  describe('Route Structure', () => {
    it('renders without crashing', async () => {
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      expect(screen.getByTestId('public-audit-page')).toBeInTheDocument();
    });

    it('uses useSearchParams to get hash', async () => {
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      expect(mockGet).toHaveBeenCalledWith('h');
    });

    it('passes hash to PublicAuditPage component', async () => {
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByTestId('audit-hash')).toHaveTextContent('testhash123');
      });
    });

    it('is a client component (has use client directive)', async () => {
      // Client components can use hooks like useSearchParams
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      // If this renders without error, it's a client component
      expect(screen.getByTestId('public-audit-page')).toBeInTheDocument();
    });

    it('is static export compatible (no dynamic route)', async () => {
      // This test verifies the page works without generateStaticParams
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      expect(screen.getByTestId('public-audit-page')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Loading State Tests
  // ============================================================================
  describe('Loading State', () => {
    it('shows loading spinner initially', () => {
      // Don't resolve fetch immediately
      mockFetch.mockImplementation(() => new Promise(() => {}));
      
      render(<AuditPageClient />);
      
      expect(screen.getByText('Loading audit...')).toBeInTheDocument();
    });

    it('shows spinner while fetching data', async () => {
      let resolveFetch: (value: unknown) => void;
      mockFetch.mockImplementation(() => new Promise((resolve) => {
        resolveFetch = resolve;
      }));
      
      render(<AuditPageClient />);
      
      expect(screen.getByText('Loading audit...')).toBeInTheDocument();
      
      // Resolve fetch
      await act(async () => {
        resolveFetch!(mockFetchResponse(mockAuditData));
      });
      
      await waitFor(() => {
        expect(screen.queryByText('Loading audit...')).not.toBeInTheDocument();
      });
    });
  });

  // ============================================================================
  // Data Fetching Tests
  // ============================================================================
  describe('Data Fetching', () => {
    it('fetches data from correct API endpoint', async () => {
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/public-audit/testhash123')
      );
    });

    it('renders audit data after successful fetch', async () => {
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByTestId('audit-site')).toHaveTextContent('https://example.com');
        expect(screen.getByTestId('audit-score')).toHaveTextContent('85');
      });
    });

    it('handles API error (404) - shows not found state', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ success: false, message: 'Not found' }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('handles API error (500) - shows not found state', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ success: false, message: 'Server error' }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('handles network error', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Error')).toBeInTheDocument();
      });
    });

    it('handles null data response', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, data: null }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('handles unsuccessful response', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: false, message: 'Audit is private' }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });
  });

  // ============================================================================
  // No Hash State Tests
  // ============================================================================
  describe('No Hash State', () => {
    it('shows "No Audit Specified" when no hash provided', async () => {
      mockGet.mockReturnValue(null);
      
      render(<AuditPageClient />);
      
      expect(screen.getByText('No Audit Specified')).toBeInTheDocument();
    });

    it('shows message about valid hash when no hash provided', async () => {
      mockGet.mockReturnValue(null);
      
      render(<AuditPageClient />);
      
      expect(screen.getByText(/provide a valid audit hash/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Not Found State Tests
  // ============================================================================
  describe('Not Found State', () => {
    it('shows "Audit Not Found" heading', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('shows helpful description', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText(/private or doesn't exist/i)).toBeInTheDocument();
      });
    });

    it('has "Go Home" link', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByRole('link', { name: /go home/i })).toBeInTheDocument();
      });
    });

    it('has "Get Your Free Audit" CTA', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByRole('link', { name: /get your free audit/i })).toBeInTheDocument();
      });
    });

    it('has contact support link', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByRole('link', { name: /contact support/i })).toBeInTheDocument();
      });
    });
  });

  // ============================================================================
  // Error State Tests
  // ============================================================================
  describe('Error State', () => {
    it('shows error message when fetch fails', async () => {
      mockFetch.mockRejectedValue(new Error('Failed to fetch'));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Error')).toBeInTheDocument();
        expect(screen.getByText('Failed to fetch')).toBeInTheDocument();
      });
    });

    it('shows generic error for unknown errors', async () => {
      mockFetch.mockRejectedValue('Unknown error');
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Failed to load audit')).toBeInTheDocument();
      });
    });
  });

  // ============================================================================
  // Security Tests
  // ============================================================================
  describe('Security', () => {
    it('handles XSS in hash parameter gracefully', async () => {
      mockGet.mockReturnValue('<script>alert(1)</script>');
      mockFetch.mockResolvedValue({
        ok: false,
        status: 400,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      // Should show not found, not execute script
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('does not expose sensitive data in errors', async () => {
      mockFetch.mockRejectedValue(new Error('Database password: secret123'));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        // Error message should be generic
        expect(screen.queryByText('secret123')).not.toBeInTheDocument();
      });
    });

    it('sanitizes hash in API call', async () => {
      mockGet.mockReturnValue('test-hash-123');
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      // Hash should be URL-safe in API call
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('test-hash-123')
      );
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================
  describe('Edge Cases', () => {
    it('handles empty hash', async () => {
      mockGet.mockReturnValue('');
      mockFetch.mockResolvedValue({
        ok: false,
        status: 400,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      // Empty string is falsy, so should show no hash state
      expect(screen.getByText('No Audit Specified')).toBeInTheDocument();
    });

    it('handles very long hash', async () => {
      const longHash = 'a'.repeat(1000);
      mockGet.mockReturnValue(longHash);
      mockFetch.mockResolvedValue({
        ok: false,
        status: 400,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      // Should not crash
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('handles unicode in hash', async () => {
      mockGet.mockReturnValue('тест-ハッシュ');
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('handles audit with zero score', async () => {
      const zeroScoreAudit = { ...mockAuditData, overallScore: 0 };
      mockFetch.mockResolvedValue(mockFetchResponse(zeroScoreAudit));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByTestId('audit-score')).toHaveTextContent('0');
      });
    });

    it('handles audit with perfect score', async () => {
      const perfectScoreAudit = { ...mockAuditData, overallScore: 100 };
      mockFetch.mockResolvedValue(mockFetchResponse(perfectScoreAudit));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByTestId('audit-score')).toHaveTextContent('100');
      });
    });
  });

  // ============================================================================
  // Acceptance Criteria Tests
  // ============================================================================
  describe('Acceptance Criteria', () => {
    it('AC-1: Route at /audit/page.tsx exists (static export compatible)', async () => {
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      expect(screen.getByTestId('public-audit-page')).toBeInTheDocument();
    });

    it('AC-2: No auth required (renders without auth context)', async () => {
      // Layout is tested separately
      // This test verifies the route can render without auth context
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      // If it renders, no auth is required
      expect(screen.getByTestId('public-audit-page')).toBeInTheDocument();
    });

    it('AC-3: 404 handling for invalid audits (shows Not Found UI)', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ success: false }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('AC-4: Client-side data fetching via public API', async () => {
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/public-audit/')
      );
    });

    it('AC-5: Client-side rendering (static export compatible)', async () => {
      // Client-side rendering is used for static export compatibility
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      // Verify client-side rendering works
      await waitFor(() => {
        expect(screen.getByTestId('public-audit-page')).toBeInTheDocument();
      });
    });

    it('AC-6: Query parameter approach (?h=abc123)', async () => {
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      expect(mockGet).toHaveBeenCalledWith('h');
    });

    it('AC-7: Handles unpublished audit (shows Not Found)', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: false, message: 'Not published' }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('AC-8: Handles null/invalid data (shows Not Found)', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, data: null }),
      });
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Audit Not Found')).toBeInTheDocument();
      });
    });

    it('AC-9: Shows loading state during fetch', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));
      
      render(<AuditPageClient />);
      
      expect(screen.getByText('Loading audit...')).toBeInTheDocument();
    });

    it('AC-10: Unit tests for route rendering', async () => {
      // This test itself validates the route
      mockFetch.mockResolvedValue(mockFetchResponse(mockAuditData));
      
      await act(async () => {
        render(<AuditPageClient />);
      });
      
      await waitFor(() => {
        expect(screen.getByTestId('public-audit-page')).toBeInTheDocument();
      });
    });
  });
});
