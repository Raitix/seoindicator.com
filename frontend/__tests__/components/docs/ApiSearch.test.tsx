/**
 * ApiSearch Component Tests
 * Task: TASK-6-18
 * 
 * Tests for the API documentation search functionality.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Search: () => <span data-testid="search-icon">🔍</span>,
  X: () => <span data-testid="x-icon">✕</span>,
}));

interface SearchResult {
  type: 'endpoint' | 'section' | 'error-code';
  title: string;
  description: string;
  path?: string;
  method?: string;
}

interface ApiSearchProps {
  onSearch?: (query: string) => SearchResult[];
  onResultClick?: (result: SearchResult) => void;
  placeholder?: string;
  shortcutKey?: string;
}

// Mock component for TDD
const MockApiSearch = ({
  onSearch,
  onResultClick,
  placeholder = 'Search API documentation...',
  shortcutKey = 'Ctrl+K',
}: ApiSearchProps) => {
  const [query, setQuery] = React.useState('');
  const [results, setResults] = React.useState<SearchResult[]>([]);
  const [isOpen, setIsOpen] = React.useState(false);
  const [selectedIndex, setSelectedIndex] = React.useState(0);
  const inputRef = React.useRef<HTMLInputElement>(null);

  const handleInput = (value: string) => {
    setQuery(value);
    if (onSearch && value.length > 0) {
      const searchResults = onSearch(value);
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
          onResultClick?.(results[selectedIndex]);
          setIsOpen(false);
        }
        break;
      case 'Escape':
        setIsOpen(false);
        break;
    }
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

  const resultTypeIcons: Record<string, string> = {
    endpoint: '⚡',
    section: '📄',
    'error-code': '⚠',
  };

  return (
    <div data-testid="api-search" className="relative">
      {/* Input */}
      <div className="relative">
        <span data-testid="search-icon" className="absolute left-3 top-1/2 -translate-y-1/2">
          🔍
        </span>
        <input
          ref={inputRef}
          type="search"
          data-testid="search-input"
          value={query}
          onChange={(e) => handleInput(e.target.value)}
          onKeyDown={handleKeyDown}
          onFocus={() => query && results.length > 0 && setIsOpen(true)}
          onBlur={() => setTimeout(() => setIsOpen(false), 200)}
          placeholder={placeholder}
          aria-label="Search API documentation"
          aria-expanded={isOpen}
          aria-autocomplete="list"
          role="combobox"
          className="w-full pl-10 pr-10 py-2 border rounded-lg"
        />
        {query && (
          <button
            data-testid="clear-btn"
            onClick={handleClear}
            className="absolute right-3 top-1/2 -translate-y-1/2"
            aria-label="Clear search"
          >
            <span data-testid="x-icon">✕</span>
          </button>
        )}
        <kbd
          data-testid="shortcut-badge"
          className="absolute right-3 top-1/2 -translate-y-1/2 bg-gray-100 px-2 py-1 rounded text-xs"
        >
          {shortcutKey}
        </kbd>
      </div>

      {/* Results Dropdown */}
      {isOpen && results.length > 0 && (
        <ul
          data-testid="search-results"
          role="listbox"
          className="absolute top-full left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg max-h-96 overflow-auto z-50"
        >
          {results.map((result, index) => (
            <li
              key={`${result.type}-${index}`}
              data-testid={`result-${index}`}
              data-type={result.type}
              data-selected={index === selectedIndex}
              onClick={() => {
                onResultClick?.(result);
                setIsOpen(false);
              }}
              role="option"
              aria-selected={index === selectedIndex}
              className={`px-4 py-2 cursor-pointer ${
                index === selectedIndex ? 'bg-blue-50' : 'hover:bg-gray-50'
              }`}
            >
              <div className="flex items-center gap-2">
                <span data-testid="result-icon">{resultTypeIcons[result.type]}</span>
                <div>
                  <div data-testid="result-title" className="font-medium">
                    {result.method && (
                      <span data-testid="result-method" className="mr-2 text-xs bg-gray-100 px-1 rounded">
                        {result.method}
                      </span>
                    )}
                    {result.title}
                  </div>
                  <div data-testid="result-description" className="text-sm text-gray-500">
                    {result.path && <span data-testid="result-path">{result.path} - </span>}
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
          className="absolute top-full left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg p-4 text-center text-gray-500"
        >
          No results found for "{query}"
        </div>
      )}
    </div>
  );
};

import React from 'react';

describe('ApiSearch', () => {
  const mockResults: SearchResult[] = [
    { type: 'endpoint', title: 'List Keywords', description: 'Get all keywords for a site', path: '/keywords', method: 'GET' },
    { type: 'endpoint', title: 'Create Keyword', description: 'Add a new keyword', path: '/keywords', method: 'POST' },
    { type: 'section', title: 'Authentication', description: 'How to authenticate API requests' },
    { type: 'error-code', title: '401 Unauthorized', description: 'Authentication required' },
  ];

  const mockOnSearch = vi.fn((query: string) => {
    if (!query) return [];
    return mockResults.filter(r => 
      r.title.toLowerCase().includes(query.toLowerCase()) ||
      r.description.toLowerCase().includes(query.toLowerCase())
    );
  });

  const mockOnResultClick = vi.fn();

  beforeEach(() => {
    mockOnSearch.mockClear();
    mockOnResultClick.mockClear();
  });

  describe('Rendering', () => {
    it('renders search input', () => {
      render(<MockApiSearch />);
      expect(screen.getByTestId('search-input')).toBeInTheDocument();
    });

    it('renders search icon', () => {
      render(<MockApiSearch />);
      expect(screen.getByTestId('search-icon')).toBeInTheDocument();
    });

    it('renders shortcut badge', () => {
      render(<MockApiSearch />);
      expect(screen.getByTestId('shortcut-badge')).toBeInTheDocument();
    });

    it('uses custom placeholder', () => {
      render(<MockApiSearch placeholder="Find endpoints..." />);
      expect(screen.getByTestId('search-input')).toHaveAttribute('placeholder', 'Find endpoints...');
    });

    it('uses default placeholder', () => {
      render(<MockApiSearch />);
      expect(screen.getByTestId('search-input')).toHaveAttribute('placeholder', 'Search API documentation...');
    });

    it('shows shortcut key', () => {
      render(<MockApiSearch shortcutKey="Cmd+K" />);
      expect(screen.getByTestId('shortcut-badge')).toHaveTextContent('Cmd+K');
    });
  });

  describe('Search Input', () => {
    it('accepts text input', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'keywords');
      
      expect(input).toHaveValue('keywords');
    });

    it('calls onSearch on input', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'key');
      
      expect(mockOnSearch).toHaveBeenCalled();
    });

    it('shows clear button when query exists', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'test');
      
      expect(screen.getByTestId('clear-btn')).toBeInTheDocument();
    });

    it('hides clear button when query is empty', () => {
      render(<MockApiSearch />);
      expect(screen.queryByTestId('clear-btn')).not.toBeInTheDocument();
    });

    it('clears query on clear button click', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'test');
      await user.click(screen.getByTestId('clear-btn'));
      
      expect(input).toHaveValue('');
    });
  });

  describe('Search Results', () => {
    it('shows results dropdown when results exist', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('search-results')).toBeInTheDocument();
      });
    });

    it('displays result titles', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        const titles = screen.getAllByTestId('result-title');
        expect(titles.length).toBeGreaterThan(0);
      });
    });

    it('displays result descriptions', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        const descriptions = screen.getAllByTestId('result-description');
        expect(descriptions.length).toBeGreaterThan(0);
      });
    });

    it('displays endpoint paths', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        const paths = screen.getAllByTestId('result-path');
        expect(paths.length).toBeGreaterThan(0);
      });
    });

    it('displays method badges for endpoints', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        const methods = screen.getAllByTestId('result-method');
        expect(methods.length).toBeGreaterThan(0);
      });
    });

    it('shows result type icons', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        const icons = screen.getAllByTestId('result-icon');
        expect(icons.length).toBeGreaterThan(0);
      });
    });

    it('shows no results message when no matches', async () => {
      const user = userEvent.setup();
      const noResultsSearch = vi.fn(() => []);
      
      render(<MockApiSearch onSearch={noResultsSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'xyznotfound');
      
      await waitFor(() => {
        expect(screen.getByTestId('no-results')).toBeInTheDocument();
      });
    });

    it('no results message includes query', async () => {
      const user = userEvent.setup();
      const noResultsSearch = vi.fn(() => []);
      
      render(<MockApiSearch onSearch={noResultsSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'xyz');
      
      await waitFor(() => {
        expect(screen.getByTestId('no-results')).toHaveTextContent('xyz');
      });
    });
  });

  describe('Keyboard Navigation', () => {
    it('focuses input on Ctrl+K', async () => {
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      fireEvent.keyDown(window, { key: 'k', ctrlKey: true });
      
      await waitFor(() => {
        expect(screen.getByTestId('search-input')).toHaveFocus();
      });
    });

    it('focuses input on Cmd+K (metaKey)', async () => {
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      fireEvent.keyDown(window, { key: 'k', metaKey: true });
      
      await waitFor(() => {
        expect(screen.getByTestId('search-input')).toHaveFocus();
      });
    });

    it('selects next result on ArrowDown', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('search-results')).toBeInTheDocument();
      });
      
      fireEvent.keyDown(screen.getByTestId('search-input'), { key: 'ArrowDown' });
      
      const firstResult = screen.getByTestId('result-0');
      expect(firstResult).toHaveAttribute('data-selected', 'true');
    });

    it('selects previous result on ArrowUp', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('search-results')).toBeInTheDocument();
      });
      
      // Move down twice
      fireEvent.keyDown(screen.getByTestId('search-input'), { key: 'ArrowDown' });
      fireEvent.keyDown(screen.getByTestId('search-input'), { key: 'ArrowDown' });
      
      // Move up once
      fireEvent.keyDown(screen.getByTestId('search-input'), { key: 'ArrowUp' });
      
      const firstResult = screen.getByTestId('result-0');
      expect(firstResult).toHaveAttribute('data-selected', 'true');
    });

    it('triggers result click on Enter', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} onResultClick={mockOnResultClick} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('search-results')).toBeInTheDocument();
      });
      
      fireEvent.keyDown(screen.getByTestId('search-input'), { key: 'Enter' });
      
      expect(mockOnResultClick).toHaveBeenCalled();
    });

    it('closes results on Escape', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('search-results')).toBeInTheDocument();
      });
      
      fireEvent.keyDown(screen.getByTestId('search-input'), { key: 'Escape' });
      
      await waitFor(() => {
        expect(screen.queryByTestId('search-results')).not.toBeInTheDocument();
      });
    });
  });

  describe('Result Click', () => {
    it('calls onResultClick when result clicked', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} onResultClick={mockOnResultClick} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('result-0')).toBeInTheDocument();
      });
      
      await user.click(screen.getByTestId('result-0'));
      
      expect(mockOnResultClick).toHaveBeenCalled();
    });

    it('closes results after click', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} onResultClick={mockOnResultClick} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('result-0')).toBeInTheDocument();
      });
      
      await user.click(screen.getByTestId('result-0'));
      
      await waitFor(() => {
        expect(screen.queryByTestId('search-results')).not.toBeInTheDocument();
      });
    });
  });

  describe('Result Types', () => {
    it('displays endpoint type correctly', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        const endpointResult = screen.getAllByTestId(/result-\d+/).find(
          el => el.dataset.type === 'endpoint'
        );
        expect(endpointResult).toBeInTheDocument();
      });
    });

    it('displays section type correctly', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'auth');
      
      await waitFor(() => {
        const sectionResult = screen.getAllByTestId(/result-\d+/).find(
          el => el.dataset.type === 'section'
        );
        expect(sectionResult).toBeInTheDocument();
      });
    });

    it('displays error-code type correctly', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), '401');
      
      await waitFor(() => {
        const errorResult = screen.getAllByTestId(/result-\d+/).find(
          el => el.dataset.type === 'error-code'
        );
        expect(errorResult).toBeInTheDocument();
      });
    });
  });

  describe('Accessibility', () => {
    it('input has aria-label', () => {
      render(<MockApiSearch />);
      expect(screen.getByTestId('search-input')).toHaveAttribute('aria-label');
    });

    it('input has role="combobox"', () => {
      render(<MockApiSearch />);
      expect(screen.getByTestId('search-input')).toHaveAttribute('role', 'combobox');
    });

    it('input has aria-expanded attribute', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('search-input')).toHaveAttribute('aria-expanded', 'true');
      });
    });

    it('input has aria-autocomplete', () => {
      render(<MockApiSearch />);
      expect(screen.getByTestId('search-input')).toHaveAttribute('aria-autocomplete', 'list');
    });

    it('results have role="listbox"', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('search-results')).toHaveAttribute('role', 'listbox');
      });
    });

    it('results have role="option"', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        const result = screen.getByTestId('result-0');
        expect(result).toHaveAttribute('role', 'option');
      });
    });

    it('selected result has aria-selected', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      await user.type(screen.getByTestId('search-input'), 'keyword');
      
      await waitFor(() => {
        expect(screen.getByTestId('search-results')).toBeInTheDocument();
      });
      
      const selectedResult = screen.getByTestId('result-0');
      expect(selectedResult).toHaveAttribute('aria-selected', 'true');
    });

    it('clear button has aria-label', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch />);
      
      await user.type(screen.getByTestId('search-input'), 'test');
      
      expect(screen.getByTestId('clear-btn')).toHaveAttribute('aria-label');
    });
  });

  describe('Edge Cases', () => {
    it('handles empty search query', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'a');
      await user.clear(input);
      
      expect(screen.queryByTestId('search-results')).not.toBeInTheDocument();
    });

    it('handles unicode in search', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'キーワード');
      
      expect(input).toHaveValue('キーワード');
    });

    it('handles special characters in search', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'test<script>');
      
      expect(input).toHaveValue('test<script>');
    });

    it('handles very long queries', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch onSearch={mockOnSearch} />);
      
      const longQuery = 'a'.repeat(200);
      const input = screen.getByTestId('search-input');
      await user.type(input, longQuery);
      
      expect(input).toHaveValue(longQuery);
    });

    it('handles onSearch not provided', async () => {
      const user = userEvent.setup();
      render(<MockApiSearch />);
      
      await user.type(screen.getByTestId('search-input'), 'test');
      
      // Should not crash
      expect(screen.getByTestId('search-input')).toHaveValue('test');
    });
  });
});
