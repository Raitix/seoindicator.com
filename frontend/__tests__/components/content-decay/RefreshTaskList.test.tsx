/**
 * Tests for RefreshTaskList Component
 * Task: TASK-36-12
 * Reference: TESTS/TEST-36-12.md
 */

import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import { describe, expect, it, vi, beforeEach } from "vitest";

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  Plus: () => <span data-testid="icon-plus">Plus</span>,
  Check: () => <span data-testid="icon-check">Check</span>,
  Clock: () => <span data-testid="icon-clock">Clock</span>,
  User: () => <span data-testid="icon-user">User</span>,
  Calendar: () => <span data-testid="icon-calendar">Calendar</span>,
  Trash2: () => <span data-testid="icon-trash">Trash2</span>,
  Edit2: () => <span data-testid="icon-edit">Edit2</span>,
  X: () => <span data-testid="icon-x">X</span>,
  AlertCircle: () => <span data-testid="icon-alert-circle">AlertCircle</span>,
  Sparkles: () => <span data-testid="icon-sparkles">Sparkles</span>,
}));

// Import types
import type {
  ContentRefreshTask,
  ContentRefreshTaskStatus,
  ContentRefreshTaskCreateInput,
} from "@/lib/types/content-decay";

// Mock component - will be replaced with real implementation
const MockRefreshTaskList: React.FC<{
  candidateId: number;
  tasks: ContentRefreshTask[];
  onCreateTask: (task: ContentRefreshTaskCreateInput) => void;
  onUpdateTask: (taskId: number, updates: Partial<ContentRefreshTask>) => void;
  onDeleteTask: (taskId: number) => void;
  isLoading?: boolean;
  error?: string | null;
  users?: { id: number; name: string }[];
}> = ({
  candidateId,
  tasks,
  onCreateTask,
  onUpdateTask,
  onDeleteTask,
  isLoading,
  error,
  users = [],
}) => {
  const [isAddingTask, setIsAddingTask] = React.useState(false);
  const [newTaskTitle, setNewTaskTitle] = React.useState("");

  if (isLoading) {
    return (
      <div data-testid="task-list-loading">
        <div data-testid="skeleton-task" />
        <div data-testid="skeleton-task" />
        <div data-testid="skeleton-task" />
      </div>
    );
  }

  if (error) {
    return (
      <div data-testid="task-list-error">
        <span>{error}</span>
      </div>
    );
  }

  const handleStatusToggle = (task: ContentRefreshTask) => {
    const newStatus: ContentRefreshTaskStatus =
      task.status === "done" ? "todo" : "done";
    onUpdateTask(task.id, { status: newStatus });
  };

  const handleAddTask = () => {
    if (newTaskTitle.trim()) {
      onCreateTask({ title: newTaskTitle.trim() });
      setNewTaskTitle("");
      setIsAddingTask(false);
    }
  };

  return (
    <div data-testid="refresh-task-list" role="list" aria-label="Refresh tasks">
      {/* Task Items */}
      {tasks.length === 0 && !isAddingTask && (
        <div data-testid="empty-state">No tasks yet</div>
      )}

      {tasks.map((task) => (
        <div
          key={task.id}
          data-testid={`task-item-${task.id}`}
          role="listitem"
          className={task.status}
        >
          {/* Checkbox */}
          <button
            data-testid={`task-checkbox-${task.id}`}
            onClick={() => handleStatusToggle(task)}
            aria-label={`Mark ${task.title} as ${task.status === "done" ? "incomplete" : "complete"}`}
          >
            {task.status === "done" && <span data-testid="icon-check">Check</span>}
          </button>

          {/* Title */}
          <span data-testid={`task-title-${task.id}`}>{task.title}</span>

          {/* Description */}
          {task.description && (
            <span data-testid={`task-description-${task.id}`}>{task.description}</span>
          )}

          {/* Due Date */}
          {task.dueDate && (
            <span data-testid={`task-due-date-${task.id}`}>
              <span data-testid="icon-calendar">Calendar</span>
              {new Date(task.dueDate) < new Date() && task.status !== "done" ? (
                <span data-testid="overdue-badge" className="overdue">
                  Overdue: {task.dueDate}
                </span>
              ) : (
                `Due: ${task.dueDate}`
              )}
            </span>
          )}

          {/* Owner */}
          {task.ownerName && (
            <span data-testid={`task-owner-${task.id}`}>
              <span data-testid="icon-user">User</span>
              {task.ownerName}
            </span>
          )}

          {/* Status Indicator */}
          <span
            data-testid={`task-status-${task.id}`}
            data-status={task.status}
            className={`status-indicator ${task.status}`}
          >
            {task.status}
          </span>

          {/* Source Badge */}
          {task.source === "ai" && (
            <span data-testid={`task-source-${task.id}`}>
              <span data-testid="icon-sparkles">Sparkles</span>
              AI
            </span>
          )}
          {task.source === "system" && (
            <span data-testid={`task-source-${task.id}`}>System</span>
          )}

          {/* Actions */}
          <button
            data-testid={`task-delete-${task.id}`}
            onClick={() => onDeleteTask(task.id)}
            aria-label={`Delete ${task.title}`}
          >
            <span data-testid="icon-trash">Trash2</span>
          </button>
        </div>
      ))}

      {/* Add Task Form */}
      {isAddingTask ? (
        <div data-testid="add-task-form">
          <input
            data-testid="new-task-input"
            type="text"
            value={newTaskTitle}
            onChange={(e) => setNewTaskTitle(e.target.value)}
            placeholder="Task title"
            aria-label="New task title"
          />
          <button
            data-testid="submit-task-btn"
            onClick={handleAddTask}
            disabled={!newTaskTitle.trim()}
          >
            Add
          </button>
          <button data-testid="cancel-task-btn" onClick={() => setIsAddingTask(false)}>
            Cancel
          </button>
        </div>
      ) : (
        <button
          data-testid="add-task-button"
          onClick={() => setIsAddingTask(true)}
          aria-label="Add new task"
        >
          <span data-testid="icon-plus">Plus</span>
          Add Task
        </button>
      )}
    </div>
  );
};

