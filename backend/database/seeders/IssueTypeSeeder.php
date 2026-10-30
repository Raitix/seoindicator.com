<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder for initial SEO issue type templates.
 *
 * Populates the issue_types table with common SEO issues
 * organized by category with default severity and recommendations.
 *
 * @see TASK-14-3
 */
class IssueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $issueTypes = [
            // Meta Category
            [
                'category' => 'meta',
                'name' => 'Missing Title Tag',
                'description' => 'The page is missing a title tag which is crucial for SEO and search engine rankings.',
                'severity_default' => 'critical',
                'recommendation_template' => 'Add a descriptive title tag (50-60 characters) that includes your primary keyword.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'meta',
                'name' => 'Title Tag Too Long',
                'description' => 'The title tag exceeds the recommended length of 60 characters and may be truncated in search results.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Shorten your title tag to 50-60 characters to ensure it displays fully in search results.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'meta',
                'name' => 'Title Tag Too Short',
                'description' => 'The title tag is too short and may not provide enough context for search engines.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Expand your title tag to at least 30 characters to provide more context.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'meta',
                'name' => 'Missing Meta Description',
                'description' => 'The page is missing a meta description which appears in search results.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Add a compelling meta description (150-160 characters) that summarizes the page content.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'meta',
                'name' => 'Duplicate Title Tag',
                'description' => 'Multiple pages have the same title tag which can confuse search engines.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Ensure each page has a unique title tag that accurately describes its content.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'meta',
                'name' => 'Duplicate Meta Description',
                'description' => 'Multiple pages have the same meta description which reduces click-through rates.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Create unique meta descriptions for each page to improve CTR.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Content Category
            [
                'category' => 'content',
                'name' => 'Missing H1 Tag',
                'description' => 'The page is missing an H1 heading which is important for content structure.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Add a single H1 tag that includes your primary keyword and describes the page content.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'content',
                'name' => 'Multiple H1 Tags',
                'description' => 'The page has multiple H1 tags which can dilute the SEO value.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Use only one H1 tag per page and use H2-H6 for subheadings.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'content',
                'name' => 'Thin Content',
                'description' => 'The page has very little content which may not provide enough value.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Add more relevant content to provide value to users and search engines.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'content',
                'name' => 'Keyword Stuffing',
                'description' => 'The page appears to overuse keywords which can be seen as spammy.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Reduce keyword density and use natural language. Aim for 1-2% keyword density.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'content',
                'name' => 'Duplicate Content',
                'description' => 'The page contains content that appears elsewhere on your site or other sites.',
                'severity_default' => 'critical',
                'recommendation_template' => 'Create unique content for this page or use canonical tags to indicate the preferred version.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Backlink Category
            [
                'category' => 'backlink',
                'name' => 'Low Quality Backlinks',
                'description' => 'The site has backlinks from low-quality or spammy websites.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Disavow low-quality backlinks using Google Search Console and focus on building quality links.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'backlink',
                'name' => 'Broken Inbound Links',
                'description' => 'External sites are linking to pages that return 404 errors.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Set up 301 redirects for broken inbound links or restore the missing pages.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'backlink',
                'name' => 'Too Many Links From Same Domain',
                'description' => 'A significant portion of backlinks come from a single domain.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Diversify your backlink profile by acquiring links from various domains.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Performance Category
            [
                'category' => 'performance',
                'name' => 'Slow Page Load Time',
                'description' => 'The page takes too long to load which affects user experience and rankings.',
                'severity_default' => 'critical',
                'recommendation_template' => 'Optimize images, enable compression, and use browser caching to improve load times.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'performance',
                'name' => 'Large Page Size',
                'description' => 'The page size is too large which can affect load times.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Reduce page size by optimizing images, minifying CSS/JS, and removing unused code.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'performance',
                'name' => 'Unoptimized Images',
                'description' => 'Images on the page are not optimized which increases load time.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Compress images, use appropriate formats (WebP), and implement lazy loading.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'performance',
                'name' => 'Missing Browser Caching',
                'description' => 'Static resources are not cached by the browser.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Configure browser caching headers for static resources to improve repeat visits.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'performance',
                'name' => 'Render-Blocking Resources',
                'description' => 'CSS and JavaScript files are blocking the initial render.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Defer non-critical JavaScript and inline critical CSS to improve rendering.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('issue_types')->insert($issueTypes);

        $this->command->info('Seeded ' . count($issueTypes) . ' issue types.');
    }
}