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
        Schema::table('conference_feedback', function (Blueprint $table) {
            // Hospitality
            $table->integer('food_quality_rating')->nullable()->after('abstract_review_comments');
            $table->integer('service_quality_rating')->nullable()->after('food_quality_rating');
            $table->text('hospitality_comments')->nullable()->after('service_quality_rating');

            // More Session Insights
            $table->integer('session_variety_rating')->nullable()->after('hospitality_comments');
            $table->integer('session_timing_rating')->nullable()->after('session_variety_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            //
        });
    }
};
