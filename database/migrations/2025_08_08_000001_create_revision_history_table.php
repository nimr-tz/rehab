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
        Schema::create('revision_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abstract_submission_id');
            $table->string('action'); // revision_requested, revision_submitted, admin_approved, admin_rejected, etc.
            $table->unsignedBigInteger('user_id')->nullable(); // Who performed the action
            $table->integer('revision_round')->default(1);
            $table->text('feedback')->nullable(); // Admin/reviewer feedback
            $table->text('author_response')->nullable(); // Author's response
            $table->text('admin_notes')->nullable(); // Admin notes
            $table->string('revision_type')->nullable(); // minor, major
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            // Foreign keys
            $table->foreign('abstract_submission_id')->references('id')->on('abstract_submissions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['abstract_submission_id', 'revision_round']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revision_history');
    }
};
