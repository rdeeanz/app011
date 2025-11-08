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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tag name: "Politik", "Ekonomi", "COVID-19"
            $table->string('slug')->unique(); // URL slug: "politik", "ekonomi", "covid-19"
            $table->text('description')->nullable(); // Tag description
            $table->string('color', 7)->default('#6B7280'); // Display color
            $table->unsignedBigInteger('articles_count')->default(0); // Cache count
            $table->boolean('is_trending')->default(false); // Trending tag
            $table->timestamps();
            
            $table->index(['is_trending', 'articles_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
