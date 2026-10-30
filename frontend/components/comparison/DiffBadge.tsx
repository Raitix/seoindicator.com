interface DiffBadgeProps {
  value: number | null;
}

export default function DiffBadge({ value }: DiffBadgeProps) {
  if (value === null) {
    return (
      <span className="text-xs text-slate-500" data-testid="diff-badge">
        —
      </span>
    );
  }

  const label = value > 0 ? `(+${value})` : `(${value})`;
  const className = value > 0 ? "text-rose-300" : value < 0 ? "text-emerald-300" : "text-slate-300";

  return (
    <span className={`text-xs ${className}`} data-testid="diff-badge">
      {label}
    </span>
  );
}
