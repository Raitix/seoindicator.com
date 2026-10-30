"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Download,
  RefreshCw,
  ChevronLeft,
  ChevronRight,
  Search,
  Filter,
  FileText,
  ExternalLink,
  Copy,
  Check,
  AlertCircle,
} from "lucide-react";
import { usePaymentHistory, useDownloadReceipt } from "@/lib/api/payments";
import { formatCurrency, formatTransactionDate, getPaymentErrorMessage } from "@/lib/api/payments";

// ==================== Types ====================

export interface PaymentHistoryProps {
  transactions?: Array<{
    id: string;
    amount: number;
    currency: string;
    credits: number;
    status: string;
    payment_method: string;
    created_at: string;
    receipt_url?: string;
    description?: string;
    balance_after?: number;
  }>;
  isLoading?: boolean;
  error?: string;
  page?: number;
  limit?: number;
  total?: number;
  onPageChange?: (page: number) => void;
  onFilterChange?: (filters: {
    status?: string;
    startDate?: string;
    endDate?: string;
  }) => void;
  onRefresh?: () => void;
  className?: string;
}

// ==================== Status Badge Component ====================

function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    completed: "bg-green-500/20 text-green-400",
    pending: "bg-yellow-500/20 text-yellow-400",
    failed: "bg-red-500/20 text-red-400",
    cancelled: "bg-slate-500/20 text-slate-400",
    refunded: "bg-blue-500/20 text-blue-400",
  };

  return (
    <span
      className={cn(
        "px-2 py-0.5 text-xs font-medium rounded-full capitalize",
        styles[status] || styles.pending
      )}
    >
      {status}
    </span>
  );
}

// ==================== Main Component ====================

