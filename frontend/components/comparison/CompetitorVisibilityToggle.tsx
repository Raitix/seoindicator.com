import type { CompetitorName } from "./comparison-data";

interface CompetitorVisibilityToggleProps {
  competitors: readonly CompetitorName[];
  visibleCompetitors: CompetitorName[];
  onChange: (next: CompetitorName[]) => void;
}

export default function CompetitorVisibilityToggle({
  competitors,
  visibleCompetitors,
  onChange,
}: CompetitorVisibilityToggleProps) {
  return (
    <div className="flex flex-wrap gap-2">
      {competitors.map((competitor) => {
        const checked = visibleCompetitors.includes(competitor);
        return (
          <label
            key={competitor}
            className="flex items-center gap-2 rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-xs text-slate-200"
          >
            <input
              type="checkbox"
              checked={checked}
              onChange={(event) => {
                if (event.target.checked) {
                  const next = competitors.filter((name) =>
                    name === competitor ? true : visibleCompetitors.includes(name)
                  );
                  onChange(next as CompetitorName[]);
                  return;
                }

                if (visibleCompetitors.length === 1) {
                  return;
                }

                onChange(visibleCompetitors.filter((item) => item !== competitor));
              }}
              className="accent-emerald-500"
              aria-label={`Toggle ${competitor}`}
            />
            {competitor}
          </label>
        );
      })}
    </div>
  );
}
