/**
 * ApiExplorer Component Tests
 * Task: TASK-6-18
 * 
 * Tests for the interactive API explorer that allows users
 * to make actual API requests from the documentation page.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock fetch for API calls
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Play: () => <span data-testid="play-icon">▶</span>,
  X: () => <span data-testid="x-icon">✕</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">⟳</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  AlertCircle: () => <span data-testid="alert-icon">⚠</span>,
  Clock: () => <span data-testid="clock-icon">⏱</span>,
  Copy: () => <span data-testid="copy-icon">Copy</span>,
  Code: () => <span data-testid="code-icon">{ }</span>,
}));

// Mock clipboard API
const mockClipboardWrite = vi.fn().mockResolvedValue(undefined);
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

interface ApiEndpoint {
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  path: string;
  parameters?: Array<{
    name: string;
    in: 'path' | 'query' | 'header';
    required: boolean;
    type: string;
    default?: string;
  }>;
}

interface ApiExplorerProps {
  endpoint: ApiEndpoint;
  baseUrl?: string;
  defaultToken?: string;
  onClose?: () => void;
}

// Mock component for TDD
const MockApiExplorer = ({
  endpoint,
  baseUrl = 'https://api.seoindicator.com/api/v1',
  defaultToken = '',
  onClose,
}: ApiExplorerProps) => {
  const [token, setToken] = React.useState(defaultToken);
  const [params, setParams] = React.useState<Record<string, string>>({});
  const [response, setResponse] = React.useState<{
    status: number;
    headers: Record<string, string>;
    body: unknown;
    duration: number;
  } | null>(null);
  const [error, setError] = React.useState<string | null>(null);
  const [loading, setLoading] = React.useState(false);
  const [activeTab, setActiveTab] = React.useState<'body' | 'headers'>('body');
  const [requestHistory, setRequestHistory] = React.useState<Array<{
    timestamp: Date;
    endpoint: ApiEndpoint;
    response: unknown;
  }>>([]);

  // Build URL with path and query params
  const buildUrl = React.useCallback(() => {
    let url = `${baseUrl}${endpoint.path}`;
    
    // Replace path parameters
    for (const [key, value] of Object.entries(params)) {
      url = url.replace(`{${key}}`, encodeURIComponent(value));
    }
    
    // Add query parameters
    const queryParams = endpoint.parameters?.filter(p => p.in === 'query') || [];
    const queryString = queryParams
      .filter(p => params[p.name])
      .map(p => `${p.name}=${encodeURIComponent(params[p.name])}`)
      .join('&');
    
    if (queryString) {
      url += `?${queryString}`;
    }
    
    return url;
  }, [baseUrl, endpoint, params]);

  const handleSend = async () => {
    setLoading(true);
    setError(null);
    setResponse(null);
    
    const startTime = Date.now();
    
    try {
      const res = await fetch(buildUrl(), {
        method: endpoint.method,
        headers: {
          'Content-Type': 'application/json',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
        },
      });
      
      const data = await res.json();
      const duration = Date.now() - startTime;
      
      setResponse({
        status: res.status,
        headers: Object.fromEntries(res.headers.entries()),
        body: data,
        duration,
      });
      
      setRequestHistory(prev => [...prev.slice(-4), {
        timestamp: new Date(),
        endpoint,
        response: data,
      }]);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Request failed');
    } finally {
      setLoading(false);
    }
  };

  const handleClear = () => {
    setParams({});
    setResponse(null);
    setError(null);
  };

  const pathParams = endpoint.parameters?.filter(p => p.in === 'path') || [];
  const queryParams = endpoint.parameters?.filter(p => p.in === 'query') || [];

  return (
    <div data-testid="api-explorer" className="rounded-lg border bg-white">
      {/* Header */}
      <div data-testid="explorer-header" className="flex items-center justify-between border-b p-4">
        <div className="flex items-center gap-2">
          <span data-testid="method-badge" data-method={endpoint.method}>
            {endpoint.method}
          </span>
          <code data-testid="endpoint-path">{endpoint.path}</code>
        </div>
        {onClose && (
          <button data-testid="close-btn" onClick={onClose} aria-label="Close explorer">
            <span data-testid="x-icon">✕</span>
          </button>
        )}
      </div>

      {/* Auth Input */}
      <div data-testid="auth-section" className="p-4 border-b">
        <label htmlFor="auth-token">Authorization Token</label>
        <input
          id="auth-token"
          type="password"
          data-testid="token-input"
          value={token}
          onChange={(e) => setToken(e.target.value)}
          placeholder="Enter your API token"
          className="w-full mt-1"
        />
      </div>

      {/* Path Parameters */}
      {pathParams.length > 0 && (
        <div data-testid="path-params-section" className="p-4 border-b">
          <h4 className="font-semibold mb-2">Path Parameters</h4>
          {pathParams.map(param => (
            <div key={param.name} data-testid={`param-${param.name}`}>
              <label htmlFor={`param-${param.name}`}>
                {param.name}
                {param.required && <span data-testid="required-marker">*</span>}
              </label>
              <input
                id={`param-${param.name}`}
                type="text"
                data-testid={`input-${param.name}`}
                value={params[param.name] || param.default || ''}
                onChange={(e) => setParams(prev => ({ ...prev, [param.name]: e.target.value }))}
                placeholder={param.type}
              />
            </div>
          ))}
        </div>
      )}

      {/* Query Parameters */}
      {queryParams.length > 0 && (
        <div data-testid="query-params-section" className="p-4 border-b">
          <h4 className="font-semibold mb-2">Query Parameters</h4>
          {queryParams.map(param => (
            <div key={param.name} data-testid={`param-${param.name}`}>
              <label htmlFor={`param-${param.name}`}>
                {param.name}
                {param.required && <span data-testid="required-marker">*</span>}
              </label>
              <input
                id={`param-${param.name}`}
                type="text"
                data-testid={`input-${param.name}`}
                value={params[param.name] || param.default || ''}
                onChange={(e) => setParams(prev => ({ ...prev, [param.name]: e.target.value }))}
                placeholder={param.type}
              />
            </div>
          ))}
        </div>
      )}

      {/* Actions */}
      <div data-testid="actions-section" className="p-4 border-b flex gap-2">
        <button
          data-testid="send-btn"
          onClick={handleSend}
          disabled={loading}
          className="bg-blue-500 text-white px-4 py-2 rounded"
        >
          {loading ? (
            <>
              <span data-testid="loader-icon">⟳</span>
              Sending...
            </>
          ) : (
            <>
              <span data-testid="play-icon">▶</span>
              Send Request
            </>
          )}
        </button>
        <button
          data-testid="clear-btn"
          onClick={handleClear}
          className="border px-4 py-2 rounded"
        >
          Clear
        </button>
      </div>

      {/* Loading State */}
      {loading && (
        <div data-testid="loading-state" className="p-4 text-center">
          <span data-testid="loader-icon" className="animate-spin">⟳</span>
          <span>Sending request...</span>
        </div>
      )}

      {/* Error State */}
      {error && (
        <div data-testid="error-state" className="p-4 bg-red-50 text-red-700">
          <span data-testid="alert-icon">⚠</span>
          <span data-testid="error-message">{error}</span>
        </div>
      )}

      {/* Response */}
      {response && (
        <div data-testid="response-section" className="p-4">
          {/* Status and Duration */}
          <div data-testid="response-meta" className="flex items-center gap-4 mb-4">
            <span
              data-testid="status-badge"
              data-status={response.status}
              className={`px-2 py-1 rounded ${
                response.status < 300 ? 'bg-green-100 text-green-800' :
                response.status < 400 ? 'bg-yellow-100 text-yellow-800' :
                'bg-red-100 text-red-800'
              }`}
            >
              {response.status} {response.status < 300 ? 'OK' : response.status < 400 ? 'Redirect' : 'Error'}
            </span>
            <span data-testid="duration-badge">
              <span data-testid="clock-icon">⏱</span>
              {response.duration}ms
            </span>
          </div>

          {/* Tabs */}
          <div data-testid="response-tabs" className="border-b mb-4">
            <button
              data-testid="tab-body"
              onClick={() => setActiveTab('body')}
              data-active={activeTab === 'body'}
            >
              Body
            </button>
            <button
              data-testid="tab-headers"
              onClick={() => setActiveTab('headers')}
              data-active={activeTab === 'headers'}
            >
              Headers
            </button>
          </div>

          {/* Body Tab */}
          {activeTab === 'body' && (
            <div data-testid="response-body">
              <button data-testid="copy-response-btn">
                <span data-testid="copy-icon">Copy</span>
              </button>
              <pre data-testid="response-json">
                {JSON.stringify(response.body, null, 2)}
              </pre>
            </div>
          )}

          {/* Headers Tab */}
          {activeTab === 'headers' && (
            <div data-testid="response-headers">
              <table>
                <tbody>
                  {Object.entries(response.headers).map(([key, value]) => (
                    <tr key={key} data-testid={`header-${key}`}>
                      <td>{key}</td>
                      <td>{value}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* History */}
      {requestHistory.length > 0 && (
        <div data-testid="history-section" className="p-4 border-t">
          <h4 className="font-semibold mb-2">Recent Requests</h4>
          <ul data-testid="history-list">
            {requestHistory.map((item, index) => (
              <li key={index} data-testid={`history-item-${index}`}>
                {item.endpoint.method} {item.endpoint.path}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
};

import React from 'react';

describe('ApiExplorer', () => {
  const defaultEndpoint: ApiEndpoint = {
    method: 'GET',
    path: '/keywords',
    parameters: [
      { name: 'site_id', in: 'query', required: true, type: 'integer' },
      { name: 'page', in: 'query', required: false, type: 'integer', default: '1' },
    ],
  };

  beforeEach(() => {
    mockFetch.mockReset();
    mockClipboardWrite.mockClear();
  });

  describe('Rendering', () => {
    it('renders explorer panel', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('api-explorer')).toBeInTheDocument();
    });

    it('displays endpoint method', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('method-badge')).toHaveTextContent('GET');
    });

    it('displays endpoint path', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('endpoint-path')).toHaveTextContent('/keywords');
    });

    it('shows close button when onClose provided', () => {
      const onClose = vi.fn();
      render(<MockApiExplorer endpoint={defaultEndpoint} onClose={onClose} />);
      expect(screen.getByTestId('close-btn')).toBeInTheDocument();
    });

    it('hides close button when onClose not provided', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.queryByTestId('close-btn')).not.toBeInTheDocument();
    });
  });

  describe('Authentication', () => {
    it('shows token input field', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('token-input')).toBeInTheDocument();
    });

    it('token input is password type', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('token-input')).toHaveAttribute('type', 'password');
    });

    it('token input has placeholder', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('token-input')).toHaveAttribute('placeholder');
    });

    it('uses default token when provided', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} defaultToken="my-token" />);
      expect(screen.getByTestId('token-input')).toHaveValue('my-token');
    });

    it('token input updates on change', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      const input = screen.getByTestId('token-input');
      await user.type(input, 'new-token');
      
      expect(input).toHaveValue('new-token');
    });
  });

  describe('Parameters', () => {
    it('shows query parameters section', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('query-params-section')).toBeInTheDocument();
    });

    it('displays all query parameters', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('param-site_id')).toBeInTheDocument();
      expect(screen.getByTestId('param-page')).toBeInTheDocument();
    });

    it('shows required marker for required params', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      const siteIdParam = screen.getByTestId('param-site_id');
      expect(within(siteIdParam).getByTestId('required-marker')).toBeInTheDocument();
    });

    it('uses default value when provided', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('input-page')).toHaveValue('1');
    });

    it('parameter input updates on change', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      const input = screen.getByTestId('input-site_id');
      await user.type(input, '123');
      
      expect(input).toHaveValue('123');
    });

    it('shows path parameters section for path params', () => {
      const endpointWithPath: ApiEndpoint = {
        method: 'GET',
        path: '/keywords/{id}',
        parameters: [
          { name: 'id', in: 'path', required: true, type: 'integer' },
        ],
      };
      
      render(<MockApiExplorer endpoint={endpointWithPath} />);
      expect(screen.getByTestId('path-params-section')).toBeInTheDocument();
    });

    it('hides parameter sections when no params', () => {
      render(<MockApiExplorer endpoint={{ method: 'GET', path: '/health' }} />);
      expect(screen.queryByTestId('query-params-section')).not.toBeInTheDocument();
      expect(screen.queryByTestId('path-params-section')).not.toBeInTheDocument();
    });
  });

  describe('Send Request', () => {
    it('shows send button', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('send-btn')).toBeInTheDocument();
    });

    it('sends request on click', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ data: [] }),
        headers: new Headers(),
      });
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      expect(mockFetch).toHaveBeenCalled();
    });

    it('includes auth header when token provided', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ data: [] }),
        headers: new Headers(),
      });
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} defaultToken="test-token" />);
      
      await user.click(screen.getByTestId('send-btn'));
      
      expect(mockFetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.objectContaining({
            Authorization: 'Bearer test-token',
          }),
        })
      );
    });

    it('shows loading state during request', async () => {
      mockFetch.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)));
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.click(screen.getByTestId('send-btn'));
      
      expect(screen.getByTestId('loading-state')).toBeInTheDocument();
    });

    it('disables send button during loading', async () => {
      mockFetch.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)));
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.click(screen.getByTestId('send-btn'));
      
      expect(screen.getByTestId('send-btn')).toBeDisabled();
    });

    it('shows error state on failure', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-state')).toBeInTheDocument();
      });
    });

    it('shows error message', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('error-message')).toHaveTextContent('Network error');
      });
    });
  });

  describe('Response Display', () => {
    beforeEach(() => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ data: [{ id: 1, keyword: 'test' }] }),
        headers: new Headers({ 'content-type': 'application/json' }),
      });
    });

    it('shows response section after successful request', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('response-section')).toBeInTheDocument();
      });
    });

    it('shows status badge', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('status-badge')).toHaveTextContent('200');
      });
    });

    it('shows duration badge', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('duration-badge')).toBeInTheDocument();
      });
    });

    it('shows response body tab', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('tab-body')).toBeInTheDocument();
      });
    });

    it('shows response JSON', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('response-json')).toBeInTheDocument();
      });
    });

    it('switches to headers tab', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('response-section')).toBeInTheDocument();
      });
      
      await user.click(screen.getByTestId('tab-headers'));
      
      expect(screen.getByTestId('response-headers')).toBeInTheDocument();
    });
  });

  describe('Status Colors', () => {
    it('200 status shows green badge', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: () => Promise.resolve({}),
        headers: new Headers(),
      });
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        const badge = screen.getByTestId('status-badge');
        expect(badge.className).toMatch(/green/);
      });
    });

    it('400 status shows red badge', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 400,
        json: () => Promise.resolve({ error: 'Bad request' }),
        headers: new Headers(),
      });
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        const badge = screen.getByTestId('status-badge');
        expect(badge.className).toMatch(/red/);
      });
    });
  });

  describe('Clear Button', () => {
    it('shows clear button', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      expect(screen.getByTestId('clear-btn')).toBeInTheDocument();
    });

    it('clears parameters on click', async () => {
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '123');
      await user.click(screen.getByTestId('clear-btn'));
      
      expect(screen.getByTestId('input-site_id')).toHaveValue('');
    });

    it('clears response on click', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: () => Promise.resolve({}),
        headers: new Headers(),
      });
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('response-section')).toBeInTheDocument();
      });
      
      await user.click(screen.getByTestId('clear-btn'));
      
      expect(screen.queryByTestId('response-section')).not.toBeInTheDocument();
    });
  });

  describe('Close Handler', () => {
    it('calls onClose when close button clicked', async () => {
      const onClose = vi.fn();
      const user = userEvent.setup();
      
      render(<MockApiExplorer endpoint={defaultEndpoint} onClose={onClose} />);
      
      await user.click(screen.getByTestId('close-btn'));
      
      expect(onClose).toHaveBeenCalled();
    });
  });

  describe('History', () => {
    it('shows history after successful request', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: () => Promise.resolve({}),
        headers: new Headers(),
      });
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('history-section')).toBeInTheDocument();
      });
    });

    it('history shows request info', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: () => Promise.resolve({}),
        headers: new Headers(),
      });
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('history-item-0')).toHaveTextContent('GET /keywords');
      });
    });
  });

  describe('Accessibility', () => {
    it('close button has aria-label', () => {
      const onClose = vi.fn();
      render(<MockApiExplorer endpoint={defaultEndpoint} onClose={onClose} />);
      expect(screen.getByTestId('close-btn')).toHaveAttribute('aria-label');
    });

    it('token input has associated label', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      const input = screen.getByTestId('token-input');
      expect(input).toHaveAttribute('id');
      expect(document.querySelector(`label[for="${input.id}"]`)).toBeInTheDocument();
    });

    it('parameter inputs have associated labels', () => {
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      const input = screen.getByTestId('input-site_id');
      expect(input).toHaveAttribute('id');
      expect(document.querySelector(`label[for="${input.id}"]`)).toBeInTheDocument();
    });

    it('status badge has data-status attribute', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: () => Promise.resolve({}),
        headers: new Headers(),
      });
      
      const user = userEvent.setup();
      render(<MockApiExplorer endpoint={defaultEndpoint} />);
      
      await user.type(screen.getByTestId('input-site_id'), '1');
      await user.click(screen.getByTestId('send-btn'));
      
      await waitFor(() => {
        expect(screen.getByTestId('status-badge')).toHaveAttribute('data-status', '200');
      });
    });
  });
});
