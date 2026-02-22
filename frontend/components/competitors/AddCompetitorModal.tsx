"use client";

import * as React from "react";
import { useForm } from "react-hook-form";
import { Loader2, Plus } from "lucide-react";

import { useCreateCompetitor } from "@/lib/api/competitors";
import { sanitizeDomain, validateDomain } from "@/lib/validators/domain";
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

interface AddCompetitorFormValues {
  domain: string;
  displayName: string;
  notes: string;
}

export default function AddCompetitorModal() {
  const [open, setOpen] = React.useState(false);
  const [errorMessage, setErrorMessage] = React.useState<string | null>(null);
  const createCompetitor = useCreateCompetitor();

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<AddCompetitorFormValues>({
    mode: "onChange",
    defaultValues: {
      domain: "",
      displayName: "",
      notes: "",
    },
  });

  const onSubmit = async (values: AddCompetitorFormValues) => {
    setErrorMessage(null);
    try {
      await createCompetitor({
        domain: sanitizeDomain(values.domain),
        displayName: values.displayName,
        notes: values.notes,
      });
      reset();
      setOpen(false);
    } catch (error: any) {
      setErrorMessage(error?.message ?? "Unable to create competitor");
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button className="bg-emerald-500 text-white hover:bg-emerald-600">
          <Plus className="mr-2 h-4 w-4" />
          Add Competitor
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add competitor</DialogTitle>
          <DialogDescription>
            Track a competitor domain to compare keyword performance.
          </DialogDescription>
        </DialogHeader>
        <form className="space-y-4" onSubmit={handleSubmit(onSubmit)}>
          <div className="space-y-2">
            <Label htmlFor="domain">Domain</Label>
            <Input
              id="domain"
              placeholder="example.com"
              className="bg-slate-900 text-white"
              {...register("domain", {
                validate: validateDomain,
              })}
            />
            {errors.domain && (
              <p className="text-xs text-rose-400">
                {errors.domain.message}
              </p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="displayName">Display name</Label>
            <Input
              id="displayName"
              placeholder="Competitor Name"
              className="bg-slate-900 text-white"
              {...register("displayName")}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="notes">Notes</Label>
            <Textarea
              id="notes"
              placeholder="Optional notes about this competitor"
              {...register("notes")}
            />
          </div>
          {errorMessage && (
            <div className="rounded-md border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
              {errorMessage}
            </div>
          )}
          <DialogFooter>
            <Button
              type="submit"
              className="bg-emerald-500 text-white hover:bg-emerald-600"
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <span className="flex items-center gap-2">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Saving...
                </span>
              ) : (
                "Add competitor"
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
