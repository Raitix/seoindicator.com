import { Button } from "@/components/ui/button";

interface PaginationControlsProps {
  pageIndex: number;
  pageCount: number;
  canPrevious: boolean;
  canNext: boolean;
  onPrevious: () => void;
  onNext: () => void;
}

export default function PaginationControls({
  pageIndex,
  pageCount,
  canPrevious,
  canNext,
  onPrevious,
  onNext,
}: PaginationControlsProps) {
  return (
    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-400">
      <div>
        Page {pageIndex + 1} of {pageCount}
      </div>
      <div className="flex items-center gap-2">
        <Button
          variant="outline"
          className="border-slate-700 text-slate-200"
          onClick={onPrevious}
          disabled={!canPrevious}
        >
          Previous
        </Button>
        <Button
          variant="outline"
          className="border-slate-700 text-slate-200"
          onClick={onNext}
          disabled={!canNext}
        >
          Next
        </Button>
      </div>
    </div>
  );
}
