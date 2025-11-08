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
        Schema::create('article_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            
            // Additional pivot fields
            $table->enum('tag_type', ['manual', 'auto', 'suggested'])->default('manual'); // How tag was added
            $table->decimal('relevance_score', 3, 2)->nullable(); // AI-calculated relevance (0.00-1.00)
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null'); // Who added the tag
            $table->text('context')->nullable(); // Why this tag was added
            
            $table->timestamps();
            
            // Unique constraint to prevent duplicate tags
            $table->unique(['article_id', 'tag_id']);
            
            // Indexes for performance
            $table->index(['article_id', 'tag_type']);
            $table->index(['tag_id', 'relevance_score']);
            $table->index(['added_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_tag');
    }
};
