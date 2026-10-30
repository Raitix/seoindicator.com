"use client";

import { Pencil, Trash2 } from "lucide-react";

import type { Competitor } from "@/lib/types/competitors";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface CompetitorCardProps {
  competitor: Competitor;
  onEdit: (competitor: Competitor) => void;
  onDelete: (competitor: Competitor) => void;
}

export default function CompetitorCard({
  competitor,
  onEdit,
  onDelete,
}: CompetitorCardProps) {
  return (
    <Card className="border-slate-800 bg-slate-900/80">
      <CardHeader className="flex flex-row items-start justify-between gap-4">
        <div>
          <CardTitle className="text-lg text-white">
            {competitor.displayName || competitor.domain}
          </CardTitle>
          <p className="text-sm text-slate-400">{competitor.domain}</p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="icon"
            className="text-slate-300 hover:text-white"
            onClick={() => onEdit(competitor)}
            aria-label={`Edit ${competitor.domain}`}
          >
            <Pencil className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="text-rose-400 hover:text-rose-300"
            onClick={() => onDelete(competitor)}
            aria-label={`Delete ${competitor.domain}`}
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      </CardHeader>
      <CardContent className="space-y-3 text-sm text-slate-300">
        <div className="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2">
          <span className="text-slate-400">Tracked keywords</span>
          <span className="font-semibold text-emerald-300">
            {competitor.trackedKeywordsCount}
          </span>
        </div>
        {competitor.notes ? (
          <p className="text-slate-400">{competitor.notes}</p>
        ) : (
          <p className="text-slate-600 italic">No notes added yet.</p>
        )}
      </CardContent>
    </Card>
  );
}
