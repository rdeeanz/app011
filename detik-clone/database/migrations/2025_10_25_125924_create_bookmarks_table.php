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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('bookmarkable'); // Polymorphic: articles, categories, tags
            
            // Organization
            $table->string('folder')->nullable(); // User-defined folders like "Read Later", "Favorites"
            $table->text('notes')->nullable(); // User notes about the bookmark
            $table->json('tags')->nullable(); // User-defined tags for organization
            
            // Metadata
            $table->boolean('is_favorite')->default(false); // Star/favorite status
            $table->boolean('is_read')->default(false); // Mark as read
            $table->timestamp('read_at')->nullable();
            $table->unsignedInteger('read_count')->default(0); // How many times accessed
            $table->timestamp('last_accessed_at')->nullable();
            
            // Sync & sharing
            $table->boolean('is_public')->default(false); // Can others see this bookmark
            $table->string('share_token', 32)->nullable()->unique(); // For sharing bookmarks
            $table->json('sync_data')->nullable(); // Cross-device sync metadata
            
            // Reminders
            $table->timestamp('remind_at')->nullable(); // Reminder timestamp
            $table->boolean('reminder_sent')->default(false);
            $table->enum('reminder_frequency', ['once', 'daily', 'weekly', 'monthly'])->nullable();
            
            $table->timestamps();
            
            // Prevent duplicate bookmarks
            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id'], 'user_bookmark_unique');
            
            // Indexes for efficient queries
            $table->index(['user_id', 'folder', 'created_at']);
            $table->index(['user_id', 'is_favorite']);
            $table->index(['user_id', 'is_read']);
            // Note: bookmarkable_type and bookmarkable_id index is automatically created by morphs()
            $table->index(['remind_at', 'reminder_sent']);
            $table->index(['is_public', 'share_token']);
            $table->index(['last_accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
