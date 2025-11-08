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
            // Add only the columns that are definitely missing
            if (!Schema::hasColumn('articles', 'featured_image_caption')) {
                $table->string('featured_image_caption')->nullable()->after('featured_image');
            }
            if (!Schema::hasColumn('articles', 'likes_count')) {
                $table->unsignedBigInteger('likes_count')->default(0)->after('comments_count');
            }
            if (!Schema::hasColumn('articles', 'bookmarks_count')) {
                $table->unsignedBigInteger('bookmarks_count')->default(0)->after('likes_count');
            }
            if (!Schema::hasColumn('articles', 'is_editors_pick')) {
                $table->boolean('is_editors_pick')->default(false)->after('is_sponsored');
            }
            if (!Schema::hasColumn('articles', 'allow_comments')) {
                $table->boolean('allow_comments')->default(true)->after('is_premium');
            }
            if (!Schema::hasColumn('articles', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('published_at');
            }
            if (!Schema::hasColumn('articles', 'content_type')) {
                $table->enum('content_type', ['text', 'video', 'gallery', 'infographic'])->default('text')->after('featured_image_caption');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'featured_image_caption',
                'likes_count',
                'bookmarks_count',
                'is_editors_pick',
                'allow_comments',
                'scheduled_at',
                'content_type'
            ]);
        });
    }
};
