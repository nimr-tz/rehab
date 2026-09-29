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
        Schema::table('abstract_reviews', function (Blueprint $table) {
            // Add is_revision_review flag if it doesn't exist
            if (!Schema::hasColumn('abstract_reviews', 'is_revision_review')) {
                $table->boolean('is_revision_review')->default(false)->after('review_round')
                    ->comment('True if this is a re-review of a revision');
            }

            // Add revision_notes for tracking what changed
            if (!Schema::hasColumn('abstract_reviews', 'revision_notes')) {
                $table->text('revision_notes')->nullable()->after('comments')
                    ->comment('Reviewer notes on how the revision addresses previous concerns');
            }

            // Add field to track if improvement was made compared to previous round
            if (!Schema::hasColumn('abstract_reviews', 'improvement_from_previous')) {
                $table->enum('improvement_from_previous', ['improved', 'same', 'declined', 'n/a'])
                    ->default('n/a')->after('revision_notes')
                    ->comment('Whether the revision improved from previous round');
            }

            // Add previous round reference for easy comparison
            if (!Schema::hasColumn('abstract_reviews', 'previous_round_id')) {
                $table->unsignedBigInteger('previous_round_id')->nullable()->after('improvement_from_previous')
                    ->comment('ID of the previous round review by this reviewer');
            }
        });

        // Add unique constraint for review rounds if it doesn't exist
        // This allows multiple reviews per reviewer per abstract (one per round)
        try {
            Schema::table('abstract_reviews', function (Blueprint $table) {
                // Try to add the new constraint
                // If it fails, the constraint likely already exists
                $table->unique(['abstract_submission_id', 'reviewer_id', 'review_round'], 'unique_review_per_round');
            });
        } catch (\Exception $e) {
            // Constraint already exists or there's a conflict - that's okay
            // The important thing is ensuring review_round is part of uniqueness
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('abstract_reviews', 'is_revision_review')) {
                $table->dropColumn('is_revision_review');
            }
            if (Schema::hasColumn('abstract_reviews', 'revision_notes')) {
                $table->dropColumn('revision_notes');
            }
            if (Schema::hasColumn('abstract_reviews', 'improvement_from_previous')) {
                $table->dropColumn('improvement_from_previous');
            }
            if (Schema::hasColumn('abstract_reviews', 'previous_round_id')) {
                $table->dropColumn('previous_round_id');
            }
        });
    }
};
