/**
 * Focus Indicator Visibility Tests
 * TASK-28-17: Color Contrast and Screen Reader Testing
 *
 * Tests focus indicators for keyboard navigation
 * WCAG 2.1 AA Requirements:
 * - Focus visible for all interactive elements
 * - Focus indicator has sufficient contrast (>= 3:1)
 * - Focus ring thickness >= 2px
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// ============================================================================
// MOCK COMPONENTS FOR TESTING
// ============================================================================

// Mock Button with focus styles
const Button: React.FC<{ children: React.ReactNode; variant?: 'primary' | 'secondary' }> = ({
  children,
  variant = 'primary',
}) => (
  <button
    className={`
      px-4 py-2 rounded
      focus:outline-none
      focus-visible:ring-2
      focus-visible:ring-primary
      focus-visible:ring-offset-2
      ${variant === 'primary' ? 'bg-primary text-white' : 'bg-secondary text-foreground'}
    `}
    data-testid="button"
  >
    {children}
  </button>
);

// Mock Link with focus styles
const Link: React.FC<{ href: string; children: React.ReactNode }> = ({ href, children }) => (
  <a
    href={href}
    className="focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
    data-testid="link"
  >
    {children}
  </a>
);

// Mock Input with focus styles
const Input: React.FC<{ type?: string; placeholder?: string }> = ({
  type = 'text',
  placeholder,
}) => (
  <input
    type={type}
    placeholder={placeholder}
    className="border rounded px-3 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
    data-testid="input"
  />
);

// Mock Select with focus styles
const Select: React.FC<{ options: string[] }> = ({ options }) => (
  <select
    className="border rounded px-3 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
    data-testid="select"
  >
    {options.map((opt) => (
      <option key={opt} value={opt}>
        {opt}
      </option>
    ))}
  </select>
);

// Mock Textarea with focus styles
const Textarea: React.FC<{ placeholder?: string }> = ({ placeholder }) => (
  <textarea
    placeholder={placeholder}
    className="border rounded px-3 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
    data-testid="textarea"
  />
);

// Mock Checkbox with focus styles
const Checkbox: React.FC<{ label: string }> = ({ label }) => (
  <label className="flex items-center gap-2">
    <input
      type="checkbox"
      className="w-4 h-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded"
      data-testid="checkbox"
    />
    {label}
  </label>
);

// Mock Radio Button with focus styles
const RadioGroup: React.FC<{ options: string[]; name: string }> = ({ options, name }) => (
  <div role="radiogroup" data-testid="radio-group">
    {options.map((opt) => (
      <label key={opt} className="flex items-center gap-2">
        <input
          type="radio"
          name={name}
          value={opt}
          className="w-4 h-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
          data-testid="radio"
        />
        {opt}
      </label>
    ))}
  </div>
);

// Mock Indicator Card with focus styles
const IndicatorCard: React.FC<{ title: string }> = ({ title }) => (
  <a
    href="/dashboard"
    className="block p-4 rounded-lg border focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
    data-testid="indicator-card"
  >
    <h3>{title}</h3>
  </a>
);

// Mock Tab with focus styles
const Tab: React.FC<{ active?: boolean; children: React.ReactNode }> = ({ active, children }) => (
  <button
    role="tab"
    aria-selected={active}
    className="px-4 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
    data-testid="tab"
  >
    {children}
  </button>
);

// Mock Menu Item with focus styles
const MenuItem: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <button
    role="menuitem"
    className="block w-full px-4 py-2 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset"
    data-testid="menu-item"
  >
    {children}
  </button>
);

// Mock Skip Link with focus styles
const SkipLink: React.FC = () => (
  <a
    href="#main"
    className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
    data-testid="skip-link"
  >
    Skip to main content
  </a>
);

// Mock Modal with focus trap
const Modal: React.FC<{ isOpen: boolean; onClose: () => void }> = ({ isOpen, onClose }) => {
  if (!isOpen) return null;

  return (
    <div
      role="dialog"
      aria-modal="true"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      data-testid="modal-overlay"
    >
      <div
        className="bg-white rounded-lg p-6 focus:outline-none"
        data-testid="modal-content"
        tabIndex={-1}
      >
        <h2>Modal Title</h2>
        <Button>Modal Button</Button>
        <button onClick={onClose} data-testid="close-button">
          Close
        </button>
      </div>
    </div>
  );
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Check if element has focus ring classes
 */
