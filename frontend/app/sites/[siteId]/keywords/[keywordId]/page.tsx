interface PageProps {
  params: { siteId: string; keywordId: string };
}

export const dynamicParams = false;

export async function generateStaticParams(): Promise<Array<{ siteId: string; keywordId: string }>> {
  return [{ siteId: "1", keywordId: "1" }];
}

export default function KeywordPage({ params }: PageProps) {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10 text-slate-300">
      <div className="mx-auto w-full max-w-4xl rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <h1 className="text-2xl font-semibold text-white">Keyword {params.keywordId}</h1>
        <p className="mt-2 text-sm text-slate-400">
          Use the edit view to update keyword tracking details for site {params.siteId}.
        </p>
      </div>
    </div>
  );
}
