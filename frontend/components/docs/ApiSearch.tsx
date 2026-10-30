'use client';

import * as React from 'react';
import { Search, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ParsedEndpoint } from '@/lib/api/openapi-parser';

export interface SearchResult {
  type: 'endpoint' | 'section' | 'error-code';
  title: string;
  description: string;
  path?: string;
  method?: string;
  sectionId?: string;
  endpoint?: ParsedEndpoint;
}

interface ApiSearchProps {
  onSearch?: (query: string) => SearchResult[];
  onResultClick?: (result: SearchResult) => void;
  placeholder?: string;
  shortcutKey?: string;
  className?: string;
  endpoints?: ParsedEndpoint[];
  sections?: { id: string; label: string }[];
}

const resultTypeIcons: Record<string, string> = {
  endpoint: '⚡',
  section: '📄',
  'error-code': '⚠',
};

export function ApiSearch({
  onSearch,
  onResultClick,
  placeholder = 'Search API documentation...',
  shortcutKey = 'Ctrl+K',
  className = '',
  endpoints = [],
  sections = [],
}: ApiSearchProps) {
  const [query, setQuery] = React.useState('');
  const [results, setResults] = React.useState<SearchResult[]>([]);
  const [isOpen, setIsOpen] = React.useState(false);
  const [selectedIndex, setSelectedIndex] = React.useState(0);
  const inputRef = React.useRef<HTMLInputElement>(null);
  const containerRef = React.useRef<HTMLDivElement>(null);

  // Default search implementation
  const defaultSearch = React.useCallback(
    (searchQuery: string): SearchResult[] => {
      if (!searchQuery.trim()) return [];

      const searchResults: SearchResult[] = [];
      const lowerQuery = searchQuery.toLowerCase();

      // Search endpoints
      for (const endpoint of endpoints) {
        if (
          endpoint.path.toLowerCase().includes(lowerQuery) ||
          endpoint.summary?.toLowerCase().includes(lowerQuery) ||
          endpoint.description?.toLowerCase().includes(lowerQuery) ||
          endpoint.tags?.some(tag => tag.toLowerCase().includes(lowerQuery))
        ) {
          searchResults.push({
            type: 'endpoint',
            title: endpoint.summary || endpoint.path,
            description: endpoint.description || `${endpoint.method} ${endpoint.path}`,
            path: endpoint.path,
            method: endpoint.method,
            endpoint,
          });
        }
      }

      // Search sections
      for (const section of sections) {
        if (section.label.toLowerCase().includes(lowerQuery)) {
          searchResults.push({
            type: 'section',
            title: section.label,
            description: `Navigate to ${section.label}`,
            sectionId: section.id,
          });
        }
      }

      // Search error codes (built-in)
      const errorCodes = [
        { code: '400', title: 'Bad Request', description: 'Invalid input parameters' },
        { code: '401', title: 'Unauthorized', description: 'Authentication required' },
        { code: '403', title: 'Forbidden', description: 'Insufficient permissions' },
        { code: '404', title: 'Not Found', description: 'Resource not found' },
        { code: '422', title: 'Validation Error', description: 'Invalid data provided' },
        { code: '429', title: 'Rate Limited', description: 'Too many requests' },
        { code: '500', title: 'Server Error', description: 'Internal server error' },
      ];

      for (const error of errorCodes) {
        if (
          error.code.includes(lowerQuery) ||
          error.title.toLowerCase().includes(lowerQuery)
        ) {
          searchResults.push({
            type: 'error-code',
            title: `${error.code} ${error.title}`,
            description: error.description,
            sectionId: 'errors',
          });
        }
      }

      return searchResults.slice(0, 10); // Limit results
    },
    [endpoints, sections]
  );

  const handleInput = (value: string) => {
    setQuery(value);
    if (value.length > 0) {
      const searchFn = onSearch || defaultSearch;
      const searchResults = searchFn(value);
      setResults(searchResults);
      setIsOpen(true);
      setSelectedIndex(0);
    } else {
      setResults([]);
      setIsOpen(false);
    }
  };

  const handleClear = () => {
    setQuery('');
    setResults([]);
    setIsOpen(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (!isOpen || results.length === 0) return;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setSelectedIndex(prev => Math.min(prev + 1, results.length - 1));
        break;
      case 'ArrowUp':
        e.preventDefault();
        setSelectedIndex(prev => Math.max(prev - 1, 0));
        break;
      case 'Enter':
        e.preventDefault();
        if (results[selectedIndex]) {
          handleResultClick(results[selectedIndex]);
        }
        break;
      case 'Escape':
        setIsOpen(false);
        break;
    }
  };

  const handleResultClick = (result: SearchResult) => {
    onResultClick?.(result);
    setIsOpen(false);
    setQuery('');
    setResults([]);
  };

  // Global shortcut listener
  React.useEffect(() => {
    const handleShortcut = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        inputRef.current?.focus();
      }
    };

    window.addEventListener('keydown', handleShortcut);
    return () => window.removeEventListener('keydown', handleShortcut);
  }, []);

  // Close on outside click
  React.useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  return (
    <div ref={containerRef} data-testid="api-search" className={cn('relative', className)}>
      {/* Input */}
      <div className="relative">
        <Search
          data-testid="search-icon"
          className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
        />
        <input
          ref={inputRef}
          type="search"
          data-testid="search-input"
          value={query}
          onChange={(e) => handleInput(e.target.value)}
          onKeyDown={handleKeyDown}
          onFocus={() => query && results.length > 0 && setIsOpen(true)}
          placeholder={placeholder}
          aria-label="Search API documentation"
          aria-expanded={isOpen}
          aria-autocomplete="list"
          role="combobox"
          className="w-full pl-10 pr-20 py-2 border dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        {query && (
          <button
            data-testid="clear-btn"
            onClick={handleClear}
            className="absolute right-16 top-1/2 -translate-y-1/2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
            aria-label="Clear search"
          >
            <X data-testid="x-icon" className="h-4 w-4 text-gray-400" />
          </button>
        )}
        <kbd
          data-testid="shortcut-badge"
          className="absolute right-3 top-1/2 -translate-y-1/2 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs text-gray-500 dark:text-gray-400 hidden sm:block"
        >
          {shortcutKey}
        </kbd>
      </div>

      {/* Results Dropdown */}
      {isOpen && results.length > 0 && (
        <ul
          data-testid="search-results"
          role="listbox"
          className="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-lg shadow-lg max-h-96 overflow-auto z-50"
        >
          {results.map((result, index) => (
            <li
              key={`${result.type}-${index}`}
              data-testid={`result-${index}`}
              data-type={result.type}
              data-selected={index === selectedIndex}
              onClick={() => handleResultClick(result)}
              role="option"
              aria-selected={index === selectedIndex}
              className={cn(
                'px-4 py-3 cursor-pointer border-b dark:border-gray-800 last:border-b-0',
                index === selectedIndex
                  ? 'bg-blue-50 dark:bg-blue-900/20'
                  : 'hover:bg-gray-50 dark:hover:bg-gray-800'
              )}
            >
              <div className="flex items-center gap-3">
                <span
                  data-testid="result-icon"
                  className="text-lg"
                >
                  {resultTypeIcons[result.type]}
                </span>
                <div className="flex-1 min-w-0">
                  <div
                    data-testid="result-title"
                    className="font-medium text-gray-900 dark:text-gray-100 text-sm truncate"
                  >
                    {result.method && (
                      <span
                        data-testid="result-method"
                        className="inline-block mr-2 text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded font-mono"
                      >
                        {result.method}
                      </span>
                    )}
                    {result.title}
                  </div>
                  <div
                    data-testid="result-description"
                    className="text-xs text-gray-500 dark:text-gray-400 truncate"
                  >
                    {result.path && (
                      <span data-testid="result-path" className="font-mono mr-1">
                        {result.path} -
                      </span>
                    )}
                    {result.description}
                  </div>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}

      {/* No Results */}
      {isOpen && query && results.length === 0 && (
        <div
          data-testid="no-results"
          className="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-lg shadow-lg p-4 text-center text-gray-500 dark:text-gray-400 text-sm z-50"
        >
          No results found for &quot;{query}&quot;
        </div>
      )}
    </div>
  );
}
