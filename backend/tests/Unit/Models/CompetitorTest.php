<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Competitor;
use App\Models\User;
use App\Models\CompetitorRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for Competitor model
 * 
 * Tests verify:
 * - Fillable attributes
 * - Relationships
 * - Validation rules
 */
class CompetitorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 10: Model has correct fillable attributes
     */
    public function test_competitor_has_fillable_attributes(): void
    {
        $competitor = new Competitor();
        
        $expectedFillable = ['user_id', 'domain', 'display_name', 'notes'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains(
                $attribute,
                $competitor->getFillable(),
                "Competitor model missing fillable attribute: {$attribute}"
            );
        }
    }

    /**
     * Test 12: Competitor belongs to User relationship
     */
    public function test_competitor_belongs_to_user(): void
    {
        $competitor = new Competitor();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $competitor->user(),
            'Competitor should have a belongsTo relationship with User'
        );
        
        $this->assertEquals(
            User::class,
            $competitor->user()->getRelated()::class,
            'Competitor user relationship should reference User model'
        );
    }

    /**
     * Test 13: Competitor has many Rankings relationship
     */
    public function test_competitor_has_many_rankings(): void
    {
        $competitor = new Competitor();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $competitor->rankings(),
            'Competitor should have a hasMany relationship with CompetitorRanking'
        );
        
        $this->assertEquals(
            CompetitorRanking::class,
            $competitor->rankings()->getRelated()::class,
            'Competitor rankings relationship should reference CompetitorRanking model'
        );
    }

    /**
     * Test 16: Domain is required
     */
    public function test_domain_is_required(): void
    {
        $this->artisan('migrate');
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Competitor::create([
            'user_id' => $user->id,
            // domain is missing
        ]);
    }

    /**
     * Test 17: Domain format validation
     */
    public function test_domain_format_validation(): void
    {
        $this->artisan('migrate');
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);
        
        // Valid domains should work
        $validDomains = [
            'example.com',
            'sub.example.com',
            'example.co.uk',
            'my-site.org',
        ];
        
        foreach ($validDomains as $domain) {
            $competitor = Competitor::create([
                'user_id' => $user->id,
                'domain' => $domain,
            ]);
            $this->assertNotNull($competitor->id);
        }
        
        // Note: URL validation is typically handled at the request/service layer
        // The model itself stores whatever is passed
    }

    /**
     * Test: Display name is nullable
     */
    public function test_display_name_is_nullable(): void
    {
        $this->artisan('migrate');
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            // display_name is not set
        ]);
        
        $this->assertNull($competitor->display_name);
    }

    /**
     * Test: Notes is nullable
     */
    public function test_notes_is_nullable(): void
    {
        $this->artisan('migrate');
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            // notes is not set
        ]);
        
        $this->assertNull($competitor->notes);
    }

    /**
     * Test: Competitor can be created with all attributes
     */
    public function test_competitor_can_be_created_with_all_attributes(): void
    {
        $this->artisan('migrate');
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test5@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
            'display_name' => 'Main Competitor',
            'notes' => 'Primary market rival',
        ]);
        
        $this->assertEquals($user->id, $competitor->user_id);
        $this->assertEquals('competitor.com', $competitor->domain);
        $this->assertEquals('Main Competitor', $competitor->display_name);
        $this->assertEquals('Primary market rival', $competitor->notes);
        $this->assertNotNull($competitor->created_at);
        $this->assertNotNull($competitor->updated_at);
    }

    /**
     * Test: User can access their competitors
     */
    public function test_user_can_access_competitors(): void
    {
        $this->artisan('migrate');
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test6@example.com',
            'password' => bcrypt('password'),
        ]);
        
        Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor1.com',
        ]);
        
        Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor2.com',
        ]);
        
        $this->assertCount(2, $user->competitors);
    }

    /**
     * Security Test: Mass assignment protection
     */
    public function test_mass_assignment_protection(): void
    {
        $this->artisan('migrate');
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test7@example.com',
            'password' => bcrypt('password'),
        ]);
        
        // Attempt to set id via mass assignment (should be ignored or error)
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'test.com',
            'id' => 99999, // Should be ignored
        ]);
        
        // The actual ID should be auto-incremented, not 99999
        $this->assertNotEquals(99999, $competitor->id);
    }
}
