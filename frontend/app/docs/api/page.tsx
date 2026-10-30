'use client';

import * as React from 'react';
import { Menu, X, ExternalLink, Book, Key, Clock, AlertCircle, Webhook, Code } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  CodeExample,
  codeExamples,
  ApiEndpointCard,
  ApiExplorer,
  ApiSearch,
  type SearchResult,
} from '@/components/docs';
import {
  parseOpenApiSpec,
  extractEndpoints,
  extractSecuritySchemes,
  groupEndpointsByTag,
  type ParsedEndpoint,
  type OpenApiSpec,
} from '@/lib/api/openapi-parser';

interface Section {
  id: string;
  label: string;
  icon: React.ReactNode;
}

const sections: Section[] = [
  { id: 'getting-started', label: 'Getting Started', icon: <Book className="h-4 w-4" /> },
  { id: 'authentication', label: 'Authentication', icon: <Key className="h-4 w-4" /> },
  { id: 'rate-limiting', label: 'Rate Limiting', icon: <Clock className="h-4 w-4" /> },
  { id: 'errors', label: 'Error Codes', icon: <AlertCircle className="h-4 w-4" /> },
  { id: 'webhooks', label: 'Webhooks', icon: <Webhook className="h-4 w-4" /> },
  { id: 'endpoints', label: 'Endpoints', icon: <Code className="h-4 w-4" /> },
];

const errorCodes = [
  { code: '400', title: 'Bad Request', description: 'The request was invalid or malformed. Check your request parameters.' },
  { code: '401', title: 'Unauthorized', description: 'Authentication failed or was not provided. Include a valid API key.' },
  { code: '403', title: 'Forbidden', description: 'You do not have permission to access this resource.' },
  { code: '404', title: 'Not Found', description: 'The requested resource was not found.' },
  { code: '422', title: 'Validation Error', description: 'The request data failed validation. Check the response for details.' },
  { code: '429', title: 'Rate Limited', description: 'Too many requests. Wait before retrying.' },
  { code: '500', title: 'Server Error', description: 'An internal server error occurred. Try again later.' },
];

const webhookEvents = [
  { event: 'keyword.position_changed', description: 'Triggered when a keyword position changes' },
  { event: 'report.generated', description: 'Triggered when a report is generated' },
  { event: 'audit.completed', description: 'Triggered when an audit is completed' },
  { event: 'site.added', description: 'Triggered when a new site is added' },
  { event: 'keyword.added', description: 'Triggered when a new keyword is tracked' },
];

