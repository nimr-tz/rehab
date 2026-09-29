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
            // Overall
            if (!Schema::hasColumn('conference_feedback', 'would_recommend')) {
                $table->string('would_recommend')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'future_attendance')) {
                $table->string('future_attendance')->nullable();
            }

            // Submission & Review Process
            if (!Schema::hasColumn('conference_feedback', 'submission_ease_rating')) {
                $table->integer('submission_ease_rating')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'guidelines_clear')) {
                $table->string('guidelines_clear')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'submission_improvements')) {
                $table->text('submission_improvements')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'review_time_rating')) {
                $table->integer('review_time_rating')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'reviewer_comments_quality')) {
                $table->integer('reviewer_comments_quality')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'review_fair')) {
                $table->string('review_fair')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'review_process_comments')) {
                $table->text('review_process_comments')->nullable();
            }

            // Communication & Platform
            if (!Schema::hasColumn('conference_feedback', 'communication_rating')) {
                $table->integer('communication_rating')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'platform_usability')) {
                $table->integer('platform_usability')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'platform_feedback')) {
                $table->text('platform_feedback')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'speaker_quality_rating')) {
                $table->integer('speaker_quality_rating')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'session_organization_rating')) {
                $table->integer('session_organization_rating')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'learning_value_rating')) {
                $table->integer('learning_value_rating')->nullable();
            }

            // Networking
            if (!Schema::hasColumn('conference_feedback', 'networking_activities')) {
                $table->text('networking_activities')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'new_connections')) {
                $table->string('new_connections')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'valuable_interaction')) {
                $table->text('valuable_interaction')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'contact_permission')) {
                $table->boolean('contact_permission')->default(false);
            }
            if (!Schema::hasColumn('conference_feedback', 'liked_most')) {
                $table->text('liked_most')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'improvement_suggestions')) {
                $table->text('improvement_suggestions')->nullable();
            }
            if (!Schema::hasColumn('conference_feedback', 'future_topics')) {
                $table->text('future_topics')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            $table->dropColumn([
                'would_recommend', 'future_attendance', 'submission_ease_rating',
                'guidelines_clear', 'submission_improvements', 'review_time_rating',
                'reviewer_comments_quality', 'review_fair', 'review_process_comments',
                'communication_rating', 'platform_usability', 'platform_feedback',
                'speaker_quality_rating', 'session_organization_rating', 'learning_value_rating',
                'networking_activities', 'new_connections', 'valuable_interaction',
                'contact_permission', 'liked_most', 'improvement_suggestions', 'future_topics'
            ]);
        });
    }
};
