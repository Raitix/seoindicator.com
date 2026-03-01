/**
 * AnalysisProgress Component Stories - TASK-19-11
 *
 * Storybook stories for the AnalysisProgress component.
 *
 * @see TASK-19-11
 */

import type { Meta, StoryObj } from '@storybook/react';
import { AnalysisProgress } from '@/components/analysis/AnalysisProgress';

const meta: Meta<typeof AnalysisProgress> = {
  title: 'Analysis/AnalysisProgress',
  component: AnalysisProgress,
  tags: ['autodocs'],
  argTypes: {
    jobId: {
      control: 'number',
      description: 'The ID of the analysis job to track',
    },
    onComplete: {
      action: 'complete',
      description: 'Callback when analysis completes successfully',
    },
    onCancel: {
      action: 'cancel',
      description: 'Callback when user cancels the job',
    },
    className: {
      control: 'text',
      description: 'Optional CSS class name',
    },
    pollingInterval: {
      control: 'number',
      description: 'Polling interval in ms (default: 2000)',
    },
    maxReconnectAttempts: {
      control: 'number',
      description: 'Max SSE reconnection attempts before polling fallback (default: 3)',
    },
    autoStart: {
      control: 'boolean',
      description: 'Auto-start polling on mount (default: true)',
    },
  },
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component: `
The AnalysisProgress component displays real-time progress updates for SEO analysis jobs.

## Features
- Real-time progress updates via Server-Sent Events (SSE)
- Automatic reconnection with exponential backoff
- Fallback to polling if SSE fails
- Progress bar with percentage display
- Status indicator (pending/running/completed/cancelled/failed)
- Cancel button for running jobs
- Elapsed time display
- Full accessibility support with ARIA labels

## Usage

\`\`\`tsx
import { AnalysisProgress } from '@/components/analysis/AnalysisProgress';

function MyComponent() {
  const handleComplete = (results) => {
    console.log('Analysis completed:', results);
  };

  const handleCancel = () => {
    console.log('Analysis cancelled');
  };

  return (
    <AnalysisProgress
      jobId={123}
      onComplete={handleComplete}
      onCancel={handleCancel}
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
      <div className="w-96">
        <Story />
      </div>
    ),
  ],
};

export default meta;
type Story = StoryObj<typeof AnalysisProgress>;

/**
 * Default story showing a running analysis.
 * Note: In Storybook, the SSE connection won't work without a backend.
 * This story demonstrates the UI structure.
 */
export const Default: Story = {
  args: {
    jobId: 1,
    autoStart: false,
  },
};

/**
 * Pending state - waiting for analysis to start.
 */
export const Pending: Story = {
  args: {
    jobId: 1,
    autoStart: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'The component shows a pending state before analysis starts.',
      },
    },
  },
};

/**
 * With custom polling interval.
 */
export const CustomPolling: Story = {
  args: {
    jobId: 1,
    pollingInterval: 5000,
    autoStart: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'Configure a custom polling interval (5 seconds in this example).',
      },
    },
  },
};

/**
 * With custom max reconnect attempts.
 */
export const CustomReconnect: Story = {
  args: {
    jobId: 1,
    maxReconnectAttempts: 5,
    autoStart: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'Configure the number of SSE reconnection attempts before falling back to polling.',
      },
    },
  },
};

/**
 * Manual start mode.
 */
export const ManualStart: Story = {
  args: {
    jobId: 1,
    autoStart: false,
  },
  parameters: {
    docs: {
      description: {
        story: 'The component can be configured to not auto-start. Call the start() method from the hook to begin tracking.',
      },
    },
  },
};
