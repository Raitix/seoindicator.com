"use client";

import { AdminLayout, AdminGuard } from "@/components/admin";

/**
 * Admin Usage Page
 * Usage statistics and analytics
 */
export default function AdminUsagePage() {
  return (
    <AdminGuard>
      <AdminLayout
        title="Usage Statistics"
        breadcrumbs={[
          { label: "Admin", href: "/admin" },
          { label: "Usage" },
        ]}
      >
        <div data-testid="page-usage" className="space-y-6">
          <h1 className="text-2xl font-bold text-white">Usage Statistics</h1>

          {/* Time Range Selector */}
          <div className="flex items-center gap-2">
            <button className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white">
              Last 7 Days
            </button>
            <button className="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700">
              Last 30 Days
            </button>
            <button className="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700">
              Last 90 Days
            </button>
          </div>

          {/* Usage Charts */}
          <div className="grid gap-6 lg:grid-cols-2">
            <div className="rounded-lg bg-slate-800 p-6">
              <h2 className="mb-4 text-lg font-semibold text-white">
                Analyses per Day
              </h2>
              <div
                data-testid="usage-chart"
                className="flex h-48 items-center justify-center text-slate-400"
              >
                Chart Placeholder
              </div>
            </div>

            <div className="rounded-lg bg-slate-800 p-6">
              <h2 className="mb-4 text-lg font-semibold text-white">
                API Calls
              </h2>
              <div className="flex h-48 items-center justify-center text-slate-400">
                Chart Placeholder
              </div>
            </div>
          </div>

          {/* Usage Breakdown */}
          <div className="rounded-lg bg-slate-800 p-6">
            <h2 className="mb-4 text-lg font-semibold text-white">
              Usage Breakdown
            </h2>
            <div className="grid gap-4 md:grid-cols-3">
              <div>
                <div className="text-sm text-slate-400">Total Analyses</div>
                <div className="text-2xl font-bold text-white">45,678</div>
              </div>
              <div>
                <div className="text-sm text-slate-400">API Calls</div>
                <div className="text-2xl font-bold text-white">123,456</div>
              </div>
              <div>
                <div className="text-sm text-slate-400">Storage Used</div>
                <div className="text-2xl font-bold text-white">2.4 GB</div>
              </div>
            </div>
          </div>
        </div>
      </AdminLayout>
    </AdminGuard>
  );
}
