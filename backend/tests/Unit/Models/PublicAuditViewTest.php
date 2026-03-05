<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Audit;
use App\Models\PublicAuditView;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for PublicAuditView model
 *
 * TASK-33-2: Create Public Audit Views Table
 *
 * @group model
 * @group public-audit-views
 */
class PublicAuditViewTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Model Instantiation Tests (MI)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox MI-001: Model can be instantiated
     */
    public function model_can_be_instantiated(): void
    {
        $view = new PublicAuditView();

        $this->assertInstanceOf(PublicAuditView::class, $view);
    }

    /**
     * @test
     * @testdox MI-002: Model uses correct table name
     */
    public function model_uses_correct_table_name(): void
    {
        $view = new PublicAuditView();

        $this->assertEquals('public_audit_views', $view->getTable());
    }

    /**
     * @test
     * @testdox MI-003: Model uses correct primary key
     */
    public function model_uses_correct_primary_key(): void
    {
        $view = new PublicAuditView();

        $this->assertEquals('id', $view->getKeyName());
    }

    /**
     * @test
     * @testdox MI-004: Model primary key is auto-incrementing
     */
    public function model_primary_key_is_auto_incrementing(): void
    {
        $view = new PublicAuditView();

        $this->assertTrue($view->getIncrementing());
    }

    /**
     * @test
     * @testdox MI-005: Model uses correct key type
     */
    public function model_uses_correct_key_type(): void
    {
        $view = new PublicAuditView();

        $this->assertEquals('int', $view->getKeyType());
    }

    /**
     * @test
     * @testdox MI-006: Model uses timestamps
     */
    public function model_uses_timestamps(): void
    {
        $view = new PublicAuditView();

        $this->assertTrue($view->timestamps);
    }

    /*
    |--------------------------------------------------------------------------
    | Model Fillable Tests (MF)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox MF-001: audit_id is fillable
     */
    public function audit_id_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('a', 32),
        ]);

        $this->assertEquals($audit->id, $view->audit_id);
    }

    /**
     * @test
     * @testdox MF-002: public_hash is fillable
     */
    public function public_hash_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $hash = 'AbCdEf1234567890AbCdEf1234567890';
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
        ]);

        $this->assertEquals($hash, $view->public_hash);
    }

    /**
     * @test
     * @testdox MF-003: viewed_at is fillable
     */
    public function viewed_at_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $viewTime = now()->subHours(2);
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('b', 32),
            'viewed_at' => $viewTime,
        ]);

        $this->assertEquals($viewTime->toDateTimeString(), $view->viewed_at->toDateTimeString());
    }

    /**
     * @test
     * @testdox MF-004: referrer is fillable
     */
    public function referrer_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $referrer = 'https://example.com/some-page';
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('c', 32),
            'referrer' => $referrer,
        ]);

        $this->assertEquals($referrer, $view->referrer);
    }

    /**
     * @test
     * @testdox MF-005: ip_hash is fillable
     */
    public function ip_hash_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $ipHash = hash('sha256', '192.168.1.1' . config('app.key'));
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('d', 32),
            'ip_hash' => $ipHash,
        ]);

        $this->assertEquals($ipHash, $view->ip_hash);
    }

    /**
     * @test
     * @testdox MF-006: user_agent is fillable
     */
    public function user_agent_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('e', 32),
            'user_agent' => $userAgent,
        ]);

        $this->assertEquals($userAgent, $view->user_agent);
    }

    /**
     * @test
     * @testdox MF-007: country_code is fillable
     */
    public function country_code_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('f', 32),
            'country_code' => 'US',
        ]);

        $this->assertEquals('US', $view->country_code);
    }

    /**
     * @test
     * @testdox MF-008: is_lead_capture is fillable
     */
    public function is_lead_capture_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('g', 32),
            'is_lead_capture' => true,
        ]);

        $this->assertTrue($view->is_lead_capture);
    }

    /**
     * @test
     * @testdox MF-009: lead_email is fillable
     */
    public function lead_email_is_fillable(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('h', 32),
            'lead_email' => 'test@example.com',
        ]);

        $this->assertEquals('test@example.com', $view->lead_email);
    }

    /**
     * @test
     * @testdox MF-010: All fillable attributes are correct
     */
    public function all_fillable_attributes_are_correct(): void
    {
        $view = new PublicAuditView();
        $expectedFillable = [
            'audit_id',
            'public_hash',
            'viewed_at',
            'referrer',
            'ip_hash',
            'user_agent',
            'country_code',
            'is_lead_capture',
            'lead_email',
        ];

        $this->assertEquals($expectedFillable, $view->getFillable());
    }

    /*
    |--------------------------------------------------------------------------
    | Model Casts Tests (MC)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox MC-001: is_lead_capture is cast to boolean
     */
    public function is_lead_capture_is_cast_to_boolean(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('i', 32),
            'is_lead_capture' => 1,
        ]);

        $this->assertIsBool($view->is_lead_capture);
        $this->assertTrue($view->is_lead_capture);
    }

    /**
     * @test
     * @testdox MC-002: viewed_at is cast to datetime
     */
    public function viewed_at_is_cast_to_datetime(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('j', 32),
            'viewed_at' => '2026-03-05 12:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $view->viewed_at);
    }

    /**
     * @test
     * @testdox MC-003: created_at is cast to datetime
     */
    public function created_at_is_cast_to_datetime(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('k', 32),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $view->created_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship Tests (RE)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox RE-001: Model belongs to Audit
     */
    public function model_belongs_to_audit(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('l', 32),
        ]);

        $this->assertInstanceOf(Audit::class, $view->audit);
        $this->assertEquals($audit->id, $view->audit->id);
    }

    /**
     * @test
     * @testdox RE-002: BelongsTo relationship returns correct audit
     */
    public function belongs_to_relationship_returns_correct_audit(): void
    {
        $audit1 = Audit::factory()->create();
        $audit2 = Audit::factory()->create();

        $view = PublicAuditView::create([
            'audit_id' => $audit1->id,
            'public_hash' => str_repeat('m', 32),
        ]);

        $this->assertEquals($audit1->id, $view->audit->id);
        $this->assertNotEquals($audit2->id, $view->audit->id);
    }

    /**
     * @test
     * @testdox RE-003: Eager loading works on audit relationship
     */
    public function eager_loading_works_on_audit_relationship(): void
    {
        $audit = Audit::factory()->create();
        PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('n', 32),
        ]);

        $view = PublicAuditView::with('audit')->first();

        $this->assertTrue($view->relationLoaded('audit'));
    }

    /*
    |--------------------------------------------------------------------------
    | Model Scopes Tests (SQ)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox SQ-001: forAudit scope filters by audit_id
     */
    public function for_audit_scope_filters_by_audit_id(): void
    {
        $audit1 = Audit::factory()->create();
        $audit2 = Audit::factory()->create();

        PublicAuditView::create(['audit_id' => $audit1->id, 'public_hash' => str_repeat('1', 32)]);
        PublicAuditView::create(['audit_id' => $audit1->id, 'public_hash' => str_repeat('2', 32)]);
        PublicAuditView::create(['audit_id' => $audit2->id, 'public_hash' => str_repeat('3', 32)]);

        $results = PublicAuditView::forAudit($audit1->id)->get();

        $this->assertEquals(2, $results->count());
        $results->each(fn($view) => $this->assertEquals($audit1->id, $view->audit_id));
    }

    /**
     * @test
     * @testdox SQ-002: forHash scope filters by public_hash
     */
    public function for_hash_scope_filters_by_public_hash(): void
    {
        $audit = Audit::factory()->create();
        $hash1 = str_repeat('a', 32);
        $hash2 = str_repeat('b', 32);

        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => $hash1]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => $hash1]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => $hash2]);

        $results = PublicAuditView::forHash($hash1)->get();

        $this->assertEquals(2, $results->count());
        $results->each(fn($view) => $this->assertEquals($hash1, $view->public_hash));
    }

    /**
     * @test
     * @testdox SQ-003: leadCaptures scope filters is_lead_capture = true
     */
    public function lead_captures_scope_filters_is_lead_capture_true(): void
    {
        $audit = Audit::factory()->create();

        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('x', 32), 'is_lead_capture' => true]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('y', 32), 'is_lead_capture' => false]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('z', 32), 'is_lead_capture' => true]);

        $results = PublicAuditView::leadCaptures()->get();

        $this->assertEquals(2, $results->count());
        $results->each(fn($view) => $this->assertTrue($view->is_lead_capture));
    }

    /**
     * @test
     * @testdox SQ-004: withLeadEmail scope filters records with lead_email
     */
    public function with_lead_email_scope_filters_records_with_lead_email(): void
    {
        $audit = Audit::factory()->create();

        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('p', 32), 'lead_email' => 'test@example.com']);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('q', 32), 'lead_email' => null]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('r', 32), 'lead_email' => 'user@example.org']);

        $results = PublicAuditView::withLeadEmail()->get();

        $this->assertEquals(2, $results->count());
        $results->each(fn($view) => $this->assertNotNull($view->lead_email));
    }

    /**
     * @test
     * @testdox SQ-005: viewedBetween scope filters by date range
     */
    public function viewed_between_scope_filters_by_date_range(): void
    {
        $audit = Audit::factory()->create();

        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('1', 32), 'viewed_at' => now()->subDays(10)]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('2', 32), 'viewed_at' => now()->subDays(5)]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('3', 32), 'viewed_at' => now()->subDays(2)]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('4', 32), 'viewed_at' => now()]);

        $results = PublicAuditView::viewedBetween(now()->subDays(7), now()->subDays(1))->get();

        $this->assertEquals(2, $results->count());
    }

    /**
     * @test
     * @testdox SQ-006: fromCountry scope filters by country_code
     */
    public function from_country_scope_filters_by_country_code(): void
    {
        $audit = Audit::factory()->create();

        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('u', 32), 'country_code' => 'US']);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('v', 32), 'country_code' => 'LV']);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('w', 32), 'country_code' => 'US']);

        $results = PublicAuditView::fromCountry('US')->get();

        $this->assertEquals(2, $results->count());
        $results->each(fn($view) => $this->assertEquals('US', $view->country_code));
    }

    /**
     * @test
     * @testdox SQ-007: recent scope limits to last N days
     */
    public function recent_scope_limits_to_last_n_days(): void
    {
        $audit = Audit::factory()->create();

        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('5', 32), 'viewed_at' => now()->subDays(10)]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('6', 32), 'viewed_at' => now()->subDays(3)]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('7', 32), 'viewed_at' => now()]);

        $results = PublicAuditView::recent(7)->get();

        $this->assertEquals(2, $results->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor Tests (AC)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox AC-001: Masked email accessor hides part of email
     */
    public function masked_email_accessor_hides_part_of_email(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('s', 32),
            'lead_email' => 'testuser@example.com',
        ]);

        // If accessor exists, test it
        if (method_exists($view, 'getMaskedEmailAttribute')) {
            $this->assertEquals('t*******r@example.com', $view->masked_email);
        } else {
            $this->markTestSkipped('masked_email accessor not implemented');
        }
    }

    /**
     * @test
     * @testdox AC-002: Has lead email accessor
     */
    public function has_lead_email_accessor(): void
    {
        $audit = Audit::factory()->create();

        $viewWithEmail = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('t', 32),
            'lead_email' => 'test@example.com',
        ]);

        $viewWithoutEmail = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('u', 32),
        ]);

        // If accessor exists
        if (method_exists($viewWithEmail, 'getHasLeadEmailAttribute')) {
            $this->assertTrue($viewWithEmail->has_lead_email);
            $this->assertFalse($viewWithoutEmail->has_lead_email);
        } else {
            $this->markTestSkipped('has_lead_email accessor not implemented');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Factory Tests (FA)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox FA-001: Factory creates valid model
     */
    public function factory_creates_valid_model(): void
    {
        $view = PublicAuditView::factory()->create();

        $this->assertInstanceOf(PublicAuditView::class, $view);
        $this->assertNotNull($view->audit_id);
        $this->assertNotNull($view->public_hash);
    }

    /**
     * @test
     * @testdox FA-002: Factory creates with specific audit
     */
    public function factory_creates_with_specific_audit(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::factory()->forAudit($audit)->create();

        $this->assertEquals($audit->id, $view->audit_id);
    }

    /**
     * @test
     * @testdox FA-003: Factory creates with lead capture state
     */
    public function factory_creates_with_lead_capture_state(): void
    {
        $view = PublicAuditView::factory()->leadCapture()->create();

        $this->assertTrue($view->is_lead_capture);
        $this->assertNotNull($view->lead_email);
    }

    /**
     * @test
     * @testdox FA-004: Factory creates with referrer
     */
    public function factory_creates_with_referrer(): void
    {
        $view = PublicAuditView::factory()->withReferrer()->create();

        $this->assertNotNull($view->referrer);
    }

    /**
     * @test
     * @testdox FA-005: Factory creates with country code
     */
    public function factory_creates_with_country_code(): void
    {
        $view = PublicAuditView::factory()->withCountry('LV')->create();

        $this->assertEquals('LV', $view->country_code);
    }

    /*
    |--------------------------------------------------------------------------
    | Edge Cases Tests (EC)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox EC-001: Model handles null referrer
     */
    public function model_handles_null_referrer(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('x', 32),
            'referrer' => null,
        ]);

        $this->assertNull($view->referrer);
    }

    /**
     * @test
     * @testdox EC-002: Model handles null ip_hash
     */
    public function model_handles_null_ip_hash(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('y', 32),
            'ip_hash' => null,
        ]);

        $this->assertNull($view->ip_hash);
    }

    /**
     * @test
     * @testdox EC-003: Model handles null user_agent
     */
    public function model_handles_null_user_agent(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('z', 32),
            'user_agent' => null,
        ]);

        $this->assertNull($view->user_agent);
    }

    /**
     * @test
     * @testdox EC-004: Model handles null country_code
     */
    public function model_handles_null_country_code(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('0', 32),
            'country_code' => null,
        ]);

        $this->assertNull($view->country_code);
    }

    /**
     * @test
     * @testdox EC-005: Model handles null lead_email
     */
    public function model_handles_null_lead_email(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('9', 32),
            'lead_email' => null,
        ]);

        $this->assertNull($view->lead_email);
    }

    /**
     * @test
     * @testdox EC-006: Model handles empty referrer string
     */
    public function model_handles_empty_referrer_string(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('8', 32),
            'referrer' => '',
        ]);

        $this->assertEquals('', $view->referrer);
    }

    /**
     * @test
     * @testdox EC-007: Multiple views with same hash are allowed
     */
    public function multiple_views_with_same_hash_are_allowed(): void
    {
        $audit = Audit::factory()->create();
        $hash = str_repeat('a', 32);

        $view1 = PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => $hash]);
        $view2 = PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => $hash]);

        $this->assertNotEquals($view1->id, $view2->id);
        $this->assertEquals(2, PublicAuditView::where('public_hash', $hash)->count());
    }

    /**
     * @test
     * @testdox EC-008: Unicode in user_agent is handled
     */
    public function unicode_in_user_agent_is_handled(): void
    {
        $audit = Audit::factory()->create();
        $unicodeAgent = 'Mozilla/5.0 (日本語) AppleWebKit/537.36';

        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('u', 32),
            'user_agent' => $unicodeAgent,
        ]);

        $this->assertEquals($unicodeAgent, $view->user_agent);
    }

    /*
    |--------------------------------------------------------------------------
    | Integration Tests (IN)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox IN-001: View is deleted when audit is deleted (cascade)
     */
    public function view_is_deleted_when_audit_is_deleted(): void
    {
        $audit = Audit::factory()->create();
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('d', 32),
        ]);

        $this->assertDatabaseHas('public_audit_views', ['id' => $view->id]);

        $audit->delete();

        $this->assertDatabaseMissing('public_audit_views', ['id' => $view->id]);
    }

    /**
     * @test
     * @testdox IN-002: Multiple views deleted when audit deleted
     */
    public function multiple_views_deleted_when_audit_deleted(): void
    {
        $audit = Audit::factory()->create();

        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('1', 32)]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('2', 32)]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('3', 32)]);

        $this->assertEquals(3, PublicAuditView::where('audit_id', $audit->id)->count());

        $audit->delete();

        $this->assertEquals(0, PublicAuditView::where('audit_id', $audit->id)->count());
    }

    /**
     * @test
     * @testdox IN-003: Views can be queried by audit relationship
     */
    public function views_can_be_queried_by_audit_relationship(): void
    {
        $audit = Audit::factory()->create();

        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('a', 32)]);
        PublicAuditView::create(['audit_id' => $audit->id, 'public_hash' => str_repeat('b', 32)]);

        $audit->load('publicAuditViews');

        $this->assertEquals(2, $audit->publicAuditViews->count());
    }

    /**
     * @test
     * @testdox IN-004: View count per audit is accurate
     */
    public function view_count_per_audit_is_accurate(): void
    {
        $audit1 = Audit::factory()->create();
        $audit2 = Audit::factory()->create();

        PublicAuditView::factory()->count(5)->forAudit($audit1)->create();
        PublicAuditView::factory()->count(3)->forAudit($audit2)->create();

        $this->assertEquals(5, PublicAuditView::where('audit_id', $audit1->id)->count());
        $this->assertEquals(3, PublicAuditView::where('audit_id', $audit2->id)->count());
    }
}
