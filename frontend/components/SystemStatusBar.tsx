"use client";

/**
 * Live system status bar.
 *
 * Reads the real backend health endpoint (`/api/health`) so the homepage
 * never claims "Operational" when the API is actually down. The previous
 * implementation hardcoded the message.
 */

import * as React from "react";

interface HealthResponse {
  status?: string;
  database?: string;
  app?: string;
  env?: string;
}

type State =
  | { kind: "loading" }
  | { kind: "ok"; database: string; app: string }
  | { kind: "degraded"; message: string }
  | { kind: "unknown" };

const HEALTH_URL = "/api/health";

export default function SystemStatusBar() {
  const [state, setState] = React.useState<State>({ kind: "loading" });

  React.useEffect(() => {
    let cancelled = false;

    async function check() {
      try {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 5000);

        const response = await fetch(HEALTH_URL, {
          signal: controller.signal,
          headers: { Accept: "application/json" },
        });
        clearTimeout(timeout);

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = (await response.json()) as HealthResponse;

        if (cancelled) return;

        if (data.status === "ok") {
          setState({
            kind: "ok",
            database: data.database ?? "unknown",
            app: data.app ?? "SEOIndicator",
          });
        } else {
          setState({
            kind: "degraded",
            message: data.status ?? "unknown status",
          });
        }
      } catch {
        if (!cancelled) {
          setState({ kind: "unknown" });
        }
      }
    }

    check();

    // Re-check periodically so the badge does not go stale.
    const interval = setInterval(check, 60_000);
    return () => {
      cancelled = true;
      clearInterval(interval);
    };
  }, []);

  const dotClass =
    state.kind === "ok"
      ? "bg-success"
      : state.kind === "loading"
        ? "bg-slate-500"
        : "bg-warning";

  return (
    <div
      className="flex items-center justify-between py-4"
      data-testid="system-status-bar"
    >
      <div className="flex items-center gap-3">
        <div
          className={`h-3 w-3 ${dotClass} rounded-full animate-pulse`}
          aria-hidden="true"
        />
        <span className="text-slate-300" data-testid="system-status-label">
          {state.kind === "ok" && "System Status: Operational"}
          {state.kind === "loading" && "System Status: Checking..."}
          {state.kind === "degraded" &&
            `System Status: Degraded (${state.message})`}
          {state.kind === "unknown" && "System Status: Unreachable"}
        </span>
      </div>
      <span className="text-slate-500 text-sm" data-testid="system-status-detail">
        {state.kind === "ok" &&
          `${state.app} API Ready • Database ${state.database}`}
        {state.kind === "loading" && "Checking backend..."}
        {state.kind === "degraded" && "Backend reported a problem"}
        {state.kind === "unknown" && "Backend API not responding"}
      </span>
    </div>
  );
}
