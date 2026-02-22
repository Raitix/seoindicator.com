"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";

import { createKeyword, useKeywords } from "@/lib/api/keywords";
import { keywordCreateSchema, type KeywordCreateValues } from "@/lib/validators/keyword";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ToastBanner } from "@/components/ui/toast-banner";

interface KeywordCreateFormProps {
  siteId: number;
}

const defaultValues: KeywordCreateValues = {
  keyword: "",
  targetUrl: "",
  searchEngine: "google",
  location: "us",
  device: "desktop",
};

export default function KeywordCreateForm({ siteId }: KeywordCreateFormProps) {
  const router = useRouter();
  const [toast, setToast] = useState<{ variant: "success" | "error" | "warning"; message: string } | null>(null);
  const { keywords } = useKeywords(siteId, { status: "all", perPage: 100 });

  const {
    register,
    handleSubmit,
    watch,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<KeywordCreateValues>({
    resolver: zodResolver(keywordCreateSchema),
    defaultValues,
  });

  const [keywordValue, searchEngine, location, device] = watch([
    "keyword",
    "searchEngine",
    "location",
    "device",
  ]);

  const duplicateKeyword = useMemo(() => {
    const normalized = keywordValue?.trim().toLowerCase();
    if (!normalized) return false;

    return keywords.some((item) => {
      const matchesKeyword = item.keyword?.trim().toLowerCase() === normalized;
      const matchesSearchEngine = (item.searchEngine ?? "google") === (searchEngine ?? "google");
      const matchesLocation = (item.location ?? "us") === (location ?? "us");
      const matchesDevice = (item.device ?? "desktop") === (device ?? "desktop");
      return matchesKeyword && matchesSearchEngine && matchesLocation && matchesDevice;
    });
  }, [keywordValue, keywords, searchEngine, location, device]);

  const onSubmit = async (values: KeywordCreateValues) => {
    if (duplicateKeyword) {
      setToast({
        variant: "warning",
        message: "This keyword already exists for the selected targeting. You can still try to save it.",
      });
    }

    try {
      await createKeyword(siteId, values);
      setToast({ variant: "success", message: "Keyword added successfully." });
      reset(defaultValues);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to add keyword.";
      setToast({ variant: "error", message });
    }
  };

  return (
    <Card className="border-slate-200/10 bg-slate-900/60 text-slate-100 shadow-lg">
      <CardHeader>
        <CardTitle className="text-lg text-white">Add keyword</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="keyword">Keyword</Label>
              <Input
                id="keyword"
                placeholder="e.g. technical seo audit"
                {...register("keyword")}
              />
              {errors.keyword ? (
                <p className="text-sm text-rose-400">{errors.keyword.message}</p>
              ) : duplicateKeyword ? (
                <p className="text-sm text-amber-400">Potential duplicate keyword found.</p>
              ) : (
                <p className="text-xs text-slate-400">Max 255 characters.</p>
              )}
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
              <Label htmlFor="searchEngine">Search engine</Label>
              <select
                id="searchEngine"
                className="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                {...register("searchEngine")}
              >
                <option value="google">Google</option>
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="location">Location</Label>
              <select
                id="location"
                className="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                {...register("location")}
              >
                <option value="us">United States</option>
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="device">Device</Label>
              <select
                id="device"
                className="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                {...register("device")}
              >
                <option value="desktop">Desktop</option>
              </select>
            </div>
          </div>

          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex gap-3">
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? "Saving..." : "Add keyword"}
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
          <ToastBanner
            variant={toast.variant}
            message={toast.message}
            onDismiss={() => setToast(null)}
          />
        </div>
      ) : null}
    </Card>
  );
}
