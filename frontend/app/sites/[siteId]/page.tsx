/**
 * Site Overview Page
 * 
 * Task: TASK-36-14 - Navigation & Dashboard Integration
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Site-specific dashboard showing overview widgets including Content Decay.
 */

"use client";

import * as React from "react";
import { useParams, useRouter } from "next/navigation";
import { RefreshCw, ArrowLeft, Settings, ExternalLink } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { ContentDecayWidget } from "@/components/dashboard";
import { AuditWidget } from "@/components/dashboard";
import {
  useContentDecaySummary,
  useContentDecayCandidates,
  useRunContentDecayScan,
  useUpdateCandidateStatus,
} from "@/lib/hooks/useContentDecay";
import type { ContentDecayCandidateStatus } from "@/lib/types/content-decay";

// ============================================================
// Loading Skeleton
// ============================================================

function PageSkeleton() {
  return (
    <div className="space-y-6 p-6">
      <div className="flex items-center justify-between">
        <Skeleton className="h-8 w-48" />
        <div className="flex gap-2">
          <Skeleton className="h-10 w-24" />
          <Skeleton className="h-10 w-24" />
        </div>
      </div>
      <div className="grid gap-6 lg:grid-cols-2">
        <Skeleton className="h-64" />
        <Skeleton className="h-64" />
      </div>
    </div>
  );
}

// ============================================================
// Site Overview Content
// ============================================================

function SiteOverviewContent({ siteId }: { siteId: string }) {
  const router = useRouter();

  // Content Decay hooks
  const {
    summary: decaySummary,
    isLoading: isDecayLoading,
    error: decayError,
    mutate: mutateDecaySummary,
  } = useContentDecaySummary(siteId);

  const {
    candidates: topCandidates,
    isLoading: isCandidatesLoading,
    mutate: mutateCandidates,
  } = useContentDecayCandidates(siteId, {
    status: "all",
    severity: "all",
    pageType: "all",
    sort: "decay_score",
    order: "desc",
  });

  const { runScan, isRunning: isScanRunning } = useRunContentDecayScan(siteId);

  // Handlers
  const handleRefresh = async () => {
    const result = await runScan();
    if (result.success) {
      mutateDecaySummary();
      mutateCandidates();
    }
  };

  const handleViewCandidate = (id: number) => {
    router.push(`/sites/${siteId}/content-decay?candidate=${id}`);
  };

  const handleMarkPlanned = async (id: number) => {
    // Use a simplified update - in production this would use the proper hook
    router.push(`/sites/${siteId}/content-decay?candidate=${id}&action=plan`);
  };

  const handleIgnore = async (id: number) => {
    // Use a simplified update - in production this would use the proper hook
    router.push(`/sites/${siteId}/content-decay?candidate=${id}&action=ignore`);
  };

  // Audit widget handlers (placeholder)
  const handleStartAudit = () => {
    router.push(`/sites/${siteId}/audits`);
  };

  const handleViewAllAudits = () => {
    router.push(`/sites/${siteId}/audits`);
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      <div className="container mx-auto px-4 py-6">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center gap-4">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => router.push("/dashboard")}
              className="text-slate-400 hover:text-slate-200"
            >
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back to Dashboard
            </Button>
          </div>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.push(`/sites/${siteId}/settings`)}
              className="border-slate-700 text-slate-300"
            >
              <Settings className="h-4 w-4 mr-2" />
              Site Settings
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={handleRefresh}
              disabled={isScanRunning}
              className="border-slate-700 text-slate-300"
            >
              <RefreshCw className={`h-4 w-4 mr-2 ${isScanRunning ? "animate-spin" : ""}`} />
              Refresh
            </Button>
          </div>
        </div>

        {/* Site Title */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-white">Site Overview</h1>
          <p className="text-sm text-slate-400">
            Site ID: {siteId}
          </p>
        </div>

        {/* Widgets Grid */}
        <div className="grid gap-6 lg:grid-cols-2" data-testid="widgets-grid">
          {/* Audit Widget */}
          <AuditWidget
            siteId={siteId}
            onStartAudit={handleStartAudit}
            onViewAll={handleViewAllAudits}
          />

          {/* Content Decay Widget */}
          <ContentDecayWidget
            siteId={siteId}
            summary={decaySummary}
            topCandidates={topCandidates.slice(0, 3)}
            isLoading={isDecayLoading || isCandidatesLoading}
            error={decayError?.message}
            onRefresh={handleRefresh}
            onViewCandidate={handleViewCandidate}
            onMarkPlanned={handleMarkPlanned}
            onIgnore={handleIgnore}
          />
        </div>

        {/* Quick Links */}
        <div className="mt-8 grid gap-4 md:grid-cols-4">
          <QuickLinkCard
            title="Keywords"
            description="Track keyword rankings"
            href={`/sites/${siteId}/keywords`}
          />
          <QuickLinkCard
            title="Audits"
            description="Technical SEO audits"
            href={`/sites/${siteId}/audits`}
          />
          <QuickLinkCard
            title="Content Decay"
            description="Monitor content performance"
            href={`/sites/${siteId}/content-decay`}
          />
          <QuickLinkCard
            title="Local SEO"
            description="Local search optimization"
            href={`/sites/${siteId}/local`}
          />
        </div>
      </div>
    </div>
  );
}

// ============================================================
// Quick Link Card
// ============================================================

interface QuickLinkCardProps {
  title: string;
  description: string;
  href: string;
}

function QuickLinkCard({ title, description, href }: QuickLinkCardProps) {
  return (
    <a
      href={href}
      className="block p-4 rounded-lg bg-slate-800/50 border border-slate-700 hover:bg-slate-800 transition-colors group"
    >
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-sm font-medium text-slate-200 group-hover:text-white">
            {title}
          </h3>
          <p className="text-xs text-slate-400 mt-1">{description}</p>
        </div>
        <ExternalLink className="h-4 w-4 text-slate-500 group-hover:text-slate-300" />
      </div>
    </a>
  );
}

// ============================================================
// Page Component
// ============================================================

export default function SiteOverviewPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  if (!siteId) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <p className="text-slate-400">Site not found</p>
      </div>
    );
  }

  return <SiteOverviewContent siteId={siteId} />;
}
