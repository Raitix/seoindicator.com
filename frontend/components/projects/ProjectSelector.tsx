"use client";

import * as React from "react";
import { ChevronDown, Plus, Loader2, AlertCircle, RefreshCw } from "lucide-react";

import type { Project } from "@/lib/types/projects";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";

interface ProjectSelectorProps {
  projects: Project[];
  currentProject: Project | null;
  onSelect: (projectId: number) => void;
  onCreateNew: () => void;
  isLoading?: boolean;
  error?: Error | null;
  onRetry?: () => void;
  className?: string;
}

export function ProjectSelector({
  projects,
  currentProject,
  onSelect,
  onCreateNew,
  isLoading = false,
  error = null,
  onRetry,
  className,
}: ProjectSelectorProps) {
  const [isOpen, setIsOpen] = React.useState(false);
  const [highlightedIndex, setHighlightedIndex] = React.useState(-1);
  const dropdownRef = React.useRef<HTMLDivElement>(null);
  const buttonRef = React.useRef<HTMLButtonElement>(null);
  const listRef = React.useRef<HTMLUListElement>(null);

  const activeProjects = React.useMemo(
    () => projects.filter((p) => p.status === "active"),
    [projects]
  );

  // Close dropdown on outside click
  React.useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  // Reset highlighted index when dropdown opens
  React.useEffect(() => {
    if (isOpen) {
      const currentIndex = activeProjects.findIndex((p) => p.id === currentProject?.id);
      setHighlightedIndex(currentIndex >= 0 ? currentIndex : 0);
    }
  }, [isOpen, activeProjects, currentProject]);

  // Scroll highlighted item into view
  React.useEffect(() => {
    if (isOpen && listRef.current && highlightedIndex >= 0) {
      const items = listRef.current.querySelectorAll("[role='option']");
      const highlightedItem = items[highlightedIndex];
      if (highlightedItem) {
        highlightedItem.scrollIntoView({ block: "nearest" });
      }
    }
  }, [isOpen, highlightedIndex]);

  // Keyboard navigation
  const handleKeyDown = React.useCallback(
    (event: React.KeyboardEvent) => {
      if (isLoading) return;

      switch (event.key) {
        case "Enter":
        case " ":
          event.preventDefault();
          if (isOpen && highlightedIndex >= 0) {
            const selected = activeProjects[highlightedIndex];
            if (selected) {
              onSelect(selected.id);
              setIsOpen(false);
              buttonRef.current?.focus();
            }
          } else {
            setIsOpen(true);
          }
          break;
        case "ArrowDown":
          event.preventDefault();
          if (!isOpen) {
            setIsOpen(true);
          } else {
            setHighlightedIndex((prev) =>
              prev < activeProjects.length ? prev + 1 : prev
            );
          }
          break;
        case "ArrowUp":
          event.preventDefault();
          if (isOpen) {
            setHighlightedIndex((prev) => (prev > 0 ? prev - 1 : 0));
          }
          break;
        case "Escape":
          event.preventDefault();
          setIsOpen(false);
          buttonRef.current?.focus();
          break;
        case "Home":
          if (isOpen) {
            event.preventDefault();
            setHighlightedIndex(0);
          }
          break;
        case "End":
          if (isOpen) {
            event.preventDefault();
            setHighlightedIndex(activeProjects.length);
          }
          break;
      }
    },
    [isOpen, highlightedIndex, activeProjects, onSelect, isLoading]
  );

  const handleSelect = (project: Project) => {
    onSelect(project.id);
    setIsOpen(false);
    buttonRef.current?.focus();
  };

  const handleCreateNew = () => {
    setIsOpen(false);
    onCreateNew();
  };

  // Truncate long names
  const truncateName = (name: string, maxLength = 30) => {
    if (name.length <= maxLength) return name;
    return `${name.slice(0, maxLength)}...`;
  };

  // Loading state
  if (isLoading) {
    return (
      <div
        className={cn(
          "flex items-center gap-2 rounded-md border border-slate-700 bg-slate-800/50 px-3 py-2 text-slate-400",
          className
        )}
      >
        <Loader2 className="h-4 w-4 animate-spin" />
        <span className="text-sm">Loading projects...</span>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div
        className={cn(
          "flex items-center gap-2 rounded-md border border-rose-500/50 bg-rose-500/10 px-3 py-2",
          className
        )}
      >
        <AlertCircle className="h-4 w-4 text-rose-400" />
        <span className="text-sm text-rose-300">Failed to load</span>
        {onRetry && (
          <button
            onClick={onRetry}
            className="ml-1 rounded p-1 hover:bg-rose-500/20"
            aria-label="Retry"
          >
            <RefreshCw className="h-3 w-3 text-rose-400" />
          </button>
        )}
      </div>
    );
  }

  // Empty state
  if (activeProjects.length === 0) {
    return (
      <Button
        onClick={onCreateNew}
        variant="outline"
        className={cn("border-dashed", className)}
      >
        <Plus className="mr-2 h-4 w-4" />
        Create your first project
      </Button>
    );
  }

  return (
    <div ref={dropdownRef} className={cn("relative", className)}>
      <button
        ref={buttonRef}
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        onKeyDown={handleKeyDown}
        aria-expanded={isOpen}
        aria-haspopup="listbox"
        aria-label="Select project"
        className={cn(
          "flex items-center gap-2 rounded-md border border-slate-700 bg-slate-800/50 px-3 py-2 text-left",
          "hover:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/50",
          "transition-colors"
        )}
      >
        <span className="flex-1 truncate text-sm font-medium text-white">
          {currentProject ? truncateName(currentProject.name) : "Select project"}
        </span>
        <ChevronDown
          className={cn(
            "h-4 w-4 text-slate-400 transition-transform",
            isOpen && "rotate-180"
          )}
        />
      </button>

      {isOpen && (
        <div
          className="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-md border border-slate-700 bg-slate-900 shadow-lg"
          role="listbox"
          aria-label="Projects"
        >
          <ul ref={listRef} className="max-h-60 overflow-y-auto py-1">
            {activeProjects.map((project, index) => (
              <li key={project.id}>
                <button
                  type="button"
                  role="option"
                  aria-selected={project.id === currentProject?.id}
                  onClick={() => handleSelect(project)}
                  onMouseEnter={() => setHighlightedIndex(index)}
                  className={cn(
                    "flex w-full items-center gap-2 px-3 py-2 text-left text-sm",
                    "transition-colors",
                    project.id === currentProject?.id
                      ? "bg-emerald-500/20 text-emerald-200"
                      : highlightedIndex === index
                        ? "bg-slate-800 text-white"
                        : "text-slate-300 hover:bg-slate-800"
                  )}
                >
                  <span className="flex-1 truncate">
                    {truncateName(project.name, 40)}
                  </span>
                  {project.domain && (
                    <span className="truncate text-xs text-slate-500">
                      {project.domain}
                    </span>
                  )}
                </button>
              </li>
            ))}
          </ul>

          <div className="border-t border-slate-700">
            <button
              type="button"
              onClick={handleCreateNew}
              className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-emerald-400 hover:bg-emerald-500/10"
            >
              <Plus className="h-4 w-4" />
              <span>Create new project</span>
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default ProjectSelector;
