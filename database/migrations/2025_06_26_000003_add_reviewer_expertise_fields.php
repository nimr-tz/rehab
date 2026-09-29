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
        Schema::table('users', function (Blueprint $table) {
            // Add reviewer expertise tracking fields
            if (!Schema::hasColumn('users', 'review_experience_years')) {
                $table->integer('review_experience_years')->default(0)->after('email');
            }
            
            if (!Schema::hasColumn('users', 'reviewer_consistency_score')) {
                $table->decimal('reviewer_consistency_score', 5, 2)->default(75.00)->after('review_experience_years');
            }
            
            if (!Schema::hasColumn('users', 'total_reviews_completed')) {
                $table->integer('total_reviews_completed')->default(0)->after('reviewer_consistency_score');
            }
            
            if (!Schema::hasColumn('users', 'average_review_turnaround_days')) {
                $table->decimal('average_review_turnaround_days', 5, 2)->nullable()->after('total_reviews_completed');
            }
        });
        
        // Update existing data with default values for active reviewers (only if abstracts reviews table exists)
        if (Schema::hasTable('abstract_reviews')) {
            DB::table('users')
                ->whereIn('id', function($query) {
                    $query->select('reviewer_id')
                          ->from('abstract_reviews')
                          ->whereNotNull('reviewer_id');
                })
                ->update([
                    'review_experience_years' => 3,  // Default to moderate experience
                    'reviewer_consistency_score' => 75.00, // Default score
                    'total_reviews_completed' => DB::raw('(SELECT COUNT(*) FROM abstract_reviews WHERE reviewer_id = users.id AND status = "submitted")')
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'review_experience_years',
                'reviewer_consistency_score', 
                'total_reviews_completed',
                'average_review_turnaround_days'
            ]);
        });
    }
};