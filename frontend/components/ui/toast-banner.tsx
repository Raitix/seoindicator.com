import { cn } from "@/lib/utils";

export type ToastVariant = "success" | "error" | "warning";

interface ToastBannerProps {
  variant: ToastVariant;
  message: string;
  onDismiss?: () => void;
}

const variantStyles: Record<ToastVariant, string> = {
  success: "border-emerald-500/40 bg-emerald-500/10 text-emerald-100",
  error: "border-rose-500/40 bg-rose-500/10 text-rose-100",
  warning: "border-amber-400/40 bg-amber-400/10 text-amber-100",
};

export function ToastBanner({ variant, message, onDismiss }: ToastBannerProps) {
  return (
    <div
      className={cn(
        "flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg backdrop-blur",
        variantStyles[variant]
      )}
      role="status"
    >
      <span className="flex-1">{message}</span>
      {onDismiss ? (
        <button
          type="button"
          onClick={onDismiss}
          className="text-xs uppercase tracking-wide opacity-80 transition hover:opacity-100"
        >
          Dismiss
        </button>
      ) : null}
    </div>
  );
}
