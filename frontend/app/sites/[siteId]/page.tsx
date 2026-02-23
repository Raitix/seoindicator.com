interface PageProps {
  params: { siteId: string };
}

export const dynamicParams = false;

export async function generateStaticParams(): Promise<Array<{ siteId: string }>> {
  return [{ siteId: "1" }];
}

export default async function SitePage({ params }: PageProps) {
  const { siteId } = await params;

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10 text-slate-300">
      <div className="mx-auto w-full max-w-4xl rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <h1 className="text-2xl font-semibold text-white">Site {siteId}</h1>
        <p className="mt-2 text-sm text-slate-400">
          Select a keyword view from the navigation to manage tracking details.
        </p>
      </div>
    </div>
  );
}
