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
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->boolean('is_blind_review')->default(true)->after('status');
            $table->json('anonymized_data')->nullable()->after('is_blind_review');
            $table->boolean('conflict_checked')->default(false)->after('anonymized_data');
        });
        
        // Add conflict tracking table
        Schema::create('review_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->string('conflict_type'); // institution, collaboration, personal
            $table->text('conflict_reason')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
            
            $table->unique(['abstract_submission_id', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_conflicts');
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropColumn(['is_blind_review', 'anonymized_data', 'conflict_checked']);
        });
    }
};
