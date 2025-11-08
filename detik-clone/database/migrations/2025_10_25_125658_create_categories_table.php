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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Kategori name like "News", "Finance", "Sports"
            $table->string('slug')->unique(); // URL-friendly version: "news", "finance", "sports"
            $table->text('description')->nullable(); // Category description
            $table->string('color', 7)->default('#000000'); // Hex color for category
            $table->string('icon')->nullable(); // Icon class or path
            $table->unsignedBigInteger('parent_id')->nullable(); // For nested categories
            $table->integer('sort_order')->default(0); // Display order
            $table->boolean('is_active')->default(true); // Active status
            $table->json('meta')->nullable(); // Additional metadata
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['is_active', 'sort_order']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
