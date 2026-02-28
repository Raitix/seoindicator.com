<?php

namespace Tests\Feature\Mail;

use App\Mail\RankingAlertEmail;
use App\Models\Keyword;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingAlertEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
        $site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);
    }

    // ==================== Constructor Tests ====================

    /** @test */
    public function constructor_accepts_user(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $this->assertEquals($this->user->id, $mailable->user->id);
    }

    /** @test */
    public function constructor_accepts_keyword(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $this->assertEquals($this->keyword->id, $mailable->keyword->id);
    }

    /** @test */
    public function constructor_accepts_events(): void
    {
        $events = [
            ['event' => 'position_drop', 'change' => 5],
            ['event' => 'left_top_10', 'change' => null],
        ];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $this->assertCount(2, $mailable->events);
    }

    /** @test */
    public function subject_format_correct(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSubjectIs("[SEOIndicator] Alert: seo tools position_drop");
    }

    /** @test */
    public function recipient_is_user_email(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertTo($this->user->email);
    }

    // ==================== Content Tests ====================

    /** @test */
    public function email_contains_user_greeting(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('Test User');
    }

    /** @test */
    public function email_contains_keyword_name(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('seo tools');
    }

    /** @test */
    public function email_contains_position_change(): void
    {
        $events = [
            'event' => 'position_drop',
            'change' => 5,
            'old_position' => 5,
            'new_position' => 10,
        ];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('5');
        $mailable->assertSeeInHtml('10');
    }

    /** @test */
    public function email_contains_dashboard_link(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml(route('keywords.show', $this->keyword->id));
    }

    /** @test */
    public function email_contains_unsubscribe_link(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('unsubscribe');
    }

    /** @test */
    public function multiple_events_listed(): void
    {
        $events = [
            ['event' => 'position_drop', 'change' => 5],
            ['event' => 'left_top_10'],
        ];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('position_drop');
        $mailable->assertSeeInHtml('left_top_10');
    }

    // ==================== Event Type Tests ====================

    /** @test */
    public function position_drop_message(): void
    {
        $events = [
            'event' => 'position_drop',
            'change' => 5,
            'old_position' => 5,
            'new_position' => 10,
        ];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('dropped');
        $mailable->assertSeeInHtml('5 positions');
    }

    /** @test */
    public function position_gain_message(): void
    {
        $events = [
            'event' => 'position_gain',
            'change' => 7,
            'old_position' => 15,
            'new_position' => 8,
        ];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('improved');
        $mailable->assertSeeInHtml('7 positions');
    }

    /** @test */
    public function entered_top_10_message(): void
    {
        $events = [
            'event' => 'entered_top_10',
            'old_position' => 15,
            'new_position' => 8,
        ];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('entered top 10');
    }

    /** @test */
    public function left_top_10_message(): void
    {
        $events = [
            'event' => 'left_top_10',
            'old_position' => 8,
            'new_position' => 12,
        ];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        $mailable->assertSeeInHtml('left top 10');
    }

    // ==================== Security Tests ====================

    /** @test */
    public function xss_in_keyword_is_escaped(): void
    {
        $this->keyword->update(['keyword' => '<script>alert("xss")</script>']);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        // Should NOT contain raw script tag
        $mailable->assertDontSeeInHtml('<script>alert');
        // Should contain escaped version
        $mailable->assertSeeInHtml('&lt;script');
    }

    /** @test */
    public function email_has_plain_text_version(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $mailable = new RankingAlertEmail($this->user, $this->keyword, $events);
        
        // Mailable should have both HTML and text versions
        $this->assertNotNull($mailable->textView);
    }
}
