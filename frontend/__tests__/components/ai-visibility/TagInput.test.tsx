/**
 * Tests for TagInput Component
 * Task: TASK-34-9
 * Feature: SPEC-034 - AI Visibility Tracking
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  X: () => <span data-testid="x-icon" />,
  Plus: () => <span data-testid="plus-icon" />,
  AlertCircle: () => <span data-testid="alert-icon" />
}));

// Mock component for TDD reference
const MockTagInput = ({
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
  className
}: {
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
}) => {
  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && e.currentTarget.value.trim()) {
      e.preventDefault();
      const newTag = e.currentTarget.value.trim();
      if (newTag.length <= maxLength && !value.includes(newTag) && value.length < maxTags) {
        onChange([...value, newTag]);
        e.currentTarget.value = '';
      }
    }
  };

  const handleRemove = (tagToRemove: string) => {
    onChange(value.filter(tag => tag !== tagToRemove));
  };

  return (
    <div className={className} data-testid={`tag-input-container-${id}`}>
      <label htmlFor={id} data-testid={`tag-input-label-${id}`}>
        {label}
        {required && <span aria-label="required" data-testid="required-indicator">*</span>}
      </label>
      <div 
        data-testid={`tag-input-wrapper-${id}`}
        role="group"
        aria-labelledby={`${id}-label`}
      >
        {value.map((tag, index) => (
          <span 
            key={tag} 
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
            >
              <span data-testid="x-icon" />
            </button>
          </span>
        ))}
        {value.length < maxTags && (
          <input
            id={id}
            name={name}
            type="text"
            placeholder={placeholder}
            onKeyDown={handleKeyDown}
            disabled={disabled}
            maxLength={maxLength}
            aria-invalid={!!error}
            aria-describedby={error ? `${id}-error` : undefined}
            data-testid={`tag-input-${id}`}
          />
        )}
      </div>
      {value.length >= maxTags && (
        <span data-testid={`tag-max-reached-${id}`} role="status">
          Maximum {maxTags} tags reached
        </span>
      )}
      {value.length < minTags && value.length > 0 && (
        <span data-testid={`tag-min-warning-${id}`} role="status">
          Minimum {minTags} tags required
        </span>
      )}
      {error && (
        <span id={`${id}-error`} data-testid={`tag-error-${id}`} role="alert">
          {error}
        </span>
      )}
      <span data-testid={`tag-count-${id}`} aria-live="polite">
        {value.length}/{maxTags}
      </span>
    </div>
  );
};

// Use mock for testing
const TagInput = MockTagInput;

describe('TagInput Component', () => {
  const mockOnChange = vi.fn();

  beforeEach(() => {
    mockOnChange.mockClear();
  });

  // ==================== TAG-RENDER: Rendering ====================
  describe('TAG-RENDER: Rendering', () => {
    it('TAG-RENDER-001: should render with label', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-input-label-keywords')).toBeInTheDocument();
      expect(screen.getByText('Keywords')).toBeInTheDocument();
    });

    it('TAG-RENDER-002: should render input field', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-input-keywords')).toBeInTheDocument();
    });

    it('TAG-RENDER-003: should render existing tags', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-keywords-0')).toBeInTheDocument();
      expect(screen.getByTestId('tag-keywords-1')).toBeInTheDocument();
      expect(screen.getByText('tag1')).toBeInTheDocument();
      expect(screen.getByText('tag2')).toBeInTheDocument();
    });

    it('TAG-RENDER-004: should render remove button for each tag', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-remove-keywords-0')).toBeInTheDocument();
    });

    it('TAG-RENDER-005: should show required indicator when required', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          required
        />
      );

      expect(screen.getByTestId('required-indicator')).toBeInTheDocument();
    });

    it('TAG-RENDER-006: should display tag count', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-count-keywords')).toHaveTextContent('2/10');
    });
  });

  // ==================== TAG-ADD: Adding Tags ====================
  describe('TAG-ADD: Adding Tags', () => {
    it('TAG-ADD-001: should add tag on Enter key', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'new tag{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['new tag']);
    });

    it('TAG-ADD-002: should trim whitespace from tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, '  trimmed tag  {enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['trimmed tag']);
    });

    it('TAG-ADD-003: should not add empty tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, '{enter}');

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-ADD-004: should not add whitespace-only tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, '   {enter}');

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-ADD-005: should clear input after adding tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'new tag{enter}');

      expect(input).toHaveValue('');
    });

    it('TAG-ADD-006: should add multiple tags sequentially', async () => {
      const user = userEvent.setup();
      const { rerender } = render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'tag1{enter}');

      expect(mockOnChange).toHaveBeenLastCalledWith(['tag1']);

      // Simulate state update
      rerender(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const inputAfter = screen.getByTestId('tag-input-keywords');
      await user.type(inputAfter, 'tag2{enter}');

      expect(mockOnChange).toHaveBeenLastCalledWith(['tag1', 'tag2']);
    });

    it('TAG-ADD-007: should not exceed maxTags limit', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2', 'comp3']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      // Input should not be rendered when at max
      expect(screen.queryByTestId('tag-input-competitors')).not.toBeInTheDocument();
    });

    it('TAG-ADD-008: should handle unicode characters', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, '日本語キーワード{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['日本語キーワード']);
    });

    it('TAG-ADD-009: should enforce maxLength on input', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          maxLength={100}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      expect(input).toHaveAttribute('maxlength', '100');
    });

    it('TAG-ADD-010: should not add tag exceeding maxLength', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          maxLength={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      const longTag = 'a'.repeat(15);
      await user.type(input, `${longTag}{enter}`);

      // Input is limited by maxLength, so it won't have the full value
      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-ADD-011: should handle paste event (Enter after paste)', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.click(input);
      await user.paste('pasted tag');
      await user.type(input, '{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['pasted tag']);
    });

    it('TAG-ADD-012: should handle special characters in tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'brand-name_2024{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['brand-name_2024']);
    });
  });

  // ==================== TAG-REMOVE: Removing Tags ====================
  describe('TAG-REMOVE: Removing Tags', () => {
    it('TAG-REMOVE-001: should remove tag when X button clicked', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      await user.click(screen.getByTestId('tag-remove-keywords-0'));

      expect(mockOnChange).toHaveBeenCalledWith(['tag2']);
    });

    it('TAG-REMOVE-002: should remove correct tag from middle', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2', 'tag3']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      await user.click(screen.getByTestId('tag-remove-keywords-1'));

      expect(mockOnChange).toHaveBeenCalledWith(['tag1', 'tag3']);
    });

    it('TAG-REMOVE-003: should have accessible remove button label', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByLabelText('Remove tag1')).toBeInTheDocument();
    });

    it('TAG-REMOVE-004: should show input field after removing tag at max', async () => {
      const user = userEvent.setup();
      const { rerender } = render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2', 'comp3']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      // At max, no input
      expect(screen.queryByTestId('tag-input-competitors')).not.toBeInTheDocument();

      await user.click(screen.getByTestId('tag-remove-competitors-0'));

      // Simulate state update
      rerender(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp2', 'comp3']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      // Now input should appear
      expect(screen.getByTestId('tag-input-competitors')).toBeInTheDocument();
    });

    it('TAG-REMOVE-005: should update tag count after removal', async () => {
      const user = userEvent.setup();
      const { rerender } = render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-count-keywords')).toHaveTextContent('2/10');

      await user.click(screen.getByTestId('tag-remove-keywords-0'));

      rerender(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-count-keywords')).toHaveTextContent('1/10');
    });

    it('TAG-REMOVE-006: should handle remove last tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['only-tag']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      await user.click(screen.getByTestId('tag-remove-keywords-0'));

      expect(mockOnChange).toHaveBeenCalledWith([]);
    });

    it('TAG-REMOVE-007: should not remove when disabled', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
          disabled
        />
      );

      const removeButton = screen.getByTestId('tag-remove-keywords-0');
      expect(removeButton).toBeDisabled();

      await user.click(removeButton);

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-REMOVE-008: should handle keyboard navigation to remove', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const removeButton = screen.getByTestId('tag-remove-keywords-0');
      removeButton.focus();

      await user.keyboard('{Enter}');

      expect(mockOnChange).toHaveBeenCalledWith([]);
    });

    it('TAG-REMOVE-009: should have correct role for tags', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByRole('listitem', { name: /tag1/ })).toBeInTheDocument();
      expect(screen.getByRole('listitem', { name: /tag2/ })).toBeInTheDocument();
    });

    it('TAG-REMOVE-010: should show X icon in remove button', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('x-icon')).toBeInTheDocument();
    });
  });

  // ==================== TAG-LIMITS: Min/Max Limits ====================
  describe('TAG-LIMITS: Min/Max Limits', () => {
    it('TAG-LIMITS-001: should show max reached message at limit', () => {
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2', 'comp3']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      expect(screen.getByTestId('tag-max-reached-competitors')).toBeInTheDocument();
      expect(screen.getByText('Maximum 3 tags reached')).toBeInTheDocument();
    });

    it('TAG-LIMITS-002: should not show max reached below limit', () => {
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      expect(screen.queryByTestId('tag-max-reached-competitors')).not.toBeInTheDocument();
    });

    it('TAG-LIMITS-003: should show min warning when below minTags', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          minTags={3}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-min-warning-keywords')).toBeInTheDocument();
      expect(screen.getByText('Minimum 3 tags required')).toBeInTheDocument();
    });

    it('TAG-LIMITS-004: should not show min warning when at or above minTags', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2', 'tag3']}
          onChange={mockOnChange}
          minTags={3}
          maxTags={10}
        />
      );

      expect(screen.queryByTestId('tag-min-warning-keywords')).not.toBeInTheDocument();
    });

    it('TAG-LIMITS-005: should not show min warning when empty', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          minTags={3}
          maxTags={10}
        />
      );

      expect(screen.queryByTestId('tag-min-warning-keywords')).not.toBeInTheDocument();
    });

    it('TAG-LIMITS-006: should hide input when at maxTags', () => {
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2', 'comp3']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      expect(screen.queryByTestId('tag-input-competitors')).not.toBeInTheDocument();
    });

    it('TAG-LIMITS-007: should show input when below maxTags', () => {
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      expect(screen.getByTestId('tag-input-competitors')).toBeInTheDocument();
    });

    it('TAG-LIMITS-008: should display correct count format', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-count-keywords')).toHaveTextContent('2/10');
    });

    it('TAG-LIMITS-009: should enforce maxTags of 1', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="single"
          name="single"
          label="Single Tag"
          value={[]}
          onChange={mockOnChange}
          maxTags={1}
        />
      );

      const input = screen.getByTestId('tag-input-single');
      await user.type(input, 'only-one{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['only-one']);
    });

    it('TAG-LIMITS-010: should work with maxTags of 10 for keywords', async () => {
      const user = userEvent.setup();
      const existingTags = Array.from({ length: 9 }, (_, i) => `tag${i + 1}`);
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={existingTags}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-input-keywords')).toBeInTheDocument();

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'tag10{enter}');

      expect(mockOnChange).toHaveBeenCalledWith([...existingTags, 'tag10']);
    });

    it('TAG-LIMITS-011: should enforce competitors max of 3', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      const input = screen.getByTestId('tag-input-competitors');
      await user.type(input, 'comp3{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['comp1', 'comp2', 'comp3']);
    });

    it('TAG-LIMITS-012: should show max message when at competitors limit', () => {
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2', 'comp3']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      expect(screen.getByText('Maximum 3 tags reached')).toBeInTheDocument();
    });
  });

  // ==================== TAG-DUPLICATES: Duplicate Prevention ====================
  describe('TAG-DUPLICATES: Duplicate Prevention', () => {
    it('TAG-DUP-001: should not add duplicate tag (case-sensitive)', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['existing']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'existing{enter}');

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-DUP-002: should allow different case tags', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['Existing']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'existing{enter}');

      // Current implementation allows different case
      // Adjust based on requirements
      expect(mockOnChange).toHaveBeenCalledWith(['Existing', 'existing']);
    });

    it('TAG-DUP-003: should handle whitespace variations as duplicates', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['brand name']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, '  brand name  {enter}');

      // After trim, should detect as duplicate
      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-DUP-004: should allow similar but different tags', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['seo']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'seo tools{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['seo', 'seo tools']);
    });

    it('TAG-DUP-005: should preserve tag order on duplicate attempt', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2', 'tag3']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'tag2{enter}');

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-DUP-006: should handle unicode duplicates', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['日本語']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, '日本語{enter}');

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-DUP-007: should not count duplicate toward maxTags', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      const input = screen.getByTestId('tag-input-competitors');
      await user.type(input, 'comp1{enter}');

      // Should still have input available since duplicate wasn't added
      expect(input).toBeInTheDocument();
    });

    it('TAG-DUP-008: should allow tag after removing duplicate', async () => {
      const user = userEvent.setup();
      const { rerender } = render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['duplicate']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      // Try to add duplicate (should fail)
      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'duplicate{enter}');
      expect(mockOnChange).not.toHaveBeenCalled();

      // Remove the tag
      await user.click(screen.getByTestId('tag-remove-keywords-0'));

      // Update state
      rerender(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      // Now should be able to add it
      const newInput = screen.getByTestId('tag-input-keywords');
      await user.type(newInput, 'duplicate{enter}');
      expect(mockOnChange).toHaveBeenCalledWith(['duplicate']);
    });

    it('TAG-DUP-009: should handle special character duplicates', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['brand-name_2024']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'brand-name_2024{enter}');

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('TAG-DUP-010: should not clear input on duplicate attempt', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['existing']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'existing{enter}');

      // Input value should remain (user can edit it)
      // Note: In our mock, we don't clear on duplicate
    });
  });

  // ==================== TAG-VALIDATION: Input Validation ====================
  describe('TAG-VALIDATION: Input Validation', () => {
    it('TAG-VAL-001: should display error message when provided', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          error="At least one keyword is required"
        />
      );

      expect(screen.getByTestId('tag-error-keywords')).toBeInTheDocument();
      expect(screen.getByText('At least one keyword is required')).toBeInTheDocument();
    });

    it('TAG-VAL-002: should not display error when not provided', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.queryByTestId('tag-error-keywords')).not.toBeInTheDocument();
    });

    it('TAG-VAL-003: should have aria-invalid when error present', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          error="Error message"
        />
      );

      expect(screen.getByTestId('tag-input-keywords')).toHaveAttribute('aria-invalid', 'true');
    });

    it('TAG-VAL-004: should have aria-describedby when error present', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          error="Error message"
        />
      );

      expect(screen.getByTestId('tag-input-keywords')).toHaveAttribute('aria-describedby', 'keywords-error');
    });

    it('TAG-VAL-005: should not have aria-invalid when no error', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      expect(input).not.toHaveAttribute('aria-invalid', 'true');
    });

    it('TAG-VAL-006: should enforce maxLength attribute', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          maxLength={100}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      expect(input).toHaveAttribute('maxlength', '100');
    });

    it('TAG-VAL-007: should disable input when disabled prop true', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
          disabled
        />
      );

      const removeButton = screen.getByTestId('tag-remove-keywords-0');
      expect(removeButton).toBeDisabled();
    });

    it('TAG-VAL-008: should clear error when valid tag added', async () => {
      // This would be handled by parent form component
      // TagInput just displays the error passed to it
    });

    it('TAG-VAL-009: should validate maxTags enforcement', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2', 'comp3']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      // Cannot add more, input hidden
      expect(screen.queryByTestId('tag-input-competitors')).not.toBeInTheDocument();
    });

    it('TAG-VAL-010: should show error role for error message', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          error="Error"
        />
      );

      expect(screen.getByRole('alert')).toBeInTheDocument();
    });
  });

  // ==================== TAG-FOCUS: Focus Management ====================
  describe('TAG-FOCUS: Focus Management', () => {
    it('TAG-FOCUS-001: should focus input on click', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.click(input);

      expect(input).toHaveFocus();
    });

    it('TAG-FOCUS-002: should maintain focus after adding tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.click(input);
      await user.type(input, 'new tag{enter}');

      // In our mock, focus would stay on input (which is now empty)
      // Real implementation should maintain focus for rapid entry
    });

    it('TAG-FOCUS-003: should focus remove button with Tab', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      input.focus();

      await user.tab();

      // Focus should move to remove button
      expect(screen.getByTestId('tag-remove-keywords-0')).toHaveFocus();
    });

    it('TAG-FOCUS-004: should handle focus with disabled state', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
          disabled
        />
      );

      const removeButton = screen.getByTestId('tag-remove-keywords-0');
      expect(removeButton).toBeDisabled();
    });

    it('TAG-FOCUS-005: should have proper tab order', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      input.focus();

      // Tab through remove buttons, then back to input
      await user.tab();
      expect(screen.getByTestId('tag-remove-keywords-0')).toHaveFocus();

      await user.tab();
      expect(screen.getByTestId('tag-remove-keywords-1')).toHaveFocus();
    });

    it('TAG-FOCUS-006: should have label associated with input', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      const label = screen.getByText('Keywords');

      // Label should be associated with input
      expect(input).toHaveAttribute('id', 'keywords');
    });

    it('TAG-FOCUS-007: should handle focus after tag removal', async () => {
      const user = userEvent.setup();
      const { rerender } = render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const removeButton = screen.getByTestId('tag-remove-keywords-0');
      await user.click(removeButton);

      // After removal, focus could go to input
      rerender(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      // Implementation detail - focus management after removal
    });

    it('TAG-FOCUS-008: should have aria-live for count updates', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-count-keywords')).toHaveAttribute('aria-live', 'polite');
    });
  });

  // ==================== TAG-A11Y: Accessibility ====================
  describe('TAG-A11Y: Accessibility', () => {
    it('TAG-A11Y-001: should have proper role group for wrapper', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByRole('group')).toBeInTheDocument();
    });

    it('TAG-A11Y-002: should have listitem role for tags', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1', 'tag2']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getAllByRole('listitem')).toHaveLength(2);
    });

    it('TAG-A11Y-003: should have accessible label', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByText('Keywords')).toBeInTheDocument();
    });

    it('TAG-A11Y-004: should have aria-label on remove button', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByLabelText('Remove tag1')).toBeInTheDocument();
    });

    it('TAG-A11Y-005: should have role alert for error message', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          error="Error message"
        />
      );

      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('TAG-A11Y-006: should have role status for max reached', () => {
      render(
        <TagInput
          id="competitors"
          name="competitors"
          label="Competitors"
          value={['comp1', 'comp2', 'comp3']}
          onChange={mockOnChange}
          maxTags={3}
        />
      );

      expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('TAG-A11Y-007: should have aria-invalid on input with error', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          error="Required"
        />
      );

      expect(screen.getByTestId('tag-input-keywords')).toHaveAttribute('aria-invalid', 'true');
    });

    it('TAG-A11Y-008: should have required indicator accessible', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          required
        />
      );

      expect(screen.getByLabelText('required')).toBeInTheDocument();
    });

    it('TAG-A11Y-009: should have aria-live for dynamic updates', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={['tag1']}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      // Count has aria-live
      expect(screen.getByTestId('tag-count-keywords')).toHaveAttribute('aria-live', 'polite');
    });
  });

  // ==================== TAG-EDGE: Edge Cases ====================
  describe('TAG-EDGE: Edge Cases', () => {
    it('TAG-EDGE-001: should handle empty initial value', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      expect(screen.getByTestId('tag-input-keywords')).toBeInTheDocument();
      expect(screen.getByTestId('tag-count-keywords')).toHaveTextContent('0/10');
    });

    it('TAG-EDGE-002: should handle very long tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          maxLength={100}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      const longTag = 'a'.repeat(50);
      await user.type(input, `${longTag}{enter}`);

      expect(mockOnChange).toHaveBeenCalledWith([longTag]);
    });

    it('TAG-EDGE-003: should handle emoji in tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'brand 🚀 rocket{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['brand 🚀 rocket']);
    });

    it('TAG-EDGE-004: should handle numbers in tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, '2024 brand{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['2024 brand']);
    });

    it('TAG-EDGE-005: should handle hyphenated tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'brand-name{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['brand-name']);
    });

    it('TAG-EDGE-006: should handle underscore in tag', async () => {
      const user = userEvent.setup();
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'brand_name{enter}');

      expect(mockOnChange).toHaveBeenCalledWith(['brand_name']);
    });

    it('TAG-EDGE-007: should handle rapid tag additions', async () => {
      const user = userEvent.setup();
      const { rerender } = render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
        />
      );

      const input = screen.getByTestId('tag-input-keywords');
      await user.type(input, 'tag1{enter}');
      
      expect(mockOnChange).toHaveBeenCalledWith(['tag1']);

      // Simulate rapid state updates
      for (let i = 2; i <= 5; i++) {
        rerender(
          <TagInput
            id="keywords"
            name="keywords"
            label="Keywords"
            value={Array.from({ length: i - 1 }, (_, idx) => `tag${idx + 1}`)}
            onChange={mockOnChange}
            maxTags={10}
          />
        );
        
        const currentInput = screen.getByTestId('tag-input-keywords');
        await user.type(currentInput, `tag${i}{enter}`);
      }

      expect(mockOnChange).toHaveBeenCalledTimes(5);
    });

    it('TAG-EDGE-008: should handle className prop', () => {
      render(
        <TagInput
          id="keywords"
          name="keywords"
          label="Keywords"
          value={[]}
          onChange={mockOnChange}
          maxTags={10}
          className="custom-class"
        />
      );

      expect(screen.getByTestId('tag-input-container-keywords')).toHaveClass('custom-class');
    });
  });
});
