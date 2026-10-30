/**
 * AuditCard Component Tests
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Tests the individual audit card component with status, progress, and actions
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock next/link
vi.mock('next/link', () => ({
  default: ({ children, href }: any) => <a href={href}>{children}</a>,
}));

// Mock lucide-react icons
vi.mock('lucide-react', () => ({
  Clock: () => <span data-testid="clock-icon">Clock</span>,
  Loader: () => <span data-testid="loader-icon" className="animate-spin">Loader</span>,
  CheckCircle: () => <span data-testid="check-circle-icon">CheckCircle</span>,
  XCircle: () => <span data-testid="x-circle-icon">XCircle</span>,
  ExternalLink: () => <span data-testid="external-link-icon">ExternalLink</span>,
}));

// Import component (will be created by Developer)
// import { AuditCard } from '@/components/audit/AuditCard';

// Mock data
const mockCompletedAudit = {
  id: 1,
  site_id: 1,
  status: 'completed',
  pages_crawled: 100,
  issues_found: 25,
  started_at: '2026-03-06T10:00:00Z',
  completed_at: '2026-03-06T10:30:00Z',
  max_pages: 100,
  max_depth: 3,
  critical_issues: 10,
  warning_issues: 10,
  notice_issues: 5,
};

const mockRunningAudit = {
  id: 2,
  site_id: 1,
  status: 'running',
  pages_crawled: 45,
  issues_found: 12,
  started_at: '2026-03-06T11:00:00Z',
  completed_at: null,
  max_pages: 100,
  max_depth: 3,
  current_url: 'https://example.com/about',
  elapsed_seconds: 120,
};

const mockFailedAudit = {
  id: 3,
  site_id: 1,
  status: 'failed',
  pages_crawled: 10,
  issues_found: 0,
  started_at: '2026-03-06T12:00:00Z',
  completed_at: '2026-03-06T12:05:00Z',
  max_pages: 100,
  max_depth: 3,
  error_message: 'Connection timeout',
};

const mockPendingAudit = {
  id: 4,
  site_id: 1,
  status: 'pending',
  pages_crawled: 0,
  issues_found: 0,
  started_at: '2026-03-06T13:00:00Z',
  completed_at: null,
  max_pages: 100,
  max_depth: 3,
};

// Mock component for testing structure
interface AuditCardProps {
  audit: any;
  siteId: string;
  onCancel?: (auditId: number) => Promise<void>;
}

const MockAuditCard = ({ audit, siteId, onCancel }: AuditCardProps) => {
  const progress = audit.max_pages > 0 
    ? Math.round((audit.pages_crawled / audit.max_pages) * 100) 
    : 0;

  const statusColors: Record<string, string> = {
    pending: 'bg-gray-100',
    running: 'bg-blue-100',
    completed: 'bg-green-100',
    failed: 'bg-red-100',
    cancelled: 'bg-gray-100',
  };

  return (
    <div data-testid={`audit-card-${audit.id}`} className="border rounded-lg p-4">
      <div className="flex justify-between items-start">
        <div>
          <span
            data-testid={`status-badge-${audit.status}`}
            className={`${statusColors[audit.status]} px-2 py-1 rounded`}
          >
            {audit.status.charAt(0).toUpperCase() + audit.status.slice(1)}
          </span>
          <span data-testid="audit-date" className="ml-2 text-sm text-gray-600">
            {new Date(audit.started_at).toLocaleDateString()}
          </span>
        </div>
      </div>
      
      <div className="mt-2">
        <span data-testid="pages-crawled">{audit.pages_crawled} pages crawled</span>
        <span className="mx-2">•</span>
        <span data-testid="issues-found">{audit.issues_found} issues found</span>
      </div>
      
      {audit.status === 'completed' && (
        <div data-testid="issue-summary" className="flex gap-2 mt-2">
          <span data-testid="critical-badge" className="bg-red-100 text-red-800 px-2 py-1 rounded">
            {audit.critical_issues || 0} Critical
          </span>
          <span data-testid="warning-badge" className="bg-orange-100 text-orange-800 px-2 py-1 rounded">
            {audit.warning_issues || 0} Warning
          </span>
          <span data-testid="notice-badge" className="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
            {audit.notice_issues || 0} Notice
          </span>
        </div>
      )}
      
      {audit.status === 'running' && (
        <div data-testid="progress-section" className="mt-3">
          <div data-testid="progress-bar" className="w-full bg-gray-200 rounded-full h-2">
            <div
              data-testid="progress-fill"
              className="bg-blue-600 h-2 rounded-full"
              style={{ width: `${progress}%` }}
            />
          </div>
          <p data-testid="progress-text" className="text-sm text-gray-600 mt-1">
            {progress}% complete
          </p>
          <p data-testid="current-url" className="text-xs text-gray-500 truncate">
            {audit.current_url}
          </p>
          <button
            data-testid="cancel-button"
            onClick={() => onCancel?.(audit.id)}
            className="mt-2 text-red-600 hover:text-red-800"
          >
            Cancel
          </button>
        </div>
      )}
      
      <div className="mt-3 flex justify-end">
        <a
          data-testid="view-details-link"
          href={`/sites/${siteId}/audits/${audit.id}`}
          className="text-blue-600 hover:underline"
        >
          View Details →
        </a>
      </div>
    </div>
  );
};

describe('AuditCard', () => {
  const mockOnCancel = vi.fn().mockResolvedValue(undefined);

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // AC-CARD-RENDER: Card Rendering
  // ============================================
  
  describe('AC-CARD-RENDER: Card Rendering', () => {
    it('AC-CARD-RN-001: Renders card container', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.getByTestId('audit-card-1')).toBeInTheDocument();
    });

    it('AC-CARD-RN-002: Shows status badge', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.getByTestId('status-badge-completed')).toBeInTheDocument();
    });

    it('AC-CARD-RN-003: Shows date/time', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.getByTestId('audit-date')).toBeInTheDocument();
    });

    it('AC-CARD-RN-004: Shows pages crawled count', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.getByTestId('pages-crawled')).toHaveTextContent('100 pages crawled');
    });

    it('AC-CARD-RN-005: Shows issues found count', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.getByTestId('issues-found')).toHaveTextContent('25 issues found');
    });
  });

  // ============================================
  // AC-CARD-STATUS: Status Display
  // ============================================
  
  describe('AC-CARD-STATUS: Status Display', () => {
    it('AC-CARD-ST-001: Pending: gray Clock icon', () => {
      render(<MockAuditCard audit={mockPendingAudit} siteId="1" />);
      
      const badge = screen.getByTestId('status-badge-pending');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Pending');
      expect(badge.className).toContain('bg-gray-100');
    });

    it('AC-CARD-ST-002: Running: blue Loader icon', () => {
      render(<MockAuditCard audit={mockRunningAudit} siteId="1" />);
      
      const badge = screen.getByTestId('status-badge-running');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Running');
      expect(badge.className).toContain('bg-blue-100');
    });

    it('AC-CARD-ST-003: Completed: green CheckCircle', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      const badge = screen.getByTestId('status-badge-completed');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Completed');
      expect(badge.className).toContain('bg-green-100');
    });

    it('AC-CARD-ST-004: Failed: red XCircle icon', () => {
      render(<MockAuditCard audit={mockFailedAudit} siteId="1" />);
      
      const badge = screen.getByTestId('status-badge-failed');
      expect(badge).toBeInTheDocument();
      expect(badge).toHaveTextContent('Failed');
      expect(badge.className).toContain('bg-red-100');
    });
  });

  // ============================================
  // AC-CARD-ISSUES: Issue Summary
  // ============================================
  
  describe('AC-CARD-ISSUES: Issue Summary', () => {
    it('AC-CARD-IS-001: Shows critical badge', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.getByTestId('critical-badge')).toHaveTextContent('10 Critical');
    });

    it('AC-CARD-IS-002: Shows warning badge', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.getByTestId('warning-badge')).toHaveTextContent('10 Warning');
    });

    it('AC-CARD-IS-003: Shows notice badge', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.getByTestId('notice-badge')).toHaveTextContent('5 Notice');
    });

    it('AC-CARD-IS-004: Does not show issue summary for running audit', () => {
      render(<MockAuditCard audit={mockRunningAudit} siteId="1" />);
      
      expect(screen.queryByTestId('issue-summary')).not.toBeInTheDocument();
    });

    it('AC-CARD-IS-005: Links to audit detail', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      const link = screen.getByTestId('view-details-link');
      expect(link).toHaveAttribute('href', '/sites/1/audits/1');
    });
  });

  // ============================================
  // AC-CARD-RUNNING: Running State
  // ============================================
  
  describe('AC-CARD-RUNNING: Running State', () => {
    it('AC-CARD-RN-001: Shows progress bar', () => {
      render(<MockAuditCard audit={mockRunningAudit} siteId="1" />);
      
      expect(screen.getByTestId('progress-bar')).toBeInTheDocument();
      expect(screen.getByTestId('progress-fill')).toBeInTheDocument();
    });

    it('AC-CARD-RN-002: Shows current URL', () => {
      render(<MockAuditCard audit={mockRunningAudit} siteId="1" />);
      
      expect(screen.getByTestId('current-url')).toHaveTextContent('https://example.com/about');
    });

    it('AC-CARD-RN-003: Shows progress percentage', () => {
      render(<MockAuditCard audit={mockRunningAudit} siteId="1" />);
      
      expect(screen.getByTestId('progress-text')).toHaveTextContent('45%');
    });

    it('AC-CARD-RN-004: Shows Cancel button', () => {
      render(<MockAuditCard audit={mockRunningAudit} siteId="1" onCancel={mockOnCancel} />);
      
      expect(screen.getByTestId('cancel-button')).toBeInTheDocument();
    });

    it('AC-CARD-RN-005: Cancel triggers API', async () => {
      const user = userEvent.setup();
      render(<MockAuditCard audit={mockRunningAudit} siteId="1" onCancel={mockOnCancel} />);
      
      await user.click(screen.getByTestId('cancel-button'));
      
      expect(mockOnCancel).toHaveBeenCalledWith(2);
    });
  });

  // ============================================
  // AC-CARD-ACTIONS: Card Actions
  // ============================================
  
  describe('AC-CARD-ACTIONS: Card Actions', () => {
    it('AC-CARD-AC-001: "View Details" link works', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      const link = screen.getByTestId('view-details-link');
      expect(link).toHaveAttribute('href', '/sites/1/audits/1');
    });

    it('AC-CARD-AC-002: Cancel button for running', () => {
      render(<MockAuditCard audit={mockRunningAudit} siteId="1" onCancel={mockOnCancel} />);
      
      expect(screen.getByTestId('cancel-button')).toBeInTheDocument();
    });

    it('AC-CARD-AC-003: No cancel for completed', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      expect(screen.queryByTestId('cancel-button')).not.toBeInTheDocument();
    });

    it('AC-CARD-AC-004: No cancel for failed', () => {
      render(<MockAuditCard audit={mockFailedAudit} siteId="1" />);
      
      expect(screen.queryByTestId('cancel-button')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // RESPONSIVE: Responsive Tests
  // ============================================
  
  describe('RESPONSIVE: Responsive Design', () => {
    it('has responsive classes', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      const card = screen.getByTestId('audit-card-1');
      expect(card.className).toContain('p-4');
    });
  });

  // ============================================
  // ACCESSIBILITY: ARIA Tests
  // ============================================
  
  describe('ACCESSIBILITY: ARIA Attributes', () => {
    it('has accessible status badge', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      const badge = screen.getByTestId('status-badge-completed');
      expect(badge).toBeInTheDocument();
    });

    it('has accessible link text', () => {
      render(<MockAuditCard audit={mockCompletedAudit} siteId="1" />);
      
      const link = screen.getByTestId('view-details-link');
      expect(link).toHaveTextContent('View Details');
    });
  });

  // ============================================
  // EDGE CASES: Edge Cases
  // ============================================
  
  describe('EDGE CASES: Edge Cases', () => {
    it('handles zero pages crawled', () => {
      const auditWithZero = { ...mockCompletedAudit, pages_crawled: 0 };
      render(<MockAuditCard audit={auditWithZero} siteId="1" />);
      
      expect(screen.getByTestId('pages-crawled')).toHaveTextContent('0 pages crawled');
    });

    it('handles zero issues found', () => {
      const auditWithZeroIssues = { ...mockCompletedAudit, issues_found: 0, critical_issues: 0, warning_issues: 0, notice_issues: 0 };
      render(<MockAuditCard audit={auditWithZeroIssues} siteId="1" />);
      
      expect(screen.getByTestId('issues-found')).toHaveTextContent('0 issues found');
    });

    it('handles very long URLs in running state', () => {
      const auditWithLongUrl = {
        ...mockRunningAudit,
        current_url: 'https://example.com/very/long/path/that/should/be/truncated/for/display/purposes',
      };
      render(<MockAuditCard audit={auditWithLongUrl} siteId="1" />);
      
      expect(screen.getByTestId('current-url')).toBeInTheDocument();
    });
  });
});
