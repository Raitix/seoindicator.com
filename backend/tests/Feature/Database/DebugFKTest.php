<?php

namespace Tests\Feature\Database;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebugFKTest extends TestCase
{
    use RefreshDatabase;

    public function test_fk_debug(): void
    {
        $user = \App\Models\User::factory()->create();
        $site = \App\Models\Site::factory()->create(['user_id' => $user->id]);
        $keyword = \App\Models\Keyword::factory()->create(['site_id' => $site->id]);
        
        $keywordId = $keyword->id;
        echo "Keyword ID: $keywordId\n";
        
        $onboardingId = \DB::table('user_onboarding')->insertGetId([
            'user_id' => $user->id,
            'keyword_id' => $keywordId,
            'current_step' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "Onboarding ID: $onboardingId\n";
        
        // Check before delete
        $before = \DB::table('user_onboarding')->where('id', $onboardingId)->first();
        echo "Before delete - keyword_id: " . $before->keyword_id . "\n";
        
        // Delete keyword using raw query to ensure it actually deletes
        $deleted = \DB::table('keywords')->where('id', $keywordId)->delete();
        echo "Keywords deleted: $deleted\n";
        
        // Check after delete
        $after = \DB::table('user_onboarding')->where('id', $onboardingId)->first();
        echo "After delete - keyword_id: " . var_export($after->keyword_id, true) . "\n";
        
        $this->assertNull($after->keyword_id, 'keyword_id should be null after keyword is deleted');
    }
}
