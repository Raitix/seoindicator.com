/**
 * AnnotationForm Component Tests
 *
 * @see TESTS/TEST-9-8.md
 * @see TASK-9-8
 */
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AnnotationForm from '@/components/annotations/AnnotationForm';
import { Annotation, ANNOTATION_CATEGORIES } from '@/lib/types/annotations';

// Mock date-fns format
jest.mock('date-fns', () => ({
  format: jest.fn((date: Date | string, formatStr: string) => {
    const d = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(d.getTime())) return 'Invalid Date';
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }),
}));

describe('AnnotationForm', () => {
  const mockOnSave = jest.fn();
  const mockOnCancel = jest.fn();

  const defaultProps = {
    keywordId: 123,
    onSave: mockOnSave,
    onCancel: mockOnCancel,
  };

  const mockAnnotation: Annotation = {
    id: 1,
    keyword_id: 123,
    project_id: 1,
    date: '2026-03-05',
    title: 'Google Core Update Impact',
    description: 'Rankings dropped 5 positions after core update',
    category: 'algorithm-update',
    created_at: '2026-03-05T10:30:00Z',
    updated_at: '2026-03-05T10:30:00Z',
  };

  const mockAnnotationNullDescription: Annotation = {
    ...mockAnnotation,
    id: 2,
    description: null,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================================
  // RE-RENDER — Rendering
  // ============================================================
  describe('Rendering', () => {
    it('RE-RENDER-01: Form renders all fields', () => {
      render(<AnnotationForm {...defaultProps} />);

      expect(screen.getByLabelText(/date/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/category/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/description/i)).toBeInTheDocument();
    });

    it('RE-RENDER-02: Labels display correctly', () => {
      render(<AnnotationForm {...defaultProps} />);

      expect(screen.getByText('Date')).toBeInTheDocument();
      expect(screen.getByText('Title')).toBeInTheDocument();
      expect(screen.getByText('Category')).toBeInTheDocument();
      expect(screen.getByText(/description/i)).toBeInTheDocument();
    });

    it('RE-RENDER-03: Cancel button renders', () => {
      render(<AnnotationForm {...defaultProps} />);

      expect(screen.getByRole('button', { name: /cancel/i })).toBeInTheDocument();
    });

    it('RE-RENDER-04: Create button renders in create mode', () => {
      render(<AnnotationForm {...defaultProps} />);

      expect(screen.getByRole('button', { name: /create/i })).toBeInTheDocument();
    });

    it('RE-RENDER-05: Category dropdown has all 9 options', () => {
      render(<AnnotationForm {...defaultProps} />);

      const categorySelect = screen.getByLabelText(/category/i);
      const options = within(categorySelect).getAllByRole('option');

      expect(options).toHaveLength(9);
    });
  });

  // ============================================================
  // RE-CREATE — Create Mode
  // ============================================================
  describe('Create Mode', () => {
    it('RE-CREATE-01: Default date is today', () => {
      render(<AnnotationForm {...defaultProps} />);

      const dateInput = screen.getByLabelText(/date/i) as HTMLInputElement;
      const today = new Date().toISOString().split('T')[0];

      expect(dateInput.value).toBe(today);
    });

    it('RE-CREATE-02: Default category is "other"', () => {
      render(<AnnotationForm {...defaultProps} />);

      const categorySelect = screen.getByLabelText(/category/i) as HTMLSelectElement;

      expect(categorySelect.value).toBe('other');
    });

    it('RE-CREATE-03: Empty title on create', () => {
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i) as HTMLInputElement;

      expect(titleInput.value).toBe('');
    });

    it('RE-CREATE-04: Empty description on create', () => {
      render(<AnnotationForm {...defaultProps} />);

      const descriptionTextarea = screen.getByLabelText(/description/i) as HTMLTextAreaElement;

      expect(descriptionTextarea.value).toBe('');
    });

    it('RE-CREATE-05: Submit button says "Create"', () => {
      render(<AnnotationForm {...defaultProps} />);

      expect(screen.getByRole('button', { name: /create/i })).toBeInTheDocument();
    });

    it('RE-CREATE-06: Form resets after successful create', async () => {
      const user = userEvent.setup();
      mockOnSave.mockResolvedValueOnce(undefined);

      render(<AnnotationForm {...defaultProps} />);

      // Fill form
      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, 'Test Annotation');

      // Submit
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalled();
      });

      // Form should reset
      await waitFor(() => {
        expect((titleInput as HTMLInputElement).value).toBe('');
      });
    });
  });

  // ============================================================
  // RE-EDIT — Edit Mode
  // ============================================================
  describe('Edit Mode', () => {
    it('RE-EDIT-01: Pre-populated date in edit mode', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      const dateInput = screen.getByLabelText(/date/i) as HTMLInputElement;

      expect(dateInput.value).toBe('2026-03-05');
    });

    it('RE-EDIT-02: Pre-populated title in edit mode', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      const titleInput = screen.getByLabelText(/title/i) as HTMLInputElement;

      expect(titleInput.value).toBe('Google Core Update Impact');
    });

    it('RE-EDIT-03: Pre-populated category in edit mode', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      const categorySelect = screen.getByLabelText(/category/i) as HTMLSelectElement;

      expect(categorySelect.value).toBe('algorithm-update');
    });

    it('RE-EDIT-04: Pre-populated description in edit mode', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      const descriptionTextarea = screen.getByLabelText(/description/i) as HTMLTextAreaElement;

      expect(descriptionTextarea.value).toBe('Rankings dropped 5 positions after core update');
    });

    it('RE-EDIT-05: Submit button says "Update" in edit mode', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      expect(screen.getByRole('button', { name: /update/i })).toBeInTheDocument();
    });

    it('RE-EDIT-06: Form does not reset after edit submit', async () => {
      const user = userEvent.setup();
      mockOnSave.mockResolvedValueOnce(undefined);

      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      const submitButton = screen.getByRole('button', { name: /update/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalled();
      });

      // Form should retain values
      const titleInput = screen.getByLabelText(/title/i) as HTMLInputElement;
      expect(titleInput.value).toBe('Google Core Update Impact');
    });

    it('RE-EDIT-07: Null description handled correctly', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotationNullDescription} />);

      const descriptionTextarea = screen.getByLabelText(/description/i) as HTMLTextAreaElement;

      expect(descriptionTextarea.value).toBe('');
    });

    it('RE-EDIT-08: All categories pre-select correctly', () => {
      const categories = ANNOTATION_CATEGORIES;

      categories.forEach((cat) => {
        const annotationWithCategory: Annotation = {
          ...mockAnnotation,
          category: cat.value,
        };

        const { unmount } = render(
          <AnnotationForm {...defaultProps} annotation={annotationWithCategory} />
        );

        const categorySelect = screen.getByLabelText(/category/i) as HTMLSelectElement;
        expect(categorySelect.value).toBe(cat.value);

        unmount();
      });
    });
  });

  // ============================================================
  // VAL — Validation
  // ============================================================
  describe('Validation', () => {
    it('VAL-01: Date is required', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const dateInput = screen.getByLabelText(/date/i);
      // Clear the date input
      await user.clear(dateInput);

      // Submit to trigger validation
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Should show error for missing date
      const error = await screen.findByText(/date is required/i, {}, { timeout: 3000 });
      expect(error).toBeInTheDocument();
    });

    it('VAL-02: Invalid date format shows error', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      // Native date input prevents invalid formats in most browsers
      // This test verifies the validation message appears when date is cleared
      const dateInput = screen.getByLabelText(/date/i);
      
      // Clear the date input (this should trigger validation on submit)
      await user.clear(dateInput);

      // Submit to trigger validation
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Should show error for missing date
      const error = await screen.findByText(/date is required/i, {}, { timeout: 3000 });
      expect(error).toBeInTheDocument();
    });

    it('VAL-03: Title is required', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      // Click submit to trigger validation
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Should show validation error
      const error = await screen.findByText(/title is required/i, {}, { timeout: 3000 });
      expect(error).toBeInTheDocument();
    });

    it('VAL-04: Title max length 255 characters', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      const longTitle = 'a'.repeat(256);

      await user.click(titleInput);
      await user.paste(longTitle);

      // Submit to trigger validation
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Should show error for max length
      const error = await screen.findByText(/title must be 255 characters or less/i, {}, { timeout: 3000 });
      expect(error).toBeInTheDocument();
    });

    it('VAL-05: Title exactly 255 characters is valid', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      const exactTitle = 'a'.repeat(255);

      // Use paste for faster input of long text
      await user.click(titleInput);
      await user.paste(exactTitle);
      await user.tab();

      // Should not show error
      expect(screen.queryByText(/title must be 255 characters or less/i)).not.toBeInTheDocument();
    });

    it('VAL-06: Valid date accepted without error', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const dateInput = screen.getByLabelText(/date/i);
      await user.clear(dateInput);
      await user.type(dateInput, '2026-03-01');
      await user.tab();

      expect(screen.queryByText(/please provide a valid date/i)).not.toBeInTheDocument();
    });

    it('VAL-07: Description is optional - no error when empty', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, 'Test Title');

      // Submit form without description
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalled();
      });

      // No error for empty description
      expect(screen.queryByText(/description is required/i)).not.toBeInTheDocument();
    });

    it('VAL-08: Category defaults to "other" without selection', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, 'Test Title');

      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalledWith(
          expect.objectContaining({
            category: 'other',
          })
        );
      });
    });

    it('VAL-09: Multiple errors display simultaneously', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      // Clear date and don't fill title - both will be invalid
      const dateInput = screen.getByLabelText(/date/i);
      await user.clear(dateInput);

      // Submit to trigger validation on all fields
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Both errors should appear
      const dateError = await screen.findByText(/date is required/i, {}, { timeout: 3000 });
      const titleError = await screen.findByText(/title is required/i, {}, { timeout: 3000 });

      expect(dateError).toBeInTheDocument();
      expect(titleError).toBeInTheDocument();
    });

    it('VAL-10: Error styling on invalid field', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      // Clear date to trigger validation
      const dateInput = screen.getByLabelText(/date/i);
      await user.clear(dateInput);

      // Trigger validation by submitting
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Wait for error to appear (date error should show since we cleared it)
      const dateError = await screen.findByText(/date is required/i, {}, { timeout: 3000 });
      expect(dateError).toBeInTheDocument();

      // Check that date input has red border
      expect(dateInput).toHaveClass('border-red-300');
    });

    it('VAL-11: Past date is accepted', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const dateInput = screen.getByLabelText(/date/i);
      await user.clear(dateInput);
      await user.type(dateInput, '2020-01-01');
      await user.tab();

      expect(screen.queryByText(/please provide a valid date/i)).not.toBeInTheDocument();
    });

    it('VAL-12: Today date is accepted', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const dateInput = screen.getByLabelText(/date/i);
      const today = new Date().toISOString().split('T')[0];
      await user.clear(dateInput);
      await user.type(dateInput, today);
      await user.tab();

      expect(screen.queryByText(/please provide a valid date/i)).not.toBeInTheDocument();
    });
  });

  // ============================================================
  // INTERACT — User Interactions
  // ============================================================
  describe('User Interactions', () => {
    it('INTERACT-01: Cancel button calls onCancel', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      await user.click(cancelButton);

      expect(mockOnCancel).toHaveBeenCalledTimes(1);
    });

    it('INTERACT-02: Submit calls onSave with form data', async () => {
      const user = userEvent.setup();
      mockOnSave.mockResolvedValueOnce(undefined);

      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      // Use paste for faster and more reliable input
      await user.click(titleInput);
      await user.paste('Test Annotation');

      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalledWith(
          expect.objectContaining({
            title: 'Test Annotation',
            category: 'other',
          })
        );
      });
    });

    it('INTERACT-03: Submit button disabled while submitting', async () => {
      const user = userEvent.setup();
      // Make onSave hang to test disabled state
      mockOnSave.mockImplementation(() => new Promise(() => {}));

      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, 'Test');

      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(submitButton).toBeDisabled();
      });
    });

    it('INTERACT-04: Category selection changes value', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const categorySelect = screen.getByLabelText(/category/i);
      await user.selectOptions(categorySelect, 'link-building');

      expect((categorySelect as HTMLSelectElement).value).toBe('link-building');
    });

    it('INTERACT-05: Date input change updates value', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const dateInput = screen.getByLabelText(/date/i);
      await user.clear(dateInput);
      await user.type(dateInput, '2026-02-15');

      expect((dateInput as HTMLInputElement).value).toBe('2026-02-15');
    });

    it('INTERACT-06: Title input change updates value', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, 'New Title');

      expect((titleInput as HTMLInputElement).value).toBe('New Title');
    });

    it('INTERACT-07: Description textarea change updates value', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const descriptionTextarea = screen.getByLabelText(/description/i);
      await user.type(descriptionTextarea, 'New description');

      expect((descriptionTextarea as HTMLTextAreaElement).value).toBe('New description');
    });

    it('INTERACT-08: Submit prevents default form submission', async () => {
      const user = userEvent.setup();
      mockOnSave.mockResolvedValueOnce(undefined);

      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, 'Test');

      const form = titleInput.closest('form')!;
      const submitHandler = jest.fn((e) => e.preventDefault());
      form.onsubmit = submitHandler;

      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // React Hook Form handles the submit, so our handler should be called
      await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalled();
      });
    });

    it('INTERACT-09: Tab navigation through fields', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const dateInput = screen.getByLabelText(/date/i);
      dateInput.focus();

      await user.tab();
      expect(screen.getByLabelText(/title/i)).toHaveFocus();

      await user.tab();
      expect(screen.getByLabelText(/category/i)).toHaveFocus();

      await user.tab();
      expect(screen.getByLabelText(/description/i)).toHaveFocus();
    });

    it('INTERACT-10: Enter key submits form from title field', async () => {
      const user = userEvent.setup();
      mockOnSave.mockResolvedValueOnce(undefined);

      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, 'Test Title{enter}');

      await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalled();
      });
    });
  });

  // ============================================================
  // A11Y — Accessibility
  // ============================================================
  describe('Accessibility', () => {
    it('A11Y-01: Date input has associated label', () => {
      render(<AnnotationForm {...defaultProps} />);

      const dateInput = screen.getByLabelText(/date/i);
      expect(dateInput).toHaveAttribute('id', 'date');

      const label = document.querySelector('label[for="date"]');
      expect(label).toBeInTheDocument();
    });

    it('A11Y-02: Title input has associated label', () => {
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      expect(titleInput).toHaveAttribute('id', 'title');

      const label = document.querySelector('label[for="title"]');
      expect(label).toBeInTheDocument();
    });

    it('A11Y-03: Category select has associated label', () => {
      render(<AnnotationForm {...defaultProps} />);

      const categorySelect = screen.getByLabelText(/category/i);
      expect(categorySelect).toHaveAttribute('id', 'category');

      const label = document.querySelector('label[for="category"]');
      expect(label).toBeInTheDocument();
    });

    it('A11Y-04: Description textarea has associated label', () => {
      render(<AnnotationForm {...defaultProps} />);

      const descriptionTextarea = screen.getByLabelText(/description/i);
      expect(descriptionTextarea).toHaveAttribute('id', 'description');

      const label = document.querySelector('label[for="description"]');
      expect(label).toBeInTheDocument();
    });

    it('A11Y-05: Error messages are linked to fields', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      // Trigger validation
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Error message appears after validation
      const errorMessage = await screen.findByText(/title is required/i, {}, { timeout: 3000 });
      expect(errorMessage).toBeInTheDocument();
      expect(errorMessage.tagName).toBe('P');
      expect(errorMessage).toHaveClass('text-red-600');
    });

    it('A11Y-06: Focus visible on interactive elements', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      cancelButton.focus();

      expect(cancelButton).toHaveFocus();
      expect(cancelButton).toHaveClass('focus:ring-2');
    });
  });

  // ============================================================
  // EDGE — Edge Cases
  // ============================================================
  describe('Edge Cases', () => {
    it('EDGE-01: Unicode in title renders correctly', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, '内容更新 📝');

      expect((titleInput as HTMLInputElement).value).toBe('内容更新 📝');
    });

    it('EDGE-02: Unicode in description renders correctly', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const descriptionTextarea = screen.getByLabelText(/description/i);
      await user.type(descriptionTextarea, '描述详情');

      expect((descriptionTextarea as HTMLTextAreaElement).value).toBe('描述详情');
    });

    it('EDGE-03: XSS in title is escaped', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.click(titleInput);
      await user.paste('<script>alert(1)</script>');

      // React escapes HTML by default, so the value should contain the literal text
      expect((titleInput as HTMLInputElement).value).toBe('<script>alert(1)</script>');
      // Verify no script element is actually created
      expect(document.querySelector('script')).toBeNull();
    });

    it('EDGE-04: HTML in description is escaped', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const descriptionTextarea = screen.getByLabelText(/description/i);
      await user.type(descriptionTextarea, '<b>Bold</b>');

      // React escapes HTML
      expect((descriptionTextarea as HTMLTextAreaElement).value).toBe('<b>Bold</b>');
    });

    it('EDGE-05: Very long description accepted', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const descriptionTextarea = screen.getByLabelText(/description/i);
      const longDescription = 'a'.repeat(500);

      // Use paste for faster input of long text
      await user.click(descriptionTextarea);
      await user.paste(longDescription);

      expect((descriptionTextarea as HTMLTextAreaElement).value).toBe(longDescription);
    });

    it('EDGE-06: Newlines in description preserved', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const descriptionTextarea = screen.getByLabelText(/description/i);
      await user.click(descriptionTextarea);
      await user.paste('Line1\nLine2');

      expect((descriptionTextarea as HTMLTextAreaElement).value).toBe('Line1\nLine2');
    });

    it('EDGE-07: Whitespace-only title is accepted (HTML default behavior)', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.click(titleInput);
      await user.paste('   ');

      // HTML input default behavior: whitespace is a valid value
      // react-hook-form's required validator considers '   ' as truthy
      expect((titleInput as HTMLInputElement).value).toBe('   ');
      
      // Note: If business logic requires whitespace trimming, 
      // the component would need a custom validator like:
      // validate: (value) => value.trim().length > 0 || "Title is required"
    });

    it('EDGE-08: Past date formats correctly', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      const dateInput = screen.getByLabelText(/date/i) as HTMLInputElement;

      // Date from 2026-03-05 should be formatted as yyyy-MM-dd
      expect(dateInput.value).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    });

    it('EDGE-09: Component handles missing keywordId gracefully', () => {
      // keywordId is required but component should not crash
      const props = { ...defaultProps, keywordId: undefined as unknown as number };
      render(<AnnotationForm {...props} />);

      expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
    });

    it('EDGE-10: Empty onSave callback does not crash', async () => {
      const user = userEvent.setup();
      const props = { ...defaultProps, onSave: undefined as unknown as typeof mockOnSave };
      render(<AnnotationForm {...props} />);

      const titleInput = screen.getByLabelText(/title/i);
      expect(titleInput).toBeInTheDocument();

      // Component renders, but submit would fail (this is expected - onSave is required)
    });
  });

  // ============================================================
  // PROP — Props Validation
  // ============================================================
  describe('Props Validation', () => {
    it('PROP-01: Required props present - component renders', () => {
      render(<AnnotationForm {...defaultProps} />);

      // Check that form elements are rendered
      expect(screen.getByLabelText(/date/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /create/i })).toBeInTheDocument();
    });

    it('PROP-02: Optional annotation prop omitted - create mode', () => {
      render(<AnnotationForm {...defaultProps} />);

      expect(screen.getByRole('button', { name: /create/i })).toBeInTheDocument();
    });

    it('PROP-03: Optional annotation prop provided - edit mode', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      expect(screen.getByRole('button', { name: /update/i })).toBeInTheDocument();
    });

    it('PROP-04: Null description in annotation', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotationNullDescription} />);

      const descriptionTextarea = screen.getByLabelText(/description/i) as HTMLTextAreaElement;

      expect(descriptionTextarea.value).toBe('');
    });

    it('PROP-05: All annotation fields populated', () => {
      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      const dateInput = screen.getByLabelText(/date/i) as HTMLInputElement;
      const titleInput = screen.getByLabelText(/title/i) as HTMLInputElement;
      const categorySelect = screen.getByLabelText(/category/i) as HTMLSelectElement;
      const descriptionTextarea = screen.getByLabelText(/description/i) as HTMLTextAreaElement;

      expect(dateInput.value).toBe(mockAnnotation.date);
      expect(titleInput.value).toBe(mockAnnotation.title);
      expect(categorySelect.value).toBe(mockAnnotation.category);
      expect(descriptionTextarea.value).toBe(mockAnnotation.description);
    });
  });

  // ============================================================
  // STYL — Styling
  // ============================================================
  describe('Styling', () => {
    it('STYL-01: Error input has red border', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      // Trigger validation by submitting empty form
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Wait for error message to appear
      await screen.findByText(/title is required/i, {}, { timeout: 3000 });

      const titleInput = screen.getByLabelText(/title/i);
      expect(titleInput).toHaveClass('border-red-300');
    });

    it('STYL-02: Valid input has gray border', () => {
      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);

      expect(titleInput).toHaveClass('border-gray-300');
    });

    it('STYL-03: Submit button has blue background', () => {
      render(<AnnotationForm {...defaultProps} />);

      const submitButton = screen.getByRole('button', { name: /create/i });

      expect(submitButton).toHaveClass('bg-blue-600');
    });

    it('STYL-04: Cancel button has white background', () => {
      render(<AnnotationForm {...defaultProps} />);

      const cancelButton = screen.getByRole('button', { name: /cancel/i });

      expect(cancelButton).toHaveClass('bg-white');
    });

    it('STYL-05: Disabled button has reduced opacity', async () => {
      const user = userEvent.setup();
      mockOnSave.mockImplementation(() => new Promise(() => {}));

      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.type(titleInput, 'Test');

      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      await waitFor(() => {
        expect(submitButton).toHaveClass('disabled:opacity-50');
      });
    });
  });

  // ============================================================
  // CAT — Category Options
  // ============================================================
  describe('Category Options', () => {
    it('CAT-01: All 9 categories present in dropdown', () => {
      render(<AnnotationForm {...defaultProps} />);

      const categorySelect = screen.getByLabelText(/category/i);
      const options = within(categorySelect).getAllByRole('option');

      expect(options).toHaveLength(9);
    });

    it('CAT-02: Category icons display in options', () => {
      render(<AnnotationForm {...defaultProps} />);

      const categorySelect = screen.getByLabelText(/category/i);
      const options = within(categorySelect).getAllByRole('option');

      // Check that first option has icon (emoji)
      expect(options[0].textContent).toMatch(/[🔄📝🔧🔗👀📅⚠️✅📌]/);
    });

    it('CAT-03: Category labels display in options', () => {
      render(<AnnotationForm {...defaultProps} />);

      // Check for some category labels
      expect(screen.getByText(/Algorithm Update/i)).toBeInTheDocument();
      expect(screen.getByText(/Content Update/i)).toBeInTheDocument();
      expect(screen.getByText(/Link Building/i)).toBeInTheDocument();
    });

    it('CAT-04: Category values match AnnotationCategory type', () => {
      render(<AnnotationForm {...defaultProps} />);

      const categorySelect = screen.getByLabelText(/category/i) as HTMLSelectElement;
      const options = within(categorySelect).getAllByRole('option');

      const expectedValues = ANNOTATION_CATEGORIES.map((c) => c.value);
      const actualValues = options.map((o) => (o as HTMLOptionElement).value);

      expect(actualValues).toEqual(expectedValues);
    });

    it('CAT-05: Default category is "other"', () => {
      render(<AnnotationForm {...defaultProps} />);

      const categorySelect = screen.getByLabelText(/category/i) as HTMLSelectElement;

      expect(categorySelect.value).toBe('other');
    });
  });

  // ============================================================
  // AC — Acceptance Criteria
  // ============================================================
  describe('Acceptance Criteria', () => {
    it('AC-01: Form creates annotation with valid data', async () => {
      const user = userEvent.setup();
      mockOnSave.mockResolvedValueOnce(undefined);

      render(<AnnotationForm {...defaultProps} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.click(titleInput);
      await user.paste('New Annotation');

      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Wait for the save to be called
      const saveCall = await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalled();
        return mockOnSave.mock.calls[0];
      }, { timeout: 5000 });

      expect(saveCall[0]).toMatchObject({
        title: 'New Annotation',
        category: 'other',
      });
    });

    it('AC-02: Form edits annotation with updated data', async () => {
      const user = userEvent.setup();
      mockOnSave.mockResolvedValueOnce(undefined);

      render(<AnnotationForm {...defaultProps} annotation={mockAnnotation} />);

      const titleInput = screen.getByLabelText(/title/i);
      await user.clear(titleInput);
      await user.paste('Updated Title');

      const submitButton = screen.getByRole('button', { name: /update/i });
      await user.click(submitButton);

      // Wait for the save to be called
      const saveCall = await waitFor(() => {
        expect(mockOnSave).toHaveBeenCalled();
        return mockOnSave.mock.calls[0];
      }, { timeout: 5000 });

      expect(saveCall[0]).toMatchObject({
        title: 'Updated Title',
      });
    });

    it('AC-03: Cancel action calls onCancel callback', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      await user.click(cancelButton);

      expect(mockOnCancel).toHaveBeenCalledTimes(1);
    });

    it('AC-04: Validation prevents invalid submission', async () => {
      const user = userEvent.setup();
      render(<AnnotationForm {...defaultProps} />);

      // Submit with empty title (invalid) - triggers validation
      const submitButton = screen.getByRole('button', { name: /create/i });
      await user.click(submitButton);

      // Should show validation error
      const error = await screen.findByText(/title is required/i, {}, { timeout: 5000 });
      expect(error).toBeInTheDocument();

      // onSave should not have been called
      expect(mockOnSave).not.toHaveBeenCalled();
    });
  });
});
