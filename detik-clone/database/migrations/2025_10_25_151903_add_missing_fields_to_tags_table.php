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
        Schema::table('tags', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_trending');
            $table->string('icon')->nullable()->after('color');
            $table->string('category')->nullable()->after('is_featured');
            $table->decimal('popularity_score', 8, 2)->default(0.0)->after('category');
            $table->string('seo_title')->nullable()->after('popularity_score');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->json('meta')->nullable()->after('seo_description');
            
            // Indexes for performance
            $table->index(['is_trending', 'is_featured']);
            $table->index(['popularity_score']);
            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['is_trending', 'is_featured']);
            $table->dropIndex(['popularity_score']);
            $table->dropIndex(['category']);
            $table->dropColumn([
                'is_featured',
                'icon',
                'category',
                'popularity_score',
                'seo_title',
                'seo_description',
                'meta'
            ]);
        });
    }
};
