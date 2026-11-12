import { Button } from "@/components/ui/button";

interface ExportButtonProps {
  onExport: () => void;
}

export default function ExportButton({ onExport }: ExportButtonProps) {
  return (
    <Button onClick={onExport} className="bg-emerald-700 text-white hover:bg-emerald-800">
      Export CSV
    </Button>
  );
}