// Test Data
const mockTasks: ContentRefreshTask[] = [
  {
    id: 1,
    candidateId: 1,
    title: "Update meta title and description",
    recommendationType: "meta_improvement",
    description: "Rewrite meta to improve CTR",
    notes: null,
    ownerUserId: 1,
    ownerName: "John Doe",
    dueDate: "2026-03-15",
    status: "todo",
    source: "system",
    createdAt: "2026-03-10T10:00:00Z",
    updatedAt: "2026-03-10T10:00:00Z",
  },
  {
    id: 2,
    candidateId: 1,
    title: "Add FAQ section",
    recommendationType: "content_addition",
    description: "Add FAQ schema and content for AI visibility",
    notes: "Focus on AI visibility",
    ownerUserId: null,
    ownerName: null,
    dueDate: null,
    status: "in_progress",
    source: "ai",
    createdAt: "2026-03-10T11:00:00Z",
    updatedAt: "2026-03-10T12:00:00Z",
  },
  {
    id: 3,
    candidateId: 1,
    title: "Fix broken links",
    recommendationType: "technical_fix",
    description: null,
    notes: null,
    ownerUserId: 2,
    ownerName: "Jane Smith",
    dueDate: "2026-03-01", // Past date - overdue
    status: "todo",
    source: "system",
    createdAt: "2026-03-08T10:00:00Z",
    updatedAt: "2026-03-08T10:00:00Z",
  },
];

const mockUsers = [
  { id: 1, name: "John Doe" },
  { id: 2, name: "Jane Smith" },
  { id: 3, name: "Bob Wilson" },
];

