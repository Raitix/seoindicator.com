"use client";

import * as React from "react";
import Link from "next/link";
import { ArrowLeft } from "lucide-react";

import { AddProjectModal } from "@/components/projects";
import { useProjectContext } from "@/lib/contexts/ProjectContext";

export default function NewProjectPage() {
  const { createProject } = useProjectContext();

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Header with navigation */}
      <div className="mb-6">
        <Link
          href="/projects"
          className="flex items-center gap-2 text-sm text-slate-400 hover:text-white"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Projects
        </Link>
      </div>

      <div className="mx-auto max-w-lg">
        <h1 className="mb-6 text-2xl font-bold text-white">Create New Project</h1>
        <AddProjectModal
          onCreateProject={createProject}
          defaultOpen={true}
          onOpenChange={(open) => {
            if (!open) {
              // Navigate back to projects when modal closes
              window.location.href = "/projects";
            }
          }}
          trigger={<div />}
        />
      </div>
    </div>
  );
}
