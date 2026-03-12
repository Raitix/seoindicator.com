'use client';

import * as React from 'react';
import { ChevronDown, ChevronRight, Copy, Check, Play, Lock } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ParsedEndpoint } from '@/lib/api/openapi-parser';

interface ApiEndpointCardProps {
  endpoint: ParsedEndpoint;
  onTryIt?: () => void;
  expanded?: boolean;
  className?: string;
}

const methodColors: Record<string, string> = {
  GET: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  POST: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  PUT: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  PATCH: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
  DELETE: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

export function ApiEndpointCard({
  endpoint,
  onTryIt,
  expanded = false,
  className = '',
}: ApiEndpointCardProps) {
  const [isExpanded, setIsExpanded] = React.useState(expanded);
  const [copied, setCopied] = React.useState(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(endpoint.path);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const hasParameters = endpoint.parameters.length > 0;
  const hasResponses = endpoint.responses.length > 0;

  return (
    <div
      data-testid="endpoint-card"
      className={cn(
        'border rounded-lg overflow-hidden bg-white dark:bg-gray-900',
        className
      )}
    >
      {/* Header */}
      <div
        data-testid="endpoint-header"
        className="flex items-center gap-2 p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
        onClick={() => setIsExpanded(!isExpanded)}
      >
        <span
          data-testid="method-badge"
          data-method={endpoint.method}
          className={cn(
            'px-2 py-1 rounded font-mono text-xs font-semibold',
            methodColors[endpoint.method]
          )}
        >
          {endpoint.method}
        </span>
        <code
          data-testid="endpoint-path"
          className="font-mono text-sm flex-1 text-gray-700 dark:text-gray-300 cursor-pointer hover:text-blue-600 dark:hover:text-blue-400"
          onClick={(e) => {
            e.stopPropagation();
            handleCopy();
          }}
          title="Click to copy"
        >
          {endpoint.path}
        </code>
        {endpoint.requiresAuth && (
          <span
            data-testid="auth-badge"
            data-requires-auth="true"
            className="text-gray-400"
            title="Requires authentication"
          >
            <Lock className="h-4 w-4" />
          </span>
        )}
        <button
          data-testid="expand-btn"
          onClick={(e) => {
            e.stopPropagation();
            setIsExpanded(!isExpanded);
          }}
          aria-expanded={isExpanded}
          aria-label={isExpanded ? 'Collapse endpoint details' : 'Expand endpoint details'}
          className="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded"
        >
          {isExpanded ? (
            <ChevronDown className="h-4 w-4" />
          ) : (
            <ChevronRight className="h-4 w-4" />
          )}
        </button>
      </div>

      {/* Summary */}
      <div className="px-4 pb-2" onClick={() => setIsExpanded(!isExpanded)}>
        <p data-testid="endpoint-summary" className="text-gray-700 dark:text-gray-300 text-sm">
          {endpoint.summary || 'No description available'}
        </p>
        {endpoint.description && (
          <p data-testid="endpoint-description" className="text-gray-500 text-xs mt-1">
            {endpoint.description}
          </p>
        )}
      </div>

      {/* Copy feedback */}
      {copied && (
        <span
          data-testid="copy-feedback"
          className="absolute right-16 text-green-500 text-sm"
        >
          <Check className="h-4 w-4 inline mr-1" />
          Copied!
        </span>
      )}

      {/* Expandable content */}
      {isExpanded && (
        <div data-testid="endpoint-details" className="border-t dark:border-gray-700 p-4 space-y-4">
          {/* Parameters */}
          {hasParameters && (
            <div data-testid="parameters-section">
              <h4 className="font-semibold text-sm mb-2 text-gray-700 dark:text-gray-300">
                Parameters
              </h4>
              <div className="overflow-x-auto">
                <table
                  data-testid="parameters-table"
                  className="min-w-full text-sm border-collapse"
                >
                  <thead>
                    <tr className="border-b dark:border-gray-700">
                      <th className="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Name</th>
                      <th className="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">In</th>
                      <th className="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Type</th>
                      <th className="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Required</th>
                      <th className="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Description</th>
                    </tr>
                  </thead>
                  <tbody>
                    {endpoint.parameters.map((param, index) => (
                      <tr
                        key={`${param.name}-${index}`}
                        data-testid={`param-${index}`}
                        className="border-b dark:border-gray-800"
                      >
                        <td data-testid="param-name" className="py-2 px-2 font-mono text-xs">
                          {param.name}
                          {param.default && (
                            <span className="text-gray-400 ml-1">={param.default}</span>
                          )}
                        </td>
                        <td data-testid="param-in" className="py-2 px-2 text-gray-600 dark:text-gray-400">
                          {param.in}
                        </td>
                        <td data-testid="param-type" className="py-2 px-2 text-gray-600 dark:text-gray-400">
                          {param.type}
                        </td>
                        <td data-testid="param-required" className="py-2 px-2">
                          {param.required ? (
                            <span className="text-red-600 dark:text-red-400 text-xs">Yes</span>
                          ) : (
                            <span className="text-gray-400 text-xs">No</span>
                          )}
                        </td>
                        <td data-testid="param-description" className="py-2 px-2 text-gray-500 text-xs">
                          {param.description || '-'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Request Body */}
          {endpoint.requestBody && (
            <div data-testid="request-body-section">
              <h4 className="font-semibold text-sm mb-2 text-gray-700 dark:text-gray-300">
                Request Body
                {endpoint.requestBody.required && (
                  <span className="text-red-500 ml-1">*</span>
                )}
              </h4>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Schema: <code className="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">{endpoint.requestBody.schema || 'object'}</code>
              </p>
            </div>
          )}

          {/* Responses */}
          {hasResponses && (
            <div data-testid="responses-section">
              <h4 className="font-semibold text-sm mb-2 text-gray-700 dark:text-gray-300">
                Responses
              </h4>
              <div className="overflow-x-auto">
                <table
                  data-testid="responses-table"
                  className="min-w-full text-sm border-collapse"
                >
                  <thead>
                    <tr className="border-b dark:border-gray-700">
                      <th className="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Status</th>
                      <th className="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Description</th>
                    </tr>
                  </thead>
                  <tbody>
                    {endpoint.responses.map((response, index) => (
                      <tr
                        key={`${response.status}-${index}`}
                        data-testid={`response-${index}`}
                        className="border-b dark:border-gray-800"
                      >
                        <td data-testid="response-status" className="py-2 px-2">
                          <span
                            className={cn(
                              'px-2 py-0.5 rounded text-xs font-medium',
                              response.status.startsWith('2')
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                : response.status.startsWith('3')
                                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                            )}
                          >
                            {response.status}
                          </span>
                        </td>
                        <td data-testid="response-description" className="py-2 px-2 text-gray-600 dark:text-gray-400">
                          {response.description}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Try It button */}
          <div data-testid="actions-section">
            <button
              data-testid="try-it-btn"
              onClick={onTryIt}
              className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors"
            >
              <Play className="h-4 w-4" />
              Try It
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
