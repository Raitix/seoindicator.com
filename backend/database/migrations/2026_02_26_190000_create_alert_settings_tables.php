<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alert Settings - user global alert preferences
        Schema::create('alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('global_enabled')->default(true);
            $table->unsignedInteger('default_threshold')->default(3);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->enum('digest_mode', ['instant', 'daily', 'weekly'])->default('instant');
            $table->boolean('serp_feature_alerts_enabled')->default(true);
            $table->json('serp_feature_types')->nullable(); // e.g., ["featured_snippet", "local_pack"]
            $table->timestamps();

            $table->unique('user_id');
        });

        // Keyword Alert Settings - per-keyword alert preferences
        Schema::create('keyword_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('custom_threshold')->nullable();
            $table->json('alert_events')->nullable(); // ["position_drop", "serp_feature_gained", etc.]
            $table->json('notification_channels')->nullable(); // ["email", "in_app"]
            $table->timestamps();

            $table->unique('keyword_id');
        });

        // Notifications - in-app notifications storage
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 50); // position_drop, serp_feature_gained, etc.
            $table->foreignId('keyword_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'type']);
            $table->index('created_at');
        });

        // Site Notification Settings - per-site notification preferences
        Schema::create('site_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('in_app_notifications')->default(true);
            $table->boolean('serp_feature_alerts')->default(true);
            $table->timestamps();

            $table->unique('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_notification_settings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('keyword_alert_settings');
        Schema::dropIfExists('alert_settings');
    }
};
