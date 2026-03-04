"use client";

import { AdminLayout, AdminGuard } from "@/components/admin";

/**
 * Admin Overview Page
 * Dashboard home with key metrics and quick actions
 */
export default function AdminOverviewPage() {
  return (
    <AdminGuard>
      <AdminLayout
        title="Dashboard Overview"
        breadcrumbs={[
          { label: "Admin", href: "/admin" },
          { label: "Overview" },
        ]}
      >
        <div data-testid="page-overview" className="space-y-6">
          <h1 className="text-2xl font-bold text-white">Dashboard Overview</h1>
          <p className="text-slate-400">Welcome to the admin dashboard.</p>

          {/* Quick Stats */}
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="text-sm text-slate-400">Total Users</div>
              <div className="text-2xl font-bold text-white">1,234</div>
            </div>
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="text-sm text-slate-400">Active Today</div>
              <div className="text-2xl font-bold text-white">567</div>
            </div>
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="text-sm text-slate-400">Analyses Today</div>
              <div className="text-2xl font-bold text-white">890</div>
            </div>
            <div className="rounded-lg bg-slate-800 p-4">
              <div className="text-sm text-slate-400">Revenue (MTD)</div>
              <div className="text-2xl font-bold text-white">$12,345</div>
            </div>
          </div>

          {/* Recent Activity */}
          <div className="rounded-lg bg-slate-800 p-6">
            <h2 className="mb-4 text-lg font-semibold text-white">
              Recent Activity
            </h2>
            <div className="text-slate-400">
              Activity feed will be displayed here...
            </div>
          </div>
        </div>
      </AdminLayout>
    </AdminGuard>
  );
}
