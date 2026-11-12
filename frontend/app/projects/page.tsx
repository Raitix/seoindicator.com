"use client";

import * as React from "react";
import { AllProjectsView, AddProjectModal } from "@/components/projects";
import { useProjectContext } from "@/lib/contexts/ProjectContext";
import { useAuth } from "@/lib/contexts/AuthContext";
import GuestPreview from "@/components/auth/GuestPreview";

export default function ProjectsPage() {
  const { createProject } = useProjectContext();
  const [showCreateModal, setShowCreateModal] = React.useState(false);
  const { isAuthenticated, isLoading } = useAuth();

  // Guest preview: the project list belongs to an account, so an anonymous
  // visitor gets the page structure and a prompt rather than a loader that
  // would never resolve.
  if (!isLoading && !isAuthenticated) {
    return (
      <div className="container mx-auto px-4 py-8">
        <div className="mb-6">
          <h1 className="text-2xl font-semibold text-white">Projects</h1>
          <p className="text-sm text-slate-300">
            Group your sites by client, brand or campaign.
          </p>
        </div>
        <GuestPreview feature="your projects and the sites inside them" />
      </div>
    );
  }

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
