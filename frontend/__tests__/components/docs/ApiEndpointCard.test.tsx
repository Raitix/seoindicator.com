/**
 * ApiEndpointCard Component Tests
 * Task: TASK-6-18
 * 
 * Tests for the API endpoint card component displaying
 * method, path, summary, parameters, and responses.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  ChevronDown: () => <span data-testid="chevron-icon">▼</span>,
  ChevronRight: () => <span data-testid="chevron-icon">▶</span>,
  Copy: () => <span data-testid="copy-icon">Copy</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  Play: () => <span data-testid="play-icon">▶</span>,
  Lock: () => <span data-testid="lock-icon">🔒</span>,
  Globe: () => <span data-testid="globe-icon">🌐</span>,
}));

// Mock clipboard API
const mockClipboardWrite = vi.fn().mockResolvedValue(undefined);
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

// Types
interface ApiParameter {
  name: string;
  in: 'path' | 'query' | 'header' | 'body';
  required: boolean;
  type: string;
  description?: string;
}

interface ApiResponse {
  status: string;
  description: string;
  schema?: string;
}

interface ApiEndpointCardProps {
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  path: string;
  summary: string;
  description?: string;
  requiresAuth: boolean;
  parameters?: ApiParameter[];
  responses?: ApiResponse[];
  onTryIt?: () => void;
  expanded?: boolean;
}

// Mock component for TDD
const MockApiEndpointCard = ({
  method,
  path,
  summary,
  description,
  requiresAuth = true,
  parameters = [],
  responses = [],
  onTryIt,
  expanded = false,
}: ApiEndpointCardProps) => {
  const [isExpanded, setIsExpanded] = React.useState(expanded);
  const [copied, setCopied] = React.useState(false);

  const handleCopy = async () => {
    await navigator.clipboard.writeText(path);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const methodColors: Record<string, string> = {
    GET: 'bg-green-100 text-green-800',
    POST: 'bg-blue-100 text-blue-800',
    PUT: 'bg-yellow-100 text-yellow-800',
    PATCH: 'bg-orange-100 text-orange-800',
    DELETE: 'bg-red-100 text-red-800',
  };

  return (
    <div data-testid="endpoint-card" className="border rounded-lg p-4">
      {/* Header */}
      <div data-testid="endpoint-header" className="flex items-center gap-2">
        <span
          data-testid="method-badge"
          data-method={method}
          className={`px-2 py-1 rounded font-mono text-sm ${methodColors[method]}`}
        >
          {method}
        </span>
        <code
          data-testid="endpoint-path"
          className="font-mono text-sm flex-1 cursor-pointer"
          onClick={handleCopy}
          title="Click to copy"
        >
          {path}
        </code>
        {requiresAuth && (
          <span data-testid="auth-badge" data-requires-auth="true" className="text-gray-500">
            <span data-testid="lock-icon">🔒</span>
          </span>
        )}
        <button
          data-testid="expand-btn"
          onClick={() => setIsExpanded(!isExpanded)}
          aria-expanded={isExpanded}
          aria-label={isExpanded ? 'Collapse endpoint details' : 'Expand endpoint details'}
        >
          <span data-testid="chevron-icon">{isExpanded ? '▼' : '▶'}</span>
        </button>
      </div>

      {/* Summary */}
      <p data-testid="endpoint-summary" className="mt-2 text-gray-700">
        {summary}
      </p>

      {/* Description */}
      {description && (
        <p data-testid="endpoint-description" className="mt-1 text-gray-500 text-sm">
          {description}
        </p>
      )}

      {/* Expandable content */}
      {isExpanded && (
        <div data-testid="endpoint-details" className="mt-4 border-t pt-4">
          {/* Parameters */}
          {parameters.length > 0 && (
            <div data-testid="parameters-section">
              <h4 className="font-semibold mb-2">Parameters</h4>
              <table data-testid="parameters-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>In</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  {parameters.map((param, index) => (
                    <tr key={index} data-testid={`param-${index}`}>
                      <td data-testid="param-name">{param.name}</td>
                      <td data-testid="param-in">{param.in}</td>
                      <td data-testid="param-type">{param.type}</td>
                      <td data-testid="param-required">
                        {param.required ? 'Yes' : 'No'}
                      </td>
                      {param.description && (
                        <td data-testid="param-description">{param.description}</td>
                      )}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Responses */}
          {responses.length > 0 && (
            <div data-testid="responses-section" className="mt-4">
              <h4 className="font-semibold mb-2">Responses</h4>
              <table data-testid="responses-table">
                <thead>
                  <tr>
                    <th>Status</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  {responses.map((response, index) => (
                    <tr key={index} data-testid={`response-${index}`}>
                      <td data-testid="response-status">{response.status}</td>
                      <td data-testid="response-description">{response.description}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Try It button */}
          <div data-testid="actions-section" className="mt-4">
            <button
              data-testid="try-it-btn"
              onClick={onTryIt}
              className="bg-blue-500 text-white px-4 py-2 rounded"
            >
              <span data-testid="play-icon">▶</span> Try It
            </button>
          </div>
        </div>
      )}

      {/* Copy feedback */}
      {copied && (
        <span data-testid="copy-feedback" className="text-green-500 text-sm">
          Copied!
        </span>
      )}
    </div>
  );
};

import React from 'react';

describe('ApiEndpointCard', () => {
  const defaultProps: ApiEndpointCardProps = {
    method: 'GET',
    path: '/api/v1/keywords',
    summary: 'List all keywords',
    requiresAuth: true,
    parameters: [
      { name: 'site_id', in: 'query', required: true, type: 'integer', description: 'Site ID' },
      { name: 'page', in: 'query', required: false, type: 'integer', description: 'Page number' },
    ],
    responses: [
      { status: '200', description: 'Success' },
      { status: '401', description: 'Unauthorized' },
      { status: '404', description: 'Not Found' },
    ],
  };

  beforeEach(() => {
    mockClipboardWrite.mockClear();
  });

  describe('Rendering', () => {
    it('renders method badge', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      expect(screen.getByTestId('method-badge')).toHaveTextContent('GET');
    });

    it('renders endpoint path', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      expect(screen.getByTestId('endpoint-path')).toHaveTextContent('/api/v1/keywords');
    });

    it('renders summary', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      expect(screen.getByTestId('endpoint-summary')).toHaveTextContent('List all keywords');
    });

    it('renders description when provided', () => {
      render(<MockApiEndpointCard {...defaultProps} description="Detailed description" />);
      expect(screen.getByTestId('endpoint-description')).toHaveTextContent('Detailed description');
    });

    it('does not render description when not provided', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      expect(screen.queryByTestId('endpoint-description')).not.toBeInTheDocument();
    });
  });

  describe('Method Badges', () => {
    it('GET badge has green styling', () => {
      render(<MockApiEndpointCard {...defaultProps} method="GET" />);
      const badge = screen.getByTestId('method-badge');
      expect(badge.className).toMatch(/green/);
    });

    it('POST badge has blue styling', () => {
      render(<MockApiEndpointCard {...defaultProps} method="POST" />);
      const badge = screen.getByTestId('method-badge');
      expect(badge.className).toMatch(/blue/);
    });

    it('PUT badge has yellow styling', () => {
      render(<MockApiEndpointCard {...defaultProps} method="PUT" />);
      const badge = screen.getByTestId('method-badge');
      expect(badge.className).toMatch(/yellow/);
    });

    it('PATCH badge has orange styling', () => {
      render(<MockApiEndpointCard {...defaultProps} method="PATCH" />);
      const badge = screen.getByTestId('method-badge');
      expect(badge.className).toMatch(/orange/);
    });

    it('DELETE badge has red styling', () => {
      render(<MockApiEndpointCard {...defaultProps} method="DELETE" />);
      const badge = screen.getByTestId('method-badge');
      expect(badge.className).toMatch(/red/);
    });
  });

  describe('Authentication Badge', () => {
    it('shows auth badge when requiresAuth is true', () => {
      render(<MockApiEndpointCard {...defaultProps} requiresAuth={true} />);
      expect(screen.getByTestId('auth-badge')).toBeInTheDocument();
    });

    it('hides auth badge when requiresAuth is false', () => {
      render(<MockApiEndpointCard {...defaultProps} requiresAuth={false} />);
      expect(screen.queryByTestId('auth-badge')).not.toBeInTheDocument();
    });

    it('auth badge has lock icon', () => {
      render(<MockApiEndpointCard {...defaultProps} requiresAuth={true} />);
      expect(screen.getByTestId('lock-icon')).toBeInTheDocument();
    });
  });

  describe('Expand/Collapse', () => {
    it('details are hidden by default', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      expect(screen.queryByTestId('endpoint-details')).not.toBeInTheDocument();
    });

    it('clicking expand button shows details', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      expect(screen.getByTestId('endpoint-details')).toBeInTheDocument();
    });

    it('clicking expand button twice hides details', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      fireEvent.click(screen.getByTestId('expand-btn'));
      expect(screen.queryByTestId('endpoint-details')).not.toBeInTheDocument();
    });

    it('expand button has aria-expanded attribute', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      const btn = screen.getByTestId('expand-btn');
      expect(btn).toHaveAttribute('aria-expanded', 'false');
      
      fireEvent.click(btn);
      expect(btn).toHaveAttribute('aria-expanded', 'true');
    });

    it('can be expanded by default with prop', () => {
      render(<MockApiEndpointCard {...defaultProps} expanded={true} />);
      expect(screen.getByTestId('endpoint-details')).toBeInTheDocument();
    });
  });

  describe('Parameters Table', () => {
    it('shows parameters table when expanded with parameters', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      expect(screen.getByTestId('parameters-table')).toBeInTheDocument();
    });

    it('hides parameters section when no parameters', () => {
      render(<MockApiEndpointCard {...defaultProps} parameters={[]} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      expect(screen.queryByTestId('parameters-section')).not.toBeInTheDocument();
    });

    it('displays all parameters', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.getByTestId('param-0')).toBeInTheDocument();
      expect(screen.getByTestId('param-1')).toBeInTheDocument();
    });

    it('displays parameter name', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.getAllByTestId('param-name')[0]).toHaveTextContent('site_id');
    });

    it('displays parameter location', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.getAllByTestId('param-in')[0]).toHaveTextContent('query');
    });

    it('displays parameter type', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.getAllByTestId('param-type')[0]).toHaveTextContent('integer');
    });

    it('displays required status', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      const requiredCells = screen.getAllByTestId('param-required');
      expect(requiredCells[0]).toHaveTextContent('Yes');
      expect(requiredCells[1]).toHaveTextContent('No');
    });

    it('displays parameter description', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.getAllByTestId('param-description')[0]).toHaveTextContent('Site ID');
    });
  });

  describe('Responses Table', () => {
    it('shows responses table when expanded with responses', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      expect(screen.getByTestId('responses-table')).toBeInTheDocument();
    });

    it('hides responses section when no responses', () => {
      render(<MockApiEndpointCard {...defaultProps} responses={[]} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      expect(screen.queryByTestId('responses-section')).not.toBeInTheDocument();
    });

    it('displays all responses', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.getByTestId('response-0')).toBeInTheDocument();
      expect(screen.getByTestId('response-1')).toBeInTheDocument();
      expect(screen.getByTestId('response-2')).toBeInTheDocument();
    });

    it('displays response status code', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.getAllByTestId('response-status')[0]).toHaveTextContent('200');
    });

    it('displays response description', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.getAllByTestId('response-description')[0]).toHaveTextContent('Success');
    });
  });

  describe('Try It Button', () => {
    it('shows Try It button when expanded', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      expect(screen.getByTestId('try-it-btn')).toBeInTheDocument();
    });

    it('calls onTryIt callback when clicked', () => {
      const onTryIt = vi.fn();
      render(<MockApiEndpointCard {...defaultProps} onTryIt={onTryIt} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      fireEvent.click(screen.getByTestId('try-it-btn'));
      
      expect(onTryIt).toHaveBeenCalled();
    });

    it('has play icon', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      expect(screen.getByTestId('play-icon')).toBeInTheDocument();
    });
  });

  describe('Copy Path', () => {
    it('copies path to clipboard on click', async () => {
      const user = userEvent.setup();
      render(<MockApiEndpointCard {...defaultProps} />);
      
      await user.click(screen.getByTestId('endpoint-path'));
      
      expect(mockClipboardWrite).toHaveBeenCalledWith('/api/v1/keywords');
    });

    it('shows copy feedback after copying', async () => {
      const user = userEvent.setup();
      render(<MockApiEndpointCard {...defaultProps} />);
      
      await user.click(screen.getByTestId('endpoint-path'));
      
      expect(screen.getByTestId('copy-feedback')).toHaveTextContent('Copied!');
    });

    it('path has title attribute for tooltip', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      expect(screen.getByTestId('endpoint-path')).toHaveAttribute('title', 'Click to copy');
    });
  });

  describe('Accessibility', () => {
    it('expand button has aria-label', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      expect(screen.getByTestId('expand-btn')).toHaveAttribute('aria-label');
    });

    it('tables have proper headers', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      const paramTable = screen.getByTestId('parameters-table');
      const paramHeaders = within(paramTable).getAllByRole('columnheader');
      expect(paramHeaders).toHaveLength(5);
      
      const responseTable = screen.getByTestId('responses-table');
      const responseHeaders = within(responseTable).getAllByRole('columnheader');
      expect(responseHeaders).toHaveLength(2);
    });

    it('method badge has data-method attribute', () => {
      render(<MockApiEndpointCard {...defaultProps} />);
      expect(screen.getByTestId('method-badge')).toHaveAttribute('data-method', 'GET');
    });
  });

  describe('Edge Cases', () => {
    it('handles long paths', () => {
      const longPath = '/api/v1/sites/{site_id}/keywords/{keyword_id}/rankings/history/{history_id}';
      render(<MockApiEndpointCard {...defaultProps} path={longPath} />);
      expect(screen.getByTestId('endpoint-path')).toHaveTextContent(longPath);
    });

    it('handles special characters in summary', () => {
      render(<MockApiEndpointCard {...defaultProps} summary='Create & update "keywords"' />);
      expect(screen.getByTestId('endpoint-summary')).toBeInTheDocument();
    });

    it('handles many parameters', () => {
      const manyParams = Array.from({ length: 10 }, (_, i) => ({
        name: `param_${i}`,
        in: 'query' as const,
        required: i < 5,
        type: 'string',
        description: `Parameter ${i}`,
      }));
      
      render(<MockApiEndpointCard {...defaultProps} parameters={manyParams} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      const rows = screen.getAllByTestId(/param-\d+/);
      expect(rows).toHaveLength(10);
    });

    it('handles parameters without description', () => {
      const params = [{ name: 'id', in: 'query' as const, required: true, type: 'integer' }];
      render(<MockApiEndpointCard {...defaultProps} parameters={params} />);
      fireEvent.click(screen.getByTestId('expand-btn'));
      
      expect(screen.queryByTestId('param-description')).not.toBeInTheDocument();
    });
  });
});
