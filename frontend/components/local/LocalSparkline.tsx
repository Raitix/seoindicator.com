"use client";

import { Area, AreaChart, ResponsiveContainer, Tooltip } from "recharts";

import type { TrendPoint } from "@/lib/types/location-dashboard";

export interface LocalSparklineProps {
  label: string;
  data: TrendPoint[];
  stroke?: string;
  fill?: string;
}

export function LocalSparkline({
  label,
  data,
  stroke = "#38bdf8",
  fill = "rgba(56, 189, 248, 0.18)",
}: LocalSparklineProps) {
  if (!data.length) {
    return (
      <div
        className="flex h-16 items-center justify-center rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 text-xs text-slate-500"
        role="img"
        aria-label={`${label} sparkline`}
      >
        No trend data
      </div>
    );
  }

  return (
    <div className="h-16 w-full min-w-0" role="img" aria-label={`${label} sparkline`}>
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart data={data} margin={{ top: 4, right: 0, left: 0, bottom: 0 }}>
          <Tooltip
            cursor={false}
            formatter={(value) => {
              const v = Array.isArray(value) ? value[0] : value;
              return [
                typeof v === "number" ? v.toLocaleString() : String(v ?? 0),
                label,
              ];
            }}
            contentStyle={{
              borderRadius: 12,
              border: "1px solid rgba(51, 65, 85, 1)",
              backgroundColor: "rgba(2, 6, 23, 0.95)",
              color: "#e2e8f0",
              fontSize: 12,
            }}
          />
          <Area type="monotone" dataKey="value" stroke={stroke} fill={fill} strokeWidth={2} dot={false} />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
}

export default LocalSparkline;
