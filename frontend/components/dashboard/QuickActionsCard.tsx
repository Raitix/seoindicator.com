"use client";

import * as React from "react";
import Link from "next/link";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Plus, History, BarChart3, Settings } from "lucide-react";

export interface QuickActionsProps {
  onNewAnalysis?: () => void;
  onViewHistory?: () => void;
}

export function QuickActionsCard({ onNewAnalysis, onViewHistory }: QuickActionsProps) {
  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-white">
          <Settings className="h-5 w-5 text-slate-400" />
          Quick Actions
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        <Button
          asChild
          className="w-full bg-emerald-500 text-white hover:bg-emerald-600"
        >
          <Link href="/tools">
            <Plus className="h-4 w-4 mr-2" />
            Run New Analysis
          </Link>
        </Button>
        <Button
          asChild
          variant="outline"
          className="w-full border-slate-700 text-slate-200 hover:bg-slate-800"
        >
          <Link href="/dashboard/history">
            <History className="h-4 w-4 mr-2" />
            View All History
          </Link>
        </Button>
        <Button
          asChild
          variant="outline"
          className="w-full border-slate-700 text-slate-200 hover:bg-slate-800"
        >
          <Link href="/projects">
            <BarChart3 className="h-4 w-4 mr-2" />
            View Projects
          </Link>
        </Button>
      </CardContent>
    </Card>
  );
}
