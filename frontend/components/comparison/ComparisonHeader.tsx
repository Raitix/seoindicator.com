import { CardDescription, CardTitle } from "@/components/ui/card";

interface ComparisonHeaderProps {
  title: string;
  description: string;
}

export default function ComparisonHeader({ title, description }: ComparisonHeaderProps) {
  return (
    <div>
      <CardTitle className="text-white">{title}</CardTitle>
      <CardDescription>{description}</CardDescription>
    </div>
  );
}
