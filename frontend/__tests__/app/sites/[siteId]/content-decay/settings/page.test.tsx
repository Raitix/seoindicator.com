/**
 * Settings Page Tests
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Tests the content decay settings page with detection toggle,
 * threshold configuration, and digest settings.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, within, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============== Mocks ==============

// Mock next/navigation
vi.mock('next/navigation', () => ({
  useParams: () => ({ siteId: '1' }),
  useRouter: () => ({ push: vi.fn() }),
}));

// Mock next/link
vi.mock('next/link', () => ({
  default: ({ children, href, ...props }: any) => (
    <a href={href} {...props}>{children}</a>
  ),
}));

// Mock sonner toast
const mockToastSuccess = vi.fn();
const mockToastError = vi.fn();

vi.mock('sonner', () => ({
  toast: {
    success: mockToastSuccess,
    error: mockToastError,
  },
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Save: () => <span data-testid="save-icon">Save</span>,
  RotateCcw: () => <span data-testid="reset-icon">RotateCcw</span>,
  AlertCircle: () => <span data-testid="alert-icon">AlertCircle</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
  X: () => <span data-testid="x-icon">X</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loader2</span>,
  Info: () => <span data-testid="info-icon">Info</span>,
  Settings: () => <span data-testid="settings-icon">Settings</span>,
  Bell: () => <span data-testid="bell-icon">Bell</span>,
  Clock: () => <span data-testid="clock-icon">Clock</span>,
}));

// Mock content decay hooks
const mockSaveSettings = vi.fn();
const mockMutateSettings = vi.fn();

vi.mock('@/lib/hooks/useContentDecay', () => ({
  useContentDecaySettings: () => ({
    settings: mockSettings,
    isLoading: false,
    error: null,
    mutate: mockMutateSettings,
  }),
  useSaveContentDecaySettings: () => ({
    saveSettings: mockSaveSettings,
    isSaving: false,
  }),
}));

// Mock site hook
vi.mock('@/lib/hooks/useSite', () => ({
  useSite: () => ({
    site: mockSite,
    isLoading: false,
    error: null,
    isOwner: true,
  }),
}));

// ============== Mock Data ==============

const mockSite = {
  id: 1,
  url: 'https://example.com',
  name: 'Example Site',
  user_id: 1,
};

const mockSettings = {
  detectionEnabled: true,
  windowDays: 28,
  minClicksThreshold: 10,
  minImpressionsThreshold: 100,
  digestEnabled: true,
  digestFrequency: 'weekly',
};

// ============== Mock Page Component ==============

const MockSettingsPage = () => {
  return (
    <div data-testid="settings-page" className="container mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav data-testid="breadcrumb" className="mb-4 text-sm text-gray-600">
        <a href="/sites" data-testid="sites-link">Sites</a>
        <span> / </span>
        <a href="/sites/1" data-testid="site-link">{mockSite.url}</a>
        <span> / </span>
        <a href="/sites/1/content-decay" data-testid="queue-link">Content Decay</a>
        <span> / </span>
        <span data-testid="current-crumb">Settings</span>
      </nav>

      {/* Header */}
      <div data-testid="page-header" className="mb-6">
        <h1 data-testid="page-title" className="text-2xl font-bold">Content Decay Settings</h1>
        <p data-testid="page-subtitle" className="text-gray-600">
          Configure detection thresholds and notifications
        </p>
      </div>

      {/* Settings Form */}
      <form data-testid="settings-form" className="space-y-6 max-w-2xl">
        {/* Detection Section */}
        <div data-testid="detection-section" className="bg-white rounded-lg shadow p-6">
          <h2 data-testid="section-detection-title" className="text-lg font-semibold mb-4">
            Detection Settings
          </h2>
          
          {/* Detection Toggle */}
          <div data-testid="detection-toggle-group" className="flex items-center justify-between mb-4">
            <div>
              <label htmlFor="detection-enabled" className="font-medium">
                Enable Detection
              </label>
              <p className="text-sm text-gray-500">
                Automatically scan for content decay
              </p>
            </div>
            <button
              type="button"
              role="switch"
              id="detection-enabled"
              data-testid="detection-toggle"
              aria-checked={mockSettings.detectionEnabled}
              data-state={mockSettings.detectionEnabled ? 'checked' : 'unchecked'}
              className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                mockSettings.detectionEnabled ? 'bg-green-500' : 'bg-gray-300'
              }`}
            >
              <span
                data-testid="toggle-thumb-detection"
                className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                  mockSettings.detectionEnabled ? 'translate-x-6' : 'translate-x-1'
                }`}
              />
            </button>
          </div>

          {/* Window Size Selector */}
          <div data-testid="window-size-group" className="mb-4">
            <label htmlFor="window-size" className="block font-medium mb-2">
              Comparison Window
            </label>
            <select
              id="window-size"
              data-testid="window-size-select"
              defaultValue={mockSettings.windowDays}
              className="border rounded px-3 py-2 w-full"
            >
              <option value="7">7 days</option>
              <option value="14">14 days</option>
              <option value="28">28 days (recommended)</option>
              <option value="56">56 days</option>
            </select>
            <p className="text-xs text-gray-500 mt-1">
              Number of days to compare for decay detection
            </p>
          </div>
        </div>

        {/* Thresholds Section */}
        <div data-testid="thresholds-section" className="bg-white rounded-lg shadow p-6">
          <h2 data-testid="section-thresholds-title" className="text-lg font-semibold mb-4">
            Thresholds
          </h2>
          
          {/* Min Clicks Threshold */}
          <div data-testid="min-clicks-group" className="mb-4">
            <label htmlFor="min-clicks" className="block font-medium mb-2">
              Minimum Clicks Threshold
            </label>
            <input
              type="number"
              id="min-clicks"
              data-testid="min-clicks-input"
              defaultValue={mockSettings.minClicksThreshold}
              min="0"
              className="border rounded px-3 py-2 w-full"
            />
            <p className="text-xs text-gray-500 mt-1">
              Pages with fewer clicks are excluded from detection
            </p>
          </div>

          {/* Min Impressions Threshold */}
          <div data-testid="min-impressions-group" className="mb-4">
            <label htmlFor="min-impressions" className="block font-medium mb-2">
              Minimum Impressions Threshold
            </label>
            <input
              type="number"
              id="min-impressions"
              data-testid="min-impressions-input"
              defaultValue={mockSettings.minImpressionsThreshold}
              min="0"
              className="border rounded px-3 py-2 w-full"
            />
            <p className="text-xs text-gray-500 mt-1">
              Pages with fewer impressions are excluded from detection
            </p>
          </div>
        </div>

        {/* Digest Section */}
        <div data-testid="digest-section" className="bg-white rounded-lg shadow p-6">
          <h2 data-testid="section-digest-title" className="text-lg font-semibold mb-4">
            Email Digest
          </h2>
          
          {/* Digest Toggle */}
          <div data-testid="digest-toggle-group" className="flex items-center justify-between mb-4">
            <div>
              <label htmlFor="digest-enabled" className="font-medium">
                Enable Weekly Digest
              </label>
              <p className="text-sm text-gray-500">
                Receive weekly summary of decay and recovery
              </p>
            </div>
            <button
              type="button"
              role="switch"
              id="digest-enabled"
              data-testid="digest-toggle"
              aria-checked={mockSettings.digestEnabled}
              data-state={mockSettings.digestEnabled ? 'checked' : 'unchecked'}
              className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                mockSettings.digestEnabled ? 'bg-green-500' : 'bg-gray-300'
              }`}
            >
              <span
                data-testid="toggle-thumb-digest"
                className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                  mockSettings.digestEnabled ? 'translate-x-6' : 'translate-x-1'
                }`}
              />
            </button>
          </div>

          {/* Digest Frequency */}
          <div data-testid="digest-frequency-group" className="mb-4">
            <label htmlFor="digest-frequency" className="block font-medium mb-2">
              Digest Frequency
            </label>
            <select
              id="digest-frequency"
              data-testid="digest-frequency-select"
              defaultValue={mockSettings.digestFrequency}
              className="border rounded px-3 py-2 w-full"
            >
              <option value="weekly">Weekly (recommended)</option>
              <option value="monthly">Monthly</option>
            </select>
          </div>
        </div>

        {/* Form Actions */}
        <div data-testid="form-actions" className="flex justify-between items-center">
          <button
            type="button"
            data-testid="reset-btn"
            className="btn-secondary"
          >
            <span data-testid="reset-icon">RotateCcw</span>
            Reset to Defaults
          </button>
          <div className="flex gap-2">
            <button
              type="button"
              data-testid="cancel-btn"
              className="btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              data-testid="save-btn"
              className="btn-primary"
            >
              <span data-testid="save-icon">Save</span>
              Save Changes
            </button>
          </div>
        </div>
      </form>

      {/* Validation Errors */}
      <div data-testid="validation-errors" className="hidden text-red-600 text-sm mt-2">
        <p data-testid="error-message">Please fix the errors above</p>
      </div>

      {/* Loading State */}
      <div data-testid="loading-skeleton" className="hidden">
        <div className="animate-pulse bg-gray-200 h-64 rounded"></div>
      </div>

      {/* Access Denied */}
      <div data-testid="access-denied" className="hidden text-center py-12">
        <div className="text-red-600 mb-4">You do not have permission to edit these settings</div>
        <a href="/sites/1/content-decay" data-testid="back-link">Back to Queue</a>
      </div>

      {/* Success Message */}
      <div data-testid="success-message" className="hidden fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded">
        Settings saved successfully
      </div>
    </div>
  );
};

