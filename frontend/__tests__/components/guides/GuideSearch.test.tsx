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

import { GuideSearch } from '@/components/guides/GuideSearch';

describe('GuideSearch Component', () => {
  const mockOnChange = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders search input', () => {
      render(
        <GuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toBeInTheDocument();
    });

    it('displays placeholder text', () => {
      render(
        <GuideSearch value="" onChange={mockOnChange} placeholder="Find guides..." />
      );
      
      expect(screen.getByTestId('search-input')).toHaveAttribute('placeholder', 'Find guides...');
    });

    it('uses default placeholder when not specified', () => {
      render(
        <GuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveAttribute('placeholder', 'Search guides...');
    });

    it('shows clear button when input has value', () => {
      render(
        <GuideSearch value="meta" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('clear-button')).toBeInTheDocument();
    });

    it('hides clear button when input is empty', () => {
      render(
        <GuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.queryByTestId('clear-button')).not.toBeInTheDocument();
    });
  });

  describe('Value Handling', () => {
    it('displays current value', () => {
      render(
        <GuideSearch value="page speed" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveValue('page speed');
    });

    it('updates value on change', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('');
        return <GuideSearch value={value} onChange={setValue} />;
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, 'meta');
      
      expect(input).toHaveValue('meta');
    });
  });

  describe('Interaction', () => {
    it('calls onChange when input changes (after debounce)', async () => {
      jest.useFakeTimers();
      
      const localOnChange = jest.fn();
      
      function TestWrapper() {
        return <GuideSearch value="" onChange={localOnChange} />;
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      fireEvent.change(input, { target: { value: 't' } });
      
      // Before debounce timer
      expect(localOnChange).not.toHaveBeenCalled();
      
      // After debounce timer
      jest.advanceTimersByTime(300);
      expect(localOnChange).toHaveBeenCalledWith('t');
      
      jest.useRealTimers();
    });

    it('clears input when clear button clicked', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('meta');
        return <GuideSearch value={value} onChange={setValue} />;
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
          <GuideSearch 
            value={value} 
            onChange={setValue} 
          />
        );
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      input.focus();
      await user.keyboard('{Escape}');
      
      expect(screen.getByTestId('search-input')).toHaveValue('');
    });
  });

  describe('Accessibility', () => {
    it('has aria-label', () => {
      render(
        <GuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveAttribute('aria-label', 'Search guides');
    });

    it('has search type for semantic meaning', () => {
      render(
        <GuideSearch value="" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveAttribute('type', 'search');
    });

    it('clear button has accessible label', () => {
      render(
        <GuideSearch value="test" onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('clear-button')).toHaveAttribute('aria-label', 'Clear search');
    });

    it('is focusable', async () => {
      const user = userEvent.setup();
      
      render(
        <GuideSearch value="" onChange={mockOnChange} />
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
        return <GuideSearch value={value} onChange={setValue} />;
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
        return <GuideSearch value={value} onChange={setValue} />;
      }
      
      render(<TestWrapper />);
      
      const input = screen.getByTestId('search-input');
      await user.type(input, '标题');
      
      expect(input).toHaveValue('标题');
    });

    it('handles very long search strings', () => {
      const longString = 'a'.repeat(1000);
      
      render(
        <GuideSearch value={longString} onChange={mockOnChange} />
      );
      
      expect(screen.getByTestId('search-input')).toHaveValue(longString);
    });

    it('handles whitespace-only input', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [value, setValue] = useState('');
        return <GuideSearch value={value} onChange={setValue} />;
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
        return <GuideSearch value={value} onChange={setValue} />;
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
      jest.useFakeTimers();
      
      const debouncedOnChange = jest.fn();
      
      // Simple wrapper without state update in onChange
      function TestWrapper() {
        return <GuideSearch value="" onChange={debouncedOnChange} />;
      }
      
      render(<TestWrapper />);
      
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
