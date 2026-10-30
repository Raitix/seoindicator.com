/**
 * AuditDetailPage Component Tests
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { AuditDetailPage } from '@/components/audit/AuditDetailPage';

// Mock next/navigation
const mockPush = vi.fn();
const mockReplace = vi.fn();
vi.mock('next/navigation', () => ({
  useParams: () => ({ siteId: '1', auditId: '123' }),
  useSearchParams: () => new URLSearchParams(),
  useRouter: () => ({ push: mockPush, replace: mockReplace }),
}));

// Mock window.location
const originalLocation = window.location;
beforeAll(() => {
  Object.defineProperty(window, 'location', {
    value: {
      pathname: '/sites/1/audits/123',
      search: '',
    },
    writable: true,
  });
});
afterAll(() => {
  window.location = originalLocation;
});

// Mock API hooks
vi.mock('@/lib/api/audit-issues', () => ({
  useAuditSummary: () => ({
    audit: {
      id: 123,
      status: 'completed',
      pages_crawled: 100,
      issues_found: 25,
      started_at: '2026-03-06T10:00:00Z',
      completed_at: '2026-03-06T10:30:00Z',
      site_id: 1,
    },
    severityCounts: { critical: 10, warning: 10, notice: 5, clean: 75 },
    isLoading: false,
    error: null,
    mutate: vi.fn(),
  }),
  useAuditIssues: () => ({
    issues: [
      {
        id: 1,
        url: 'https://example.com/page1',
        issue_type: { id: 1, name: 'Missing title tag', category: 'Meta' },
        severity: 'critical',
        description: 'This page is missing a title tag.',
        recommendation: 'Add a unique, descriptive title tag.',
        status: 'open',
        created_at: '2026-03-06T10:15:00Z',
        audit_id: 123,
      },
    ],
    meta: { current_page: 1, per_page: 25, total: 1, last_page: 1 },
    isLoading: false,
    error: null,
    mutate: vi.fn(),
  }),
  useUpdateIssueStatus: () => ({ updateStatus: vi.fn() }),
  useBulkUpdateIssues: () => ({ bulkUpdate: vi.fn() }),
}));

describe('AuditDetailPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('ADP-RENDER: Page Rendering', () => {
    it('ADP-RN-001: Renders AuditSummary component', () => {
      render(<AuditDetailPage />);
      expect(screen.getByTestId('audit-summary')).toBeInTheDocument();
    });

    it('ADP-RN-002: Renders issue filters', () => {
      render(<AuditDetailPage />);
      expect(screen.getByText(/filters/i)).toBeInTheDocument();
    });

    it('ADP-RN-003: Renders issue list', () => {
      render(<AuditDetailPage />);
      expect(screen.getByText(/issues/i)).toBeInTheDocument();
    });

    it('ADP-RN-004: Displays audit ID in header', () => {
      render(<AuditDetailPage />);
      expect(screen.getByText(/Audit #123/i)).toBeInTheDocument();
    });

    it('ADP-RN-005: Displays severity counts', () => {
      render(<AuditDetailPage />);
      expect(screen.getByTestId('severity-card-critical')).toBeInTheDocument();
      expect(screen.getByTestId('severity-card-warning')).toBeInTheDocument();
      expect(screen.getByTestId('severity-card-notice')).toBeInTheDocument();
    });
  });

  describe('ADP-DATA: Data Loading', () => {
    it('ADP-DL-001: Displays pages crawled count', () => {
      render(<AuditDetailPage />);
      expect(screen.getByTestId('audit-pages')).toHaveTextContent('100 pages crawled');
    });

    it('ADP-DL-002: Displays total issues count', () => {
      render(<AuditDetailPage />);
      expect(screen.getByTestId('audit-issues-count')).toHaveTextContent('25 total issues');
    });

    it('ADP-DL-003: Displays audit status', () => {
      render(<AuditDetailPage />);
      expect(screen.getByTestId('audit-status-badge')).toHaveTextContent('Completed');
    });
  });

  describe('ADP-ROUTE: Route Structure', () => {
    it('ADP-RT-001: Has back to audits link', () => {
      render(<AuditDetailPage />);
      expect(screen.getByTestId('back-to-audits-link')).toBeInTheDocument();
    });

    it('ADP-RT-002: Back link navigates to audits list', () => {
      render(<AuditDetailPage />);
      const backLink = screen.getByTestId('back-to-audits-link');
      expect(backLink).toHaveAttribute('href', '/sites/1/audits');
    });
  });

  describe('AS-SEVERITY: Severity Cards', () => {
    it('AS-SV-001: Critical card shows correct count', () => {
      render(<AuditDetailPage />);
      const criticalCard = screen.getByTestId('severity-card-critical');
      expect(criticalCard).toHaveTextContent('10');
    });

    it('AS-SV-002: Warning card shows correct count', () => {
      render(<AuditDetailPage />);
      const warningCard = screen.getByTestId('severity-card-warning');
      expect(warningCard).toHaveTextContent('10');
    });

    it('AS-SV-003: Notice card shows correct count', () => {
      render(<AuditDetailPage />);
      const noticeCard = screen.getByTestId('severity-card-notice');
      expect(noticeCard).toHaveTextContent('5');
    });
  });

  describe('II-RENDER: Issue Item Rendering', () => {
    it('II-RN-001: Renders issue severity badge', () => {
      render(<AuditDetailPage />);
      expect(screen.getByText('CRITICAL')).toBeInTheDocument();
    });

    it('II-RN-002: renders issue title', () => {
      render(<AuditDetailPage />);
      expect(screen.getByText('Missing title tag')).toBeInTheDocument();
    });

    it('II-RN-003: Renders issue URL', () => {
      render(<AuditDetailPage />);
      expect(screen.getByText(/example.com\/page1/i)).toBeInTheDocument();
    });
  });
});
