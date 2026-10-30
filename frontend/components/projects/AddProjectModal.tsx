"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Loader2, Plus, Globe, FileText } from "lucide-react";

import type { Project, ProjectCreatePayload } from "@/lib/types/projects";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

// Form validation schema
const createProjectSchema = z.object({
  name: z
    .string()
    .min(1, "Project name is required")
    .max(255, "Name too long")
    .refine((val) => val.trim().length > 0, "Project name cannot be only whitespace"),
  domain: z
    .string()
    .max(500, "Domain too long")
    .refine(
      (val) =>
        !val ||
        /^[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]*(\.[a-zA-Z]{2,})+$/.test(
          val.replace(/^https?:\/\//, "").replace(/\/$/, "")
        ),
      "Invalid domain format (e.g., example.com)"
    )
    .nullable()
    .transform((val) =>
      val === ""
        ? null
        : val?.replace(/^https?:\/\//, "").replace(/\/$/, "") ?? null
    ),
  notes: z.string().max(5000, "Notes too long").nullable(),
});

type CreateProjectFormValues = z.infer<typeof createProjectSchema>;

interface AddProjectModalProps {
  onCreateProject: (payload: ProjectCreatePayload) => Promise<Project>;
  trigger?: React.ReactNode;
  defaultOpen?: boolean;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
}

export function AddProjectModal({
  onCreateProject,
  trigger,
  defaultOpen = false,
  open: controlledOpen,
  onOpenChange,
}: AddProjectModalProps) {
  const [internalOpen, setInternalOpen] = React.useState(defaultOpen);
  const [errorMessage, setErrorMessage] = React.useState<string | null>(null);

  // Use controlled open if provided, otherwise use internal state
  const open = controlledOpen !== undefined ? controlledOpen : internalOpen;

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<CreateProjectFormValues>({
    resolver: zodResolver(createProjectSchema),
    defaultValues: {
      name: "",
      domain: "",
      notes: "",
    },
  });

  const handleOpenChange = (newOpen: boolean) => {
    if (controlledOpen === undefined) {
      setInternalOpen(newOpen);
    }
    onOpenChange?.(newOpen);
    if (!newOpen) {
      reset();
      setErrorMessage(null);
    }
  };

  const onSubmit = async (values: CreateProjectFormValues) => {
    setErrorMessage(null);
    try {
      await onCreateProject({
        name: values.name.trim(),
        domain: values.domain,
        notes: values.notes?.trim() ?? null,
      });
      reset();
      handleOpenChange(false);
    } catch (error: any) {
      setErrorMessage(error?.message ?? "Failed to create project");
    }
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      {trigger ? (
        <DialogTrigger asChild>{trigger}</DialogTrigger>
      ) : (
        <DialogTrigger asChild>
          <Button className="bg-emerald-500 text-white hover:bg-emerald-600">
            <Plus className="mr-2 h-4 w-4" />
            New Project
          </Button>
        </DialogTrigger>
      )}
      <DialogContent className="border-slate-700 bg-slate-900 sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="text-white">Create New Project</DialogTitle>
          <DialogDescription className="text-slate-400">
            Create a new project to organize keywords and track SEO performance
            for a website.
          </DialogDescription>
        </DialogHeader>
        <form className="space-y-4" onSubmit={handleSubmit(onSubmit)}>
          {/* Project Name */}
          <div className="space-y-2">
            <Label htmlFor="name" className="text-slate-300">
              Project Name <span className="text-rose-400">*</span>
            </Label>
            <div className="relative">
              <FileText className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <Input
                id="name"
                {...register("name")}
                placeholder="My Website"
                className="border-slate-700 bg-slate-800/50 pl-10 text-white placeholder-slate-500"
              />
            </div>
            {errors.name && (
              <p className="text-xs text-rose-400">{errors.name.message}</p>
            )}
          </div>

          {/* Domain */}
          <div className="space-y-2">
            <Label htmlFor="domain" className="text-slate-300">
              Domain
            </Label>
            <div className="relative">
              <Globe className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <Input
                id="domain"
                {...register("domain")}
                placeholder="example.com"
                className="border-slate-700 bg-slate-800/50 pl-10 text-white placeholder-slate-500"
              />
            </div>
            {errors.domain && (
              <p className="text-xs text-rose-400">{errors.domain.message}</p>
            )}
            <p className="text-xs text-slate-500">
              Enter the website domain without the protocol (e.g., example.com)
            </p>
          </div>

          {/* Notes */}
          <div className="space-y-2">
            <Label htmlFor="notes" className="text-slate-300">
              Notes
            </Label>
            <Textarea
              id="notes"
              {...register("notes")}
              placeholder="Add any notes about this project..."
              className="min-h-[80px] border-slate-700 bg-slate-800/50 text-white placeholder-slate-500"
            />
            {errors.notes && (
              <p className="text-xs text-rose-400">{errors.notes.message}</p>
            )}
          </div>

          {/* Error Message */}
          {errorMessage && (
            <div className="rounded-md border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
              {errorMessage}
            </div>
          )}

          <DialogFooter className="gap-2 sm:gap-0">
            <Button
              type="button"
              variant="outline"
              onClick={() => handleOpenChange(false)}
              className="border-slate-700 text-slate-300"
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={isSubmitting}
              className="bg-emerald-500 text-white hover:bg-emerald-600"
            >
              {isSubmitting ? (
                <span className="flex items-center gap-2">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Creating...
                </span>
              ) : (
                <>
                  <Plus className="mr-2 h-4 w-4" />
                  Create Project
                </>
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default AddProjectModal;
