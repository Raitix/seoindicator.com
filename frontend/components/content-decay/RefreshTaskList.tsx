/**
 * RefreshTaskList Component
 * Task management for content refresh candidates
 * 
 * @see TASK-36-12 for implementation requirements
 */

"use client";

import * as React from "react";
import {
  Plus,
  Check,
  Clock,
  User,
  Calendar,
  Trash2,
  Sparkles,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import type {
  ContentRefreshTask,
  ContentRefreshTaskStatus,
  ContentRefreshTaskCreateInput,
} from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface RefreshTaskListProps {
  /** Candidate ID for the tasks */
  candidateId: number;
  /** List of tasks to display */
  tasks: ContentRefreshTask[];
  /** Callback when a new task is created */
  onCreateTask: (task: ContentRefreshTaskCreateInput) => void;
  /** Callback when a task is updated */
  onUpdateTask: (taskId: number, updates: Partial<ContentRefreshTask>) => void;
  /** Callback when a task is deleted */
  onDeleteTask: (taskId: number) => void;
  /** Show loading state */
  isLoading?: boolean;
  /** Error message to display */
  error?: string | null;
  /** Available users for assignment */
  users?: Array<{ id: number; name: string }>;
}

const STATUS_CONFIG: Record<ContentRefreshTaskStatus, { label: string; color: string }> = {
  todo: { label: "To Do", color: "bg-gray-100 text-gray-700" },
  in_progress: { label: "In Progress", color: "bg-blue-100 text-blue-700" },
  done: { label: "Done", color: "bg-green-100 text-green-700" },
  skipped: { label: "Skipped", color: "bg-yellow-100 text-yellow-700" },
};

export function RefreshTaskList({
  candidateId,
  tasks,
  onCreateTask,
  onUpdateTask,
  onDeleteTask,
  isLoading = false,
  error = null,
  users = [],
}: RefreshTaskListProps) {
  const [isAddingTask, setIsAddingTask] = React.useState(false);
  const [newTaskTitle, setNewTaskTitle] = React.useState("");
  const [newTaskDescription, setNewTaskDescription] = React.useState("");

  const handleStatusToggle = (task: ContentRefreshTask) => {
    const newStatus: ContentRefreshTaskStatus = task.status === "done" ? "todo" : "done";
    onUpdateTask(task.id, { status: newStatus });
  };

  const handleAddTask = () => {
    if (newTaskTitle.trim()) {
      onCreateTask({
        title: newTaskTitle.trim(),
        description: newTaskDescription.trim() || undefined,
      });
      setNewTaskTitle("");
      setNewTaskDescription("");
      setIsAddingTask(false);
    }
  };

  const handleCancelAdd = () => {
    setIsAddingTask(false);
    setNewTaskTitle("");
    setNewTaskDescription("");
  };

  const isOverdue = (dueDate: string | null, status: ContentRefreshTaskStatus): boolean => {
    if (!dueDate || status === "done") return false;
    return new Date(dueDate) < new Date();
  };

  if (isLoading) {
    return (
      <div data-testid="task-list-loading" className="space-y-2">
        <Skeleton data-testid="skeleton-task" className="h-12 w-full" />
        <Skeleton data-testid="skeleton-task" className="h-12 w-full" />
        <Skeleton data-testid="skeleton-task" className="h-12 w-full" />
      </div>
    );
  }

  if (error) {
    return (
      <div
        data-testid="task-list-error"
        className="text-center text-red-600 py-4"
      >
        {error}
      </div>
    );
  }

  return (
    <div
      data-testid="refresh-task-list"
      role="list"
      aria-label="Refresh tasks"
      className="space-y-2"
    >
      {/* Task Items */}
      {tasks.length === 0 && !isAddingTask && (
        <div
          data-testid="empty-state"
          className="text-center text-muted-foreground py-4"
        >
          No tasks yet
        </div>
      )}

      {tasks.map((task) => (
        <div
          key={task.id}
          data-testid={`task-item-${task.id}`}
          role="listitem"
          className={cn(
            "flex items-start gap-3 p-3 rounded-lg border bg-card",
            task.status === "done" && "opacity-60"
          )}
        >
          {/* Checkbox */}
          <button
            data-testid={`task-checkbox-${task.id}`}
            onClick={() => handleStatusToggle(task)}
            aria-label={`Mark ${task.title} as ${task.status === "done" ? "incomplete" : "complete"}`}
            className="mt-0.5"
          >
            <div
              className={cn(
                "h-5 w-5 rounded border-2 flex items-center justify-center",
                task.status === "done"
                  ? "bg-green-500 border-green-500"
                  : "border-gray-300"
              )}
            >
              {task.status === "done" && (
                <Check className="h-3 w-3 text-white" data-testid="icon-check" />
              )}
            </div>
          </button>

          {/* Content */}
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2">
              <span
                data-testid={`task-title-${task.id}`}
                className={cn(
                  "font-medium",
                  task.status === "done" && "line-through"
                )}
              >
                {task.title}
              </span>

              {/* Source Badge */}
              {task.source === "ai" && (
                <Badge
                  data-testid={`task-source-${task.id}`}
                  variant="outline"
                  className="text-xs bg-purple-50 text-purple-700 border-purple-200"
                >
                  <Sparkles className="h-3 w-3 mr-1" data-testid="icon-sparkles" />
                  AI
                </Badge>
              )}
              {task.source === "system" && (
                <Badge
                  data-testid={`task-source-${task.id}`}
                  variant="outline"
                  className="text-xs"
                >
                  System
                </Badge>
              )}
            </div>

            {task.description && (
              <p
                data-testid={`task-description-${task.id}`}
                className="text-sm text-muted-foreground mt-0.5"
              >
                {task.description}
              </p>
            )}

            {/* Meta Info */}
            <div className="flex items-center gap-3 mt-1.5 text-xs text-muted-foreground">
              {/* Due Date */}
              {task.dueDate && (
                <span
                  data-testid={`task-due-date-${task.id}`}
                  className={cn(
                    "flex items-center gap-1",
                    isOverdue(task.dueDate, task.status) && "text-red-600"
                  )}
                >
                  <Calendar className="h-3 w-3" data-testid="icon-calendar" />
                  {isOverdue(task.dueDate, task.status) ? (
                    <span data-testid="overdue-badge" className="overdue font-medium">
                      Overdue: {task.dueDate}
                    </span>
                  ) : (
                    `Due: ${task.dueDate}`
                  )}
                </span>
              )}

              {/* Owner */}
              {task.ownerName && (
                <span data-testid={`task-owner-${task.id}`} className="flex items-center gap-1">
                  <User className="h-3 w-3" data-testid="icon-user" />
                  {task.ownerName}
                </span>
              )}

              {/* Status */}
              <span
                data-testid={`task-status-${task.id}`}
                data-status={task.status}
                className={cn(
                  "px-2 py-0.5 rounded text-xs font-medium",
                  STATUS_CONFIG[task.status]?.color
                )}
              >
                {STATUS_CONFIG[task.status]?.label || task.status}
              </span>
            </div>
          </div>

          {/* Delete Button */}
          <Button
            data-testid={`task-delete-${task.id}`}
            variant="ghost"
            size="icon-xs"
            onClick={() => onDeleteTask(task.id)}
            aria-label={`Delete ${task.title}`}
            className="text-muted-foreground hover:text-red-600"
          >
            <Trash2 className="h-4 w-4" data-testid="icon-trash" />
          </Button>
        </div>
      ))}

      {/* Add Task Form */}
      {isAddingTask ? (
        <div
          data-testid="add-task-form"
          className="flex flex-col gap-2 p-3 border rounded-lg bg-muted/30"
        >
          <Input
            data-testid="new-task-input"
            type="text"
            value={newTaskTitle}
            onChange={(e) => setNewTaskTitle(e.target.value)}
            placeholder="Task title"
            aria-label="New task title"
            className="w-full"
          />
          <Input
            type="text"
            value={newTaskDescription}
            onChange={(e) => setNewTaskDescription(e.target.value)}
            placeholder="Description (optional)"
            aria-label="New task description"
            className="w-full"
          />
          <div className="flex gap-2 justify-end">
            <Button
              data-testid="cancel-task-btn"
              variant="outline"
              size="sm"
              onClick={handleCancelAdd}
            >
              Cancel
            </Button>
            <Button
              data-testid="submit-task-btn"
              size="sm"
              onClick={handleAddTask}
              disabled={!newTaskTitle.trim()}
            >
              Add
            </Button>
          </div>
        </div>
      ) : (
        <Button
          data-testid="add-task-button"
          variant="outline"
          size="sm"
          onClick={() => setIsAddingTask(true)}
          aria-label="Add new task"
          className="w-full border-dashed"
        >
          <Plus className="h-4 w-4 mr-1" data-testid="icon-plus" />
          Add Task
        </Button>
      )}
    </div>
  );
}
