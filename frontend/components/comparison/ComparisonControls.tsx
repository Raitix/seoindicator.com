import type { ReactNode } from "react";

interface ComparisonControlsProps {
  search: ReactNode;
  filter: ReactNode;
  sort: ReactNode;
}

export default function ComparisonControls({ search, filter, sort }: ComparisonControlsProps) {
  return <div className="flex flex-wrap gap-3">{search}{filter}{sort}</div>;
}
