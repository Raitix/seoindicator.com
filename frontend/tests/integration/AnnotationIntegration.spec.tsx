/**
 * Frontend Integration Tests for Annotations
 * 
 * Tests for AnnotationList and AnnotationForm components.
 * Validates component behavior, API integration, and user interactions.
 * 
 * @see TEST-9-10 for full test documentation
 */

// Using Jest globals - no import needed
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import AnnotationList from '@/components/annotations/AnnotationList';
import AnnotationForm from '@/components/annotations/AnnotationForm';
import type { Annotation } from '@/lib/types/annotations';

// ============================================
// Mock Data
// ============================================

const mockAnnotations: Annotation[] = [
  {
    id: 1,
    keyword_id: 42,
    project_id: 1,
    date: '2026-02-27',
    title: 'Google Core Update Impact',
    description: 'Position dropped from #5 to #12',
    category: 'algorithm-update',
    created_at: '2026-02-27T10:00:00Z',
    updated_at: '2026-02-27T10:00:00Z',
  },
  {
    id: 2,
    keyword_id: 42,
    project_id: 1,
    date: '2026-02-20',
    title: 'Content Published',
    description: 'New blog post targeting this keyword',
    category: 'content-update',
    created_at: '2026-02-20T14:00:00Z',
    updated_at: '2026-02-20T14:00:00Z',
  },
  {
    id: 3,
    keyword_id: 42,
    project_id: 1,
    date: '2026-02-15',
    title: 'Backlink Acquired',
    description: 'High DA backlink from partner site',
    category: 'link-building',
    created_at: '2026-02-15T09:00:00Z',
    updated_at: '2026-02-15T09:00:00Z',
  },
];

const createQueryClient = () => new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
    },
  },
});

// ============================================
// AnnotationList Component Tests (FE-001 to FE-005, FE-014, FE-015)
// ============================================

describe('AnnotationList Component', () => {
  const mockOnEdit = vi.fn();
  const mockOnDelete = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  const renderAnnotationList = (props: Partial<React.ComponentProps<typeof AnnotationList>> = {}) => {
    const queryClient = createQueryClient();
    return render(
      <QueryClientProvider client={queryClient}>
        <AnnotationList
          keywordId={42}
          annotations={mockAnnotations}
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          {...props}
        />
      </QueryClientProvider>
    );
  };

  /** @test FE-001: AnnotationList renders empty state */
  it('renders empty state when no annotations', () => {
    renderAnnotationList({ annotations: [] });
    
    expect(screen.getByText(/no annotations/i)).toBeInTheDocument();
  });

  /** @test FE-002: AnnotationList renders annotations */
  it('renders all annotations', () => {
    renderAnnotationList();
    
    expect(screen.getByText('Google Core Update Impact')).toBeInTheDocument();
    expect(screen.getByText('Content Published')).toBeInTheDocument();
    expect(screen.getByText('Backlink Acquired')).toBeInTheDocument();
  });

  /** @test FE-003: AnnotationList renders chronologically */
  it('renders annotations ordered by date DESC', () => {
    renderAnnotationList();
    
    const titles = screen.getAllByRole('heading', { level: 3 });
    
    expect(titles[0]).toHaveTextContent('Google Core Update Impact'); // 2026-02-27
    expect(titles[1]).toHaveTextContent('Content Published'); // 2026-02-20
    expect(titles[2]).toHaveTextContent('Backlink Acquired'); // 2026-02-15
  });

  /** @test FE-004: AnnotationList edit button fires callback */
  it('fires onEdit callback when edit button clicked', async () => {
    const user = userEvent.setup();
    renderAnnotationList();
    
    const editButtons = screen.getAllByRole('button', { name: /edit/i });
    await user.click(editButtons[0]);
    
    expect(mockOnEdit).toHaveBeenCalledWith(mockAnnotations[0]);
    expect(mockOnEdit).toHaveBeenCalledTimes(1);
  });

  /** @test FE-005: AnnotationList delete button fires callback */
  it('fires onDelete callback when delete button clicked', async () => {
    const user = userEvent.setup();
    renderAnnotationList();
    
    const deleteButtons = screen.getAllByRole('button', { name: /delete/i });
    await user.click(deleteButtons[0]);
    
    expect(mockOnDelete).toHaveBeenCalledWith(1);
    expect(mockOnDelete).toHaveBeenCalledTimes(1);
  });

  /** @test FE-014: AnnotationList handles loading state */
  it('shows loading state when isLoading is true', () => {
    renderAnnotationList({ annotations: [], isLoading: true });
    
    expect(screen.getByTestId('annotations-loading')).toBeInTheDocument();
  });

  /** @test FE-015: AnnotationList handles error state */
  it('shows error message when error is provided', () => {
    const error = new Error('Failed to load annotations');
    renderAnnotationList({ annotations: [], error });
    
    expect(screen.getByText(/failed to load annotations/i)).toBeInTheDocument();
  });
});

