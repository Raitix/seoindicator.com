"use client";

import * as React from "react";
import { AlertTriangle, RefreshCcw } from "lucide-react";

import { useCompetitors } from "@/lib/api/competitors";
import type { Competitor } from "@/lib/types/competitors";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import AddCompetitorModal from "@/components/competitors/AddCompetitorModal";
import CompetitorCard from "@/components/competitors/CompetitorCard";
import DeleteCompetitorDialog from "@/components/competitors/DeleteCompetitorDialog";
import EditCompetitorModal from "@/components/competitors/EditCompetitorModal";

const FALLBACK_LIMIT = 3;

export default function CompetitorList() {
  const { competitors, meta, isLoading, error, mutate } = useCompetitors();
  const [selectedCompetitor, setSelectedCompetitor] = React.useState<
    Competitor | null
  >(null);
  const [deleteTarget, setDeleteTarget] = React.useState<Competitor | null>(null);

  const limit = meta?.limit ?? FALLBACK_LIMIT;
  const used = meta?.used ?? competitors.length;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 className="text-2xl font-semibold text-white">
            Competitor management
          </h2>
          <p className="text-slate-400">
            Track competitor domains and compare keyword performance.
          </p>
        </div>
        <div className="flex flex-col gap-2 sm:items-end">
          <div className="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-sm text-emerald-200">
            {used}/{limit} competitors used
          </div>
          <AddCompetitorModal />
        </div>
      </div>

      {isLoading && (
        <div className="grid gap-4 md:grid-cols-2">
          {Array.from({ length: 2 }).map((_, index) => (
            <Card
              key={index}
              className="h-36 animate-pulse border-slate-800 bg-slate-900/60"
            >
              <CardContent className="h-full" />
            </Card>
          ))}
        </div>
      )}

      {!isLoading && error && (
        <Card className="border-rose-500/40 bg-rose-500/10">
          <CardContent className="flex flex-col gap-3 p-6 text-rose-200">
            <div className="flex items-center gap-2">
              <AlertTriangle className="h-5 w-5" />
              <span className="font-semibold">
                Failed to load competitors
              </span>
            </div>
            <p className="text-sm text-rose-200/80">
              {error.message ?? "Please try again."}
            </p>
            <Button
              variant="outline"
              className="w-fit border-rose-400 text-rose-100"
              onClick={() => mutate()}
            >
              <RefreshCcw className="mr-2 h-4 w-4" />
              Retry
            </Button>
          </CardContent>
        </Card>
      )}

      {!isLoading && !error && competitors.length === 0 && (
        <Card className="border-slate-800 bg-slate-900/60">
          <CardContent className="flex flex-col items-center gap-2 p-10 text-center">
            <p className="text-lg font-semibold text-white">
              No competitors added yet
            </p>
            <p className="text-sm text-slate-400">
              Add your first competitor to start tracking keyword positions.
            </p>
          </CardContent>
        </Card>
      )}

      {!isLoading && !error && competitors.length > 0 && (
        <div className="grid gap-4 md:grid-cols-2">
          {competitors.map((competitor) => (
            <CompetitorCard
              key={competitor.id}
              competitor={competitor}
              onEdit={setSelectedCompetitor}
              onDelete={setDeleteTarget}
            />
          ))}
        </div>
      )}

      <EditCompetitorModal
        competitor={selectedCompetitor}
        open={Boolean(selectedCompetitor)}
        onClose={() => setSelectedCompetitor(null)}
      />
      <DeleteCompetitorDialog
        competitor={deleteTarget}
        open={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
      />
    </div>
  );
}
