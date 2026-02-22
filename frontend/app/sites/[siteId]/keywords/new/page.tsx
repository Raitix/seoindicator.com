import KeywordCreateForm from "@/components/keywords/KeywordCreateForm";

interface PageProps {
  params: { siteId: string };
}

export async function generateStaticParams() {
  return [] as Array<{ siteId: string }>;
}

export default function NewKeywordPage({ params }: PageProps) {
  const siteId = Number(params.siteId);

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <div>
          <p className="text-sm uppercase tracking-[0.2em] text-emerald-400">Keyword Tracking</p>
          <h1 className="text-3xl font-semibold text-white">Add a new keyword</h1>
          <p className="mt-2 text-slate-400">
            Define the keyword you want to monitor and provide targeting details for rankings.
          </p>
        </div>

        <KeywordCreateForm siteId={siteId} />
      </div>
    </div>
  );
}