// ============================================
// AnnotationForm Component Tests (FE-006 to FE-013)
// ============================================

describe('AnnotationForm Component', () => {
  const mockOnSave = vi.fn();
  const mockOnCancel = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  const renderAnnotationForm = (props: Partial<React.ComponentProps<typeof AnnotationForm>> = {}) => {
    const queryClient = createQueryClient();
    return render(
      <QueryClientProvider client={queryClient}>
        <AnnotationForm
          keywordId={42}
          onSave={mockOnSave}
          onCancel={mockOnCancel}
          {...props}
        />
      </QueryClientProvider>
    );
  };

  /** @test FE-006: AnnotationForm renders in create mode */
  it('renders in create mode with empty fields', () => {
    renderAnnotationForm();
    
    expect(screen.getByLabelText(/title/i)).toHaveValue('');
    expect(screen.getByLabelText(/date/i)).toHaveValue('');
    expect(screen.getByRole('button', { name: /create|save/i })).toBeInTheDocument();
  });

  /** @test FE-007: AnnotationForm renders in edit mode */
  it('renders in edit mode with pre-filled fields', () => {
    renderAnnotationForm({ annotation: mockAnnotations[0] });
    
    expect(screen.getByLabelText(/title/i)).toHaveValue('Google Core Update Impact');
    expect(screen.getByLabelText(/description/i)).toHaveValue('Position dropped from #5 to #12');
    expect(screen.getByRole('button', { name: /update|save/i })).toBeInTheDocument();
  });

  /** @test FE-008: AnnotationForm validates required fields */
  it('shows validation error when title is empty', async () => {
    const user = userEvent.setup();
    renderAnnotationForm();
    
    const submitButton = screen.getByRole('button', { name: /create|save/i });
    await user.click(submitButton);
    
    await waitFor(() => {
      expect(screen.getByText(/title is required/i)).toBeInTheDocument();
    });
    
    expect(mockOnSave).not.toHaveBeenCalled();
  });

  /** @test FE-009: AnnotationForm date field validation */
  it('shows validation error for invalid date', async () => {
    const user = userEvent.setup();
    renderAnnotationForm();
    
    const dateInput = screen.getByLabelText(/date/i);
    await user.clear(dateInput);
    await user.type(dateInput, 'invalid-date');
    
    const submitButton = screen.getByRole('button', { name: /create|save/i });
    await user.click(submitButton);
    
    await waitFor(() => {
      expect(screen.getByText(/valid date/i)).toBeInTheDocument();
    });
  });

  /** @test FE-010: AnnotationForm category dropdown */
  it('shows category dropdown with predefined options', async () => {
    const user = userEvent.setup();
    renderAnnotationForm();
    
    const categorySelect = screen.getByLabelText(/category/i);
    await user.click(categorySelect);
    
    // Check for some category options
    expect(screen.getByRole('option', { name: /algorithm/i })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /content/i })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: /link/i })).toBeInTheDocument();
  });

  /** @test FE-011: AnnotationForm submit fires onSave */
  it('fires onSave with form data on submit', async () => {
    const user = userEvent.setup();
    renderAnnotationForm();
    
    await user.type(screen.getByLabelText(/title/i), 'New Annotation');
    await user.type(screen.getByLabelText(/date/i), '2026-02-27');
    await user.selectOptions(screen.getByLabelText(/category/i), 'algorithm-update');
    
    const submitButton = screen.getByRole('button', { name: /create|save/i });
    await user.click(submitButton);
    
    await waitFor(() => {
      expect(mockOnSave).toHaveBeenCalledWith({
        title: 'New Annotation',
        date: '2026-02-27',
        category: 'algorithm-update',
        description: '',
      });
    });
  });

  /** @test FE-012: AnnotationForm cancel fires onCancel */
  it('fires onCancel when cancel button clicked', async () => {
    const user = userEvent.setup();
    renderAnnotationForm();
    
    const cancelButton = screen.getByRole('button', { name: /cancel/i });
    await user.click(cancelButton);
    
    expect(mockOnCancel).toHaveBeenCalledTimes(1);
  });

  /** @test FE-013: AnnotationForm clears on successful create */
  it('clears fields after successful creation', async () => {
    const user = userEvent.setup();
    mockOnSave.mockResolvedValueOnce(undefined);
    
    renderAnnotationForm();
    
    await user.type(screen.getByLabelText(/title/i), 'Test Annotation');
    await user.type(screen.getByLabelText(/date/i), '2026-02-27');
    
    const submitButton = screen.getByRole('button', { name: /create|save/i });
    await user.click(submitButton);
    
    await waitFor(() => {
      expect(screen.getByLabelText(/title/i)).toHaveValue('');
      expect(screen.getByLabelText(/date/i)).toHaveValue('');
    });
  });
});

