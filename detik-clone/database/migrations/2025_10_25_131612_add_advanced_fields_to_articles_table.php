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
        Schema::table('articles', function (Blueprint $table) {
            // Advanced content fields
            $table->json('content_blocks')->nullable()->after('content'); // Structured content blocks
            $table->string('content_format', 20)->default('html')->after('content_blocks'); // html, markdown, json
            $table->text('excerpt_auto')->nullable()->after('excerpt'); // Auto-generated excerpt
            $table->unsignedInteger('word_count')->default(0)->after('reading_time');
            $table->json('content_analysis')->nullable()->after('word_count'); // SEO analysis, readability
            
            // Advanced media fields
            $table->json('media_gallery')->nullable()->after('gallery'); // Structured media data
            $table->string('featured_video')->nullable()->after('video_url'); // Local video file
            $table->json('embedded_media')->nullable()->after('featured_video'); // YouTube, Vimeo, etc.
            $table->string('audio_file')->nullable()->after('embedded_media'); // Podcast/audio content
            
            // Publication workflow
            $table->enum('editorial_status', ['draft', 'pending_review', 'in_review', 'approved', 'rejected', 'published', 'archived'])
                  ->default('draft')->after('status');
            $table->text('editorial_notes')->nullable()->after('editorial_status');
            $table->json('revision_history')->nullable()->after('editorial_notes');
            $table->timestamp('submitted_at')->nullable()->after('published_at');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null')->after('editor_id');
            
            // Content relationships
            $table->json('related_articles')->nullable()->after('location'); // Manual related articles
            $table->json('internal_links')->nullable()->after('related_articles'); // Internal links analysis
            $table->json('external_links')->nullable()->after('internal_links'); // External links
            $table->string('series_id')->nullable()->after('external_links'); // Article series
            $table->unsignedInteger('series_order')->nullable()->after('series_id');
            
            // Advanced engagement
            $table->decimal('engagement_rate', 5, 2)->default(0)->after('comments_count');
            $table->unsignedInteger('unique_views')->default(0)->after('views_count');
            $table->unsignedInteger('time_spent_total')->default(0)->after('unique_views'); // Total reading time in seconds
            $table->decimal('avg_time_spent', 8, 2)->default(0)->after('time_spent_total'); // Average reading time
            $table->unsignedInteger('bounce_rate')->default(0)->after('avg_time_spent'); // Percentage
            $table->json('social_shares')->nullable()->after('shares_count'); // Platform-specific shares
            
            // Monetization
            $table->boolean('has_ads')->default(true)->after('is_sponsored');
            $table->json('ad_config')->nullable()->after('has_ads'); // Ad placement configuration
            $table->boolean('is_premium')->default(false)->after('ad_config');
            $table->decimal('premium_price', 8, 2)->nullable()->after('is_premium');
            $table->enum('monetization_type', ['free', 'premium', 'freemium', 'subscription'])->default('free')->after('premium_price');
            
            // AI & Automation
            $table->json('ai_analysis')->nullable()->after('content_analysis'); // AI content analysis
            $table->boolean('auto_generated')->default(false)->after('ai_analysis');
            $table->string('generation_model')->nullable()->after('auto_generated'); // GPT-4, Claude, etc.
            $table->decimal('ai_confidence', 3, 2)->nullable()->after('generation_model'); // 0.00-1.00
            $table->json('suggested_tags')->nullable()->after('ai_confidence'); // AI-suggested tags
            $table->json('suggested_categories')->nullable()->after('suggested_tags');
            
            // Performance tracking
            $table->json('performance_metrics')->nullable()->after('suggested_categories'); // Various performance data
            $table->timestamp('last_crawled_at')->nullable()->after('performance_metrics'); // Search engine crawl
            $table->json('seo_score')->nullable()->after('last_crawled_at'); // SEO analysis results
            
            // Multi-language support
            $table->string('language', 5)->default('id')->after('seo_score');
            $table->json('translations')->nullable()->after('language'); // Translation status
            $table->string('original_article_id')->nullable()->after('translations'); // If this is a translation
            
            // Additional indexes for new fields
            $table->index(['editorial_status', 'submitted_at']);
            $table->index(['reviewer_id', 'reviewed_at']);
            $table->index(['series_id', 'series_order']);
            $table->index(['is_premium', 'monetization_type']);
            $table->index(['auto_generated', 'generation_model']);
            $table->index(['language', 'published_at']);
            $table->index(['engagement_rate', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            //
        });
    }
};
