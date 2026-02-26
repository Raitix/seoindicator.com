<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating test project data.
 *
 * Creates a mix of active, archived, default, and non-default projects
 * for testing the multi-site project management feature.
 */
class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users or create some if none exist
        $users = User::all();
        
        if ($users->isEmpty()) {
            $users = User::factory(3)->create();
        }

        foreach ($users as $user) {
            // Create one default project per user
            Project::factory()
                ->forUser($user)
                ->default()
                ->create([
                    'name' => 'Default Project',
                    'domain' => "https://{$user->id}.example.com",
                    'notes' => 'Default project for this user',
                ]);

            // Create 2-3 active projects per user
            Project::factory()
                ->count(rand(2, 3))
                ->forUser($user)
                ->create();

            // Create 1 archived project per user
            Project::factory()
                ->forUser($user)
                ->archived()
                ->create([
                    'name' => 'Archived Project',
                    'domain' => "https://archived-{$user->id}.example.com",
                ]);
        }

        $totalProjects = Project::count();
        $this->command->info("Created {$totalProjects} projects for {$users->count()} users.");
    }
}
