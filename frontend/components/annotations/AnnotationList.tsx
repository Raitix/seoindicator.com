"use client";

import * as React from "react";
import { format } from "date-fns";
import {
  Annotation,
  AnnotationListProps,
  ANNOTATION_CATEGORIES,
} from "@/lib/types/annotations";
import { cn } from "@/lib/utils";

/**
 * AnnotationList Component
 *
 * Displays a list of annotations for a keyword with edit/delete actions.
 *
 * @see TASK-9-7
 */
export default function AnnotationList({
  keywordId,
  annotations,
  onEdit,
  onDelete,
  isLoading = false,
  error = null,
}: AnnotationListProps) {
  // Loading state
  if (isLoading) {
    return (
      <div
        data-testid="annotations-loading"
        className="flex items-center justify-center py-8"
      >
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="rounded-md bg-red-50 p-4">
        <p className="text-sm text-red-700">
          {error.message || "Failed to load annotations"}
        </p>
      </div>
    );
  }

  // Empty state
  if (annotations.length === 0) {
    return (
      <div className="text-center py-8">
        <p className="text-gray-500 text-sm">No annotations yet</p>
        <p className="text-gray-400 text-xs mt-1">
          Add your first annotation to track SEO events
        </p>
      </div>
    );
  }

  // Sort annotations by date DESC
  const sortedAnnotations = [...annotations].sort((a, b) => {
    const dateCompare = new Date(b.date).getTime() - new Date(a.date).getTime();
    if (dateCompare !== 0) return dateCompare;
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
  });

  const getCategoryLabel = (category: string) => {
    return (
      ANNOTATION_CATEGORIES.find((c) => c.value === category)?.label || category
    );
  };

  const getCategoryIcon = (category: string) => {
    return (
      ANNOTATION_CATEGORIES.find((c) => c.value === category)?.icon || "📌"
    );
  };

  const handleDeleteClick = (annotationId: number) => {
    if (window.confirm("Are you sure you want to delete this annotation?")) {
      onDelete(annotationId);
    }
  };

  return (
    <div className="space-y-4">
      {sortedAnnotations.map((annotation) => (
        <div
          key={annotation.id}
          className="border rounded-lg p-4 hover:border-gray-300 transition-colors"
        >
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1 min-w-0">
              {/* Header: Date + Category */}
              <div className="flex items-center gap-2 mb-2">
                <span className="text-sm font-medium text-gray-900">
                  {format(new Date(annotation.date), "MMM d, yyyy")}
                </span>
                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">
                  <span>{getCategoryIcon(annotation.category)}</span>
                  <span>{getCategoryLabel(annotation.category)}</span>
                </span>
              </div>

              {/* Title */}
              <h3 className="text-base font-semibold text-gray-900 truncate">
                {annotation.title}
              </h3>

              {/* Description */}
              {annotation.description && (
                <p className="mt-1 text-sm text-gray-600 line-clamp-2">
                  {annotation.description}
                </p>
              )}
            </div>

            {/* Actions */}
            <div className="flex items-center gap-2 shrink-0">
              <button
                type="button"
                onClick={() => onEdit(annotation)}
                className="text-sm text-blue-600 hover:text-blue-700 font-medium"
                aria-label={`Edit annotation: ${annotation.title}`}
              >
                Edit
              </button>
              <button
                type="button"
                onClick={() => handleDeleteClick(annotation.id)}
                className="text-sm text-red-600 hover:text-red-700 font-medium"
                aria-label={`Delete annotation: ${annotation.title}`}
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
