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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Article title
            $table->string('slug')->unique(); // SEO-friendly URL slug
            $table->text('excerpt')->nullable(); // Short summary/lead
            $table->longText('content'); // Main article content (HTML)
            $table->string('featured_image')->nullable(); // Main image path
            $table->json('gallery')->nullable(); // Additional images for photo galleries
            $table->string('video_url')->nullable(); // YouTube/Vimeo embed URL
            $table->enum('type', ['article', 'photo_gallery', 'video', 'infographic'])->default('article');
            
            // SEO & Meta
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable(); // Array of keywords
            
            // Publishing
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Auto-archive date
            
            // Authoring
            $table->foreignId('author_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('editor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            
            // Engagement metrics
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('shares_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->decimal('reading_time', 4, 1)->nullable(); // in minutes
            
            // Content flags
            $table->boolean('is_featured')->default(false); // Homepage feature
            $table->boolean('is_breaking')->default(false); // Breaking news
            $table->boolean('is_sponsored')->default(false); // Sponsored content
            $table->boolean('comments_enabled')->default(true);
            
            // Location & source
            $table->string('location')->nullable(); // City/Region
            $table->string('source')->nullable(); // News source attribution
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'published_at']);
            $table->index(['is_featured', 'published_at']);
            $table->index(['is_breaking', 'published_at']);
            $table->index('author_id');
            // Note: Full-text search indexes require MySQL/PostgreSQL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
