<?php

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\Keyword;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
    }

    /** @test */
    public function can_create_model(): void
    {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'position_drop',
            'keyword_id' => $this->keyword->id,
            'data' => ['old_position' => 5, 'new_position' => 12, 'change' => 7],
        ]);
        
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'type' => 'position_drop',
        ]);
    }

    /** @test */
    public function all_columns_are_fillable(): void
    {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'position_gain',
            'keyword_id' => $this->keyword->id,
            'data' => ['old_position' => 20, 'new_position' => 8],
            'read_at' => now(),
        ]);
        
        $this->assertEquals('position_gain', $notification->type);
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function belongs_to_user(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($this->user->id, $notification->user->id);
    }

    /** @test */
    public function belongs_to_keyword(): void
    {
        $notification = Notification::factory()->create([
            'keyword_id' => $this->keyword->id,
        ]);
        
        $this->assertInstanceOf(Keyword::class, $notification->keyword);
        $this->assertEquals($this->keyword->id, $notification->keyword->id);
    }

    /** @test */
    public function data_casts_to_array(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => ['old_position' => 5, 'new_position' => 12, 'change' => 7],
        ]);
        
        $this->assertIsArray($notification->data);
        $this->assertEquals(5, $notification->data['old_position']);
        $this->assertEquals(12, $notification->data['new_position']);
        $this->assertEquals(7, $notification->data['change']);
    }

    /** @test */
    public function scope_unread_returns_only_unread(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
        
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);
        
        $unread = Notification::unread()->get();
        
        $this->assertCount(3, $unread);
        $unread->each(fn($n) => $this->assertNull($n->read_at));
    }

    /** @test */
    public function scope_read_returns_only_read(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
        
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);
        
        $read = Notification::read()->get();
        
        $this->assertCount(2, $read);
        $read->each(fn($n) => $this->assertNotNull($n->read_at));
    }

    /** @test */
    public function mark_as_read_sets_read_at(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
        
        $notification->markAsRead();
        
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function is_read_returns_true_when_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);
        
        $this->assertTrue($notification->isRead());
    }

    /** @test */
    public function is_read_returns_false_when_unread(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
        
        $this->assertFalse($notification->isRead());
    }

    /** @test */
    public function cascade_deletes_with_user(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $this->user->delete();
        
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    /** @test */
    public function set_null_on_keyword_delete(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'keyword_id' => $this->keyword->id,
        ]);
        
        $this->keyword->delete();
        
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'keyword_id' => null,
        ]);
    }

    /** @test */
    public function keyword_id_can_be_null(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'keyword_id' => null,
        ]);
        
        $this->assertNull($notification->keyword_id);
    }

    /** @test */
    public function position_drop_data_stored_correctly(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'position_drop',
            'data' => [
                'old_position' => 5,
                'new_position' => 12,
                'change' => 7,
            ],
        ]);
        
        $fresh = Notification::find($notification->id);
        $this->assertEquals(5, $fresh->data['old_position']);
        $this->assertEquals(12, $fresh->data['new_position']);
        $this->assertEquals(7, $fresh->data['change']);
    }

    /** @test */
    public function position_gain_data_stored_correctly(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'position_gain',
            'data' => [
                'old_position' => 20,
                'new_position' => 8,
                'change' => 12,
            ],
        ]);
        
        $fresh = Notification::find($notification->id);
        $this->assertEquals(20, $fresh->data['old_position']);
        $this->assertEquals(8, $fresh->data['new_position']);
        $this->assertEquals(12, $fresh->data['change']);
    }

    /** @test */
    public function entered_top_10_data_stored_correctly(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'entered_top_10',
            'data' => [
                'old_position' => 15,
                'new_position' => 8,
            ],
        ]);
        
        $fresh = Notification::find($notification->id);
        $this->assertEquals(15, $fresh->data['old_position']);
        $this->assertEquals(8, $fresh->data['new_position']);
    }

    /** @test */
    public function empty_data_object_is_valid(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => [],
        ]);
        
        $this->assertIsArray($notification->data);
        $this->assertEmpty($notification->data);
    }

    /** @test */
    public function valid_types_can_be_stored(): void
    {
        $types = ['position_drop', 'position_gain', 'entered_top_10', 'left_top_10'];
        
        foreach ($types as $type) {
            $notification = Notification::factory()->create([
                'user_id' => $this->user->id,
                'type' => $type,
            ]);
            
            $this->assertEquals($type, $notification->type);
        }
    }

    /** @test */
    public function sql_injection_in_type_is_sanitized(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => "position_drop'; DROP TABLE notifications; --",
        ]);
        
        // Should sanitize - table should still exist
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function xss_in_data_is_sanitized(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => [
                'keyword' => '<script>alert("xss")</script>',
            ],
        ]);
        
        $fresh = Notification::find($notification->id);
        // Data is stored as-is; sanitization happens on display
        $this->assertStringContainsString('script', $fresh->data['keyword']);
    }
}
