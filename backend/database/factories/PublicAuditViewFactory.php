<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\PublicAuditView;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for PublicAuditView model
 *
 * TASK-33-2: Create Public Audit Views Table
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PublicAuditView>
 */
class PublicAuditViewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PublicAuditView::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_id' => Audit::factory(),
            'public_hash' => Str::random(32),
            'viewed_at' => now(),
            'referrer' => null,
            'ip_hash' => null,
            'user_agent' => $this->faker->userAgent(),
            'country_code' => null,
            'is_lead_capture' => false,
            'lead_email' => null,
        ];
    }

    /**
     * Create for a specific audit
     * @param Audit|int $audit Audit model or ID
     */
    public function forAudit(Audit|int $audit): static
    {
        return $this->state(fn(array $attributes) => [
            'audit_id' => $audit instanceof Audit ? $audit->id : $audit,
        ]);
    }

    /**
     * Create with a specific public hash
     */
    public function withHash(string $hash): static
    {
        return $this->state(fn(array $attributes) => [
            'public_hash' => $hash,
        ]);
    }

    /**
     * Create with referrer
     */
    public function withReferrer(string $referrer = null): static
    {
        return $this->state(fn(array $attributes) => [
            'referrer' => $referrer ?? $this->faker->url(),
        ]);
    }

    /**
     * Create with specific referrer
     */
    public function withSpecificReferrer(string $referrer): static
    {
        return $this->state(fn(array $attributes) => [
            'referrer' => $referrer,
        ]);
    }

    /**
     * Create with IP hash (simulating hashed visitor IP)
     * @param string $ipOrHash IP address to hash, or pre-computed hash (64 chars)
     */
    public function withIpHash(string $ipOrHash = '192.168.1.1'): static
    {
        // If it looks like a SHA-256 hash (64 hex chars), use it directly
        // Otherwise, hash the IP address
        $hash = (strlen($ipOrHash) === 64 && ctype_xdigit($ipOrHash))
            ? $ipOrHash
            : hash('sha256', $ipOrHash . config('app.key'));
        
        return $this->state(fn(array $attributes) => [
            'ip_hash' => $hash,
        ]);
    }

    /**
     * Create with specific user agent
     */
    public function withUserAgent(string $userAgent): static
    {
        return $this->state(fn(array $attributes) => [
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Create with country code
     */
    public function withCountry(string $countryCode): static
    {
        return $this->state(fn(array $attributes) => [
            'country_code' => strtoupper($countryCode),
        ]);
    }

    /**
     * Create as lead capture with email
     */
    public function leadCapture(string $email = null): static
    {
        return $this->state(fn(array $attributes) => [
            'is_lead_capture' => true,
            'lead_email' => $email ?? $this->faker->safeEmail(),
        ]);
    }

    /**
     * Create with specific viewed_at timestamp
     */
    public function viewedAt(\DateTimeInterface $timestamp): static
    {
        return $this->state(fn(array $attributes) => [
            'viewed_at' => $timestamp,
        ]);
    }

    /**
     * Create with viewed_at in the past
     */
    public function viewedDaysAgo(int $days): static
    {
        return $this->state(fn(array $attributes) => [
            'viewed_at' => now()->subDays($days),
        ]);
    }

    /**
     * Create as Google referrer
     */
    public function fromGoogle(): static
    {
        return $this->state(fn(array $attributes) => [
            'referrer' => 'https://www.google.com/search?q=seo+audit',
        ]);
    }

    /**
     * Create as direct visit (no referrer)
     */
    public function directVisit(): static
    {
        return $this->state(fn(array $attributes) => [
            'referrer' => null,
        ]);
    }

    /**
     * Create with common browser user agent
     */
    public function chromeBrowser(): static
    {
        return $this->state(fn(array $attributes) => [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        ]);
    }

    /**
     * Create with Safari user agent
     */
    public function safariBrowser(): static
    {
        return $this->state(fn(array $attributes) => [
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        ]);
    }

    /**
     * Create with mobile user agent
     */
    public function mobileDevice(): static
    {
        return $this->state(fn(array $attributes) => [
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ]);
    }

    /**
     * Create with full analytics data
     */
    public function withFullAnalytics(): static
    {
        return $this->state(fn(array $attributes) => [
            'referrer' => $this->faker->url(),
            'ip_hash' => hash('sha256', $this->faker->ipv4() . config('app.key')),
            'user_agent' => $this->faker->userAgent(),
            'country_code' => $this->faker->countryCode(),
        ]);
    }
}
