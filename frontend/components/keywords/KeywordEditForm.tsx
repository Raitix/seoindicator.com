"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";

import { updateKeyword, useKeyword } from "@/lib/api/keywords";
import { keywordEditSchema, type KeywordEditValues } from "@/lib/validators/keyword";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ToastBanner } from "@/components/ui/toast-banner";

interface KeywordEditFormProps {
  siteId: number;
  keywordId: number;
}

const defaultValues: KeywordEditValues = {
  targetUrl: "",
  status: "active",
};

export default function KeywordEditForm({ siteId, keywordId }: KeywordEditFormProps) {
  const router = useRouter();
  const [toast, setToast] = useState<{ variant: "success" | "error"; message: string } | null>(null);
  const { keyword, isLoading } = useKeyword(keywordId);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<KeywordEditValues>({
    resolver: zodResolver(keywordEditSchema),
    defaultValues,
  });

  useEffect(() => {
    if (!keyword) return;
    reset({
      targetUrl: keyword.targetUrl ?? "",
      status: keyword.status === "paused" ? "paused" : "active",
    });
  }, [keyword, reset]);

  const onSubmit = async (values: KeywordEditValues) => {
    try {
      await updateKeyword(keywordId, values);
      setToast({ variant: "success", message: "Keyword updated successfully." });
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to update keyword.";
      setToast({ variant: "error", message });
    }
  };

  return (
    <Card className="border-slate-200/10 bg-slate-900/60 text-slate-100 shadow-lg">
      <CardHeader>
        <CardTitle className="text-lg text-white">Edit keyword</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="keyword">Keyword</Label>
              <Input id="keyword" value={keyword?.keyword ?? ""} disabled />
              <p className="text-xs text-slate-400">Keyword text cannot be edited.</p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="targetUrl">Target URL</Label>
              <Input
                id="targetUrl"
                placeholder="https://example.com/landing"
                {...register("targetUrl")}
              />
              {errors.targetUrl ? (
                <p className="text-sm text-rose-400">{errors.targetUrl.message}</p>
              ) : (
                <p className="text-xs text-slate-400">Optional. Must be a valid URL.</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <select
                id="status"
                className="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                {...register("status")}
              >
                <option value="active">Active</option>
                <option value="paused">Paused</option>
              </select>
            </div>
          </div>

          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex gap-3">
              <Button type="submit" disabled={isSubmitting || isLoading}>
                {isSubmitting ? "Saving..." : "Save changes"}
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => router.push(`/sites/${siteId}/keywords`)}
              >
                Cancel
              </Button>
            </div>
            <Link
              href={`/sites/${siteId}/keywords`}
              className="text-sm text-slate-400 transition hover:text-slate-200"
            >
              Back to keyword list
            </Link>
          </div>
        </form>
      </CardContent>

      {toast ? (
        <div className="fixed right-6 top-6 z-50 max-w-sm">
          <ToastBanner variant={toast.variant} message={toast.message} onDismiss={() => setToast(null)} />
        </div>
      ) : null}
    </Card>
  );
}
