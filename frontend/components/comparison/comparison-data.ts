export const competitorsList = ["Searchly", "RankForge", "VistaSEO"] as const;

export type CompetitorName = (typeof competitorsList)[number];

export type ComparisonStatus = "winning" | "losing" | "gap" | "both";

export type ComparisonRow = {
  keyword: string;
  myPosition: number | null;
  competitors: Record<CompetitorName, number | null>;
  status: ComparisonStatus;
  bestCompetitorPosition: number | null;
  diff: number | null;
};

export const mockRows: ComparisonRow[] = [
  {
    keyword: "seo dashboard",
    myPosition: 3,
    competitors: { Searchly: 5, RankForge: 8, VistaSEO: 6 },
    status: "winning",
    bestCompetitorPosition: 5,
    diff: 2,
  },
  {
    keyword: "keyword gap tool",
    myPosition: 12,
    competitors: { Searchly: 6, RankForge: 9, VistaSEO: 11 },
    status: "losing",
    bestCompetitorPosition: 6,
    diff: -6,
  },
  {
    keyword: "competitor ranking tracker",
    myPosition: null,
    competitors: { Searchly: 7, RankForge: 10, VistaSEO: 9 },
    status: "gap",
    bestCompetitorPosition: 7,
    diff: null,
  },
  {
    keyword: "serp visibility score",
    myPosition: 9,
    competitors: { Searchly: 9, RankForge: 15, VistaSEO: 14 },
    status: "both",
    bestCompetitorPosition: 9,
    diff: 0,
  },
  {
    keyword: "seo audit insights",
    myPosition: 2,
    competitors: { Searchly: 4, RankForge: null, VistaSEO: 5 },
    status: "winning",
    bestCompetitorPosition: 4,
    diff: 2,
  },
  {
    keyword: "site health score",
    myPosition: 18,
    competitors: { Searchly: 13, RankForge: 12, VistaSEO: 16 },
    status: "losing",
    bestCompetitorPosition: 12,
    diff: -6,
  },
  {
    keyword: "technical seo report",
    myPosition: 6,
    competitors: { Searchly: null, RankForge: 7, VistaSEO: 8 },
    status: "both",
    bestCompetitorPosition: 7,
    diff: 1,
  },
  {
    keyword: "content opportunity finder",
    myPosition: null,
    competitors: { Searchly: 10, RankForge: 14, VistaSEO: 13 },
    status: "gap",
    bestCompetitorPosition: 10,
    diff: null,
  },
];

export const statusMeta: Record<ComparisonStatus, { label: string; className: string }> = {
  winning: { label: "I’m Winning", className: "bg-emerald-500/15 text-emerald-200 border-emerald-500/40" },
  losing: { label: "They’re Winning", className: "bg-rose-500/15 text-rose-200 border-rose-500/40" },
  gap: { label: "Keyword Gap", className: "bg-amber-500/15 text-amber-200 border-amber-500/40" },
  both: { label: "Both Rank", className: "bg-sky-500/15 text-sky-200 border-sky-500/40" },
};

export const filterOptions = ["All", "I’m Winning", "They’re Winning", "Keyword Gaps", "Both Rank"] as const;
export const sortOptions = ["Keyword", "My Position", "Competitor Position", "Diff"] as const;

export type FilterOption = (typeof filterOptions)[number];
export type SortOption = (typeof sortOptions)[number];

export function formatPosition(position: number | null) {
  return position === null ? "Not ranking" : `#${position}`;
}
