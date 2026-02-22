import KeywordEditForm from "@/components/keywords/KeywordEditForm";

interface PageProps {
  params: { siteId: string; keywordId: string };
}

export const dynamicParams = false;

export async function generateStaticParams(): Promise<Array<{ siteId: string; keywordId: string }>> {
  return [{ siteId: "1", keywordId: "1" }];
}

export default function EditKeywordPage({ params }: PageProps) {
  const siteId = Number(params.siteId);
  const keywordId = Number(params.keywordId);

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <div>
          <p className="text-sm uppercase tracking-[0.2em] text-emerald-400">Keyword Tracking</p>
          <h1 className="text-3xl font-semibold text-white">Edit keyword</h1>
          <p className="mt-2 text-slate-400">
            Update the target URL or pause keywords that are no longer relevant.
          </p>
        </div>

        <KeywordEditForm siteId={siteId} keywordId={keywordId} />
      </div>
    </div>
  );
}