// ============================================
// Full Workflow Integration Tests (WF-001 to WF-003)
// ============================================

describe('Annotation Workflow Integration', () => {
  const mockOnSave = vi.fn();
  const mockOnEdit = vi.fn();
  const mockOnDelete = vi.fn();
  const mockOnCancel = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  /** @test WF-001: Create annotation workflow */
  it('completes create annotation workflow', async () => {
    const user = userEvent.setup();
    const queryClient = createQueryClient();
    
    const { rerender } = render(
      <QueryClientProvider client={queryClient}>
        <AnnotationForm
          keywordId={42}
          onSave={mockOnSave}
          onCancel={mockOnCancel}
        />
      </QueryClientProvider>
    );
    
    // Fill form
    await user.type(screen.getByLabelText(/title/i), 'New Workflow Annotation');
    await user.type(screen.getByLabelText(/date/i), '2026-02-27');
    await user.type(screen.getByLabelText(/description/i), 'Test workflow');
    
    // Submit
    await user.click(screen.getByRole('button', { name: /create|save/i }));
    
    expect(mockOnSave).toHaveBeenCalled();
  });

  /** @test WF-002: Edit annotation workflow */
  it('completes edit annotation workflow', async () => {
    const user = userEvent.setup();
    const queryClient = createQueryClient();
    
    render(
      <QueryClientProvider client={queryClient}>
        <AnnotationForm
          keywordId={42}
          annotation={mockAnnotations[0]}
          onSave={mockOnSave}
          onCancel={mockOnCancel}
        />
      </QueryClientProvider>
    );
    
    // Modify description
    const descriptionInput = screen.getByLabelText(/description/i);
    await user.clear(descriptionInput);
    await user.type(descriptionInput, 'Updated description');
    
    // Submit
    await user.click(screen.getByRole('button', { name: /update|save/i }));
    
    expect(mockOnSave).toHaveBeenCalledWith(
      expect.objectContaining({
        description: expect.stringContaining('Updated'),
      })
    );
  });

  /** @test WF-003: Delete annotation workflow */
  it('completes delete annotation workflow', async () => {
    const user = userEvent.setup();
    const queryClient = createQueryClient();
    
    // Mock window.confirm
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    
    render(
      <QueryClientProvider client={queryClient}>
        <AnnotationList
          keywordId={42}
          annotations={mockAnnotations}
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
        />
      </QueryClientProvider>
    );
    
    // Click delete on first annotation
    const deleteButtons = screen.getAllByRole('button', { name: /delete/i });
    await user.click(deleteButtons[0]);
    
    expect(mockOnDelete).toHaveBeenCalledWith(1);
  });
});

// ============================================
// Category Dropdown Tests
// ============================================

describe('Category Dropdown Options', () => {
  const expectedCategories = [
    { value: 'algorithm-update', label: /algorithm/i },
    { value: 'content-update', label: /content/i },
    { value: 'technical-change', label: /technical/i },
    { value: 'link-building', label: /link/i },
    { value: 'competition', label: /competitor/i },
    { value: 'seasonal', label: /seasonal/i },
    { value: 'manual-action', label: /manual/i },
    { value: 'recovery', label: /recovery/i },
    { value: 'other', label: /other/i },
  ];

  it('displays all expected category options', async () => {
    const user = userEvent.setup();
    const queryClient = createQueryClient();
    
    render(
      <QueryClientProvider client={queryClient}>
        <AnnotationForm
          keywordId={42}
          onSave={vi.fn()}
          onCancel={vi.fn()}
        />
      </QueryClientProvider>
    );
    
    const categorySelect = screen.getByLabelText(/category/i);
    await user.click(categorySelect);
    
    for (const category of expectedCategories) {
      expect(screen.getByRole('option', { name: category.label })).toBeInTheDocument();
    }
  });
});
