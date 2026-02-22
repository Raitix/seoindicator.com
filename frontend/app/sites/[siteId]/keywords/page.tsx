import Link from "next/link";

interface PageProps {
  params: { siteId: string };
}

export const dynamicParams = true;

export async function generateStaticParams() {
  return [] as Array<{ siteId: string }>;
}

export default function KeywordsPage({ params }: PageProps) {
  const siteId = Number(params.siteId);

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <div className="flex flex-col gap-2">
          <p className="text-sm uppercase tracking-[0.2em] text-emerald-400">Keyword Tracking</p>
          <h1 className="text-3xl font-semibold text-white">Keywords</h1>
          <p className="text-slate-400">
            Manage keywords tracked for this site. Use the actions below to add or edit keyword targets.
          </p>
        </div>

        <div className="flex flex-wrap gap-3">
          <Link
            href={`/sites/${siteId}/keywords/new`}
            className="inline-flex items-center justify-center rounded-md bg-emerald-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-600"
          >
            Add keyword
          </Link>
        </div>

        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-6 text-sm text-slate-400">
          Keyword list UI will appear here once keyword tracking views are finalized.
        </div>
      </div>
    </div>
  );
}
