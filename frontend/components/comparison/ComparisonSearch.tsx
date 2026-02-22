import { Input } from "@/components/ui/input";

interface ComparisonSearchProps {
  value: string;
  onChange: (value: string) => void;
}

export default function ComparisonSearch({ value, onChange }: ComparisonSearchProps) {
  return (
    <Input
      value={value}
      onChange={(event) => onChange(event.target.value)}
      placeholder="Search keyword..."
      className="max-w-xs border-slate-700 bg-slate-950 text-slate-200"
      aria-label="Search keyword"
    />
  );
}
