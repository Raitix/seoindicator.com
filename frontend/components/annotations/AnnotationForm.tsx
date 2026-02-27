"use client";

import * as React from "react";
import { useForm } from "react-hook-form";
import { format } from "date-fns";
import {
  AnnotationFormProps,
  CreateAnnotationDTO,
  Annotation,
  ANNOTATION_CATEGORIES,
} from "@/lib/types/annotations";
import { cn } from "@/lib/utils";

/**
 * AnnotationForm Component
 *
 * Form for creating and editing annotations.
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
      date: annotation?.date
        ? format(new Date(annotation.date), "yyyy-MM-dd")
        : format(new Date(), "yyyy-MM-dd"),
      title: annotation?.title || "",
      description: annotation?.description || "",
      category: annotation?.category || "other",
    },
  });

  const onSubmit = async (data: CreateAnnotationDTO) => {
    await onSave(data);
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
          placeholder="e.g., Google Core Update Impact"
          className={cn(
            "w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500",
            errors.title ? "border-red-300" : "border-gray-300"
          )}
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
          className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          {ANNOTATION_CATEGORIES.map((cat) => (
            <option key={cat.value} value={cat.value}>
              {cat.icon} {cat.label}
            </option>
          ))}
        </select>
      </div>

      {/* Description Field */}
      <div>
        <label
          htmlFor="description"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Description
          <span className="text-gray-400 font-normal ml-1">(optional)</span>
        </label>
        <textarea
          id="description"
          rows={3}
          {...register("description")}
          placeholder="Add details about this annotation..."
          className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
        />
      </div>

      {/* Actions */}
      <div className="flex items-center justify-end gap-3 pt-2">
        <button
          type="button"
          onClick={onCancel}
          className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={isSubmitting}
          className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isEditMode ? "Update" : "Create"}
        </button>
      </div>
    </form>
  );
}
