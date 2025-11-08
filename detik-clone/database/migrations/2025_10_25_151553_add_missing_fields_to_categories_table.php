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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->unsignedBigInteger('articles_count')->default(0)->after('is_featured');
            $table->string('seo_title')->nullable()->after('articles_count');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->text('seo_keywords')->nullable()->after('seo_description');
            $table->string('og_image')->nullable()->after('seo_keywords');
            
            // Indexes for performance
            $table->index(['is_featured', 'is_active']);
            $table->index(['articles_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_featured', 'is_active']);
            $table->dropIndex(['articles_count']);
            $table->dropColumn([
                'is_featured',
                'articles_count', 
                'seo_title',
                'seo_description',
                'seo_keywords',
                'og_image'
            ]);
        });
    }
};
