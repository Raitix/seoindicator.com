'use client';

import * as React from 'react';
import { Play, X, Loader2, Check, AlertCircle, Clock, Copy } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ParsedEndpoint } from '@/lib/api/openapi-parser';

interface ApiExplorerProps {
  endpoint: ParsedEndpoint;
  baseUrl?: string;
  defaultToken?: string;
  onClose?: () => void;
  className?: string;
}

interface RequestHistoryItem {
  timestamp: Date;
  endpoint: ParsedEndpoint;
  response: unknown;
  status: number;
  duration: number;
}

export function ApiExplorer({
  endpoint,
  baseUrl = 'https://api.seoindicator.com/api/v1',
  defaultToken = '',
  onClose,
  className = '',
}: ApiExplorerProps) {
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
  const [requestHistory, setRequestHistory] = React.useState<RequestHistoryItem[]>([]);

  // Initialize default values for parameters
  React.useEffect(() => {
    const defaults: Record<string, string> = {};
    for (const param of endpoint.parameters) {
      if (param.default) {
        defaults[param.name] = param.default;
      }
    }
    setParams(defaults);
  }, [endpoint.parameters]);

  // Build URL with path and query params
  const buildUrl = React.useCallback(() => {
    let url = `${baseUrl}${endpoint.path}`;

    // Replace path parameters
    for (const [key, value] of Object.entries(params)) {
      url = url.replace(`{${key}}`, encodeURIComponent(value));
    }

    // Add query parameters
    const queryParams = endpoint.parameters.filter(p => p.in === 'query');
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

      // Extract headers (limited in browser)
      const headers: Record<string, string> = {};
      res.headers.forEach((value, key) => {
        headers[key] = value;
      });

      setResponse({
        status: res.status,
        headers,
        body: data,
        duration,
      });

      // Add to history
      setRequestHistory(prev => [
        ...prev.slice(-4),
        {
          timestamp: new Date(),
          endpoint,
          response: data,
          status: res.status,
          duration,
        },
      ]);
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

  const handleCopyResponse = async () => {
    if (response?.body) {
      await navigator.clipboard.writeText(JSON.stringify(response.body, null, 2));
    }
  };

  const pathParams = endpoint.parameters.filter(p => p.in === 'path');
  const queryParams = endpoint.parameters.filter(p => p.in === 'query');

  return (
    <div
      data-testid="api-explorer"
      className={cn('rounded-lg border bg-white dark:bg-gray-900 overflow-hidden', className)}
    >
      {/* Header */}
      <div
        data-testid="explorer-header"
        className="flex items-center justify-between border-b dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800"
      >
        <div className="flex items-center gap-2">
          <span
            data-testid="method-badge"
            data-method={endpoint.method}
            className={cn(
              'px-2 py-0.5 rounded text-xs font-semibold',
              endpoint.method === 'GET' && 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
              endpoint.method === 'POST' && 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
              endpoint.method === 'PUT' && 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
              endpoint.method === 'PATCH' && 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
              endpoint.method === 'DELETE' && 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
            )}
          >
            {endpoint.method}
          </span>
          <code
            data-testid="endpoint-path"
            className="font-mono text-sm text-gray-700 dark:text-gray-300"
          >
            {endpoint.path}
          </code>
        </div>
        {onClose && (
          <button
            data-testid="close-btn"
            onClick={onClose}
            aria-label="Close explorer"
            className="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>

      <div className="p-4 space-y-4">
        {/* Auth Input */}
        <div data-testid="auth-section">
          <label
            htmlFor="auth-token"
            className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            Authorization Token
          </label>
          <input
            id="auth-token"
            type="password"
            data-testid="token-input"
            value={token}
            onChange={(e) => setToken(e.target.value)}
            placeholder="Enter your API token"
            className="w-full px-3 py-2 border dark:border-gray-700 rounded-md text-sm bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        {/* Path Parameters */}
        {pathParams.length > 0 && (
          <div data-testid="path-params-section">
            <h4 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Path Parameters
            </h4>
            <div className="space-y-2">
              {pathParams.map(param => (
                <div key={param.name} data-testid={`param-${param.name}`}>
                  <label
                    htmlFor={`param-${param.name}`}
                    className="block text-xs text-gray-600 dark:text-gray-400 mb-1"
                  >
                    {param.name}
                    {param.required && (
                      <span data-testid="required-marker" className="text-red-500 ml-1">*</span>
                    )}
                  </label>
                  <input
                    id={`param-${param.name}`}
                    type="text"
                    data-testid={`input-${param.name}`}
                    value={params[param.name] || ''}
                    onChange={(e) =>
                      setParams(prev => ({ ...prev, [param.name]: e.target.value }))
                    }
                    placeholder={param.type}
                    className="w-full px-3 py-2 border dark:border-gray-700 rounded-md text-sm bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Query Parameters */}
        {queryParams.length > 0 && (
          <div data-testid="query-params-section">
            <h4 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Query Parameters
            </h4>
            <div className="space-y-2">
              {queryParams.map(param => (
                <div key={param.name} data-testid={`param-${param.name}`}>
                  <label
                    htmlFor={`param-${param.name}`}
                    className="block text-xs text-gray-600 dark:text-gray-400 mb-1"
                  >
                    {param.name}
                    {param.required && (
                      <span data-testid="required-marker" className="text-red-500 ml-1">*</span>
                    )}
                  </label>
                  <input
                    id={`param-${param.name}`}
                    type="text"
                    data-testid={`input-${param.name}`}
                    value={params[param.name] || param.default || ''}
                    onChange={(e) =>
                      setParams(prev => ({ ...prev, [param.name]: e.target.value }))
                    }
                    placeholder={param.type}
                    className="w-full px-3 py-2 border dark:border-gray-700 rounded-md text-sm bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Actions */}
        <div data-testid="actions-section" className="flex gap-2">
          <button
            data-testid="send-btn"
            onClick={handleSend}
            disabled={loading}
            className={cn(
              'flex items-center gap-2 px-4 py-2 rounded transition-colors',
              loading
                ? 'bg-gray-400 cursor-not-allowed'
                : 'bg-blue-600 hover:bg-blue-700 text-white'
            )}
          >
            {loading ? (
              <>
                <Loader2 data-testid="loader-icon" className="h-4 w-4 animate-spin" />
                Sending...
              </>
            ) : (
              <>
                <Play data-testid="play-icon" className="h-4 w-4" />
                Send Request
              </>
            )}
          </button>
          <button
            data-testid="clear-btn"
            onClick={handleClear}
            className="border dark:border-gray-700 px-4 py-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-sm"
          >
            Clear
          </button>
        </div>

        {/* Loading State */}
        {loading && (
          <div
            data-testid="loading-state"
            className="text-center py-4 text-gray-500"
          >
            <Loader2 className="h-6 w-6 animate-spin mx-auto mb-2" />
            <span>Sending request...</span>
          </div>
        )}

        {/* Error State */}
        {error && (
          <div
            data-testid="error-state"
            className="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-4 rounded-md flex items-center gap-2"
          >
            <AlertCircle data-testid="alert-icon" className="h-5 w-5 flex-shrink-0" />
            <span data-testid="error-message">{error}</span>
          </div>
        )}

        {/* Response */}
        {response && (
          <div data-testid="response-section">
            {/* Status and Duration */}
            <div
              data-testid="response-meta"
              className="flex items-center gap-4 mb-4"
            >
              <span
                data-testid="status-badge"
                data-status={response.status}
                className={cn(
                  'px-2 py-1 rounded text-sm font-medium',
                  response.status < 300
                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                    : response.status < 400
                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                )}
              >
                {response.status}{' '}
                {response.status < 300
                  ? 'OK'
                  : response.status < 400
                  ? 'Redirect'
                  : 'Error'}
              </span>
              <span
                data-testid="duration-badge"
                className="text-sm text-gray-500 flex items-center gap-1"
              >
                <Clock data-testid="clock-icon" className="h-4 w-4" />
                {response.duration}ms
              </span>
            </div>

            {/* Tabs */}
            <div data-testid="response-tabs" className="border-b dark:border-gray-700 mb-4">
              <button
                data-testid="tab-body"
                onClick={() => setActiveTab('body')}
                data-active={activeTab === 'body'}
                className={cn(
                  'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                  activeTab === 'body'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                )}
              >
                Body
              </button>
              <button
                data-testid="tab-headers"
                onClick={() => setActiveTab('headers')}
                data-active={activeTab === 'headers'}
                className={cn(
                  'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                  activeTab === 'headers'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                )}
              >
                Headers
              </button>
            </div>

            {/* Body Tab */}
            {activeTab === 'body' && (
              <div data-testid="response-body">
                <button
                  data-testid="copy-response-btn"
                  onClick={handleCopyResponse}
                  className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2"
                >
                  <Copy className="h-4 w-4" />
                  Copy
                </button>
                <pre
                  data-testid="response-json"
                  className="bg-gray-100 dark:bg-gray-800 p-4 rounded-md overflow-auto text-xs max-h-96"
                >
                  {JSON.stringify(response.body, null, 2)}
                </pre>
              </div>
            )}

            {/* Headers Tab */}
            {activeTab === 'headers' && (
              <div data-testid="response-headers">
                <table className="min-w-full text-sm">
                  <tbody>
                    {Object.entries(response.headers).map(([key, value]) => (
                      <tr key={key} data-testid={`header-${key}`}>
                        <td className="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300">
                          {key}
                        </td>
                        <td className="py-1 text-gray-600 dark:text-gray-400">{value}</td>
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
          <div data-testid="history-section" className="border-t dark:border-gray-700 pt-4">
            <h4 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Recent Requests
            </h4>
            <ul data-testid="history-list" className="space-y-1">
              {requestHistory.map((item, index) => (
                <li
                  key={index}
                  data-testid={`history-item-${index}`}
                  className="text-xs text-gray-500 flex items-center gap-2"
                >
                  <span
                    className={cn(
                      'px-1 rounded text-xs',
                      item.status < 300
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800'
                    )}
                  >
                    {item.endpoint.method}
                  </span>
                  <span>{item.endpoint.path}</span>
                  <span className="text-gray-400">{item.duration}ms</span>
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
    </div>
  );
}
