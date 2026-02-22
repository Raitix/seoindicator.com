interface SortControlProps<T extends string> {
  value: T;
  options: readonly T[];
  onChange: (value: T) => void;
}

export default function SortControl<T extends string>({ value, options, onChange }: SortControlProps<T>) {
  return (
    <select
      value={value}
      onChange={(event) => onChange(event.target.value as T)}
      className="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200"
      aria-label="Sort"
    >
      {options.map((option) => (
        <option key={option} value={option}>
          Sort by {option}
        </option>
      ))}
    </select>
  );
}
