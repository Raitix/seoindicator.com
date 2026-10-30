"use client";

import { AdminLayout, AdminGuard } from "@/components/admin";

/**
 * Admin Credits/Billing Page
 * Credit management, billing history, and payment settings
 */
export default function AdminCreditsPage() {
  return (
    <AdminGuard>
      <AdminLayout
        title="Credits & Billing"
        breadcrumbs={[
          { label: "Admin", href: "/admin" },
          { label: "Credits/Billing" },
        ]}
      >
        <div data-testid="page-credits" className="space-y-6">
          <h1 className="text-2xl font-bold text-white">Credits & Billing</h1>

          {/* Credit Overview */}
          <div className="grid gap-4 md:grid-cols-3">
            <div className="rounded-lg bg-slate-800 p-6">
              <div className="text-sm text-slate-400">Total Credits Issued</div>
              <div className="text-2xl font-bold text-white">1,234,567</div>
            </div>
            <div className="rounded-lg bg-slate-800 p-6">
              <div className="text-sm text-slate-400">Credits Used (MTD)</div>
              <div className="text-2xl font-bold text-white">89,012</div>
            </div>
            <div className="rounded-lg bg-slate-800 p-6">
              <div className="text-sm text-slate-400">Revenue (MTD)</div>
              <div className="text-2xl font-bold text-white">$45,678</div>
            </div>
          </div>

          {/* Billing Summary */}
          <div
            data-testid="billing-summary"
            className="rounded-lg bg-slate-800 p-6"
          >
            <h2 className="mb-4 text-lg font-semibold text-white">
              Billing Summary
            </h2>
            <div className="space-y-4">
              <div className="flex items-center justify-between border-b border-slate-700 pb-4">
                <span className="text-slate-400">Stripe Balance</span>
                <span className="text-white">$12,345.67</span>
              </div>
              <div className="flex items-center justify-between border-b border-slate-700 pb-4">
                <span className="text-slate-400">Pending Payouts</span>
                <span className="text-white">$5,432.10</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-400">Next Payout Date</span>
                <span className="text-white">2026-03-15</span>
              </div>
            </div>
          </div>

          {/* Recent Transactions */}
          <div className="rounded-lg bg-slate-800 p-6">
            <h2 className="mb-4 text-lg font-semibold text-white">
              Recent Transactions
            </h2>
            <div className="text-slate-400">
              Transaction history will be displayed here...
            </div>
          </div>
        </div>
      </AdminLayout>
    </AdminGuard>
  );
}