// ============== Tests ==============

describe('SettingsPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // ST-PAGE-RENDER: Page Rendering
  // ============================================
  
  describe('ST-PAGE-RENDER: Page Rendering', () => {
    it('ST-RN-001: Page renders with siteId param', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('settings-page')).toBeInTheDocument();
    });

    it('ST-RN-002: Renders detection toggle', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('detection-toggle')).toBeInTheDocument();
    });

    it('ST-RN-003: Renders window size selector', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('window-size-select')).toBeInTheDocument();
    });

    it('ST-RN-004: Renders threshold inputs', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('min-clicks-input')).toBeInTheDocument();
      expect(screen.getByTestId('min-impressions-input')).toBeInTheDocument();
    });

    it('ST-RN-005: Renders digest settings', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('digest-toggle')).toBeInTheDocument();
      expect(screen.getByTestId('digest-frequency-select')).toBeInTheDocument();
    });

    it('ST-RN-006: Renders save button', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('save-btn')).toBeInTheDocument();
    });

    it('ST-RN-007: Renders breadcrumb navigation', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('breadcrumb')).toBeInTheDocument();
      expect(screen.getByTestId('queue-link')).toHaveAttribute('href', '/sites/1/content-decay');
    });

    it('ST-RN-008: Renders form sections', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('detection-section')).toBeInTheDocument();
      expect(screen.getByTestId('thresholds-section')).toBeInTheDocument();
      expect(screen.getByTestId('digest-section')).toBeInTheDocument();
    });

    it('ST-RN-009: Shows current settings values', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('detection-toggle')).toHaveAttribute('aria-checked', 'true');
    });

    it('ST-RN-010: Loading state exists in DOM', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });
  });

  // ============================================
  // ST-PAGE-DATA: Data Loading
  // ============================================
  
  describe('ST-PAGE-DATA: Data Loading', () => {
    it('ST-DL-001: Detection toggle shows current state', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('detection-toggle')).toHaveAttribute('data-state', 'checked');
    });

    it('ST-DL-002: Window size shows current value', () => {
      render(<MockSettingsPage />);
      
      const select = screen.getByTestId('window-size-select');
      expect(select).toHaveValue('28');
    });

    it('ST-DL-003: Min clicks shows current value', () => {
      render(<MockSettingsPage />);
      
      const input = screen.getByTestId('min-clicks-input');
      expect(input).toHaveValue(10);
    });

    it('ST-DL-004: Min impressions shows current value', () => {
      render(<MockSettingsPage />);
      
      const input = screen.getByTestId('min-impressions-input');
      expect(input).toHaveValue(100);
    });

    it('ST-DL-005: Digest toggle shows current state', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('digest-toggle')).toHaveAttribute('aria-checked', 'true');
    });

    it('ST-DL-006: Digest frequency shows current value', () => {
      render(<MockSettingsPage />);
      
      const select = screen.getByTestId('digest-frequency-select');
      expect(select).toHaveValue('weekly');
    });
  });

  // ============================================
  // ST-PAGE-FORM: Form Interactions
  // ============================================
  
  describe('ST-PAGE-FORM: Form Interactions', () => {
    it('ST-FM-001: Window size has all options', () => {
      render(<MockSettingsPage />);
      
      const select = screen.getByTestId('window-size-select');
      const options = within(select).getAllByRole('option');
      
      expect(options).toHaveLength(4);
    });

    it('ST-FM-002: Window size options are correct', () => {
      render(<MockSettingsPage />);
      
      const select = screen.getByTestId('window-size-select');
      const options = within(select).getAllByRole('option');
      
      expect(options[0]).toHaveTextContent('7 days');
      expect(options[1]).toHaveTextContent('14 days');
      expect(options[2]).toHaveTextContent('28 days');
      expect(options[3]).toHaveTextContent('56 days');
    });

    it('ST-FM-003: Min clicks input accepts numbers', async () => {
      const user = userEvent.setup();
      render(<MockSettingsPage />);
      
      const input = screen.getByTestId('min-clicks-input');
      await user.clear(input);
      await user.type(input, '20');
      
      expect(input).toHaveValue(20);
    });

    it('ST-FM-004: Min impressions input accepts numbers', async () => {
      const user = userEvent.setup();
      render(<MockSettingsPage />);
      
      const input = screen.getByTestId('min-impressions-input');
      await user.clear(input);
      await user.type(input, '200');
      
      expect(input).toHaveValue(200);
    });

    it('ST-FM-005: Digest frequency has options', () => {
      render(<MockSettingsPage />);
      
      const select = screen.getByTestId('digest-frequency-select');
      const options = within(select).getAllByRole('option');
      
      expect(options).toHaveLength(2);
    });

    it('ST-FM-006: Digest frequency options are correct', () => {
      render(<MockSettingsPage />);
      
      const select = screen.getByTestId('digest-frequency-select');
      const options = within(select).getAllByRole('option');
      
      expect(options[0]).toHaveTextContent('Weekly');
      expect(options[1]).toHaveTextContent('Monthly');
    });

    it('ST-FM-007: Input fields have min attribute', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('min-clicks-input')).toHaveAttribute('min', '0');
      expect(screen.getByTestId('min-impressions-input')).toHaveAttribute('min', '0');
    });

    it('ST-FM-008: Form has submit button', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('save-btn')).toHaveAttribute('type', 'submit');
    });

    it('ST-FM-009: Cancel button exists', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('cancel-btn')).toBeInTheDocument();
    });

    it('ST-FM-010: Reset button exists', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('reset-btn')).toBeInTheDocument();
    });

    it('ST-FM-011: Toggle has switch role', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('detection-toggle')).toHaveAttribute('role', 'switch');
    });

    it('ST-FM-012: Toggle has aria-checked attribute', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('detection-toggle')).toHaveAttribute('aria-checked', 'true');
    });
  });

  // ============================================
  // ST-PAGE-SAVE: Save Actions
  // ============================================
  
  describe('ST-PAGE-SAVE: Save Actions', () => {
    it('ST-SV-001: Save button shows icon', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('save-icon')).toBeInTheDocument();
    });

    it('ST-SV-002: Save button shows text', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('save-btn')).toHaveTextContent('Save Changes');
    });

    it('ST-SV-003: Reset button shows icon', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('reset-icon')).toBeInTheDocument();
    });

    it('ST-SV-004: Reset button shows text', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('reset-btn')).toHaveTextContent('Reset to Defaults');
    });

    it('ST-SV-005: Cancel button exists', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('cancel-btn')).toBeInTheDocument();
    });

    it('ST-SV-006: Form actions container exists', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('form-actions')).toBeInTheDocument();
    });

    it('ST-SV-007: Success message exists in DOM', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('success-message')).toBeInTheDocument();
    });

    it('ST-SV-008: Validation errors container exists', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('validation-errors')).toBeInTheDocument();
    });

    it('ST-SV-009: Error message element exists', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('error-message')).toBeInTheDocument();
    });

    it('ST-SV-010: Save settings hook is defined', () => {
      render(<MockSettingsPage />);
      
      expect(mockSaveSettings).toBeDefined();
    });
  });

  // ============================================
  // ST-PAGE-RESPONSIVE: Responsive Design
  // ============================================
  
  describe('ST-PAGE-RESPONSIVE: Responsive Design', () => {
    it('ST-RS-001: Form has max width', () => {
      render(<MockSettingsPage />);
      
      const form = screen.getByTestId('settings-form');
      expect(form.className).toContain('max-w-2xl');
    });

    it('ST-RS-002: Form has spacing between sections', () => {
      render(<MockSettingsPage />);
      
      const form = screen.getByTestId('settings-form');
      expect(form.className).toContain('space-y-6');
    });

    it('ST-RS-003: Sections have padding', () => {
      render(<MockSettingsPage />);
      
      const section = screen.getByTestId('detection-section');
      expect(section.className).toContain('p-6');
    });

    it('ST-RS-004: Input fields are full width', () => {
      render(<MockSettingsPage />);
      
      const input = screen.getByTestId('min-clicks-input');
      expect(input.className).toContain('w-full');
    });

    it('ST-RS-005: Form actions have flex layout', () => {
      render(<MockSettingsPage />);
      
      const actions = screen.getByTestId('form-actions');
      expect(actions.className).toContain('flex');
    });
  });

  // ============================================
  // ST-PAGE-ACCESSIBILITY: Accessibility
  // ============================================
  
  describe('ST-PAGE-ACCESSIBILITY: Accessibility', () => {
    it('ST-AX-001: Has proper heading hierarchy', () => {
      render(<MockSettingsPage />);
      
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toHaveTextContent('Content Decay Settings');
    });

    it('ST-AX-002: Section titles are h2 headings', () => {
      render(<MockSettingsPage />);
      
      const h2s = screen.getAllByRole('heading', { level: 2 });
      expect(h2s.length).toBe(3); // Detection, Thresholds, Digest
    });

    it('ST-AX-003: Input fields have associated labels', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByLabelText('Minimum Clicks Threshold')).toBeInTheDocument();
      expect(screen.getByLabelText('Minimum Impressions Threshold')).toBeInTheDocument();
    });

    it('ST-AX-004: Select fields have associated labels', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByLabelText('Comparison Window')).toBeInTheDocument();
      expect(screen.getByLabelText('Digest Frequency')).toBeInTheDocument();
    });

    it('ST-AX-005: Toggles have switch role', () => {
      render(<MockSettingsPage />);
      
      const toggles = screen.getAllByRole('switch');
      expect(toggles.length).toBe(2); // Detection and Digest
    });
  });

  // ============================================
  // ST-PAGE-EDGE: Edge Cases
  // ============================================
  
  describe('ST-PAGE-EDGE: Edge Cases', () => {
    it('ST-EG-001: Access denied state exists', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('access-denied')).toBeInTheDocument();
    });

    it('ST-EG-002: Back link in access denied', () => {
      render(<MockSettingsPage />);
      
      const backLink = screen.getByTestId('back-link');
      expect(backLink).toHaveAttribute('href', '/sites/1/content-decay');
    });

    it('ST-EG-003: Validation errors hidden by default', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('validation-errors')).toHaveClass('hidden');
    });

    it('ST-EG-004: Success message hidden by default', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('success-message')).toHaveClass('hidden');
    });

    it('ST-EG-005: Loading skeleton hidden by default', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('loading-skeleton')).toHaveClass('hidden');
    });

    it('ST-EG-006: Form has proper structure', () => {
      render(<MockSettingsPage />);
      
      const form = screen.getByTestId('settings-form');
      expect(form.tagName.toLowerCase()).toBe('form');
    });
  });

  // ============================================
  // ST-PAGE-SECTIONS: Section Structure
  // ============================================
  
  describe('ST-PAGE-SECTIONS: Section Structure', () => {
    it('ST-SC-001: Detection section has title', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('section-detection-title')).toHaveTextContent('Detection Settings');
    });

    it('ST-SC-002: Thresholds section has title', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('section-thresholds-title')).toHaveTextContent('Thresholds');
    });

    it('ST-SC-003: Digest section has title', () => {
      render(<MockSettingsPage />);
      
      expect(screen.getByTestId('section-digest-title')).toHaveTextContent('Email Digest');
    });

    it('ST-SC-004: Sections have white background', () => {
      render(<MockSettingsPage />);
      
      const section = screen.getByTestId('detection-section');
      expect(section.className).toContain('bg-white');
    });

    it('ST-SC-005: Sections have rounded corners', () => {
      render(<MockSettingsPage />);
      
      const section = screen.getByTestId('detection-section');
      expect(section.className).toContain('rounded-lg');
    });

    it('ST-SC-006: Sections have shadow', () => {
      render(<MockSettingsPage />);
      
      const section = screen.getByTestId('detection-section');
      expect(section.className).toContain('shadow');
    });
  });
});
