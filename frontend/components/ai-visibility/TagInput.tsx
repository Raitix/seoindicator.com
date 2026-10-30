/**
 * TASK-34-9: TagInput Component
 * 
 * Reusable tag input component for keywords and competitors.
 * Supports adding/removing tags, validation, and accessibility.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-9.md
 */

'use client';

import * as React from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface TagInputProps {
  id: string;
  name: string;
  label: string;
  value?: string[];
  onChange: (tags: string[]) => void;
  minTags?: number;
  maxTags: number;
  maxLength?: number;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
  className?: string;
}

export function TagInput({
  id,
  name,
  label,
  value = [],
  onChange,
  minTags = 0,
  maxTags,
  maxLength = 100,
  placeholder = 'Add tag...',
  required = false,
  disabled = false,
  error,
  className,
}: TagInputProps) {
  const inputRef = React.useRef<HTMLInputElement>(null);

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && e.currentTarget.value.trim()) {
      e.preventDefault();
      const newTag = e.currentTarget.value.trim();

      // Check constraints
      if (newTag.length > maxLength) return;
      if (value.includes(newTag)) return; // Duplicate check
      if (value.length >= maxTags) return;

      onChange([...value, newTag]);
      e.currentTarget.value = '';
    }
  };

  const handleRemove = (tagToRemove: string) => {
    if (disabled) return;
    onChange(value.filter((tag) => tag !== tagToRemove));
  };

  const isAtMax = value.length >= maxTags;
  const isBelowMin = value.length > 0 && value.length < minTags;

  return (
    <div
      className={cn('space-y-2', className)}
      data-testid={`tag-input-container-${id}`}
    >
      {/* Label */}
      <label
        htmlFor={id}
        className="block text-sm font-medium"
        data-testid={`tag-input-label-${id}`}
      >
        {label}
        {required && (
          <span
            aria-label="required"
            className="text-red-500 ml-1"
            data-testid="required-indicator"
          >
            *
          </span>
        )}
      </label>

      {/* Tags and Input Wrapper */}
      <div
        className="flex flex-wrap gap-2 p-2 border rounded-lg min-h-[42px] bg-white dark:bg-slate-800"
        data-testid={`tag-input-wrapper-${id}`}
        role="group"
        aria-labelledby={`${id}-label`}
      >
        {/* Existing Tags */}
        {value.map((tag, index) => (
          <span
            key={tag}
            className="inline-flex items-center gap-1 px-2 py-1 bg-primary/10 text-primary rounded text-sm"
            data-testid={`tag-${id}-${index}`}
            role="listitem"
          >
            {tag}
            <button
              type="button"
              onClick={() => handleRemove(tag)}
              data-testid={`tag-remove-${id}-${index}`}
              aria-label={`Remove ${tag}`}
              disabled={disabled}
              className="hover:bg-primary/20 rounded p-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <X className="w-3 h-3" data-testid="x-icon" />
            </button>
          </span>
        ))}

        {/* Input Field */}
        {!isAtMax && (
          <input
            ref={inputRef}
            id={id}
            name={name}
            type="text"
            placeholder={placeholder}
            onKeyDown={handleKeyDown}
            disabled={disabled}
            maxLength={maxLength}
            aria-invalid={!!error}
            aria-describedby={error ? `${id}-error` : undefined}
            data-testid={`input-${id}`}
            className="flex-1 min-w-[120px] outline-none bg-transparent text-sm disabled:cursor-not-allowed disabled:opacity-50"
          />
        )}
      </div>

      {/* Max Reached Message */}
      {isAtMax && (
        <span
          className="text-sm text-gray-500"
          data-testid={`tag-max-reached-${id}`}
          role="status"
        >
          Maximum {maxTags} tags reached
        </span>
      )}

      {/* Min Tags Warning */}
      {isBelowMin && (
        <span
          className="text-sm text-yellow-600"
          data-testid={`tag-min-warning-${id}`}
          role="status"
        >
          Minimum {minTags} tags required
        </span>
      )}

      {/* Error Message */}
      {error && (
        <span
          id={`${id}-error`}
          className="text-sm text-red-500"
          data-testid={`tag-error-${id}`}
          role="alert"
        >
          {error}
        </span>
      )}

      {/* Tag Count */}
      <span
        className="text-sm text-gray-400"
        data-testid={`count-${id}`}
        aria-live="polite"
      >
        {value.length}/{maxTags}
      </span>
    </div>
  );
}

export default TagInput;
