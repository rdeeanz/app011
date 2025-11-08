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
        Schema::create('ad_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ad zone name: "Header Banner", "Sidebar", etc.
            $table->string('slug')->unique(); // URL-friendly identifier
            $table->text('description')->nullable();
            
            // Zone configuration
            $table->enum('type', ['banner', 'rectangle', 'skyscraper', 'leaderboard', 'square', 'custom'])->default('banner');
            $table->string('position', 50); // header, sidebar, footer, in-content, etc.
            $table->unsignedInteger('width')->nullable(); // Pixel width
            $table->unsignedInteger('height')->nullable(); // Pixel height
            $table->boolean('is_responsive')->default(true);
            $table->json('breakpoint_sizes')->nullable(); // Responsive ad sizes
            
            // Content targeting
            $table->json('allowed_categories')->nullable(); // Which categories can show this ad
            $table->json('blocked_categories')->nullable(); // Which categories cannot show this ad
            $table->json('allowed_tags')->nullable(); // Tag-based targeting
            $table->enum('device_targeting', ['all', 'desktop', 'mobile', 'tablet'])->default('all');
            $table->json('geo_targeting')->nullable(); // Country/region targeting
            
            // Scheduling
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('schedule_rules')->nullable(); // Day/hour scheduling
            
            // Performance settings
            $table->unsignedInteger('max_impressions')->nullable(); // Daily/total impression limit
            $table->unsignedInteger('current_impressions')->default(0);
            $table->decimal('click_rate', 5, 2)->default(0); // CTR percentage
            $table->unsignedInteger('clicks_count')->default(0); // Total clicks
            
            // Ad content
            $table->enum('ad_type', ['html', 'javascript', 'iframe', 'image', 'video'])->default('html');
            $table->longText('ad_code')->nullable(); // HTML/JS code
            $table->string('image_url')->nullable(); // For image ads
            $table->string('click_url')->nullable(); // Destination URL
            $table->string('alt_text')->nullable(); // Image alt text
            
            // Business settings
            $table->decimal('price_per_click', 8, 2)->nullable(); // CPC pricing
            $table->decimal('price_per_impression', 8, 4)->nullable(); // CPM pricing
            $table->string('advertiser_name')->nullable();
            $table->string('campaign_id')->nullable(); // External campaign reference
            
            // Display settings
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(1); // Higher number = higher priority
            $table->decimal('weight', 5, 2)->default(1.00); // For ad rotation
            $table->boolean('show_label')->default(false); // Show "Advertisement" label
            $table->string('label_text', 50)->default('Advertisement');
            
            // A/B Testing
            $table->string('test_group')->nullable(); // A/B test group
            $table->json('test_variants')->nullable(); // Different ad versions
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['is_active', 'priority', 'weight']);
            $table->index(['position', 'device_targeting']);
            $table->index(['start_date', 'end_date']);
            $table->index(['advertiser_name', 'campaign_id']);
            $table->index(['current_impressions', 'max_impressions']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_zones');
    }
};
