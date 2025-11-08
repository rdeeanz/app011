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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Null for guest comments, set null when user deleted
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade'); // For threaded replies
            
            // Comment content
            $table->text('content');
            $table->string('author_name')->nullable(); // For guest comments
            $table->string('author_email')->nullable(); // For guest comments
            $table->ipAddress('ip_address'); // For moderation
            $table->string('user_agent')->nullable(); // Browser info
            
            // Moderation
            $table->enum('status', ['pending', 'approved', 'rejected', 'spam'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Engagement
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('dislikes_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            
            // Flags
            $table->boolean('is_pinned')->default(false); // Pin important comments
            $table->unsignedInteger('reports_count')->default(0); // Abuse reports
            
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes support
            
            $table->index(['article_id', 'status', 'created_at']);
            $table->index(['parent_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
