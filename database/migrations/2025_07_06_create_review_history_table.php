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
        Schema::create('review_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('reviewer_position'); // 1 or 2
            $table->string('action'); // 'assigned', 'removed', 'replaced', 'completed'
            $table->decimal('score', 5, 2)->nullable(); // Preserved score if any
            $table->text('comments')->nullable(); // Preserved comments if any
            $table->json('metadata')->nullable(); // Additional review data
            $table->string('reason')->nullable(); // Reason for change
            $table->foreignId('admin_user_id')->constrained('users'); // Who made the change
            $table->timestamp('action_date');
            $table->timestamps();
            
            $table->index(['abstract_submission_id', 'reviewer_id']);
            $table->index(['action', 'action_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_history');
    }
};
