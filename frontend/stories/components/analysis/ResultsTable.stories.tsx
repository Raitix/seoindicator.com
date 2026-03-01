/**
 * ResultsTable Component Stories - TASK-19-12
 *
 * Storybook stories for the ResultsTable and ResultRow components.
 *
 * @see TASK-19-12
 */

import type { Meta, StoryObj } from '@storybook/react';
import { ResultsTable, ResultRow } from '@/components/analysis';
import { IndicatorResult } from '@/lib/types/analysis-job';

// Helper to create mock results
const createMockResult = (
  indicator: string,
  status: IndicatorResult['status'],
  percentage: number = 85,
  overrides: Partial<IndicatorResult> = {}
): IndicatorResult => ({
  indicator,
  status,
  score: Math.round(percentage * 0.85),
  max_score: 100,
  percentage,
  data: {},
  recommendations: [],
  ...overrides,
});

// Sample results for stories
const sampleResults: IndicatorResult[] = [
  createMockResult('meta_tags', 'completed', 95, {
    recommendations: ['Consider extending meta description to 150-160 characters'],
    data: { title: 'Example Page', title_length: 12, description_length: 155 },
  }),
  createMockResult('headings', 'completed', 75, {
    recommendations: ['Add H1 heading', 'Fix heading hierarchy'],
    data: { h1_count: 0, h2_count: 5 },
  }),
  createMockResult('images', 'failed', 0, {
    error_message: 'Failed to fetch images from page',
    recommendations: [],
  }),
  createMockResult('links', 'running', 50),
  createMockResult('url_structure', 'pending', 0),
  createMockResult('mobile_friendly', 'completed', 88, {
    data: { is_responsive: true, viewport_meta: true },
  }),
  createMockResult('page_speed', 'completed', 62, {
    recommendations: ['Optimize images', 'Minify CSS', 'Enable compression'],
    data: { performance_score: 62, fcp: '2.1s', lcp: '3.5s' },
  }),
  createMockResult('structured_data', 'completed', 45, {
    recommendations: ['Add JSON-LD schema', 'Include Organization schema'],
  }),
];

// ==================== ResultsTable Stories ====================

const tableMeta: Meta<typeof ResultsTable> = {
  title: 'Analysis/ResultsTable',
  component: ResultsTable,
  tags: ['autodocs'],
  argTypes: {
    jobId: {
      control: 'number',
      description: 'The ID of the analysis job',
    },
    results: {
      control: 'object',
      description: 'Array of indicator results to display',
    },
    onResultClick: {
      action: 'resultClick',
      description: 'Callback when a result row is clicked',
    },
    className: {
      control: 'text',
      description: 'Optional CSS class name',
    },
    initialSortBy: {
      control: 'select',
      options: ['status', 'score', 'name'],
      description: 'Initial sort criteria',
    },
    initialFilterBy: {
      control: 'select',
      options: ['all', 'completed', 'failed'],
      description: 'Initial filter criteria',
    },
    showSortControls: {
      control: 'boolean',
      description: 'Show sorting controls',
    },
    showFilterControls: {
      control: 'boolean',
      description: 'Show filtering controls',
    },
  },
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component: `
The ResultsTable component displays SEO analysis results in a sortable, filterable table.

## Features
- Real-time updates as results complete
- Sortable by status, score, or name
- Filterable by completion status
- Expandable row details
- Smooth animations for state changes
- Full accessibility support with ARIA live regions

## Usage

\`\`\`tsx
import { ResultsTable } from '@/components/analysis/ResultsTable';

function MyComponent() {
  const handleResultClick = (result) => {
    console.log('Clicked:', result);
  };

  return (
    <ResultsTable
      jobId={123}
      results={analysisResults}
      onResultClick={handleResultClick}
    />
  );
}
\`\`\`
        `,
      },
    },
  },
  decorators: [
    (Story) => (
      <div className="w-[600px]">
        <Story />
      </div>
    ),
  ],
};

export default tableMeta;
type TableStory = StoryObj<typeof ResultsTable>;

/**
 * Default view with mixed results
 */
export const Default: TableStory = {
  args: {
    jobId: 1,
    results: sampleResults,
  },
};

/**
 * All completed results
 */
export const AllCompleted: TableStory = {
  args: {
    jobId: 2,
    results: [
      createMockResult('meta_tags', 'completed', 95),
      createMockResult('headings', 'completed', 85),
      createMockResult('images', 'completed', 72),
      createMockResult('links', 'completed', 88),
      createMockResult('url_structure', 'completed', 100),
    ],
  },
};

/**
 * Results in progress (running and pending)
 */
export const InProgress: TableStory = {
  args: {
    jobId: 3,
    results: [
      createMockResult('meta_tags', 'completed', 95),
      createMockResult('headings', 'completed', 75),
      createMockResult('images', 'running', 50),
      createMockResult('links', 'running', 30),
      createMockResult('url_structure', 'pending', 0),
      createMockResult('mobile_friendly', 'pending', 0),
    ],
  },
};

/**
 * With failures
 */
