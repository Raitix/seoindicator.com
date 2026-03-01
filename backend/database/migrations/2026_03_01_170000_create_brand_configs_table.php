<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates brand_configs table for white-label customization.
     * Stores branding settings (logo, colors, footer text) per account.
     *
     * @see TASK-15-1
     * @see SPEC-015 (White-Label Customization)
     */
    public function up(): void
    {
        Schema::create('brand_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->string('logo_url', 500)->nullable();
            $table->string('brand_name', 100)->default('SEOIndicator');
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('secondary_color', 7)->default('#1E40AF');
            $table->string('favicon_url', 500)->nullable();
            $table->string('login_background_url', 500)->nullable();
            $table->text('footer_text')->nullable();
            $table->boolean('hide_powered_by')->default(false);
            $table->string('custom_email_from', 255)->nullable();
            $table->timestamps();

            // One brand config per account
            $table->unique('account_id', 'idx_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_configs');
    }
};
