"use client";

import * as React from "react";
import { X, Clock, DollarSign, AlertCircle, CheckCircle, Copy, Check } from "lucide-react";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";

import type { AnalysisDetailsModalProps, AnalysisDetail } from "@/lib/types/analysis";

// ==================== Duration Formatter ====================

function formatDuration(ms: number): string {
  if (ms < 1000) {
    return `${ms}ms`;
  }
  if (ms < 60000) {
    return `${(ms / 1000).toFixed(2)} seconds`;
  }
  const minutes = Math.floor(ms / 60000);
  const seconds = Math.round((ms % 60000) / 1000);
  return `${minutes} minute${minutes > 1 ? "s" : ""} ${seconds} seconds`;
}

// ==================== Cost Formatter ====================

function formatCost(total: number, currency: string): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 4,
  }).format(total);
}

// ==================== Date Formatter ====================

function formatDate(isoString: string): string {
  return new Date(isoString).toLocaleString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
    second: "2-digit",
  });
}

// ==================== JSON Viewer ====================

interface JsonViewerProps {
  data: Record<string, unknown>;
}

function JsonViewer({ data }: JsonViewerProps) {
  const [copied, setCopied] = React.useState(false);

  const handleCopy = async () => {
    await navigator.clipboard.writeText(JSON.stringify(data, null, 2));
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="relative">
      <Button
        variant="ghost"
        size="sm"
        className="absolute top-2 right-2 h-8 w-8 p-0"
        onClick={handleCopy}
        data-testid="copy-json-button"
      >
        {copied ? (
          <Check className="h-4 w-4 text-green-500" />
        ) : (
          <Copy className="h-4 w-4 text-slate-400" />
        )}
      </Button>
      <pre className="bg-slate-900 rounded-lg p-4 overflow-x-auto text-sm text-slate-300 font-mono">
        {JSON.stringify(data, null, 2)}
      </pre>
    </div>
  );
}

// ==================== Analysis Details Modal ====================

export function AnalysisDetailsModal({
  analysis,
  isOpen,
  onClose,
}: AnalysisDetailsModalProps) {
  // Handle escape key
  React.useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape" && isOpen) {
        onClose();
      }
    };

    document.addEventListener("keydown", handleEscape);
    return () => document.removeEventListener("keydown", handleEscape);
  }, [isOpen, onClose]);

  if (!analysis) return null;

  const isFailed = analysis.status === "failed";
  const isCompleted = analysis.status === "completed";

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent
        className="max-w-3xl max-h-[90vh] bg-slate-950 border-slate-800 text-slate-200"
        data-testid="analysis-details-modal"
      >
        <DialogHeader>
          <div className="flex items-start justify-between">
            <div>
              <DialogTitle className="text-xl text-white">
                Analysis Details
              </DialogTitle>
              <p className="text-sm text-slate-400 mt-1">
                {analysis.toolDisplayName}
              </p>
            </div>
          </div>
        </DialogHeader>

        <ScrollArea className="max-h-[calc(90vh-120px)]">
          <div className="space-y-6 py-4">
            {/* Status Banner */}
            {isFailed && analysis.errorMessage && (
              <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
                <div className="flex items-start gap-3">
                  <AlertCircle className="h-5 w-5 text-red-400 mt-0.5 flex-shrink-0" />
                  <div>
                    <h4 className="text-sm font-medium text-red-400">Analysis Failed</h4>
                    <p className="text-sm text-red-300 mt-1">{analysis.errorMessage}</p>
                  </div>
                </div>
              </div>
            )}

            {isCompleted && (
              <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                <div className="flex items-center gap-3">
                  <CheckCircle className="h-5 w-5 text-green-400" />
                  <h4 className="text-sm font-medium text-green-400">Analysis Completed Successfully</h4>
                </div>
              </div>
            )}

            {/* Meta Info Grid */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="bg-slate-900 rounded-lg p-4">
                <div className="flex items-center gap-2 text-slate-400 text-sm mb-1">
                  <Clock className="h-4 w-4" />
                  Duration
                </div>
                <p className="text-white font-medium">
                  {formatDuration(analysis.analysisDurationMs)}
                </p>
              </div>

              <div className="bg-slate-900 rounded-lg p-4">
                <div className="flex items-center gap-2 text-slate-400 text-sm mb-1">
                  <DollarSign className="h-4 w-4" />
                  Total Cost
                </div>
                <p className="text-white font-medium">
                  {analysis.cost?.total !== undefined
                    ? formatCost(analysis.cost.total, analysis.cost?.currency || "USD")
                    : "—"}
                </p>
              </div>

              <div className="bg-slate-900 rounded-lg p-4 col-span-2">
                <div className="text-slate-400 text-sm mb-1">Input URL</div>
                <p className="text-white font-medium truncate" title={analysis.inputUrl}>
                  {analysis.inputUrl}
                </p>
              </div>
            </div>

            {/* Date */}
            <div className="text-sm text-slate-400">
              Analyzed on {formatDate(analysis.analyzedAt)}
            </div>

            <Separator className="bg-slate-800" />

            {/* Cost Breakdown */}
            {analysis.costBreakdown && analysis.costBreakdown.length > 0 && (
              <>
                <div>
                  <h3 className="text-lg font-semibold text-white mb-3">Cost Breakdown</h3>
                  <div className="bg-slate-900 rounded-lg overflow-hidden">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b border-slate-800">
                          <th className="text-left p-3 text-slate-400 font-medium">Type</th>
                          <th className="text-right p-3 text-slate-400 font-medium">Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        {analysis.costBreakdown.map((item, index) => (
                          <tr key={index} className="border-b border-slate-800 last:border-0">
                            <td className="p-3 text-slate-200 capitalize">
                              {item.type.replace(/_/g, " ")}
                            </td>
                            <td className="p-3 text-slate-200 text-right">
                              {formatCost(item.amount, analysis.cost?.currency || "USD")}
                            </td>
                          </tr>
                        ))}
                        <tr className="bg-slate-800/50">
                          <td className="p-3 text-white font-medium">Total</td>
                          <td className="p-3 text-white font-medium text-right">
                            {analysis.cost?.total !== undefined
                              ? formatCost(analysis.cost.total, analysis.cost?.currency || "USD")
                              : "—"}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <Separator className="bg-slate-800" />
              </>
            )}

            {/* Full Results JSON */}
            {analysis.results && Object.keys(analysis.results).length > 0 && (
              <div>
                <h3 className="text-lg font-semibold text-white mb-3">Full Results</h3>
                <JsonViewer data={analysis.results} />
              </div>
            )}
          </div>
        </ScrollArea>

        {/* Footer */}
        <div className="flex justify-end pt-4">
          <Button
            variant="outline"
            onClick={onClose}
            className="border-slate-700 text-slate-200"
            data-testid="close-details-button"
          >
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default AnalysisDetailsModal;