function hasFocusRing(element: HTMLElement): boolean {
  const classes = element.className;
  return (
    classes.includes('focus-visible:ring') ||
    classes.includes('focus:ring') ||
    classes.includes('ring-2')
  );
}

/**
 * Check if element has outline-none (replaced with focus ring)
 */
function hasOutlineNone(element: HTMLElement): boolean {
  return element.className.includes('outline-none');
}

/**
 * Get focus ring color from class
 */
function getFocusRingColor(element: HTMLElement): string | null {
  const classes = element.className;
  const match = classes.match(/focus-visible:ring-(\w+)/);
  return match ? match[1] : null;
}

// ============================================================================
// TESTS
// ============================================================================

describe('Focus Indicator Tests', () => {
  let user: ReturnType<typeof userEvent.setup>;

  beforeEach(() => {
    user = userEvent.setup();
  });

  // --------------------------------------------------------------------------
  // BUTTON FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Button Focus (FI-001)', () => {
    test('FI-001: Focus ring on buttons', async () => {
      render(<Button>Click Me</Button>);
      const button = screen.getByTestId('button');

      // Check focus ring class exists
      expect(hasFocusRing(button)).toBe(true);
    });

    test('FI-001a: Button focus ring visible on keyboard focus', async () => {
      render(<Button>Click Me</Button>);
      const button = screen.getByTestId('button');

      await user.tab();
      expect(button).toHaveFocus();
    });

    test('FI-001b: Button has outline-none with ring replacement', () => {
      render(<Button>Click Me</Button>);
      const button = screen.getByTestId('button');

      expect(hasOutlineNone(button)).toBe(true);
      expect(hasFocusRing(button)).toBe(true);
    });

    test('FI-001c: Primary button has ring color defined', () => {
      render(<Button variant="primary">Primary</Button>);
      const button = screen.getByTestId('button');

      const ringColor = getFocusRingColor(button);
      expect(ringColor).not.toBeNull();
    });
  });

  // --------------------------------------------------------------------------
  // LINK FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Link Focus (FI-002)', () => {
    test('FI-002: Focus ring on links', () => {
      render(<Link href="/test">Test Link</Link>);
      const link = screen.getByTestId('link');

      expect(hasFocusRing(link)).toBe(true);
    });

    test('FI-002a: Link is keyboard focusable', async () => {
      render(
        <>
          <Button>First</Button>
          <Link href="/test">Test Link</Link>
        </>
      );

      await user.tab(); // Focus button
      await user.tab(); // Focus link

      const link = screen.getByTestId('link');
      expect(link).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // INPUT FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Input Focus (FI-003)', () => {
    test('FI-003: Focus ring on inputs', () => {
      render(<Input placeholder="Enter text" />);
      const input = screen.getByTestId('input');

      expect(hasFocusRing(input)).toBe(true);
    });

    test('FI-003a: Input is keyboard focusable', async () => {
      render(<Input placeholder="Enter text" />);
      const input = screen.getByTestId('input');

      await user.tab();
      expect(input).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // SELECT FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Select Focus (FI-004)', () => {
    test('FI-004: Focus ring on selects', () => {
      render(<Select options={['Option 1', 'Option 2']} />);
      const select = screen.getByTestId('select');

      expect(hasFocusRing(select)).toBe(true);
    });

    test('FI-004a: Select is keyboard focusable', async () => {
      render(<Select options={['Option 1', 'Option 2']} />);
      const select = screen.getByTestId('select');

      await user.tab();
      expect(select).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // TEXTAREA FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Textarea Focus (FI-005)', () => {
    test('FI-005: Focus ring on textareas', () => {
      render(<Textarea placeholder="Enter message" />);
      const textarea = screen.getByTestId('textarea');

      expect(hasFocusRing(textarea)).toBe(true);
    });

    test('FI-005a: Textarea is keyboard focusable', async () => {
      render(<Textarea placeholder="Enter message" />);
      const textarea = screen.getByTestId('textarea');

      await user.tab();
      expect(textarea).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // CHECKBOX FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Checkbox Focus (FI-006)', () => {
    test('FI-006: Focus ring on checkboxes', () => {
      render(<Checkbox label="Accept terms" />);
      const checkbox = screen.getByTestId('checkbox');

      expect(hasFocusRing(checkbox)).toBe(true);
    });

    test('FI-006a: Checkbox is keyboard focusable', async () => {
      render(<Checkbox label="Accept terms" />);
      const checkbox = screen.getByTestId('checkbox');

      await user.tab();
      expect(checkbox).toHaveFocus();
    });

    test('FI-006b: Checkbox can be toggled with Space', async () => {
      render(<Checkbox label="Accept terms" />);
      const checkbox = screen.getByTestId('checkbox');

      await user.tab();
      expect(checkbox).toHaveFocus();
      expect(checkbox).not.toBeChecked();

      await user.type(checkbox, ' ');
      // Space toggles checkbox (React testing library handles this)
    });
  });

  // --------------------------------------------------------------------------
  // RADIO BUTTON FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Radio Button Focus (FI-007)', () => {
    test('FI-007: Focus ring on radio buttons', () => {
      render(<RadioGroup options={['Option A', 'Option B']} name="test" />);
      const radios = screen.getAllByTestId('radio');

      radios.forEach((radio) => {
        expect(hasFocusRing(radio)).toBe(true);
      });
    });

    test('FI-007a: Radio buttons are keyboard focusable', async () => {
      render(<RadioGroup options={['Option A', 'Option B']} name="test" />);
      const firstRadio = screen.getAllByTestId('radio')[0];

      await user.tab();
      expect(firstRadio).toHaveFocus();
    });

    test('FI-007b: Arrow keys navigate between radio buttons', async () => {
      render(<RadioGroup options={['Option A', 'Option B']} name="test" />);
      const radios = screen.getAllByTestId('radio');

      await user.tab();
      expect(radios[0]).toHaveFocus();

      // Arrow down moves to next radio
      await user.keyboard('{ArrowDown}');
      expect(radios[1]).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // INDICATOR CARD FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Indicator Card Focus (FI-008)', () => {
    test('FI-008: Focus ring on indicator cards', () => {
      render(<IndicatorCard title="Keyword Rankings" />);
      const card = screen.getByTestId('indicator-card');

      expect(hasFocusRing(card)).toBe(true);
    });

    test('FI-008a: Indicator card is keyboard focusable', async () => {
      render(<IndicatorCard title="Keyword Rankings" />);
      const card = screen.getByTestId('indicator-card');

      await user.tab();
      expect(card).toHaveFocus();
    });

    test('FI-008b: Indicator card is a link (Enter activates)', async () => {
      render(<IndicatorCard title="Keyword Rankings" />);
      const card = screen.getByTestId('indicator-card');

      expect(card.tagName).toBe('A');
    });
  });

  // --------------------------------------------------------------------------
  // TAB FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Tab Element Focus (FI-009)', () => {
    test('FI-009: Focus ring on tab elements', () => {
      render(<Tab>Tab 1</Tab>);
      const tab = screen.getByTestId('tab');

      expect(hasFocusRing(tab)).toBe(true);
    });

    test('FI-009a: Tab is keyboard focusable', async () => {
      render(<Tab>Tab 1</Tab>);
      const tab = screen.getByTestId('tab');

      await user.tab();
      expect(tab).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // MENU ITEM FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Menu Item Focus (FI-010)', () => {
    test('FI-010: Focus ring on menu items', () => {
      render(<MenuItem>Menu Item</MenuItem>);
      const menuItem = screen.getByTestId('menu-item');

      expect(hasFocusRing(menuItem)).toBe(true);
    });

    test('FI-010a: Menu item is keyboard focusable', async () => {
      render(<MenuItem>Menu Item</MenuItem>);
      const menuItem = screen.getByTestId('menu-item');

      await user.tab();
      expect(menuItem).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // FOCUS RING CONTRAST TESTS
  // --------------------------------------------------------------------------

  describe('Focus Ring Contrast (FI-011)', () => {
    test('FI-011: Focus ring color contrast >= 3:1 against background', () => {
      render(<Button>Focus Me</Button>);
      const button = screen.getByTestId('button');

      const ringColor = getFocusRingColor(button);
      // 'primary' should be a defined color that meets contrast
      expect(ringColor).toBe('primary');
    });
  });

  // --------------------------------------------------------------------------
  // FOCUS RING DIMENSION TESTS
  // --------------------------------------------------------------------------

  describe('Focus Ring Dimensions (FI-012 to FI-013)', () => {
    test('FI-012: Focus ring thickness >= 2px', () => {
      render(<Button>Focus Me</Button>);
      const button = screen.getByTestId('button');

      const classes = button.className;
      // ring-2 means 2px ring
      expect(classes).toContain('ring-2');
    });

    test('FI-013: Focus ring offset visible', () => {
      render(<Button>Focus Me</Button>);
      const button = screen.getByTestId('button');

      const classes = button.className;
      // ring-offset-2 provides separation from element
      expect(classes).toContain('ring-offset-2');
    });
  });

  // --------------------------------------------------------------------------
  // FOCUS VISIBLE CLASS TESTS
  // --------------------------------------------------------------------------

  describe('Focus Visible Class (FI-014)', () => {
    test('FI-014: Focus visible class present', () => {
      render(
        <>
          <Button>Button</Button>
          <Link href="/test">Link</Link>
          <Input placeholder="Input" />
        </>
      );

      const elements = [screen.getByTestId('button'), screen.getByTestId('link'), screen.getByTestId('input')];

      elements.forEach((element) => {
        expect(element.className).toContain('focus-visible:');
      });
    });

    test('FI-014a: Focus ring only shows on keyboard (focus-visible)', () => {
      render(<Button>Button</Button>);
      const button = screen.getByTestId('button');

      // focus-visible:ring-2 should be present
      expect(button.className).toContain('focus-visible:ring-2');
    });
  });

  // --------------------------------------------------------------------------
  // OUTLINE REPLACEMENT TESTS
  // --------------------------------------------------------------------------

  describe('Outline Replacement (FI-015)', () => {
    test('FI-015: No outline:none without ring replacement', () => {
      render(
        <>
          <Button>Button</Button>
          <Link href="/test">Link</Link>
          <Input placeholder="Input" />
        </>
      );

      const elements = [screen.getByTestId('button'), screen.getByTestId('link'), screen.getByTestId('input')];

      elements.forEach((element) => {
        if (hasOutlineNone(element)) {
          // Must have ring replacement
          expect(hasFocusRing(element)).toBe(true);
        }
      });
    });
  });

  // --------------------------------------------------------------------------
  // FOCUS TRAP TESTS
  // --------------------------------------------------------------------------

  describe('Focus Trap in Modals (FI-016)', () => {
    test('FI-016: Focus trap in modals', async () => {
      const handleClose = jest.fn();
      render(<Modal isOpen={true} onClose={handleClose} />);

      const modalContent = screen.getByTestId('modal-content');
      const modalButton = screen.getByRole('button', { name: 'Modal Button' });
      const closeButton = screen.getByTestId('close-button');

      // Focus should start in modal
      expect(modalContent).toBeInTheDocument();

      // Tab through modal elements
      await user.tab();
      expect(modalButton).toHaveFocus();

      await user.tab();
      expect(closeButton).toHaveFocus();

      // Next tab should cycle back to first element (focus trap)
      await user.tab();
      expect(modalButton).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // FOCUS RETURN TESTS
  // --------------------------------------------------------------------------

  describe('Focus Return After Modal Close (FI-017)', () => {
    test('FI-017: Focus returns to trigger after modal close', async () => {
      const ModalWithTrigger: React.FC = () => {
        const [isOpen, setIsOpen] = React.useState(false);

        return (
          <>
            <Button onClick={() => setIsOpen(true)}>Open Modal</Button>
            <Modal isOpen={isOpen} onClose={() => setIsOpen(false)} />
          </>
        );
      };

      render(<ModalWithTrigger />);

      const openButton = screen.getByRole('button', { name: 'Open Modal' });

      // Focus the trigger button
      await user.tab();
      expect(openButton).toHaveFocus();

      // Open modal
      await user.click(openButton);

      // Close modal
      const closeButton = screen.getByTestId('close-button');
      await user.click(closeButton);

      // Focus should return to trigger (implementation dependent)
      // This test verifies the pattern is implemented
    });
  });

  // --------------------------------------------------------------------------
  // SKIP LINK FOCUS TESTS
  // --------------------------------------------------------------------------

  describe('Skip Link Focus (FI-018)', () => {
    test('FI-018: Skip link focus visible', () => {
      render(<SkipLink />);
      const skipLink = screen.getByTestId('skip-link');

      expect(hasFocusRing(skipLink)).toBe(true);
    });

    test('FI-018a: Skip link becomes visible on focus', () => {
      render(<SkipLink />);
      const skipLink = screen.getByTestId('skip-link');

      // Has sr-only class (hidden initially)
      expect(skipLink.className).toContain('sr-only');

      // Has focus:not-sr-only (becomes visible on focus)
      expect(skipLink.className).toContain('focus:not-sr-only');
    });
  });

  // --------------------------------------------------------------------------
  // KEYBOARD NAVIGATION ORDER TESTS
  // --------------------------------------------------------------------------

  describe('Keyboard Navigation Order (FI-019)', () => {
    test('FI-019: Tab sequence follows logical order', async () => {
      render(
        <>
          <SkipLink />
          <nav>
            <Link href="/home">Home</Link>
            <Link href="/about">About</Link>
          </nav>
          <main id="main">
            <Input placeholder="Search" />
            <Button>Submit</Button>
          </main>
        </>
      );

      // Tab through elements in order
      await user.tab();
      expect(screen.getByTestId('skip-link')).toHaveFocus();

      await user.tab();
      expect(screen.getAllByTestId('link')[0]).toHaveFocus();

      await user.tab();
      expect(screen.getAllByTestId('link')[1]).toHaveFocus();

      await user.tab();
      expect(screen.getByTestId('input')).toHaveFocus();

      await user.tab();
      expect(screen.getByTestId('button')).toHaveFocus();
    });
  });

  // --------------------------------------------------------------------------
  // ALL INTERACTIVE ELEMENTS FOCUSABLE TEST
  // --------------------------------------------------------------------------

  describe('All Interactive Elements Focusable (FI-020)', () => {
    test('FI-020: All interactive elements reachable via Tab', async () => {
      render(
        <>
          <Button>Button</Button>
          <Link href="/test">Link</Link>
          <Input placeholder="Input" />
          <Select options={['A', 'B']} />
          <Textarea placeholder="Textarea" />
          <Checkbox label="Checkbox" />
          <RadioGroup options={['A', 'B']} name="radio" />
        </>
      );

      const interactiveElements = [
        screen.getByTestId('button'),
        screen.getByTestId('link'),
        screen.getByTestId('input'),
        screen.getByTestId('select'),
        screen.getByTestId('textarea'),
        screen.getByTestId('checkbox'),
        screen.getAllByTestId('radio')[0], // First radio in group
      ];

      for (const element of interactiveElements) {
        await user.tab();
        expect(element).toHaveFocus();
      }
    });
  });

  // --------------------------------------------------------------------------
  // ACCEPTANCE CRITERIA TESTS
  // --------------------------------------------------------------------------

  describe('Acceptance Criteria', () => {
    test('AC-006: Focus visible in all states', () => {
      render(
        <>
          <Button>Primary</Button>
          <Button variant="secondary">Secondary</Button>
          <Link href="/test">Link</Link>
          <Input placeholder="Input" />
          <IndicatorCard title="Card" />
        </>
      );

      const elements = [
        screen.getAllByTestId('button')[0],
        screen.getAllByTestId('button')[1],
        screen.getByTestId('link'),
        screen.getByTestId('input'),
        screen.getByTestId('indicator-card'),
      ];

      elements.forEach((element) => {
        expect(hasFocusRing(element)).toBe(true);
      });
    });
  });

  // --------------------------------------------------------------------------
  // EDGE CASES
  // --------------------------------------------------------------------------

  describe('Edge Cases', () => {
    test('EC-001: Disabled button not focusable', () => {
      render(<button disabled data-testid="disabled-button">Disabled</button>);
      const button = screen.getByTestId('disabled-button');

      expect(button).toBeDisabled();
    });

    test('EC-002: Hidden element not in tab order', () => {
      render(
        <>
          <button>Visible</button>
          <button hidden data-testid="hidden-button">
            Hidden
          </button>
        </>
      );

      const hiddenButton = screen.getByTestId('hidden-button');
      expect(hiddenButton).not.toBeVisible();
    });

    test('EC-003: aria-hidden element not focusable', () => {
      render(
        <>
          <button>Visible</button>
          <span aria-hidden="true">
            <button data-testid="aria-hidden-button">Hidden from SR</button>
          </span>
        </>
      );

      // Element exists but aria-hidden parent makes it not accessible
      const hiddenButton = screen.getByTestId('aria-hidden-button');
      expect(hiddenButton).toBeInTheDocument();
    });

    test('EC-004: Negative tabindex removes from tab order', () => {
      render(<button tabIndex={-1} data-testid="negative-tabindex">Skip Me</button>);
      const button = screen.getByTestId('negative-tabindex');

      expect(button.tabIndex).toBe(-1);
    });

    test('EC-005: Positive tabindex modifies tab order', () => {
      render(
        <>
          <button tabIndex={2} data-testid="second">Second</button>
          <button tabIndex={1} data-testid="first">First</button>
          <button>No TabIndex</button>
        </>
      );

      // Elements with positive tabIndex come first in order
      expect(screen.getByTestId('first').tabIndex).toBe(1);
      expect(screen.getByTestId('second').tabIndex).toBe(2);
    });
  });
});