export function PaymentHistory({
  transactions: externalTransactions,
  isLoading: externalLoading = false,
  error: externalError,
  page: externalPage = 1,
  limit: externalLimit = 10,
  total: externalTotal,
  onPageChange,
  onFilterChange,
  onRefresh,
  className,
}: PaymentHistoryProps) {
  const [page, setPage] = React.useState(externalPage);
  const [filters, setFilters] = React.useState({
    status: "",
    search: "",
  });
  const [copiedId, setCopiedId] = React.useState<string | null>(null);

  const {
    transactions: fetchedTransactions,
    total: fetchedTotal,
    totalPages,
    isLoading: isLoadingHistory,
    error: fetchError,
    mutate,
  } = usePaymentHistory({
    page,
    limit: externalLimit,
    status: filters.status || undefined,
    search: filters.search || undefined,
  });

  const { downloadReceipt, isLoading: isDownloadingReceipt } = useDownloadReceipt();

  const transactions = externalTransactions || fetchedTransactions;
  const isLoading = externalLoading || isLoadingHistory || isDownloadingReceipt;
  const error = externalError || fetchError?.message;
  const total = externalTotal ?? fetchedTotal;

  const handlePageChange = (newPage: number) => {
    setPage(newPage);
    onPageChange?.(newPage);
  };

  const handleFilterChange = (key: string, value: string) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
    setPage(1);
  };

  const handleRefresh = () => {
    mutate();
    onRefresh?.();
  };

  const handleCopyId = async (id: string) => {
    await navigator.clipboard.writeText(id);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  const handleDownloadReceipt = async (transactionId: string) => {
    await downloadReceipt(transactionId);
  };

  // Loading state
  if (isLoading && transactions.length === 0) {
    return (
      <div
        data-testid="loading-skeleton"
        className={cn("space-y-4", className)}
      >
        {[1, 2, 3, 4, 5].map((i) => (
          <div
            key={i}
            className="h-16 rounded-lg bg-slate-800 animate-pulse"
          />
        ))}
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div
        className={cn("p-6 rounded-lg bg-red-500/10 border border-red-500/20 text-center", className)}
      >
        <AlertCircle className="w-10 h-10 text-red-400 mx-auto mb-4" />
        <p className="text-red-400 font-medium">Failed to load transactions</p>
        <p className="text-red-400/80 text-sm mt-1">{error}</p>
        <Button
          variant="outline"
          className="mt-4"
          onClick={handleRefresh}
        >
          <RefreshCw className="w-4 h-4 mr-2" />
          Try again
        </Button>
      </div>
    );
  }

  // Empty state
  if (transactions.length === 0) {
    return (
      <div className={cn("text-center py-12", className)}>
        <FileText className="w-12 h-12 text-slate-500 mx-auto mb-4" />
        <h3 className="text-white font-medium mb-2">No transactions yet</h3>
        <p className="text-slate-400 text-sm">
          Your payment history will appear here after your first purchase.
        </p>
      </div>
    );
  }

  return (
    <div className={cn("space-y-4", className)}>
      {/* Filters */}
      <div className="flex flex-wrap items-center gap-4">
        {/* Search */}
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <input
            type="text"
            placeholder="Search transactions..."
            value={filters.search}
            onChange={(e) => handleFilterChange("search", e.target.value)}
            className={cn(
              "w-full pl-10 pr-4 py-2 bg-slate-800 border border-slate-700 rounded-lg",
              "text-white placeholder-slate-400 text-sm",
              "focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            )}
          />
        </div>

        {/* Status Filter */}
        <select
          value={filters.status}
          onChange={(e) => handleFilterChange("status", e.target.value)}
          className={cn(
            "px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg",
            "text-white text-sm",
            "focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          )}
        >
          <option value="">All statuses</option>
          <option value="completed">Completed</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
          <option value="refunded">Refunded</option>
        </select>

        {/* Refresh */}
        <Button
          variant="ghost"
          size="icon"
          onClick={handleRefresh}
          disabled={isLoading}
        >
          <RefreshCw className={cn("w-4 h-4", isLoading && "animate-spin")} />
        </Button>
      </div>

      {/* Transaction Count */}
      <p className="text-slate-400 text-sm">
        {total} transaction{total !== 1 ? "s" : ""}
      </p>

      {/* Transaction List (Mobile Cards) */}
      <div className="lg:hidden space-y-3">
        {transactions.map((tx) => (
          <div
            key={tx.id}
            className="p-4 bg-slate-800/50 rounded-lg border border-slate-700"
          >
            <div className="flex justify-between items-start mb-3">
              <div>
                <p className="text-white font-medium">
                  +{tx.credits} credits
                </p>
                <p className="text-slate-400 text-sm">
                  {formatTransactionDate(tx.created_at)}
                </p>
              </div>
              <StatusBadge status={tx.status} />
            </div>

            <div className="flex justify-between items-center text-sm">
              <span className="text-slate-400">{tx.payment_method}</span>
              <span className="text-white font-medium">
                {formatCurrency(tx.amount, tx.currency)}
              </span>
            </div>

            {tx.receipt_url && (
              <Button
                variant="ghost"
                size="sm"
                className="mt-3 w-full"
                onClick={() => handleDownloadReceipt(tx.id)}
              >
                <Download className="w-4 h-4 mr-2" />
                Receipt
              </Button>
            )}
          </div>
        ))}
      </div>

      {/* Transaction Table (Desktop) */}
      <div className="hidden lg:block overflow-x-auto">
        <table
          role="table"
          className="w-full border-collapse"
        >
          <thead>
            <tr className="border-b border-slate-700">
              <th className="px-4 py-3 text-left text-sm font-medium text-slate-400">
                Date
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-slate-400">
                Description
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-slate-400">
                Amount
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-slate-400">
                Credits
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-slate-400">
                Payment Method
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-slate-400">
                Status
              </th>
              <th className="px-4 py-3 text-right text-sm font-medium text-slate-400">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            {transactions.map((tx) => (
              <tr
                key={tx.id}
                className="border-b border-slate-800 hover:bg-slate-800/50"
              >
                <td className="px-4 py-4 text-sm text-slate-300">
                  {formatTransactionDate(tx.created_at)}
                </td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-2">
                    <span className="text-white text-sm">
                      {tx.description || "Credit Purchase"}
                    </span>
                    <button
                      onClick={() => handleCopyId(tx.id)}
                      className="text-slate-400 hover:text-white"
                      aria-label="Copy transaction ID"
                    >
                      {copiedId === tx.id ? (
                        <Check className="w-3 h-3 text-green-400" />
                      ) : (
                        <Copy className="w-3 h-3" />
                      )}
                    </button>
                  </div>
                </td>
                <td className="px-4 py-4 text-sm text-white font-medium">
                  {formatCurrency(tx.amount, tx.currency)}
                </td>
                <td className="px-4 py-4 text-sm text-green-400">
                  +{tx.credits.toLocaleString()}
                </td>
                <td className="px-4 py-4 text-sm text-slate-300">
                  {tx.payment_method}
                </td>
                <td className="px-4 py-4">
                  <StatusBadge status={tx.status} />
                </td>
                <td className="px-4 py-4 text-right">
                  {tx.receipt_url && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleDownloadReceipt(tx.id)}
                      aria-label={`Download receipt for transaction ${tx.id}`}
                    >
                      <Download className="w-4 h-4" />
                    </Button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between pt-4">
          <p className="text-sm text-slate-400">
            Page {page} of {totalPages}
          </p>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page === 1}
              onClick={() => handlePageChange(page - 1)}
            >
              <ChevronLeft className="w-4 h-4" />
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={page === totalPages}
              onClick={() => handlePageChange(page + 1)}
            >
              Next
              <ChevronRight className="w-4 h-4" />
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}

export default PaymentHistory;
