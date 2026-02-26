"use client";

import * as React from "react";
import { AllProjectsView, AddProjectModal } from "@/components/projects";
import { useProjectContext } from "@/lib/contexts/ProjectContext";

export default function ProjectsPage() {
  const { createProject } = useProjectContext();
  const [showCreateModal, setShowCreateModal] = React.useState(false);

  return (
    <div className="container mx-auto px-4 py-8">
      <AllProjectsView
        onCreateProject={() => setShowCreateModal(true)}
      />
      <AddProjectModal
        open={showCreateModal}
        onOpenChange={setShowCreateModal}
        onCreateProject={createProject}
      />
    </div>
  );
}
