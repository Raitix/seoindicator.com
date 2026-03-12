"use client";

import * as React from "react";
import { useForm } from "react-hook-form";
import { format } from "date-fns";
import {
  Annotation,
  AnnotationFormProps,
  CreateAnnotationDTO,
  ANNOTATION_CATEGORIES,
} from "@/lib/types/annotations";
import { cn } from "@/lib/utils";

/**
 * AnnotationForm Component
 *
 * A form component for creating and editing keyword annotations.
 * Supports both create and edit modes.
 *
 * @see TASK-9-8
 */
export default function AnnotationForm({
  keywordId,
  annotation,
  onSave,
  onCancel,
}: AnnotationFormProps) {
  const isEditMode = !!annotation;

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<CreateAnnotationDTO>({
    defaultValues: {
      date: annotation?.date || format(new Date(), "yyyy-MM-dd"),
      title: annotation?.title || "",
      description: annotation?.description || "",
      category: annotation?.category || "other",
    },
  });

  // Reset form when annotation prop changes
  React.useEffect(() => {
    reset({
      date: annotation?.date || format(new Date(), "yyyy-MM-dd"),
      title: annotation?.title || "",
      description: annotation?.description || "",
      category: annotation?.category || "other",
    });
  }, [annotation, reset]);

  const onSubmit = async (data: CreateAnnotationDTO) => {
    await onSave(data);

    // Reset form only in create mode
    if (!isEditMode) {
      reset({
        date: format(new Date(), "yyyy-MM-dd"),
        title: "",
        description: "",
        category: "other",
      });
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      {/* Date Field */}
      <div>
        <label
          htmlFor="date"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Date
        </label>
        <input
          type="date"
          id="date"
          {...register("date", {
            required: "Date is required",
            validate: (value) => {
              const date = new Date(value);
              return !isNaN(date.getTime()) || "Please provide a valid date";
            },
          })}
          className={cn(
            "w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500",
            errors.date ? "border-red-300" : "border-gray-300"
          )}
        />
        {errors.date && (
          <p className="mt-1 text-sm text-red-600">{errors.date.message}</p>
        )}
      </div>

      {/* Title Field */}
      <div>
        <label
          htmlFor="title"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Title
        </label>
        <input
          type="text"
          id="title"
          {...register("title", {
            required: "Title is required",
            maxLength: {
              value: 255,
              message: "Title must be 255 characters or less",
            },
          })}
          className={cn(
            "w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500",
            errors.title ? "border-red-300" : "border-gray-300"
          )}
          placeholder="Enter annotation title"
        />
        {errors.title && (
          <p className="mt-1 text-sm text-red-600">{errors.title.message}</p>
        )}
      </div>

      {/* Category Field */}
      <div>
        <label
          htmlFor="category"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Category
        </label>
        <select
          id="category"
          {...register("category")}
          className={cn(
            "w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500",
            errors.category ? "border-red-300" : "border-gray-300"
          )}
        >
          {ANNOTATION_CATEGORIES.map((cat) => (
            <option key={cat.value} value={cat.value}>
              {cat.icon} {cat.label}
            </option>
          ))}
        </select>
        {errors.category && (
          <p className="mt-1 text-sm text-red-600">{errors.category.message}</p>
        )}
      </div>

      {/* Description Field */}
      <div>
        <label
          htmlFor="description"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Description
        </label>
        <textarea
          id="description"
          rows={3}
          {...register("description")}
          className={cn(
            "w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-y",
            errors.description ? "border-red-300" : "border-gray-300"
          )}
          placeholder="Enter additional details (optional)"
        />
        {errors.description && (
          <p className="mt-1 text-sm text-red-600">
            {errors.description.message}
          </p>
        )}
      </div>

      {/* Action Buttons */}
      <div className="flex items-center justify-end gap-3 pt-2">
        <button
          type="button"
          onClick={onCancel}
          className={cn(
            "px-4 py-2 text-sm font-medium rounded-md border border-gray-300",
            "bg-white text-gray-700 hover:bg-gray-50",
            "focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          )}
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={isSubmitting}
          className={cn(
            "px-4 py-2 text-sm font-medium rounded-md text-white",
            "bg-blue-600 hover:bg-blue-700",
            "focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500",
            "disabled:opacity-50 disabled:cursor-not-allowed"
          )}
        >
          {isSubmitting ? "Saving..." : isEditMode ? "Update" : "Create"}
        </button>
      </div>
    </form>
  );
}