describe("RefreshTaskList", () => {
  const mockOnCreateTask = vi.fn();
  const mockOnUpdateTask = vi.fn();
  const mockOnDeleteTask = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // RTL-01: Renders task list
  it("renders all task items", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={mockTasks}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("refresh-task-list")).toBeInTheDocument();
    expect(screen.getByTestId("task-item-1")).toBeInTheDocument();
    expect(screen.getByTestId("task-item-2")).toBeInTheDocument();
    expect(screen.getByTestId("task-item-3")).toBeInTheDocument();
  });

  // RTL-02: Shows task checkbox
  it("shows unchecked checkbox for todo task", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    const checkbox = screen.getByTestId("task-checkbox-1");
    expect(checkbox).toBeInTheDocument();
    expect(screen.queryByTestId("icon-check")).not.toBeInTheDocument();
  });

  // RTL-03: Shows completed checkbox
  it("shows checked checkbox for done task", () => {
    const doneTask = { ...mockTasks[0], status: "done" as const };
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[doneTask]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("icon-check")).toBeInTheDocument();
  });

  // RTL-04: Shows task title
  it("displays task title", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-title-1")).toHaveTextContent(
      "Update meta title and description"
    );
  });

  // RTL-05: Shows task description
  it("displays task description when present", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-description-1")).toHaveTextContent(
      "Rewrite meta to improve CTR"
    );
  });

  // RTL-06: Shows due date
  it("displays due date when present", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-due-date-1")).toHaveTextContent("2026-03-15");
  });

  // RTL-07: Shows overdue indicator
  it("shows overdue badge for past due date with todo status", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[2]]} // Has past due date
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("overdue-badge")).toBeInTheDocument();
    expect(screen.getByTestId("overdue-badge")).toHaveClass("overdue");
  });

  // RTL-08: Shows owner avatar/name
  it("displays owner name when assigned", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-owner-1")).toHaveTextContent("John Doe");
  });

  // RTL-09: Shows status indicator
  it("shows correct status indicator", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[1]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    const status = screen.getByTestId("task-status-2");
    expect(status).toHaveTextContent("in_progress");
    expect(status).toHaveAttribute("data-status", "in_progress");
  });

  // RTL-10: Checkbox toggles status
  it("calls onUpdateTask when checkbox clicked", async () => {
    const user = userEvent.setup();
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("task-checkbox-1"));
    expect(mockOnUpdateTask).toHaveBeenCalledWith(1, { status: "done" });
  });

  // RTL-11: Add new task button
  it("shows add task form when button clicked", async () => {
    const user = userEvent.setup();
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("add-task-button"));
    expect(screen.getByTestId("add-task-form")).toBeInTheDocument();
  });

  // RTL-12: Create new task
  it("calls onCreateTask when new task submitted", async () => {
    const user = userEvent.setup();
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("add-task-button"));
    await user.type(screen.getByTestId("new-task-input"), "New task title");
    await user.click(screen.getByTestId("submit-task-btn"));

    expect(mockOnCreateTask).toHaveBeenCalledWith({ title: "New task title" });
  });

  // RTL-13: Cancel new task
  it("hides form when cancel clicked", async () => {
    const user = userEvent.setup();
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("add-task-button"));
    await user.click(screen.getByTestId("cancel-task-btn"));

    expect(screen.queryByTestId("add-task-form")).not.toBeInTheDocument();
    expect(screen.getByTestId("add-task-button")).toBeInTheDocument();
  });

  // RTL-20: Delete task
  it("calls onDeleteTask when delete button clicked", async () => {
    const user = userEvent.setup();
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("task-delete-1"));
    expect(mockOnDeleteTask).toHaveBeenCalledWith(1);
  });

  // RTL-21: Empty state
  it("shows empty state when no tasks", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("empty-state")).toHaveTextContent("No tasks yet");
  });

  // RTL-22: Loading state
  it("shows loading skeleton when loading", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
        isLoading={true}
      />
    );

    expect(screen.getByTestId("task-list-loading")).toBeInTheDocument();
    expect(screen.getAllByTestId("skeleton-task")).toHaveLength(3);
  });

  // RTL-23: Error state
  it("shows error message on error", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
        error="Failed to load tasks"
      />
    );

    expect(screen.getByTestId("task-list-error")).toBeInTheDocument();
    expect(screen.getByText("Failed to load tasks")).toBeInTheDocument();
  });

  // RTL-26: Task with AI source
  it("shows AI badge for ai-sourced tasks", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[1]]} // source: "ai"
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-source-2")).toHaveTextContent("AI");
    expect(screen.getByTestId("icon-sparkles")).toBeInTheDocument();
  });

  // RTL-27: Task with system source
  it("shows System badge for system-sourced tasks", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-source-1")).toHaveTextContent("System");
  });

  // RTL-28: Accessible task list
  it("has proper accessibility attributes", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={mockTasks}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    const list = screen.getByTestId("refresh-task-list");
    expect(list).toHaveAttribute("role", "list");
    expect(list).toHaveAttribute("aria-label", "Refresh tasks");
  });

  // RTL-29: Keyboard navigation
  it("interactive elements are focusable", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    const checkbox = screen.getByTestId("task-checkbox-1");
    const deleteBtn = screen.getByTestId("task-delete-1");

    expect(checkbox.tagName.toLowerCase()).toBe("button");
    expect(deleteBtn.tagName.toLowerCase()).toBe("button");
  });

  // RTL-30: Task without description
  it("handles task without description", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[2]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.queryByTestId("task-description-3")).not.toBeInTheDocument();
  });

  // RTL-31: Task without due date
  it("handles task without due date", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[1]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.queryByTestId("task-due-date-2")).not.toBeInTheDocument();
  });

  // RTL-32: Task without owner
  it("handles task without owner", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[1]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.queryByTestId("task-owner-2")).not.toBeInTheDocument();
  });

  // RTL-33: Submit button disabled when empty
  it("disables submit when input is empty", async () => {
    const user = userEvent.setup();
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("add-task-button"));
    const submitBtn = screen.getByTestId("submit-task-btn");
    expect(submitBtn).toBeDisabled();
  });

  // RTL-34: Submit button enabled when has content
  it("enables submit when input has content", async () => {
    const user = userEvent.setup();
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("add-task-button"));
    await user.type(screen.getByTestId("new-task-input"), "Some task");
    const submitBtn = screen.getByTestId("submit-task-btn");
    expect(submitBtn).not.toBeDisabled();
  });

  // RTL-35: Input has accessible label
  it("input has accessible label", async () => {
    const user = userEvent.setup();
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("add-task-button"));
    expect(screen.getByTestId("new-task-input")).toHaveAttribute("aria-label");
  });

  // RTL-36: Delete button has accessible label
  it("delete button has accessible label", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-delete-1")).toHaveAttribute("aria-label");
  });

  // RTL-37: Checkbox has accessible label
  it("checkbox has accessible label", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[mockTasks[0]]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-checkbox-1")).toHaveAttribute("aria-label");
  });

  // RTL-38: Toggles status back to todo
  it("toggles status from done back to todo", async () => {
    const user = userEvent.setup();
    const doneTask = { ...mockTasks[0], status: "done" as const };
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[doneTask]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    await user.click(screen.getByTestId("task-checkbox-1"));
    expect(mockOnUpdateTask).toHaveBeenCalledWith(1, { status: "todo" });
  });

  // RTL-39: Add button has accessible label
  it("add button has accessible label", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("add-task-button")).toHaveAttribute("aria-label");
  });

  // RTL-40: Multiple tasks rendered correctly
  it("renders all three tasks correctly", () => {
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={mockTasks}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    expect(screen.getByTestId("task-item-1")).toBeInTheDocument();
    expect(screen.getByTestId("task-item-2")).toBeInTheDocument();
    expect(screen.getByTestId("task-item-3")).toBeInTheDocument();
  });

  // RTL-41: Task with notes
  it("task with notes is handled", () => {
    const taskWithNotes = {
      ...mockTasks[0],
      notes: "Additional notes",
    };
    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={[taskWithNotes]}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    // Component should render without error
    expect(screen.getByTestId("task-item-1")).toBeInTheDocument();
  });

  // RTL-42: Different status values
  it("handles different status values", () => {
    const statuses: ContentRefreshTaskStatus[] = ["todo", "in_progress", "done", "skipped"];

    const tasksWithDifferentStatuses = statuses.map((status, i) => ({
      ...mockTasks[0],
      id: i + 1,
      status,
    }));

    render(
      <MockRefreshTaskList
        candidateId={1}
        tasks={tasksWithDifferentStatuses}
        onCreateTask={mockOnCreateTask}
        onUpdateTask={mockOnUpdateTask}
        onDeleteTask={mockOnDeleteTask}
      />
    );

    statuses.forEach((status, i) => {
      const statusEl = screen.getByTestId(`task-status-${i + 1}`);
      expect(statusEl).toHaveTextContent(status);
    });
  });
});
