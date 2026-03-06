"use client";

import { AdminLayout, AdminGuard, HealthOverview, type HealthData } from "@/components/admin";

/**
 * Mock health data for development
 * Replace with actual API call when TASK-27-13 (Health API) is implemented
 */
const mockHealthData: HealthData = {
  overall: "healthy",
  services: [
    {
      name: "API Server",
      status: "healthy",
      latency: 23,
      lastCheck: new Date().toISOString(),
    },
    {
      name: "Database",
      status: "healthy",
      latency: 5,
      lastCheck: new Date().toISOString(),
    },
    {
      name: "Redis Cache",
      status: "healthy",
      latency: 2,
      lastCheck: new Date().toISOString(),
    },
    {
      name: "Queue Worker",
      status: "healthy",
      lastCheck: new Date().toISOString(),
    },
    {
      name: "Storage",
      status: "healthy",
      lastCheck: new Date().toISOString(),
    },
    {
      name: "AI Service",
      status: "healthy",
      lastCheck: new Date().toISOString(),
    },
  ],
  queue: {
    pending: 5,
    processing: 2,
    failed: 0,
    workers: 2,
    oldestJob: {
      id: "job-123",
      age: 30,
      type: "analysis",
    },
  },
  database: {
    status: "healthy",
    connections: {
      active: 12,
      idle: 8,
      max: 100,
    },
    latency: 5,
    queryCount: 1234,
    slowQueries: 0,
    tableSizes: [
      { name: "users", size: "125MB", rows: 50000 },
      { name: "analyses", size: "2.1GB", rows: 100000 },
      { name: "reports", size: "890MB", rows: 25000 },
    ],
  },
  alerts: [],
  metrics: {
    cpu: 23,
    memory: 45,
    disk: 67,
    uptime: 86400, // 1 day in seconds
  },
  lastUpdated: new Date().toISOString(),
};

/**
 * Admin System Health Page
 * System health monitoring, status checks, and alerts
 */
export default function AdminHealthPage() {
  const handleRefresh = async () => {
    // TODO: Replace with actual API call when TASK-27-13 is implemented
    console.log("Refreshing health data...");
    // Simulate API delay
    await new Promise((resolve) => setTimeout(resolve, 1000));
  };

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
          <HealthOverview
            data={mockHealthData}
            onRefresh={handleRefresh}
            refreshInterval={30000} // 30 seconds
          />
        </div>
      </AdminLayout>
    </AdminGuard>
  );
}
