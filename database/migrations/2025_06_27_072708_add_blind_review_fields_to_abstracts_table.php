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
        // Add blind review fields to existing abstract_submissions table
        Schema::table('abstract_submissions', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('abstract_submissions', 'is_blind_review')) {
                $table->boolean('is_blind_review')->default(true)->after('status');
            }
            if (!Schema::hasColumn('abstract_submissions', 'anonymized_data')) {
                $table->json('anonymized_data')->nullable()->after('is_blind_review');
            }
            if (!Schema::hasColumn('abstract_submissions', 'conflict_checked')) {
                $table->boolean('conflict_checked')->default(false)->after('anonymized_data');
            }
        });
        
        // Create conflict tracking table (only if it doesn't exist)
        if (!Schema::hasTable('review_conflicts')) {
            Schema::create('review_conflicts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('abstract_submission_id')->constrained()->onDelete('cascade');
                $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
                $table->string('conflict_type'); // institution, collaboration, personal
                $table->text('conflict_reason')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamps();
                
                $table->unique(['abstract_submission_id', 'reviewer_id'], 'unique_abstract_reviewer_conflict');
            });
        }
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
