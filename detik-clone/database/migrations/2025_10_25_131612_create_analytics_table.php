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
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            
            // Event tracking
            $table->string('event_type', 50); // page_view, article_read, comment_posted, etc.
            $table->string('event_name')->nullable(); // Specific event identifier
            $table->json('event_data')->nullable(); // Additional event parameters
            
            // Content tracking
            $table->nullableMorphs('trackable'); // Article, Category, Tag, etc.
            $table->string('url', 500)->nullable(); // Full URL
            $table->string('path')->nullable(); // URL path
            $table->string('referrer', 500)->nullable(); // HTTP referrer
            $table->string('utm_source')->nullable(); // UTM tracking
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            
            // User tracking
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 40)->nullable(); // Session identifier
            $table->string('visitor_id', 40)->nullable(); // Unique visitor (cookie-based)
            $table->ipAddress('ip_address')->nullable();
            $table->string('country', 2)->nullable(); // ISO country code
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            
            // Device & Browser
            $table->string('user_agent')->nullable();
            $table->string('device_type', 20)->nullable(); // desktop, mobile, tablet
            $table->string('browser', 50)->nullable();
            $table->string('browser_version', 20)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('os_version', 20)->nullable();
            $table->unsignedSmallInteger('screen_width')->nullable();
            $table->unsignedSmallInteger('screen_height')->nullable();
            
            // Performance metrics
            $table->unsignedInteger('page_load_time')->nullable(); // milliseconds
            $table->unsignedInteger('time_on_page')->nullable(); // seconds
            $table->unsignedInteger('scroll_depth')->nullable(); // percentage
            $table->boolean('bounced')->default(false); // Single page visit
            
            // Engagement metrics
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->decimal('engagement_score', 5, 2)->nullable(); // Calculated engagement
            
            // A/B Testing
            $table->string('experiment_id')->nullable();
            $table->string('variant_id')->nullable();
            
            // Aggregation helpers
            $table->date('date'); // For daily aggregations
            $table->unsignedTinyInteger('hour')->nullable(); // 0-23 for hourly data
            $table->boolean('is_unique_view')->default(true); // First view of the day
            
            $table->timestamps();
            
            // Indexes for analytics queries
            $table->index(['event_type', 'date']);
            $table->index(['trackable_type', 'trackable_id', 'date']);
            $table->index(['user_id', 'date']);
            $table->index(['session_id', 'created_at']);
            $table->index(['visitor_id', 'date']);
            $table->index(['ip_address', 'date']);
            $table->index(['country', 'date']);
            $table->index(['device_type', 'date']);
            $table->index(['utm_source', 'utm_campaign', 'date']);
            $table->index(['experiment_id', 'variant_id']);
            $table->index(['date', 'hour']);
            $table->index(['is_unique_view', 'date']);
            
            // Composite indexes for common queries
            $table->index(['event_type', 'trackable_type', 'date'], 'analytics_event_trackable_date');
            $table->index(['date', 'country', 'device_type'], 'analytics_geo_device_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};
