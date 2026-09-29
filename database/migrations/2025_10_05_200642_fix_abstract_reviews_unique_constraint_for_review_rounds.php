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

        // Check if the new constraint already exists
        $constraintExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'abstract_reviews' 
            AND index_name = 'unique_review_per_round'
        ");
        
        if ($constraintExists[0]->count > 0) {
            // New constraint already exists, nothing to do
            return;
        }
        
        // Check if old constraint exists
        $oldConstraintExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'abstract_reviews' 
            AND index_name = 'abstract_reviews_abstract_submission_id_reviewer_id_unique'
        ");
        
        // Only drop old constraint if it exists and new one doesn't
        if ($oldConstraintExists[0]->count > 0) {
            try {
                DB::statement('ALTER TABLE abstract_reviews DROP INDEX abstract_reviews_abstract_submission_id_reviewer_id_unique');
            } catch (\Exception $e) {
                // If it fails, it might be needed by a foreign key, so we'll just add the new constraint
                // and leave the old one in place
            }
        }
        
        // Add new unique constraint
        Schema::table('abstract_reviews', function (Blueprint $table) {
            $table->unique(['abstract_submission_id', 'reviewer_id', 'review_round'], 'unique_review_per_round');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_reviews', function (Blueprint $table) {
            // Drop the new constraint if it exists
            try {
                $table->dropUnique('unique_review_per_round');
            } catch (\Exception $e) {
                // Constraint doesn't exist, skip
            }
            
            // Restore the old constraint if it doesn't exist
            try {
                $table->unique(['abstract_submission_id', 'reviewer_id']);
            } catch (\Exception $e) {
                // Constraint already exists, skip
            }
        });
    }
};