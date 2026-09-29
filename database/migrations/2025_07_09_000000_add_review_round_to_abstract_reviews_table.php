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
            if (!Schema::hasColumn('abstract_reviews', 'review_round')) {
                $table->integer('review_round')->default(1)->nullable()->after('reviewer_number')->comment('Review round: 1=original, 2=first revision, etc.');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('abstract_reviews', 'review_round')) {
                $table->dropColumn('review_round');
            }
        });
    }
}; 