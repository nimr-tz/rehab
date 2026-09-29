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
        // First, add the missing status column
        Schema::table('abstract_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('abstract_submissions', 'status')) {
                $table->string('status')->default('draft')->after('user_id');
            }
        });
        
        // Add review score fields (only if they don't exist)
        Schema::table('abstract_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('abstract_submissions', 'reviewer_1_score')) {
                $table->integer('reviewer_1_score')->nullable()->after('reviewer_2_id');
            }
            if (!Schema::hasColumn('abstract_submissions', 'reviewer_2_score')) {
                $table->integer('reviewer_2_score')->nullable()->after('reviewer_1_score');
            }
            if (!Schema::hasColumn('abstract_submissions', 'average_score')) {
                $table->decimal('average_score', 5, 2)->nullable()->after('reviewer_2_score');
            }
            if (!Schema::hasColumn('abstract_submissions', 'review_completed_at')) {
                $table->timestamp('review_completed_at')->nullable()->after('average_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropColumn(['reviewer_1_score', 'reviewer_2_score', 'average_score', 'review_completed_at', 'status']);
        });
    }
};
