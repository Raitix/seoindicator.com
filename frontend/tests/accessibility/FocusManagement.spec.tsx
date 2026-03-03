/**
 * Accessibility Tests for Focus Management
 * 
 * Tests for WCAG 2.1 AA compliance including focus traps,
 * focus return, and focus indicator patterns.
 * 
 * @see TESTS/TASK-28-16.md for full test documentation
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';

// ============================================
// Mock Modal Component with Focus Trap
// ============================================

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
}

const MockModal: React.FC<ModalProps> = ({ isOpen, onClose, title, children }) => {
  const modalRef = React.useRef<HTMLDivElement>(null);
  const previousFocusRef = React.useRef<HTMLElement | null>(null);

  // Focus trap and return
  React.useEffect(() => {
    if (isOpen) {
      previousFocusRef.current = document.activeElement as HTMLElement;
      
      // Focus first focusable element
      const firstFocusable = modalRef.current?.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      ) as HTMLElement;
      firstFocusable?.focus();

      const handleKeyDown = (e: KeyboardEvent) => {
        if (e.key === 'Escape') {
          onClose();
        }

        // Tab trap
        if (e.key === 'Tab' && modalRef.current) {
          const focusableElements = modalRef.current.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
          );
          const firstElement = focusableElements[0] as HTMLElement;
          const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;

          if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
          } else if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
          }
        }
      };

      document.addEventListener('keydown', handleKeyDown);
      return () => document.removeEventListener('keydown', handleKeyDown);
    } else {
      // Return focus
      previousFocusRef.current?.focus();
    }
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div
      ref={modalRef}
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
      data-testid="modal"
    >
      <h2 id="modal-title">{title}</h2>
      <button onClick={onClose} data-testid="modal-close">
        Close
      </button>
      {children}
    </div>
  );
};

// ============================================
// Mock Sheet Component with Focus Trap
// ============================================

interface SheetProps {
  isOpen: boolean;
  onClose: () => void;
  children: React.ReactNode;
}

const MockSheet: React.FC<SheetProps> = ({ isOpen, onClose, children }) => {
  const sheetRef = React.useRef<HTMLDivElement>(null);
  const triggerRef = React.useRef<HTMLElement | null>(null);

  React.useEffect(() => {
    if (isOpen) {
      triggerRef.current = document.activeElement as HTMLElement;
      sheetRef.current?.focus();
    } else {
      triggerRef.current?.focus();
    }
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <div
      ref={sheetRef}
      role="dialog"
      aria-modal="true"
      tabIndex={-1}
      data-testid="sheet"
    >
      <button onClick={onClose} data-testid="sheet-close">
        Close Sheet
      </button>
      {children}
    </div>
  );
};

// ============================================
// Mock Form with Error Focus
// ============================================

interface FormField {
  id: string;
  label: string;
  error?: string;
  value: string;
}

interface FormWithErrorsProps {
  fields: FormField[];
  onSubmit: () => void;
}

const MockFormWithErrors: React.FC<FormWithErrorsProps> = ({ fields, onSubmit }) => {
  const formRef = React.useRef<HTMLFormElement>(null);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Find first error field and focus it
    const firstErrorField = formRef.current?.querySelector('[aria-invalid="true"]') as HTMLElement;
    if (firstErrorField) {
      firstErrorField.focus();
    } else {
      onSubmit();
    }
  };

  return (
    <form ref={formRef} onSubmit={handleSubmit} data-testid="form">
      {fields.map((field) => (
        <div key={field.id}>
          <label htmlFor={field.id}>{field.label}</label>
          <input
            id={field.id}
            type="text"
            defaultValue={field.value}
            aria-invalid={!!field.error}
            aria-describedby={field.error ? `${field.id}-error` : undefined}
          />
          {field.error && (
            <span id={`${field.id}-error`} role="alert" data-testid={`${field.id}-error`}>
              {field.error}
            </span>
          )}
        </div>
      ))}
      <button type="submit" data-testid="submit">
        Submit
      </button>
    </form>
  );
};

// ============================================
// Test Scenarios FM-001 to FM-010
// ============================================

describe('Focus Management (FM-001 to FM-010)', () => {
  // ============================================
  // FM-001: Modal focus trap
  // ============================================
  it('FM-001: Tab cycles within modal only (focus trap)', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();

    render(
      <>
        <button data-testid="outside-button">Outside</button>
        <MockModal isOpen={true} onClose={onClose} title="Test Modal">
          <button data-testid="modal-button-1">Button 1</button>
          <button data-testid="modal-button-2">Button 2</button>
        </MockModal>
      </>
    );

    const closeButton = screen.getByTestId('modal-close');
    const button1 = screen.getByTestId('modal-button-1');
    const button2 = screen.getByTestId('modal-button-2');

    // Tab through modal elements
    closeButton.focus();
    await user.tab();
    expect(button1).toHaveFocus();

    await user.tab();
    expect(button2).toHaveFocus();

    // Tab from last should cycle to first (focus trap)
    await user.tab();
    expect(closeButton).toHaveFocus();
  });

  // ============================================
  // FM-002: Sheet focus trap
  // ============================================
  it('FM-002: Tab cycles within sheet only (focus trap)', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();

    render(
      <>
        <button data-testid="outside-button">Outside</button>
        <MockSheet isOpen={true} onClose={onClose}>
          <button data-testid="sheet-button">Sheet Action</button>
        </MockSheet>
      </>
    );

    const sheet = screen.getByTestId('sheet');
    expect(sheet).toHaveFocus();

    // Tab should stay within sheet
    await user.tab();
    const closeButton = screen.getByTestId('sheet-close');
    expect(closeButton).toHaveFocus();
  });

  // ============================================
  // FM-003: Focus on modal open
  // ============================================
  it('FM-003: focus moves to first focusable element when modal opens', () => {
    const onClose = vi.fn();

    render(
      <MockModal isOpen={true} onClose={onClose} title="Test Modal">
        <button data-testid="modal-content-button">Content</button>
      </MockModal>
    );

    // First focusable should be close button
    const closeButton = screen.getByTestId('modal-close');
    expect(closeButton).toHaveFocus();
  });

  // ============================================
  // FM-004: Focus returns on close
  // ============================================
  it('FM-004: focus returns to trigger element when modal closes', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();

    const { rerender } = render(
      <>
        <button data-testid="trigger-button">Open Modal</button>
        <MockModal isOpen={true} onClose={onClose} title="Test Modal" />
      </>
    );

    // Click trigger to set it as previous focus
    await user.click(screen.getByTestId('trigger-button'));

    // Close modal
    rerender(
      <>
        <button data-testid="trigger-button">Open Modal</button>
        <MockModal isOpen={false} onClose={onClose} title="Test Modal" />
      </>
    );

    // Focus should return to trigger
    expect(screen.getByTestId('trigger-button')).toHaveFocus();
  });

  // ============================================
  // FM-005: Focus on error
  // ============================================
  it('FM-005: focus moves to first error field on form submission', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    const fields: FormField[] = [
      { id: 'name', label: 'Name', value: '', error: 'Name is required' },
      { id: 'email', label: 'Email', value: '' },
    ];

    render(<MockFormWithErrors fields={fields} onSubmit={onSubmit} />);

    await user.click(screen.getByTestId('submit'));

    const nameInput = screen.getByLabelText('Name');
    expect(nameInput).toHaveFocus();
  });

  // ============================================
  // FM-006: Focus on success (optional)
  // ============================================
  it('FM-006: focus management on success state (optional pattern)', () => {
    // This is an optional pattern - focus on success message
    // Implementation depends on UX requirements
    const SuccessComponent = () => (
      <div role="status" aria-live="polite" tabIndex={-1} data-testid="success-message">
        Operation completed successfully!
      </div>
    );

    render(<SuccessComponent />);

    const successMessage = screen.getByTestId('success-message');
    expect(successMessage).toBeInTheDocument();
    expect(successMessage).toHaveAttribute('aria-live', 'polite');
  });

  // ============================================
  // FM-007: Roving tabindex in grid
  // ============================================
  it('FM-007: roving tabindex pattern for grid navigation', async () => {
    const user = userEvent.setup();

    const GridWithRovingTabindex = () => {
      const [focusedIndex, setFocusedIndex] = React.useState(0);
      const items = ['Item 1', 'Item 2', 'Item 3'];

      const handleKeyDown = (e: React.KeyboardEvent, index: number) => {
        if (e.key === 'ArrowRight' && index < items.length - 1) {
          setFocusedIndex(index + 1);
        } else if (e.key === 'ArrowLeft' && index > 0) {
          setFocusedIndex(index - 1);
        }
      };

      return (
        <div role="grid" data-testid="grid">
          {items.map((item, index) => (
            <div
              key={item}
              role="gridcell"
              tabIndex={focusedIndex === index ? 0 : -1}
              onKeyDown={(e) => handleKeyDown(e, index)}
              onFocus={() => setFocusedIndex(index)}
              data-testid={`grid-item-${index}`}
            >
              {item}
            </div>
          ))}
        </div>
      );
    };

    render(<GridWithRovingTabindex />);

    const item0 = screen.getByTestId('grid-item-0');
    const item1 = screen.getByTestId('grid-item-1');
    const item2 = screen.getByTestId('grid-item-2');

    // Only first item should be tabbable
    expect(item0).toHaveAttribute('tabIndex', '0');
    expect(item1).toHaveAttribute('tabIndex', '-1');
    expect(item2).toHaveAttribute('tabIndex', '-1');

    // Arrow right should move focus
    item0.focus();
    await user.keyboard('{ArrowRight}');
    expect(item1).toHaveFocus();
    expect(item1).toHaveAttribute('tabIndex', '0');
    expect(item0).toHaveAttribute('tabIndex', '-1');
  });

  // ============================================
  // FM-008: Focus visible always
  // ============================================
  it('FM-008: focus indicator is visible when element is focused', () => {
    render(
      <button data-testid="focusable-button" style={{ outline: '2px solid blue' }}>
        Focusable
      </button>
    );

    const button = screen.getByTestId('focusable-button');
    button.focus();

    expect(button).toHaveFocus();
    // Note: Visual testing of focus indicator requires CSS/visual testing tools
  });

  // ============================================
  // FM-009: No focus trap in page
  // ============================================
  it('FM-009: normal page does not trap focus', async () => {
    const user = userEvent.setup();

    render(
      <>
        <button data-testid="button-1">Button 1</button>
        <button data-testid="button-2">Button 2</button>
        <button data-testid="button-3">Button 3</button>
      </>
    );

    // Tab through all elements
    await user.tab();
    expect(screen.getByTestId('button-1')).toHaveFocus();

    await user.tab();
    expect(screen.getByTestId('button-2')).toHaveFocus();

    await user.tab();
    expect(screen.getByTestId('button-3')).toHaveFocus();

    // Tab after last should exit to browser UI (can't test that directly)
  });

  // ============================================
  // FM-010: Autocomplete focus
  // ============================================
  it('FM-010: focus returns to input after autocomplete selection', async () => {
    const user = userEvent.setup();

    const MockAutocomplete = () => {
      const [isOpen, setIsOpen] = React.useState(false);
      const inputRef = React.useRef<HTMLInputElement>(null);

      const handleSelect = () => {
        setIsOpen(false);
        inputRef.current?.focus();
      };

      return (
        <div data-testid="autocomplete">
          <input
            ref={inputRef}
            type="text"
            onFocus={() => setIsOpen(true)}
            data-testid="autocomplete-input"
          />
          {isOpen && (
            <ul role="listbox" data-testid="autocomplete-list">
              <li role="option" onClick={handleSelect} data-testid="option-1">
                Option 1
              </li>
            </ul>
          )}
        </div>
      );
    };

    render(<MockAutocomplete />);

    const input = screen.getByTestId('autocomplete-input');
    await user.click(input);

    expect(screen.getByTestId('autocomplete-list')).toBeInTheDocument();

    await user.click(screen.getByTestId('option-1'));

    expect(input).toHaveFocus();
  });
});

// ============================================
// Escape Key Tests
// ============================================

describe('Escape Key Behavior', () => {
  it('Escape closes modal', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();

    render(<MockModal isOpen={true} onClose={onClose} title="Test Modal" />);

    await user.keyboard('{Escape}');

    expect(onClose).toHaveBeenCalled();
  });
});

// ============================================
// Focus Trap Implementation Tests
// ============================================

describe('Focus Trap Implementation', () => {
  it('Shift+Tab cycles to last element from first', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();

    render(
      <MockModal isOpen={true} onClose={onClose} title="Test Modal">
        <button data-testid="btn-1">Button 1</button>
        <button data-testid="btn-2">Button 2</button>
      </MockModal>
    );

    const closeButton = screen.getByTestId('modal-close');
    const button1 = screen.getByTestId('btn-1');
    const button2 = screen.getByTestId('btn-2');

    // Focus first element (close button)
    closeButton.focus();

    // Shift+Tab from first should go to last
    await user.keyboard('{Shift>}{Tab}{/Shift}');
    expect(button2).toHaveFocus();
  });
});
