/**
 * GuideSearch Component
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Search input for filtering guides by title.
 */

'use client';

import React, { useCallback, useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface GuideSearchProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  debounceMs?: number;
}

export function GuideSearch({ 
  value, 
  onChange, 
  placeholder = 'Search guides...',
  debounceMs = 300
}: GuideSearchProps) {
  const [localValue, setLocalValue] = useState(value);
  const [isClearing, setIsClearing] = useState(false);

  // Sync local value with external value
  useEffect(() => {
    if (!isClearing) {
      setLocalValue(value);
    }
  }, [value, isClearing]);

  // Debounced onChange
  useEffect(() => {
    if (isClearing) {
      setIsClearing(false);
      return;
    }
    
    const timer = setTimeout(() => {
      if (localValue !== value) {
        onChange(localValue);
      }
    }, debounceMs);

    return () => clearTimeout(timer);
  }, [localValue, debounceMs, onChange, value, isClearing]);

  const handleClear = useCallback(() => {
    setIsClearing(true);
    setLocalValue('');
    onChange('');
  }, [onChange]);

  const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Escape') {
      handleClear();
    }
  }, [handleClear]);

  return (
    <div 
      className="guide-search relative"
      data-testid="guide-search"
    >
      {/* Search Icon */}
      <svg
        className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
        />
      </svg>

      <Input
        type="search"
        value={localValue}
        onChange={(e) => setLocalValue(e.target.value)}
        onKeyDown={handleKeyDown}
        placeholder={placeholder}
        aria-label="Search guides"
        data-testid="search-input"
        className={cn(
          'pl-10 pr-10',
          localValue && 'pr-10'
        )}
      />

      {/* Clear Button */}
      {localValue && (
        <button
          type="button"
          onClick={handleClear}
          aria-label="Clear search"
          data-testid="clear-button"
          className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
        >
          <svg
            className="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      )}
    </div>
  );
}
