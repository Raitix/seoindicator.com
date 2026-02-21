<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder for initial SEO issue type templates.
 * 
 * Run with: php artisan db:seed --class=IssueTypeSeeder
 */
class IssueTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $issueTypes = [
            // Meta Tag Issues
            [
                'category' => 'meta',
                'name' => 'Missing Title Tag',
                'description' => 'The page does not have a title tag. Title tags are critical for SEO as they appear in search results and browser tabs.',
                'severity_default' => 'critical',
                'recommendation_template' => 'Add a unique title tag to this page. Keep it between 50-60 characters and include relevant keywords.',
            ],
            [
                'category' => 'meta',
                'name' => 'Duplicate Title',
                'description' => 'Multiple pages have the same title tag. This can confuse search engines and users.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Each page should have a unique title. Update the title for {{url}} to differentiate it from other pages.',
            ],
            [
                'category' => 'meta',
                'name' => 'Missing Meta Description',
                'description' => 'The page lacks a meta description. Meta descriptions appear in search results and influence click-through rates.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Add a compelling meta description between 150-160 characters that summarizes the page content.',
            ],
            [
                'category' => 'meta',
                'name' => 'Title Too Long',
                'description' => 'The title tag exceeds 60 characters and may be truncated in search results.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Shorten the title tag to 50-60 characters to ensure it displays fully in search results.',
            ],
            [
                'category' => 'meta',
                'name' => 'Meta Description Too Long',
                'description' => 'The meta description exceeds 160 characters and may be truncated in search results.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Condense the meta description to 150-160 characters while maintaining key information.',
            ],

            // Content Issues
            [
                'category' => 'content',
                'name' => 'Missing H1 Tag',
                'description' => 'The page does not have an H1 heading. H1 tags help search engines understand the main topic of a page.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Add an H1 tag that clearly describes the page content. Include relevant keywords naturally.',
            ],
            [
                'category' => 'content',
                'name' => 'Multiple H1 Tags',
                'description' => 'The page has more than one H1 tag. Best practice is to have a single H1 per page.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Use only one H1 tag per page. Convert additional H1 tags to H2 or H3 as appropriate.',
            ],
            [
                'category' => 'content',
                'name' => 'Thin Content',
                'description' => 'The page has very little content, which may not provide sufficient value to users or search engines.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Expand the page content to provide more value. Aim for at least 300 words of unique, relevant content.',
            ],
            [
                'category' => 'content',
                'name' => 'Duplicate Content',
                'description' => 'Substantial content appears on multiple pages, which can lead to ranking issues.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Ensure each page has unique content. Use canonical tags if duplicate content is intentional.',
            ],

            // Technical SEO Issues
            [
                'category' => 'technical',
                'name' => 'Broken Link',
                'description' => 'The page contains a link that returns a 404 or 5xx error, creating a poor user experience.',
                'severity_default' => 'critical',
                'recommendation_template' => 'Fix or remove the broken link to {{url}}. Update the destination or remove the link entirely.',
            ],
            [
                'category' => 'technical',
                'name' => 'Redirect Chain',
                'description' => 'The URL redirects multiple times before reaching the final destination, slowing page load.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Update the link to point directly to the final destination URL to reduce redirect overhead.',
            ],
            [
                'category' => 'technical',
                'name' => 'Missing Sitemap',
                'description' => 'No XML sitemap found, which helps search engines discover and index pages.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Create an XML sitemap and submit it to Google Search Console and other search engines.',
            ],
            [
                'category' => 'technical',
                'name' => 'Missing Robots.txt',
                'description' => 'No robots.txt file found, which controls crawler access to your site.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Create a robots.txt file to guide search engine crawlers on which pages to index.',
            ],
            [
                'category' => 'technical',
                'name' => 'Non-HTTPS URL',
                'description' => 'The page is not served over HTTPS, which is a ranking factor and security requirement.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Implement SSL certificate and redirect all HTTP traffic to HTTPS.',
            ],

            // Performance Issues
            [
                'category' => 'performance',
                'name' => 'Slow Page Load',
                'description' => 'The page takes more than 3 seconds to load, negatively affecting user experience and rankings.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Optimize page speed by compressing images, minifying CSS/JS, and leveraging browser caching. Current load time: {{load_time}}s.',
            ],
            [
                'category' => 'performance',
                'name' => 'Large Page Size',
                'description' => 'The page size exceeds 2MB, which can slow loading times especially on mobile devices.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Reduce page size by optimizing images and removing unnecessary resources. Current size: {{page_size}}KB.',
            ],
            [
                'category' => 'performance',
                'name' => 'Unoptimized Images',
                'description' => 'Images on the page are not optimized, leading to larger file sizes and slower loads.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Compress images and use modern formats like WebP. Consider lazy loading for images below the fold.',
            ],

            // Mobile Optimization Issues
            [
                'category' => 'mobile',
                'name' => 'Missing Viewport Meta Tag',
                'description' => 'The page lacks a viewport meta tag, causing improper rendering on mobile devices.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Add <meta name="viewport" content="width=device-width, initial-scale=1"> to the page head.',
            ],
            [
                'category' => 'mobile',
                'name' => 'Not Mobile Friendly',
                'description' => 'The page does not pass Google\'s mobile-friendly test, potentially affecting mobile rankings.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Ensure text is readable without zooming, tap targets are properly sized, and content fits the viewport.',
            ],
            [
                'category' => 'mobile',
                'name' => 'Small Tap Targets',
                'description' => 'Interactive elements are too small for touch targets, making them difficult to tap on mobile.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Increase tap target sizes to at least 48x48 pixels with adequate spacing between elements.',
            ],

            // Accessibility Issues
            [
                'category' => 'accessibility',
                'name' => 'Images Missing Alt Text',
                'description' => 'Images on the page lack alt attributes, which are important for accessibility and SEO.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Add descriptive alt text to all images. For decorative images, use empty alt="".',
            ],
            [
                'category' => 'accessibility',
                'name' => 'Low Contrast Ratio',
                'description' => 'Text on the page has insufficient color contrast, making it difficult to read.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Increase contrast ratio to at least 4.5:1 for normal text and 3:1 for large text.',
            ],
            [
                'category' => 'accessibility',
                'name' => 'Missing Form Labels',
                'description' => 'Form inputs lack associated labels, affecting screen reader users.',
                'severity_default' => 'notice',
                'recommendation_template' => 'Add explicit labels to all form inputs using the <label> element or aria-label attribute.',
            ],

            // Security Issues
            [
                'category' => 'security',
                'name' => 'Mixed Content',
                'description' => 'HTTPS page loads insecure HTTP resources, triggering browser warnings.',
                'severity_default' => 'warning',
                'recommendation_template' => 'Update all embedded resources to use HTTPS URLs to prevent mixed content warnings.',
            ],
        ];

        foreach ($issueTypes as $issueType) {
            DB::table('issue_types')->insert(array_merge($issueType, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('Seeded ' . count($issueTypes) . ' issue types.');
    }
}
