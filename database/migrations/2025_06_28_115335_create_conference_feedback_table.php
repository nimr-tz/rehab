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
        Schema::create('conference_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('participant_type')->nullable(); // attendee, presenter, reviewer
            
            // Overall Conference Rating
            $table->integer('overall_rating')->nullable(); // 1-5 stars
            $table->text('overall_comments')->nullable();
            
            // Specific Areas (1-5 rating each)
            $table->integer('venue_rating')->nullable();
            $table->text('venue_comments')->nullable();
            
            $table->integer('organization_rating')->nullable();
            $table->text('organization_comments')->nullable();
            
            $table->integer('content_quality_rating')->nullable();
            $table->text('content_quality_comments')->nullable();
            
            $table->integer('networking_rating')->nullable();
            $table->text('networking_comments')->nullable();
            
            $table->integer('registration_process_rating')->nullable();
            $table->text('registration_process_comments')->nullable();
            
            $table->integer('abstract_review_rating')->nullable();
            $table->text('abstract_review_comments')->nullable();
            
            // Improvement Suggestions
            $table->text('what_worked_well')->nullable();
            $table->text('what_needs_improvement')->nullable();
            $table->text('suggestions_for_next_year')->nullable();
            $table->text('topics_want_to_see')->nullable();
            
            // Future Participation
            $table->enum('likely_to_attend_next', ['definitely', 'probably', 'maybe', 'probably_not', 'definitely_not'])->nullable();
            $table->enum('likely_to_recommend', ['definitely', 'probably', 'maybe', 'probably_not', 'definitely_not'])->nullable();
            
            // Session Feedback
            $table->json('sessions_attended')->nullable(); // Array of session names
            $table->string('most_valuable_session')->nullable();
            $table->string('least_valuable_session')->nullable();
            
            // Demographics & Additional
            $table->enum('age_group', ['18-25', '26-35', '36-45', '46-55', '56-65', '65+'])->nullable();
            $table->enum('experience_level', ['student', 'early_career', 'mid_career', 'senior', 'retired'])->nullable();
            $table->text('additional_comments')->nullable();
            
            // Conference Value
            $table->enum('conference_value', ['excellent', 'good', 'average', 'poor', 'very_poor'])->nullable();
            $table->boolean('would_present_again')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conference_feedback');
    }
};
