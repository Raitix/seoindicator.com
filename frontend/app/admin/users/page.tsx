"use client";

import { AdminLayout, AdminGuard } from "@/components/admin";

/**
 * Admin Users Page
 * User management with search, filtering, and role management
 */
export default function AdminUsersPage() {
  return (
    <AdminGuard>
      <AdminLayout
        title="User Management"
        breadcrumbs={[
          { label: "Admin", href: "/admin" },
          { label: "Users" },
        ]}
      >
        <div data-testid="page-users" className="space-y-6">
          <h1 className="text-2xl font-bold text-white">User Management</h1>

          {/* Search and Actions */}
          <div className="flex items-center justify-between">
            <div className="relative">
              <input
                type="text"
                placeholder="Search users..."
                className="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white placeholder-slate-400 focus:border-blue-500 focus:outline-none"
              />
            </div>
            <button className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
              Add User
            </button>
          </div>

          {/* Users Table */}
          <div className="rounded-lg bg-slate-800 overflow-hidden">
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-700 bg-slate-900">
                  <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                    Name
                  </th>
                  <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                    Email
                  </th>
                  <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                    Role
                  </th>
                  <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                    Status
                  </th>
                  <th className="px-4 py-3 text-right text-sm font-medium text-slate-300">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr className="border-b border-slate-700">
                  <td className="px-4 py-3 text-white">John Doe</td>
                  <td className="px-4 py-3 text-slate-400">john@example.com</td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-blue-900/50 px-2 py-1 text-xs text-blue-400">
                      Admin
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-green-900/50 px-2 py-1 text-xs text-green-400">
                      Active
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button className="text-slate-400 hover:text-white">
                      Edit
                    </button>
                  </td>
                </tr>
                <tr className="border-b border-slate-700">
                  <td className="px-4 py-3 text-white">Jane Smith</td>
                  <td className="px-4 py-3 text-slate-400">jane@example.com</td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-slate-700 px-2 py-1 text-xs text-slate-300">
                      User
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-green-900/50 px-2 py-1 text-xs text-green-400">
                      Active
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button className="text-slate-400 hover:text-white">
                      Edit
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </AdminLayout>
    </AdminGuard>
  );
}
