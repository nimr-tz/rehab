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
        // Add reviewer expertise fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'expertise_areas')) {
                $table->json('expertise_areas')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'review_experience_years')) {
                $table->integer('review_experience_years')->default(0)->after('expertise_areas');
            }
            if (!Schema::hasColumn('users', 'reviewer_consistency_score')) {
                $table->decimal('reviewer_consistency_score', 5, 2)->default(0.0)->after('review_experience_years');
            }
            if (!Schema::hasColumn('users', 'total_reviews_completed')) {
                $table->integer('total_reviews_completed')->default(0)->after('reviewer_consistency_score');
            }

            try {
                if (!Schema::hasColumn('users', 'reviewer_consistency_score')) {
                    $table->index(['reviewer_consistency_score']);
                }
                if (!Schema::hasColumn('users', 'total_reviews_completed')) {
                    $table->index(['total_reviews_completed']);
                }
            } catch (\Throwable $e) {
                // ignore index creation errors
            }
        });
        
        // Add expertise matching to abstract_reviews (only if table exists)
        if (Schema::hasTable('abstract_reviews')) {
            Schema::table('abstract_reviews', function (Blueprint $table) {
                if (!Schema::hasColumn('abstract_reviews', 'expertise_match_score')) {
                    $table->decimal('expertise_match_score', 5, 2)->nullable()->after('score');
                }
                if (!Schema::hasColumn('abstract_reviews', 'review_weight')) {
                    $table->decimal('review_weight', 5, 2)->default(1.0)->after('expertise_match_score');
                }
                if (!Schema::hasColumn('abstract_reviews', 'expertise_justification')) {
                    $table->json('expertise_justification')->nullable()->after('review_weight');
                }

                try {
                    $table->index(['expertise_match_score']);
                    $table->index(['review_weight']);
                } catch (\Throwable $e) {
                    // ignore index errors
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_reviews', function (Blueprint $table) {
            $table->dropIndex(['expertise_match_score']);
            $table->dropIndex(['review_weight']);
            
            $table->dropColumn([
                'expertise_match_score',
                'review_weight', 
                'expertise_justification'
            ]);
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['reviewer_consistency_score']);
            $table->dropIndex(['total_reviews_completed']);
            
            $table->dropColumn([
                'expertise_areas',
                'review_experience_years',
                'reviewer_consistency_score',
                'total_reviews_completed'
            ]);
        });
    }
};