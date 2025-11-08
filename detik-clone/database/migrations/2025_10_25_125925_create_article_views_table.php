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
        Schema::create('article_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Null for anonymous, preserve views when user deleted
            $table->string('session_id', 40)->nullable(); // Session tracking
            $table->string('visitor_id', 40)->nullable(); // Unique visitor ID (cookie-based)
            
            // Request context
            $table->ipAddress('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('url', 500)->nullable(); // Page URL with parameters
            
            // Geographic data
            $table->string('country', 2)->nullable(); // ISO country code
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Device & Browser information
            $table->string('device_type', 20)->nullable(); // desktop, mobile, tablet
            $table->string('browser', 50)->nullable();
            $table->string('browser_version', 20)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('os_version', 20)->nullable();
            $table->unsignedSmallInteger('screen_width')->nullable();
            $table->unsignedSmallInteger('screen_height')->nullable();
            
            // Engagement metrics
            $table->unsignedInteger('time_spent')->nullable(); // Time on page in seconds
            $table->unsignedTinyInteger('scroll_depth')->nullable(); // Percentage scrolled (0-100)
            $table->boolean('completed_reading')->default(false); // Read to the end
            $table->unsignedInteger('interactions')->default(0); // Clicks, shares, comments
            
            // Performance metrics
            $table->unsignedInteger('page_load_time')->nullable(); // Milliseconds
            $table->unsignedInteger('time_to_first_byte')->nullable(); // TTFB in ms
            $table->unsignedInteger('dom_ready_time')->nullable(); // DOM ready time in ms
            
            // UTM tracking
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            
            // View classification
            $table->boolean('is_unique_view')->default(true); // First view of the day for this visitor
            $table->boolean('is_bounce')->default(false); // Single page session
            $table->boolean('is_returning_visitor')->default(false);
            $table->enum('view_source', ['direct', 'search', 'social', 'referral', 'email', 'other'])->default('direct');
            
            // Aggregation helpers
            $table->date('view_date'); // Date for daily aggregations
            $table->unsignedTinyInteger('view_hour'); // Hour (0-23) for hourly stats
            
            // Bot detection
            $table->boolean('is_bot')->default(false); // Detected as bot traffic
            $table->string('bot_name')->nullable(); // Bot identification
            
            $table->timestamps();
            
            // Indexes for analytics queries
            $table->index(['article_id', 'view_date']);
            $table->index(['article_id', 'is_unique_view', 'view_date']);
            $table->index(['user_id', 'view_date']);
            $table->index(['session_id', 'created_at']);
            $table->index(['visitor_id', 'view_date']);
            $table->index(['ip_address', 'view_date']);
            $table->index(['country', 'view_date']);
            $table->index(['device_type', 'view_date']);
            $table->index(['view_source', 'view_date']);
            $table->index(['utm_source', 'utm_campaign', 'view_date']);
            $table->index(['is_bot', 'view_date']);
            $table->index(['view_date', 'view_hour']);
            
            // Composite indexes for complex queries
            $table->index(['article_id', 'country', 'device_type', 'view_date'], 'article_geo_device_date');
            $table->index(['view_date', 'is_unique_view', 'is_bot'], 'date_unique_bot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_views');
    }
};