export default function ApiDocumentationPage() {
  const [activeSection, setActiveSection] = React.useState('getting-started');
  const [isSidebarOpen, setIsSidebarOpen] = React.useState(false);
  const [spec, setSpec] = React.useState<OpenApiSpec | null>(null);
  const [endpoints, setEndpoints] = React.useState<ParsedEndpoint[]>([]);
  const [groupedEndpoints, setGroupedEndpoints] = React.useState<Record<string, ParsedEndpoint[]>>({});
  const [selectedCategory, setSelectedCategory] = React.useState('all');
  const [explorerEndpoint, setExplorerEndpoint] = React.useState<ParsedEndpoint | null>(null);
  const [searchQuery, setSearchQuery] = React.useState('');

  // Load OpenAPI spec
  React.useEffect(() => {
    const loadSpec = async () => {
      // Try to load the OpenAPI spec - fallback to built-in data if not available
      const parsedSpec = await parseOpenApiSpec('/api/openapi.yaml');
      if (parsedSpec) {
        setSpec(parsedSpec);
        const eps = extractEndpoints(parsedSpec);
        setEndpoints(eps);
        setGroupedEndpoints(groupEndpointsByTag(eps));
      } else {
        // Use built-in mock data for development
        const mockSpec = getMockSpec();
        setSpec(mockSpec);
        const eps = extractEndpoints(mockSpec);
        setEndpoints(eps);
        setGroupedEndpoints(groupEndpointsByTag(eps));
      }
    };

    loadSpec();
  }, []);

  // Intersection observer for active section
  React.useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setActiveSection(entry.target.id);
          }
        });
      },
      { rootMargin: '-20% 0px -80% 0px' }
    );

    sections.forEach(({ id }) => {
      const element = document.getElementById(id);
      if (element) observer.observe(element);
    });

    return () => observer.disconnect();
  }, []);

  // Handle search result click
  const handleSearchResult = (result: SearchResult) => {
    if (result.sectionId) {
      setActiveSection(result.sectionId);
      document.getElementById(result.sectionId)?.scrollIntoView({ behavior: 'smooth' });
    }
    if (result.endpoint) {
      setActiveSection('endpoints');
      setExplorerEndpoint(result.endpoint);
      document.getElementById('endpoints')?.scrollIntoView({ behavior: 'smooth' });
    }
  };

  // Get unique categories from endpoints
  const categories = React.useMemo(() => {
    const cats = new Set<string>();
    endpoints.forEach(ep => {
      if (ep.tags) {
        ep.tags.forEach(tag => cats.add(tag));
      }
    });
    return ['all', ...Array.from(cats)];
  }, [endpoints]);

  // Filter endpoints by category and search
  const filteredEndpoints = React.useMemo(() => {
    let filtered = endpoints;

    if (selectedCategory !== 'all') {
      filtered = filtered.filter(ep => ep.tags?.includes(selectedCategory));
    }

    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(
        ep =>
          ep.path.toLowerCase().includes(query) ||
          ep.summary?.toLowerCase().includes(query)
      );
    }

    return filtered;
  }, [endpoints, selectedCategory, searchQuery]);

  const securitySchemes = spec ? extractSecuritySchemes(spec) : [];

  return (
    <div data-testid="api-docs-page" className="flex min-h-screen bg-gray-50 dark:bg-gray-950">
      {/* Mobile menu button */}
      <button
        data-testid="mobile-menu-btn"
        className="md:hidden fixed top-4 left-4 z-50 p-2 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-lg shadow-sm"
        onClick={() => setIsSidebarOpen(!isSidebarOpen)}
        aria-label="Toggle navigation menu"
      >
        {isSidebarOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
      </button>

      {/* Sidebar */}
      <aside
        data-testid="sidebar"
        className={cn(
          'fixed md:sticky top-0 left-0 h-screen w-64 bg-white dark:bg-gray-900 border-r dark:border-gray-800 z-40 transition-transform',
          isSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        )}
        role="navigation"
        aria-label="Documentation navigation"
      >
        <div className="p-4 border-b dark:border-gray-800">
          <a href="/" className="text-xl font-bold text-blue-600 dark:text-blue-400">
            SEOIndicator
          </a>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">API Documentation</p>
        </div>

        <nav className="p-2">
          <ul className="space-y-1">
            {sections.map((section) => (
              <li key={section.id}>
                <button
                  data-testid={`nav-${section.id}`}
                  data-active={activeSection === section.id}
                  onClick={() => {
                    setActiveSection(section.id);
                    document.getElementById(section.id)?.scrollIntoView({ behavior: 'smooth' });
                    setIsSidebarOpen(false);
                  }}
                  className={cn(
                    'w-full flex items-center gap-2 text-left px-3 py-2 rounded-lg text-sm transition-colors',
                    activeSection === section.id
                      ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400'
                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                  )}
                  aria-current={activeSection === section.id ? 'page' : undefined}
                >
                  {section.icon}
                  {section.label}
                </button>
              </li>
            ))}
          </ul>
        </nav>

        <div className="absolute bottom-4 left-4 right-4">
          <a
            href="https://github.com/openclaw/openclaw"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
          >
            <ExternalLink className="h-4 w-4" />
            View on GitHub
          </a>
        </div>
      </aside>

      {/* Main content */}
      <main role="main" className="flex-1 min-w-0">
        <div className="max-w-4xl mx-auto p-4 md:p-8">
          {/* Breadcrumbs */}
          <nav data-testid="breadcrumbs" aria-label="Breadcrumb" className="mb-6">
            <ol className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
              <li>
                <a href="/" className="hover:text-gray-700 dark:hover:text-gray-300">
                  Home
                </a>
              </li>
              <li>/</li>
              <li>
                <span aria-current="page" className="text-gray-900 dark:text-gray-100">
                  API Documentation
                </span>
              </li>
            </ol>
          </nav>

          {/* Search */}
          <div className="mb-8">
            <ApiSearch
              endpoints={endpoints}
              sections={sections}
              onResultClick={handleSearchResult}
              placeholder="Search endpoints, sections..."
              shortcutKey="Ctrl+K"
            />
          </div>

          {/* Getting Started Section */}
          <section
            id="getting-started"
            data-testid="section-getting-started"
            aria-labelledby="getting-started-heading"
            className="mb-16 scroll-mt-4"
          >
            <h1
              id="getting-started-heading"
              className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4"
            >
              API Documentation
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              Welcome to the SEOIndicator API documentation. Use our REST API to integrate SEO analysis
              and monitoring capabilities into your applications.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
              Quick Start
            </h2>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              Get started with the API in minutes. First, obtain your API key from the settings page,
              then make your first request:
            </p>

            <div className="space-y-4">
              <CodeExample
                language="curl"
                code={codeExamples.authExample.curl}
                title="List Keywords"
              />
            </div>

            <div className="mt-8 grid md:grid-cols-2 gap-4">
              <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                  Base URL
                </h3>
                <code className="text-sm text-blue-700 dark:text-blue-300">
                  https://api.seoindicator.com/api/v1
                </code>
              </div>
              <div className="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <h3 className="font-semibold text-green-900 dark:text-green-100 mb-2">
                  API Version
                </h3>
                <code className="text-sm text-green-700 dark:text-green-300">
                  v1.0.0
                </code>
              </div>
            </div>
          </section>

          {/* Authentication Section */}
          <section
            id="authentication"
            data-testid="section-authentication"
            aria-labelledby="auth-heading"
            className="mb-16 scroll-mt-4"
          >
            <h2
              id="auth-heading"
              className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4"
            >
              Authentication
            </h2>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              The SEOIndicator API supports two authentication methods to secure your API requests.
            </p>

            <div className="space-y-6">
              {/* Bearer Token */}
              <div className="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                  Bearer Token (OAuth 2.0)
                </h3>
                <p className="text-gray-600 dark:text-gray-400 mb-4">
                  Include your access token in the Authorization header:
                </p>
                <div className="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
                  <code data-testid="bearer-format" className="text-sm text-gray-700 dark:text-gray-300">
                    Authorization: Bearer {'{your_access_token}'}
                  </code>
                </div>
              </div>

              {/* API Key */}
              <div className="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                  API Key
                </h3>
                <p className="text-gray-600 dark:text-gray-400 mb-4">
                  Pass your API key in a custom header:
                </p>
                <div className="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
                  <code data-testid="apikey-format" className="text-sm text-gray-700 dark:text-gray-300">
                    X-API-Key: {'{your_api_key}'}
                  </code>
                </div>
              </div>

              {/* Available Scopes */}
              <div className="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                  Available Scopes
                </h3>
                <p className="text-gray-600 dark:text-gray-400 mb-4">
                  Your API key can be scoped to specific permissions:
                </p>
                <ul data-testid="scope-list" className="grid grid-cols-2 gap-2">
                  <li className="flex items-center gap-2 text-sm">
                    <code className="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">keywords:read</code>
                    <span className="text-gray-500">Read keywords</span>
                  </li>
                  <li className="flex items-center gap-2 text-sm">
                    <code className="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">keywords:write</code>
                    <span className="text-gray-500">Manage keywords</span>
                  </li>
                  <li className="flex items-center gap-2 text-sm">
                    <code className="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">sites:read</code>
                    <span className="text-gray-500">Read sites</span>
                  </li>
                  <li className="flex items-center gap-2 text-sm">
                    <code className="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">sites:write</code>
                    <span className="text-gray-500">Manage sites</span>
                  </li>
                </ul>
              </div>

              {/* Code Examples */}
              <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Code Examples
              </h3>
              <div className="space-y-4">
                <CodeExample
                  language="javascript"
                  code={codeExamples.authExample.javascript}
                  title="JavaScript"
                />
                <CodeExample
                  language="python"
                  code={codeExamples.authExample.python}
                  title="Python"
                />
              </div>
            </div>
          </section>

          {/* Rate Limiting Section */}
          <section
            id="rate-limiting"
            data-testid="section-rate-limiting"
            aria-labelledby="rate-heading"
            className="mb-16 scroll-mt-4"
          >
            <h2
              id="rate-heading"
              className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4"
            >
              Rate Limiting
            </h2>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              API requests are rate limited to ensure fair usage. Limits are applied per endpoint and
              vary by subscription tier.
            </p>

            <div className="overflow-x-auto mb-6">
              <table
                data-testid="rate-limit-table"
                className="min-w-full bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg overflow-hidden"
              >
                <thead className="bg-gray-50 dark:bg-gray-800">
                  <tr>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                      Endpoint
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                      Limit
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                      Window
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y dark:divide-gray-800">
                  <tr>
                    <td className="px-4 py-3 text-sm font-mono">/keywords</td>
                    <td className="px-4 py-3 text-sm">60</td>
                    <td className="px-4 py-3 text-sm text-gray-500">minute</td>
                  </tr>
                  <tr>
                    <td className="px-4 py-3 text-sm font-mono">/reports</td>
                    <td className="px-4 py-3 text-sm">10</td>
                    <td className="px-4 py-3 text-sm text-gray-500">minute</td>
                  </tr>
                  <tr>
                    <td className="px-4 py-3 text-sm font-mono">/audits</td>
                    <td className="px-4 py-3 text-sm">5</td>
                    <td className="px-4 py-3 text-sm text-gray-500">minute</td>
                  </tr>
                  <tr>
                    <td className="px-4 py-3 text-sm font-mono">/sites</td>
                    <td className="px-4 py-3 text-sm">30</td>
                    <td className="px-4 py-3 text-sm text-gray-500">minute</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                Response Headers
              </h3>
              <code data-testid="rate-headers" className="text-sm text-blue-700 dark:text-blue-300">
                X-RateLimit-Limit: 60<br />
                X-RateLimit-Remaining: 45<br />
                X-RateLimit-Reset: 1647643200
              </code>
            </div>
          </section>

          {/* Error Codes Section */}
          <section
            id="errors"
            data-testid="section-errors"
            aria-labelledby="errors-heading"
            className="mb-16 scroll-mt-4"
          >
            <h2
              id="errors-heading"
              className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4"
            >
              Error Codes
            </h2>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              The API uses standard HTTP status codes. Error responses include a JSON body with
              details about what went wrong.
            </p>

            <div className="overflow-x-auto">
              <table
                data-testid="error-codes-table"
                className="min-w-full bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg overflow-hidden"
              >
                <thead className="bg-gray-50 dark:bg-gray-800">
                  <tr>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                      Code
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                      Description
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y dark:divide-gray-800">
                  {errorCodes.map((error) => (
                    <tr key={error.code} data-testid={`error-${error.code}`}>
                      <td className="px-4 py-3">
                        <span className="inline-block px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-800">
                          {error.code}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                          {error.title}
                        </p>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          {error.description}
                        </p>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="mt-6">
              <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                Error Response Format
              </h3>
              <CodeExample
                language="json"
                code={`{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The request data failed validation",
    "details": [
      { "field": "keyword", "message": "Keyword is required" }
    ]
  }
}`}
              />
            </div>
          </section>

          {/* Webhooks Section */}
          <section
            id="webhooks"
            data-testid="section-webhooks"
            aria-labelledby="webhooks-heading"
            className="mb-16 scroll-mt-4"
          >
            <h2
              id="webhooks-heading"
              className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4"
            >
              Webhooks
            </h2>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              Receive real-time notifications when events occur in your SEOIndicator account.
              Configure webhook endpoints in your settings.
            </p>

            <div className="space-y-6">
              <div className="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                  Event Types
                </h3>
                <ul data-testid="webhook-events" className="space-y-2">
                  {webhookEvents.map((event) => (
                    <li key={event.event} className="flex items-start gap-3">
                      <code className="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded text-sm">
                        {event.event}
                      </code>
                      <span className="text-sm text-gray-500 dark:text-gray-400">
                        {event.description}
                      </span>
                    </li>
                  ))}
                </ul>
              </div>

              <div className="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                  Webhook Payload
                </h3>
                <CodeExample
                  language="json"
                  code={codeExamples.webhookPayload}
                />
              </div>

              <div className="p-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <h3 className="text-lg font-semibold text-yellow-900 dark:text-yellow-100 mb-3">
                  Signature Verification
                </h3>
                <p className="text-sm text-yellow-700 dark:text-yellow-300 mb-3">
                  Verify webhook signatures using the X-Webhook-Signature header:
                </p>
                <code className="text-xs text-yellow-700 dark:text-yellow-300">
                  HMAC-SHA256(payload, your_webhook_secret)
                </code>
              </div>
            </div>
          </section>

          {/* Endpoints Section */}
          <section
            id="endpoints"
            data-testid="section-endpoints"
            aria-labelledby="endpoints-heading"
            className="mb-16 scroll-mt-4"
          >
            <h2
              id="endpoints-heading"
              className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4"
            >
              API Endpoints
            </h2>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              Browse all available API endpoints. Click on an endpoint to see details and try it
              interactively.
            </p>

            {/* Category Filter */}
            <div data-testid="category-filters" className="flex flex-wrap gap-2 mb-6">
              {categories.map((category) => (
                <button
                  key={category}
                  data-testid={`filter-${category.toLowerCase()}`}
                  data-active={selectedCategory === category}
                  onClick={() => setSelectedCategory(category)}
                  className={cn(
                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                    selectedCategory === category
                      ? 'bg-blue-600 text-white'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                  )}
                >
                  {category === 'all' ? 'All' : category}
                </button>
              ))}
            </div>

            {/* Search within endpoints */}
            <div className="mb-6">
              <input
                type="search"
                placeholder="Filter endpoints..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full px-4 py-2 border dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800"
              />
            </div>

            {/* API Explorer Modal */}
            {explorerEndpoint && (
              <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div className="w-full max-w-2xl max-h-[90vh] overflow-auto">
                  <ApiExplorer
                    endpoint={explorerEndpoint}
                    onClose={() => setExplorerEndpoint(null)}
                  />
                </div>
              </div>
            )}

            {/* Endpoints List */}
            <div data-testid="endpoint-list" className="space-y-4">
              {filteredEndpoints.length === 0 ? (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  No endpoints found matching your criteria.
                </div>
              ) : (
                filteredEndpoints.map((endpoint, index) => (
                  <ApiEndpointCard
                    key={`${endpoint.method}-${endpoint.path}`}
                    endpoint={endpoint}
                    onTryIt={() => setExplorerEndpoint(endpoint)}
                  />
                ))
              )}
            </div>
          </section>
        </div>
      </main>
    </div>
  );
}

// Mock spec for development/fallback
function getMockSpec(): OpenApiSpec {
  return {
    openapi: '3.0.0',
    info: {
      title: 'SEOIndicator API',
      version: '1.0.0',
      description: 'SEO analysis and monitoring API',
    },
    servers: [
      { url: 'https://api.seoindicator.com/api/v1', description: 'Production' },
    ],
    security: { BearerAuth: [] },
    paths: {
      '/keywords': {
        get: {
          summary: 'List keywords',
          tags: ['Keywords'],
          parameters: [
            { name: 'site_id', in: 'query', required: true, schema: { type: 'integer' }, description: 'Site ID' },
            { name: 'page', in: 'query', required: false, schema: { type: 'integer' }, description: 'Page number' },
          ],
          responses: {
            '200': { description: 'Success' },
            '401': { description: 'Unauthorized' },
          },
        },
        post: {
          summary: 'Create keyword',
          tags: ['Keywords'],
          requestBody: {
            required: true,
            content: {
              'application/json': {
                schema: { $ref: '#/components/schemas/KeywordCreate' },
              },
            },
          },
          responses: {
            '201': { description: 'Created' },
            '422': { description: 'Validation error' },
          },
        },
      },
      '/keywords/{id}': {
        get: {
          summary: 'Get keyword details',
          tags: ['Keywords'],
          parameters: [
            { name: 'id', in: 'path', required: true, schema: { type: 'integer' } },
          ],
          responses: {
            '200': { description: 'Success' },
            '404': { description: 'Not found' },
          },
        },
        put: {
          summary: 'Update keyword',
          tags: ['Keywords'],
          parameters: [
            { name: 'id', in: 'path', required: true, schema: { type: 'integer' } },
          ],
          responses: {
            '200': { description: 'Updated' },
          },
        },
        delete: {
          summary: 'Delete keyword',
          tags: ['Keywords'],
          parameters: [
            { name: 'id', in: 'path', required: true, schema: { type: 'integer' } },
          ],
          responses: {
            '204': { description: 'Deleted' },
          },
        },
      },
      '/sites': {
        get: {
          summary: 'List sites',
          tags: ['Sites'],
          responses: {
            '200': { description: 'Success' },
          },
        },
        post: {
          summary: 'Create site',
          tags: ['Sites'],
          responses: {
            '201': { description: 'Created' },
          },
        },
      },
      '/sites/{id}': {
        get: {
          summary: 'Get site details',
          tags: ['Sites'],
          parameters: [
            { name: 'id', in: 'path', required: true, schema: { type: 'integer' } },
          ],
          responses: {
            '200': { description: 'Success' },
            '404': { description: 'Not found' },
          },
        },
      },
      '/reports': {
        get: {
          summary: 'List reports',
          tags: ['Reports'],
          responses: {
            '200': { description: 'Success' },
          },
        },
      },
      '/auth/login': {
        post: {
          summary: 'Login',
          tags: ['Authentication'],
          responses: {
            '200': { description: 'Success' },
            '401': { description: 'Invalid credentials' },
          },
        },
      },
      '/auth/register': {
        post: {
          summary: 'Register',
          tags: ['Authentication'],
          responses: {
            '201': { description: 'Created' },
            '422': { description: 'Validation error' },
          },
        },
      },
    },
    components: {
      securitySchemes: {
        BearerAuth: {
          type: 'http',
          scheme: 'bearer',
        },
        ApiKeyAuth: {
          type: 'apiKey',
          in: 'header',
          name: 'X-API-Key',
        },
      },
      schemas: {
        Keyword: {
          type: 'object',
          properties: {
            id: { type: 'integer', description: 'Keyword ID' },
            keyword: { type: 'string', description: 'Keyword text' },
            position: { type: 'integer', description: 'Current position' },
          },
        },
        KeywordCreate: {
          type: 'object',
          required: ['keyword'],
          properties: {
            keyword: { type: 'string' },
            site_id: { type: 'integer' },
          },
        },
      },
    },
  };
}
