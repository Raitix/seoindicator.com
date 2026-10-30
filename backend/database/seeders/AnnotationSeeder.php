<?php

namespace Database\Seeders;

use App\Models\Annotation;
use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating test annotation data.
 *
 * Creates a mix of annotations with various categories and dates
 * for testing the keyword annotation feature.
 */
class AnnotationSeeder extends Seeder
{
    /**
     * Annotation categories for test data.
     */
    protected array $categories = [
        'algorithm-update',
        'content-update',
        'technical-change',
        'link-building',
        'competition',
        'seasonal',
        'manual-action',
        'recovery',
        'other',
    ];

    /**
     * Sample annotation titles by category.
     */
    protected array $sampleTitles = [
        'algorithm-update' => [
            'Google Core Update Impact',
            'Helpful Content Update Applied',
            'Link Spam Update Detected',
            'Mobile-First Indexing Change',
        ],
        'content-update' => [
            'Published new blog post',
            'Updated meta descriptions',
            'Added FAQ schema markup',
            'Refreshed landing page content',
        ],
        'technical-change' => [
            'Site migration completed',
            'HTTPS implementation',
            'Core Web Vitals improvement',
            'Server infrastructure upgrade',
        ],
        'link-building' => [
            'Guest post published on industry blog',
            'New backlink from authority site',
            'Broken link building success',
            'HARO link acquired',
        ],
        'competition' => [
            'Competitor launched new feature',
            'Competitor price change',
            'New market entrant detected',
            'Competitor rebranding',
        ],
        'seasonal' => [
            'Black Friday campaign started',
            'Holiday season traffic spike',
            'Seasonal product launch',
            'End-of-season sale',
        ],
        'manual-action' => [
            'Google manual penalty received',
            'Penalty lifted',
            'Disavow file submitted',
            ' reconsideration request sent',
        ],
        'recovery' => [
            'Recovery from algorithm penalty',
            'Traffic restored after fix',
            'Position recovery observed',
            'Indexing issues resolved',
        ],
        'other' => [
            'General observation',
            'Team notification',
            'Client feedback received',
            'Industry news impact',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing keywords and projects
        $keywords = Keyword::all();
        $projects = Project::all();

        if ($keywords->isEmpty() || $projects->isEmpty()) {
            $this->command->warn('No keywords or projects found. Please run KeywordSeeder and ProjectSeeder first.');
            return;
        }

        $annotationsCreated = 0;

        // Create annotations for each keyword
        foreach ($keywords as $keyword) {
            // Find a project for this keyword (assume keyword belongs to a project via site or user)
            $project = $projects->first();
            
            if (!$project) {
                continue;
            }

            // Create 1-3 annotations per keyword
            $annotationCount = rand(1, 3);
            
            for ($i = 0; $i < $annotationCount; $i++) {
                $category = $this->categories[array_rand($this->categories)];
                $titles = $this->sampleTitles[$category] ?? $this->sampleTitles['other'];
                $title = $titles[array_rand($titles)];
                
                // Random date within last 90 days
                $date = now()->subDays(rand(0, 90))->format('Y-m-d');
                
                Annotation::create([
                    'keyword_id' => $keyword->id,
                    'project_id' => $project->id,
                    'date' => $date,
                    'title' => $title,
                    'description' => $this->generateDescription($category, $title),
                    'category' => $category,
                ]);
                
                $annotationsCreated++;
            }
        }

        // Create some specific sample annotations for demonstration
        $this->createSampleAnnotations($keywords, $projects);

        $this->command->info("Created {$annotationsCreated} test annotations.");
    }

    /**
     * Create specific sample annotations for demonstration purposes.
     */
    protected function createSampleAnnotations($keywords, $projects): void
    {
        if ($keywords->isEmpty() || $projects->isEmpty()) {
            return;
        }

        $keyword = $keywords->first();
        $project = $projects->first();

        // Algorithm update annotation
        Annotation::create([
            'keyword_id' => $keyword->id,
            'project_id' => $project->id,
            'date' => now()->subDays(7)->format('Y-m-d'),
            'title' => 'Google March 2026 Core Update',
            'description' => 'Position dropped from #5 to #12 after core update rollout. Need to review content quality and E-E-A-T signals.',
            'category' => 'algorithm-update',
        ]);

        // Content update annotation
        Annotation::create([
            'keyword_id' => $keyword->id,
            'project_id' => $project->id,
            'date' => now()->subDays(14)->format('Y-m-d'),
            'title' => 'Published comprehensive guide',
            'description' => 'Added 3000-word guide targeting this keyword with updated statistics and examples.',
            'category' => 'content-update',
        ]);

        // Link building annotation
        Annotation::create([
            'keyword_id' => $keyword->id,
            'project_id' => $project->id,
            'date' => now()->subDays(3)->format('Y-m-d'),
            'title' => 'New DR70 backlink acquired',
            'description' => 'Guest post published on industry-leading blog with dofollow link to target page.',
            'category' => 'link-building',
        ]);
    }

    /**
     * Generate a realistic description for an annotation.
     */
    protected function generateDescription(string $category, string $title): ?string
    {
        // 30% chance of no description
        if (rand(1, 10) <= 3) {
            return null;
        }

        $descriptions = [
            'algorithm-update' => [
                'Noticed significant position changes across multiple keywords. Monitoring closely.',
                'SERP volatility detected. Position changes may be temporary.',
                'Algorithm change affected rankings. Reviewing affected pages.',
            ],
            'content-update' => [
                'Content optimization completed. Monitoring for ranking improvements.',
                'Fresh content added to improve relevance signals.',
                'Updated content to better match search intent.',
            ],
            'technical-change' => [
                'Technical improvement implemented. Expecting positive impact on rankings.',
                'Performance optimization completed.',
                'Site structure updated for better crawlability.',
            ],
            'link-building' => [
                'New backlink acquired. Monitoring impact on rankings.',
                'Link building campaign showing results.',
                'Quality backlink from relevant source.',
            ],
            'competition' => [
                'Competitor activity may affect our rankings.',
                'Market dynamics changed. Monitoring impact.',
                'New competitive pressure detected.',
            ],
            'seasonal' => [
                'Seasonal trend affecting search volume and rankings.',
                'Expected seasonal fluctuation. No action required.',
                'Seasonal campaign active.',
            ],
            'manual-action' => [
                'Manual action requires immediate attention.',
                'Working on resolving the issue.',
                'Penalty status updated.',
            ],
            'recovery' => [
                'Recovery efforts showing positive results.',
                'Rankings improving after fix implementation.',
                'Traffic returning to normal levels.',
            ],
            'other' => [
                'General note for reference.',
                'Monitoring situation.',
                'Information logged for future reference.',
            ],
        ];

        $categoryDescriptions = $descriptions[$category] ?? $descriptions['other'];
        return $categoryDescriptions[array_rand($categoryDescriptions)];
    }
}
