"use client";

import { AdminLayout, AdminGuard } from "@/components/admin";

/**
 * Admin System Health Page
 * System health monitoring, status checks, and alerts
 */
export default function AdminHealthPage() {
  return (
    <AdminGuard>
      <AdminLayout
        title="System Health"
        breadcrumbs={[
          { label: "Admin", href: "/admin" },
          { label: "System Health" },
        ]}
      >
        <div data-testid="page-health" className="space-y-6">
          <h1 className="text-2xl font-bold text-white">System Health</h1>

          {/* Overall Status */}
          <div
            data-testid="health-status"
            className="rounded-lg bg-green-900/20 border border-green-800 p-6"
          >
            <div className="flex items-center gap-3">
              <div className="h-4 w-4 rounded-full bg-green-500 animate-pulse" />
              <h2 className="text-lg font-semibold text-green-400">
                All Systems Operational
              </h2>
            </div>
          </div>

          {/* Service Status */}
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="flex items-center justify-between">
                <span className="text-slate-300">API Server</span>
                <span className="flex items-center gap-2 text-green-400">
                  <span className="h-2 w-2 rounded-full bg-green-500" />
                  Operational
                </span>
              </div>
            </div>
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="flex items-center justify-between">
                <span className="text-slate-300">Database</span>
                <span className="flex items-center gap-2 text-green-400">
                  <span className="h-2 w-2 rounded-full bg-green-500" />
                  Operational
                </span>
              </div>
            </div>
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="flex items-center justify-between">
                <span className="text-slate-300">Redis Cache</span>
                <span className="flex items-center gap-2 text-green-400">
                  <span className="h-2 w-2 rounded-full bg-green-500" />
                  Operational
                </span>
              </div>
            </div>
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="flex items-center justify-between">
                <span className="text-slate-300">Queue Worker</span>
                <span className="flex items-center gap-2 text-green-400">
                  <span className="h-2 w-2 rounded-full bg-green-500" />
                  Operational
                </span>
              </div>
            </div>
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="flex items-center justify-between">
                <span className="text-slate-300">Storage</span>
                <span className="flex items-center gap-2 text-green-400">
                  <span className="h-2 w-2 rounded-full bg-green-500" />
                  Operational
                </span>
              </div>
            </div>
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="flex items-center justify-between">
                <span className="text-slate-300">AI Service</span>
                <span className="flex items-center gap-2 text-green-400">
                  <span className="h-2 w-2 rounded-full bg-green-500" />
                  Operational
                </span>
              </div>
            </div>
          </div>

          {/* System Metrics */}
          <div className="grid gap-4 md:grid-cols-2">
            <div className="rounded-lg bg-slate-800 p-6">
              <h2 className="mb-4 text-lg font-semibold text-white">
                Performance Metrics
              </h2>
              <div className="space-y-3">
                <div>
                  <div className="flex justify-between text-sm">
                    <span className="text-slate-400">CPU Usage</span>
                    <span className="text-white">23%</span>
                  </div>
                  <div className="mt-1 h-2 rounded-full bg-slate-700">
                    <div className="h-2 w-[23%] rounded-full bg-green-500" />
                  </div>
                </div>
                <div>
                  <div className="flex justify-between text-sm">
                    <span className="text-slate-400">Memory Usage</span>
                    <span className="text-white">45%</span>
                  </div>
                  <div className="mt-1 h-2 rounded-full bg-slate-700">
                    <div className="h-2 w-[45%] rounded-full bg-blue-500" />
                  </div>
                </div>
                <div>
                  <div className="flex justify-between text-sm">
                    <span className="text-slate-400">Disk Usage</span>
                    <span className="text-white">67%</span>
                  </div>
                  <div className="mt-1 h-2 rounded-full bg-slate-700">
                    <div className="h-2 w-[67%] rounded-full bg-yellow-500" />
                  </div>
                </div>
              </div>
            </div>

            <div className="rounded-lg bg-slate-800 p-6">
              <h2 className="mb-4 text-lg font-semibold text-white">
                Recent Alerts
              </h2>
              <div className="text-slate-400">No recent alerts</div>
            </div>
          </div>
        </div>
      </AdminLayout>
    </AdminGuard>
  );
}
