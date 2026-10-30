"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { AdminGuard } from "@/components/admin";

/**
 * Admin Index Page
 * Redirects to /admin/overview
 */
export default function AdminIndexPage() {
  const router = useRouter();

  useEffect(() => {
    router.replace("/admin/overview");
  }, [router]);

  return (
    <AdminGuard>
      <div className="flex min-h-screen items-center justify-center bg-slate-950">
        <div className="text-slate-400">Redirecting to overview...</div>
      </div>
    </AdminGuard>
  );
}
