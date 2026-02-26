"use client";

import { ProjectProvider } from "@/lib/contexts/ProjectContext";

export default function ProjectsLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <ProjectProvider>{children}</ProjectProvider>;
}
