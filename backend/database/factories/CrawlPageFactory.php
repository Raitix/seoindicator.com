<?php

namespace Database\Factories;

use App\Models\CrawlPage;
use App\Models\CrawlJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrawlPage>
 */
class CrawlPageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CrawlPage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crawl_job_id' => CrawlJob::factory(),
            'url' => $this->faker->url(),
            'depth' => 0,
            'title' => $this->faker->sentence(5),
            'rendered_html' => '<!DOCTYPE html><html><head><title>Test</title></head><body><h1>Hello World</h1></body></html>',
            'structured_text' => "Hello World\n\nThis is a test page.",
            'meta' => [
                'description' => $this->faker->sentence(),
                'robots' => 'index, follow',
                'canonical' => $this->faker->url(),
            ],
            'links' => [
                ['href' => $this->faker->url(), 'text' => 'Link 1', 'internal' => true],
                ['href' => $this->faker->url(), 'text' => 'Link 2', 'internal' => false],
            ],
            'size' => $this->faker->numberBetween(1024, 102400),
            'crawled_at' => now(),
            'error' => null,
        ];
    }

    /**
     * Indicate that the page was crawled successfully.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'error' => null,
            'crawled_at' => now(),
        ]);
    }

    /**
     * Indicate that the page has an error.
     */
    public function withError(string $error = null): static
    {
        return $this->state(fn (array $attributes) => [
            'error' => $error ?? $this->faker->sentence(),
            'rendered_html' => null,
            'structured_text' => null,
            'crawled_at' => now(),
        ]);
    }

    /**
     * Indicate that the page belongs to a specific crawl job.
     */
    public function forJob(CrawlJob|int $job): static
    {
        return $this->state(fn (array $attributes) => [
            'crawl_job_id' => $job instanceof CrawlJob ? $job->id : $job,
        ]);
    }

    /**
     * Set a specific depth for the page.
     */
    public function atDepth(int $depth): static
    {
        return $this->state(fn (array $attributes) => [
            'depth' => $depth,
        ]);
    }

    /**
     * Set a specific URL for the page.
     */
    public function withUrl(string $url): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => $url,
        ]);
    }

    /**
     * Set specific links for the page.
     */
    public function withLinks(array $links): static
    {
        return $this->state(fn (array $attributes) => [
            'links' => $links,
        ]);
    }

    /**
     * Set specific meta data for the page.
     */
    public function withMeta(array $meta): static
    {
        return $this->state(fn (array $attributes) => [
            'meta' => $meta,
        ]);
    }

    /**
     * Indicate that the page has no links.
     */
    public function withoutLinks(): static
    {
        return $this->state(fn (array $attributes) => [
            'links' => null,
        ]);
    }

    /**
     * Indicate that the page has only internal links.
     */
    public function withOnlyInternalLinks(): static
    {
        return $this->state(fn (array $attributes) => [
            'links' => [
                ['href' => '/about', 'text' => 'About', 'internal' => true],
                ['href' => '/contact', 'text' => 'Contact', 'internal' => true],
            ],
        ]);
    }

    /**
     * Indicate that the page has only external links.
     */
    public function withOnlyExternalLinks(): static
    {
        return $this->state(fn (array $attributes) => [
            'links' => [
                ['href' => 'https://external.com', 'text' => 'External 1', 'internal' => false],
                ['href' => 'https://another.com', 'text' => 'External 2', 'internal' => false],
            ],
        ]);
    }

    /**
     * Indicate that the page has mixed links.
     */
    public function withMixedLinks(): static
    {
        return $this->state(fn (array $attributes) => [
            'links' => [
                ['href' => '/about', 'text' => 'About', 'internal' => true],
                ['href' => 'https://external.com', 'text' => 'External', 'internal' => false],
            ],
        ]);
    }

    /**
     * Set a specific size for the page.
     */
    public function withSize(int $bytes): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => $bytes,
        ]);
    }

    /**
     * Create a page with large content (near 16MB limit).
     */
    public function withLargeContent(): static
    {
        $largeHtml = str_repeat('<p>' . $this->faker->paragraph() . '</p>', 10000);

        return $this->state(fn (array $attributes) => [
            'rendered_html' => $largeHtml,
            'size' => strlen($largeHtml),
        ]);
    }
}
