"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import {
  Loader2,
  Save,
  X,
  Trash2,
  Archive,
  RotateCcw,
  AlertTriangle,
} from "lucide-react";

import type { Project } from "@/lib/types/projects";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

// Form validation schema
const projectSettingsSchema = z.object({
  name: z.string().min(1, "Project name is required").max(255, "Name too long"),
  domain: z
    .string()
    .max(500, "Domain too long")
    .refine(
      (val) => !val || /^[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]*(\.[a-zA-Z]{2,})+$/.test(val.replace(/^https?:\/\//, "").replace(/\/$/, "")),
      "Invalid domain format"
    )
    .nullable()
    .transform((val) => (val === "" ? null : val?.replace(/^https?:\/\//, "").replace(/\/$/, "") ?? null)),
  notes: z.string().max(5000, "Notes too long").nullable(),
});

type ProjectSettingsFormValues = z.infer<typeof projectSettingsSchema>;

interface ProjectSettingsViewProps {
  project: Project;
  onSave: (id: number, payload: { name?: string; domain?: string | null; notes?: string | null }) => Promise<Project>;
  onDelete: (id: number) => Promise<void>;
  onArchive: (id: number) => Promise<void>;
  onRestore: (id: number) => Promise<void>;
  onCancel?: () => void;
  className?: string;
}

export function ProjectSettingsView({
  project,
  onSave,
  onDelete,
  onArchive,
  onRestore,
  onCancel,
  className,
}: ProjectSettingsViewProps) {
  const router = useRouter();
  const [isSaving, setIsSaving] = React.useState(false);
  const [saveError, setSaveError] = React.useState<string | null>(null);
  const [showDeleteDialog, setShowDeleteDialog] = React.useState(false);
  const [showArchiveDialog, setShowArchiveDialog] = React.useState(false);
  const [deleteConfirmName, setDeleteConfirmName] = React.useState("");
  const [isDeleting, setIsDeleting] = React.useState(false);
  const [isArchiving, setIsArchiving] = React.useState(false);
  const [hasUnsavedChanges, setHasUnsavedChanges] = React.useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors, isDirty },
    reset,
    watch,
  } = useForm<ProjectSettingsFormValues>({
    resolver: zodResolver(projectSettingsSchema),
    defaultValues: {
      name: project.name,
      domain: project.domain ?? "",
      notes: project.notes ?? "",
    },
  });

  // Track unsaved changes
  React.useEffect(() => {
    setHasUnsavedChanges(isDirty);
  }, [isDirty]);

  // Warn before leaving with unsaved changes
  React.useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = "";
      }
    };

    window.addEventListener("beforeunload", handleBeforeUnload);
    return () => window.removeEventListener("beforeunload", handleBeforeUnload);
  }, [hasUnsavedChanges]);

  // Reset form when project changes
  React.useEffect(() => {
    reset({
      name: project.name,
      domain: project.domain ?? "",
      notes: project.notes ?? "",
    });
    setHasUnsavedChanges(false);
  }, [project, reset]);

  const onSubmit = async (values: ProjectSettingsFormValues) => {
    setIsSaving(true);
    setSaveError(null);

    try {
      await onSave(project.id, {
        name: values.name,
        domain: values.domain,
        notes: values.notes,
      });
      setHasUnsavedChanges(false);
      // Show success feedback (could use toast)
    } catch (error: any) {
      setSaveError(error?.message ?? "Failed to save changes");
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancel = () => {
    if (hasUnsavedChanges) {
      const confirm = window.confirm(
        "You have unsaved changes. Are you sure you want to discard them?"
      );
      if (!confirm) return;
    }
    reset();
    setHasUnsavedChanges(false);
    onCancel?.();
  };

  const handleDelete = async () => {
    if (deleteConfirmName !== project.name) return;

    setIsDeleting(true);
    try {
      await onDelete(project.id);
      setShowDeleteDialog(false);
      router.push("/projects");
    } catch (error) {
      // Error handled by parent
    } finally {
      setIsDeleting(false);
    }
  };

  const handleArchive = async () => {
    setIsArchiving(true);
    try {
      await onArchive(project.id);
      setShowArchiveDialog(false);
      router.push("/projects");
    } catch (error) {
      // Error handled by parent
    } finally {
      setIsArchiving(false);
    }
  };

  const handleRestore = async () => {
    setIsArchiving(true);
    try {
      await onRestore(project.id);
    } catch (error) {
      // Error handled by parent
    } finally {
      setIsArchiving(false);
    }
  };

  return (
    <div className={cn("space-y-6", className)}>
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white">Project Settings</h1>
          <p className="text-sm text-slate-400">
            Manage your project configuration
          </p>
        </div>
        {project.status === "archived" && (
          <div className="flex items-center gap-2 rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-sm text-amber-300">
            <Archive className="h-4 w-4" />
            Archived
          </div>
        )}
      </div>

      {/* Settings Form */}
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardTitle className="text-lg text-white">General Settings</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Project Name */}
            <div className="space-y-2">
              <Label htmlFor="name" className="text-slate-300">
                Project Name <span className="text-rose-400">*</span>
              </Label>
              <Input
                id="name"
                {...register("name")}
                className="border-slate-700 bg-slate-800/50 text-white"
                placeholder="My Project"
              />
              {errors.name && (
                <p className="text-xs text-rose-400">{errors.name.message}</p>
              )}
            </div>

            {/* Domain */}
            <div className="space-y-2">
              <Label htmlFor="domain" className="text-slate-300">
                Domain
              </Label>
              <Input
                id="domain"
                {...register("domain")}
                className="border-slate-700 bg-slate-800/50 text-white"
                placeholder="example.com"
              />
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
                className="border-slate-700 bg-slate-800/50 text-white min-h-[100px]"
                placeholder="Add any notes about this project..."
              />
              {errors.notes && (
                <p className="text-xs text-rose-400">{errors.notes.message}</p>
              )}
            </div>

            {/* Error Message */}
            {saveError && (
              <div className="rounded-md border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
                {saveError}
              </div>
            )}

            {/* Action Buttons */}
            <div className="flex items-center gap-3 pt-4">
              <Button
                type="submit"
                disabled={isSaving || !isDirty}
                className="bg-emerald-500 text-white hover:bg-emerald-600 disabled:opacity-50"
              >
                {isSaving ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Saving...
                  </>
                ) : (
                  <>
                    <Save className="mr-2 h-4 w-4" />
                    Save Changes
                  </>
                )}
              </Button>
              <Button
                type="button"
                variant="outline"
                onClick={handleCancel}
                disabled={!isDirty && !hasUnsavedChanges}
                className="border-slate-700 text-slate-300 hover:bg-slate-800"
              >
                <X className="mr-2 h-4 w-4" />
                Cancel
              </Button>
            </div>
          </CardContent>
        </Card>
      </form>

      {/* Danger Zone */}
      <Card className="border-rose-500/30 bg-slate-900/60">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-lg text-rose-400">
            <AlertTriangle className="h-5 w-5" />
            Danger Zone
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-sm text-slate-400">
            These actions are irreversible. Please proceed with caution.
          </p>

          <div className="flex flex-col gap-3 sm:flex-row">
            {project.status === "active" ? (
              <Button
                variant="outline"
                onClick={() => setShowArchiveDialog(true)}
                className="border-amber-500/50 text-amber-400 hover:bg-amber-500/10"
              >
                <Archive className="mr-2 h-4 w-4" />
                Archive Project
              </Button>
            ) : (
              <Button
                variant="outline"
                onClick={handleRestore}
                disabled={isArchiving}
                className="border-emerald-500/50 text-emerald-400 hover:bg-emerald-500/10"
              >
                {isArchiving ? (
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                ) : (
                  <RotateCcw className="mr-2 h-4 w-4" />
                )}
                Restore Project
              </Button>
            )}

            <Button
              variant="outline"
              onClick={() => setShowDeleteDialog(true)}
              className="border-rose-500/50 text-rose-400 hover:bg-rose-500/10"
            >
              <Trash2 className="mr-2 h-4 w-4" />
              Delete Project
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <DialogContent className="border-slate-700 bg-slate-900">
          <DialogHeader>
            <DialogTitle className="text-rose-400">Delete Project</DialogTitle>
            <DialogDescription className="text-slate-400">
              This action cannot be undone. All keywords, competitors, and reports
              associated with this project will be permanently deleted.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <p className="text-sm text-slate-300">
              Type <strong className="text-white">{project.name}</strong> to confirm:
            </p>
            <Input
              value={deleteConfirmName}
              onChange={(e) => setDeleteConfirmName(e.target.value)}
              placeholder="Project name"
              className="border-slate-700 bg-slate-800 text-white"
            />
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setShowDeleteDialog(false)}
              className="border-slate-700 text-slate-300"
            >
              Cancel
            </Button>
            <Button
              onClick={handleDelete}
              disabled={deleteConfirmName !== project.name || isDeleting}
              className="bg-rose-500 text-white hover:bg-rose-600 disabled:opacity-50"
            >
              {isDeleting ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Deleting...
                </>
              ) : (
                <>
                  <Trash2 className="mr-2 h-4 w-4" />
                  Delete Project
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Archive Confirmation Dialog */}
      <Dialog open={showArchiveDialog} onOpenChange={setShowArchiveDialog}>
        <DialogContent className="border-slate-700 bg-slate-900">
          <DialogHeader>
            <DialogTitle className="text-amber-400">Archive Project</DialogTitle>
            <DialogDescription className="text-slate-400">
              Archiving this project will hide it from your active projects list.
              You can restore it later from the settings.
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <p className="text-sm text-slate-300">
              Are you sure you want to archive <strong className="text-white">{project.name}</strong>?
            </p>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setShowArchiveDialog(false)}
              className="border-slate-700 text-slate-300"
            >
              Cancel
            </Button>
            <Button
              onClick={handleArchive}
              disabled={isArchiving}
              className="bg-amber-500 text-white hover:bg-amber-600"
            >
              {isArchiving ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Archiving...
                </>
              ) : (
                <>
                  <Archive className="mr-2 h-4 w-4" />
                  Archive Project
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

export default ProjectSettingsView;
