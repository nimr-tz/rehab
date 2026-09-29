<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('abstract_reviews', function (Blueprint $table) {
                try {
                    $table->unique(['abstract_submission_id', 'reviewer_id', 'review_round'], 'unique_review_per_round');
                } catch (\Exception $e) {}
            });
            return;
        }

        // Check if old constraint exists and drop it
        $oldConstraintExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'abstract_reviews' 
            AND index_name = 'abstract_reviews_abstract_submission_id_reviewer_id_unique'
        ");
        
        if ($oldConstraintExists[0]->count > 0) {
            try {
                DB::statement('ALTER TABLE abstract_reviews DROP INDEX abstract_reviews_abstract_submission_id_reviewer_id_unique');
            } catch (\Exception $e) {
                // If it fails, try alternative method
                try {
                    Schema::table('abstract_reviews', function (Blueprint $table) {
                        $table->dropUnique(['abstract_submission_id', 'reviewer_id']);
                    });
                } catch (\Exception $e2) {
                    // Constraint might be needed by foreign key, continue anyway
                }
            }
        }
        
        // Check if new constraint exists
        $newConstraintExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'abstract_reviews' 
            AND index_name = 'unique_review_per_round'
        ");
        
        // Add new constraint if it doesn't exist
        if ($newConstraintExists[0]->count == 0) {
            try {
                Schema::table('abstract_reviews', function (Blueprint $table) {
                    $table->unique(['abstract_submission_id', 'reviewer_id', 'review_round'], 'unique_review_per_round');
                });
            } catch (\Exception $e) {
                // Constraint might already exist with different name, that's okay
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new constraint
        try {
            Schema::table('abstract_reviews', function (Blueprint $table) {
                $table->dropUnique('unique_review_per_round');
            });
        } catch (\Exception $e) {
            // Constraint doesn't exist, skip
        }
        
        // Restore old constraint
        try {
            Schema::table('abstract_reviews', function (Blueprint $table) {
                $table->unique(['abstract_submission_id', 'reviewer_id'], 'abstract_reviews_abstract_submission_id_reviewer_id_unique');
            });
        } catch (\Exception $e) {
            // Constraint already exists, skip
        }
    }
};
