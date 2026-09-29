<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            // Section 1: General information
            $table->string('field_discipline')->nullable();
            $table->string('attendance_history')->nullable();   // first_time | returning
            $table->string('how_heard')->nullable();

            // Section 2: Overall satisfaction
            $table->string('met_expectations')->nullable();     // exceeded | fully_met | partially_met | not_met

            // Section 3: Scientific & academic content
            $table->integer('scientific_discussion_rating')->nullable();
            $table->string('emerging_areas_covered')->nullable(); // yes | partially | no

            // Section 5: Sessions & programme
            $table->integer('session_balance_rating')->nullable();
            $table->integer('session_pacing_rating')->nullable();

            // Section 4: Logistics
            $table->integer('accommodation_transport_rating')->nullable();

            // Section 8: Materials
            $table->integer('materials_rating')->nullable();

            // Section 6: Speaker-specific
            $table->integer('presenter_support_rating')->nullable();
            $table->integer('av_quality_rating')->nullable();
            $table->integer('audience_engagement_rating')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            $table->dropColumn([
                'field_discipline', 'attendance_history', 'how_heard', 'met_expectations',
                'scientific_discussion_rating', 'emerging_areas_covered',
                'session_balance_rating', 'session_pacing_rating',
                'accommodation_transport_rating', 'materials_rating',
                'presenter_support_rating', 'av_quality_rating', 'audience_engagement_rating',
            ]);
        });
    }
};
