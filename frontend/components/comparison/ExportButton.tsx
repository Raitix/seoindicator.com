import { Button } from "@/components/ui/button";

interface ExportButtonProps {
  onExport: () => void;
}

export default function ExportButton({ onExport }: ExportButtonProps) {
  return (
    <Button onClick={onExport} className="bg-emerald-500 text-white hover:bg-emerald-600">
      Export CSV
    </Button>
  );
}