export const WithFailures: TableStory = {
  args: {
    jobId: 4,
    results: [
      createMockResult('meta_tags', 'completed', 90),
      createMockResult('images', 'failed', 0, {
        error_message: 'Could not fetch images from page',
      }),
      createMockResult('page_speed', 'failed', 0, {
        error_message: 'Page timed out during analysis',
      }),
      createMockResult('headings', 'completed', 75),
    ],
  },
};

/**
 * Sorted by score (lowest first)
 */
export const SortedByScore: TableStory = {
  args: {
    jobId: 5,
    results: sampleResults,
    initialSortBy: 'score',
  },
};

/**
 * Sorted alphabetically
 */
export const SortedByName: TableStory = {
  args: {
    jobId: 6,
    results: sampleResults,
    initialSortBy: 'name',
  },
};

/**
 * Without sort controls
 */
export const NoSortControls: TableStory = {
  args: {
    jobId: 7,
    results: sampleResults,
    showSortControls: false,
  },
};

/**
 * Without filter controls
 */
export const NoFilterControls: TableStory = {
  args: {
    jobId: 8,
    results: sampleResults,
    showFilterControls: false,
  },
};

/**
 * Minimal controls
 */
export const MinimalControls: TableStory = {
  args: {
    jobId: 9,
    results: sampleResults,
    showSortControls: false,
    showFilterControls: false,
  },
};

/**
 * Empty state
 */
export const Empty: TableStory = {
  args: {
    jobId: 10,
    results: [],
  },
};

/**
 * Single result
 */
export const SingleResult: TableStory = {
  args: {
    jobId: 11,
    results: [createMockResult('meta_tags', 'completed', 95)],
  },
};

// ==================== ResultRow Stories ====================

const rowMeta: Meta<typeof ResultRow> = {
  title: 'Analysis/ResultRow',
  component: ResultRow,
  tags: ['autodocs'],
  argTypes: {
    result: {
      control: 'object',
      description: 'The indicator result to display',
    },
    onClick: {
      action: 'click',
      description: 'Callback when row is clicked',
    },
    isExpanded: {
      control: 'boolean',
      description: 'Whether the details section is expanded',
    },
    className: {
      control: 'text',
      description: 'Optional CSS class name',
    },
  },
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component: `
Individual row component for displaying a single indicator result.

## Features
- Status indicator with color coding
- Score display with color based on value
- Expandable details section
- Click handling for interaction
- Full accessibility support

## Usage

\`\`\`tsx
import { ResultRow } from '@/components/analysis/ResultRow';

function MyComponent() {
  const handleClick = (result) => {
    console.log('Clicked:', result);
  };

  return (
    <ResultRow
      result={indicatorResult}
      onClick={handleClick}
      isExpanded={false}
      onToggleExpand={() => setExpanded(!expanded)}
    />
  );
}
\`\`\`
        `,
      },
    },
  },
  decorators: [
    (Story) => (
      <div className="w-[500px] border rounded-lg">
        <Story />
      </div>
    ),
  ],
};

type RowStory = StoryObj<typeof ResultRow>;

/**
 * Completed result with high score
 */
export const CompletedHighScore: RowStory = {
  args: {
    result: createMockResult('meta_tags', 'completed', 95, {
      data: { title: 'Example Page', title_length: 12 },
      recommendations: ['Add meta keywords'],
    }),
    isExpanded: false,
  },
};

/**
 * Completed result with medium score
 */
export const CompletedMediumScore: RowStory = {
  args: {
    result: createMockResult('headings', 'completed', 68, {
      recommendations: ['Add H1 heading', 'Fix heading hierarchy'],
    }),
    isExpanded: false,
  },
};

/**
 * Completed result with low score
 */
export const CompletedLowScore: RowStory = {
  args: {
    result: createMockResult('page_speed', 'completed', 35, {
      recommendations: [
        'Optimize images',
        'Minify CSS',
        'Enable compression',
        'Reduce server response time',
      ],
    }),
    isExpanded: false,
  },
};

/**
 * Failed result
 */
export const Failed: RowStory = {
  args: {
    result: createMockResult('images', 'failed', 0, {
      error_message: 'Could not fetch images from page. Please check the URL.',
    }),
    isExpanded: false,
  },
};

/**
 * Running status
 */
export const Running: RowStory = {
  args: {
    result: createMockResult('links', 'running', 50),
    isExpanded: false,
  },
};

/**
 * Pending status
 */
export const Pending: RowStory = {
  args: {
    result: createMockResult('url_structure', 'pending', 0),
    isExpanded: false,
  },
};

/**
 * Expanded with details
 */
export const ExpandedWithDetails: RowStory = {
  args: {
    result: createMockResult('meta_tags', 'completed', 85, {
      data: {
        title: 'Example Page Title',
        title_length: 18,
        description: 'This is the meta description for the page.',
        description_length: 40,
        has_og_tags: true,
        has_twitter_cards: false,
      },
      recommendations: [
        'Extend meta description to 150-160 characters',
        'Add Twitter Card meta tags',
      ],
    }),
    isExpanded: true,
  },
};

/**
 * Interactive (clickable)
 */
export const Interactive: RowStory = {
  args: {
    result: createMockResult('meta_tags', 'completed', 85),
    isExpanded: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'This row is interactive. Click to trigger the onClick callback.',
      },
    },
  },
};
