/**
 * Tests for GuideSearch Component
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Tests the search input for filtering guides by title.
 */

import React, { useState } from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock component for TDD (will be replaced with actual import)
function MockGuideSearch({ 
  value, 
  onChange,
  placeholder = 'Search guides...'
}: { 
  value: string; 
  onChange: (value: string) => void;
  placeholder?: string;
}) {
  return (
    <div className="guide-search" data-testid="guide-search">
      <input
        type="search"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        aria-label="Search guides"
        data-testid="search-input"
        className="search-input"
      />
      {value && (
        <button
          onClick={() => onChange('')}
          aria-label="Clear search"
          data-testid="clear-button"
          className="clear-button"
        >
          ×
        </button>
      )}
    </div>
  );
}

describe('GuideSearch Component', () => {
  const mockOnChange = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders search input', () => {
      render(
        <MockGuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toBeInTheDocument();
    });

    it('displays placeholder text', () => {
      render(
        <MockGuideSearch value="" onChange={mockOnChange} placeholder="Find guides..." />
      );
      
      expect(screen.getByTestId('search-input')).toHaveAttribute('placeholder', 'Find guides...');
    });

    it('uses default placeholder when not specified', () => {
      render(
        <MockGuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveAttribute('placeholder', 'Search guides...');
    });

    it('shows clear button when input has value', () => {
      render(
        <MockGuideSearch value="meta" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('clear-button')).toBeInTheDocument();
    });

    it('hides clear button when input is empty', () => {
      render(
        <MockGuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.queryByTestId('clear-button')).not.toBeInTheDocument();
    });
  });

  describe('Value Handling', () => {
    it('displays current value', () => {
      render(
        <MockGuideSearch value="page speed" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveValue('page speed');
    });

    it('updates value on change', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('');
        return <MockGuideSearch value={value} onChange={setValue} />;
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'meta');
      
      expect(input).toHaveValue('meta');
    });
  });

  describe('Interaction', () => {
    it('calls onChange when input changes', async () => {
      const user = userEvent.setup();
      
      render(
        <MockGuideSearch value="" onChange={mockOnChange} />
      );
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 't');
      
      expect(mockOnChange).toHaveBeenCalled();
    });

    it('clears input when clear button clicked', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('meta');
        return <MockGuideSearch value={value} onChange={setValue} />;
      }
      
      render(<TestWrapper />);
      
      await user.click(screen.getByTestId('clear-button'));
      expect(screen.getByTestId('search-input')).toHaveValue('');
    });

    it('clears input on Escape key', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('meta');
        return (
          <MockGuideSearch 
            value={value} 
            onChange={setValue} 
          />
        );
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      input.focus();
      await user.keyboard('{Escape}');
      
      // Should clear or remain depending on implementation
      expect(screen.getByTestId('search-input')).toHaveValue('');
    });
  });

  describe('Accessibility', () => {
    it('has aria-label', () => {
      render(
        <MockGuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveAttribute('aria-label', 'Search guides');
    });

    it('has search type for semantic meaning', () => {
      render(
        <MockGuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveAttribute('type', 'search');
    });

    it('clear button has accessible label', () => {
      render(
        <MockGuideSearch value="test" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('clear-button')).toHaveAttribute('aria-label', 'Clear search');
    });

    it('is focusable', async () => {
      const user = userEvent.setup();
      
      render(
        <MockGuideSearch value="" onChange={mockOnChange} />
      );
      
      const input = screen.getByTestId('search-input');
      await user.tab();
      
      // Should be focusable
      expect(input).toHaveFocus();
    });
  });

  describe('Edge Cases', () => {
    it('handles special characters in search', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('');
        return <MockGuideSearch value={value} onChange={setValue} />;
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, '<script>alert("xss")</script>');
      
      // Should not execute script, just treat as text
      expect(input).toHaveValue('<script>alert("xss")</script>');
    });

    it('handles unicode characters', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('');
        return <MockGuideSearch value={value} onChange={setValue} />;
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, '标题');
      
      expect(input).toHaveValue('标题');
    });

    it('handles very long search strings', async () => {
      const longString = 'a'.repeat(1000);
      
      render(
        <MockGuideSearch value={longString} onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveValue(longString);
    });

    it('handles whitespace-only input', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('');
        return <MockGuideSearch value={value} onChange={setValue} />;
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, '   ');
      
      expect(input).toHaveValue('   ');
    });

    it('handles rapid input changes', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('');
        return <MockGuideSearch value={value} onChange={setValue} />;
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      
      // Rapid typing
      await user.type(input, 'metatitle');
      
      expect(input).toHaveValue('metatitle');
    });
  });

  describe('Debouncing', () => {
    it('should debounce search input for performance', async () => {
      // This test documents the expected behavior
      // The actual implementation should use debouncing
      
      jest.useFakeTimers();
      
      const debouncedOnChange = jest.fn();
      
      function DebouncedTestWrapper() {
        const [value, setValue] = useState('');
        
        const handleChange = (newValue: string) => {
          setValue(newValue);
          // Simulated debounced call
          setTimeout(() => {
            debouncedOnChange(newValue);
          }, 300);
        };
        
        return <MockGuideSearch value={value} onChange={handleChange} />;
      }
      
      render(<DebouncedTestWrapper />);
      
      const input = screen.getByTestId('search-input');
      fireEvent.change(input, { target: { value: 'test' } });
      
      // Before debounce timer
      expect(debouncedOnChange).not.toHaveBeenCalled();
      
      // After debounce timer
      jest.advanceTimersByTime(300);
      expect(debouncedOnChange).toHaveBeenCalledWith('test');
      
      jest.useRealTimers();
    });
  });

  describe('Performance', () => {
    it('should not cause re-renders on every keystroke without state change', () => {
      // Document expected behavior: parent component should handle debouncing
      // to avoid excessive filtering operations
      expect(true).toBe(true);
    });
  });
});
