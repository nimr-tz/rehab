<?php

namespace Database\Factories;

use App\Models\AbstractSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AbstractSubmission>
 */
class AbstractSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $officialSubthemes = array_keys(config('conference.subtheme_prefixes', []));

        return [
            'author_name' => $this->faker->name(),
            'author_institute' => $this->faker->company(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'subtheme' => $this->faker->randomElement($officialSubthemes),
            'presentation_mode' => $this->faker->randomElement(['oral', 'poster']),
            'include_in_proceedings' => $this->faker->boolean(),
            'coauthors' => [
                [
                    'name' => $this->faker->name(),
                    'institution' => $this->faker->company(),
                    'email' => $this->faker->email()
                ]
            ],
            'user_id' => User::factory(),
            'status' => 'submitted',
            'submitted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'reviewer_id' => null,
            'reviewer_2_id' => null,
            'reviewer_1_score' => null,
            'reviewer_2_score' => null,
            'average_score' => null,
            'admin_comment' => null,
            'status_changed_at' => null,
            'status_changed_by' => null,
            'assigned_at' => null,
            'review_completed_at' => null,
            'conference_code' => null,
            'committee_notes' => null,
            'committee_selected' => false,
            'code_assigned_by' => null,
            'code_assigned_at' => null,
            'presentation_session' => null,
            'presentation_date' => null,
            'presentation_time' => null,
            'session_id' => null,
            'oral_presentation_file' => null,
            'poster_presentation_file' => null,
            'presentation_files' => null,
            'presentation_uploaded_at' => null,
            'presentation_notes' => null,
            'presentation_status' => 'pending',
            'audio_poster_file' => null,
            'audio_poster_poster_file' => null,
            'audio_poster_description' => null,
            'audio_poster_duration' => null,
            'audio_poster_metadata' => null,
            // Quality management fields
            'quality_flag' => false,
            'quality_flag_reason' => null,
            'quality_flag_priority' => null,
            'quality_flagged_at' => null,
            'quality_flagged_by' => null,
            'quality_resolution_notes' => null,
            'quality_resolution_action' => null,
            'quality_resolved_at' => null,
            'quality_resolved_by' => null,
        ];
    }

    /**
     * Indicate that the abstract is under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
            'reviewer_id' => User::factory()->state(['role' => 'reviewer']),
            'reviewer_2_id' => User::factory()->state(['role' => 'reviewer']),
            'assigned_at' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    /**
     * Indicate that the abstract has completed reviews.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'committee_review',
            'reviewer_id' => User::factory()->state(['role' => 'reviewer']),
            'reviewer_2_id' => User::factory()->state(['role' => 'reviewer']),
            'reviewer_1_score' => $this->faker->numberBetween(60, 95),
            'reviewer_2_score' => $this->faker->numberBetween(60, 95),
            'average_score' => $this->faker->numberBetween(60, 95),
            'assigned_at' => $this->faker->dateTimeBetween('-3 weeks', '-1 week'),
            'review_completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the abstract is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'reviewer_id' => User::factory()->state(['role' => 'reviewer']),
            'reviewer_2_id' => User::factory()->state(['role' => 'reviewer']),
            'reviewer_1_score' => $this->faker->numberBetween(75, 95),
            'reviewer_2_score' => $this->faker->numberBetween(75, 95),
            'average_score' => $this->faker->numberBetween(75, 95),
            'assigned_at' => $this->faker->dateTimeBetween('-4 weeks', '-2 weeks'),
            'review_completed_at' => $this->faker->dateTimeBetween('-2 weeks', '-1 week'),
        ]);
    }

    /**
     * Indicate that the abstract is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewer_id' => User::factory()->state(['role' => 'reviewer']),
            'reviewer_2_id' => User::factory()->state(['role' => 'reviewer']),
            'reviewer_1_score' => $this->faker->numberBetween(30, 60),
            'reviewer_2_score' => $this->faker->numberBetween(30, 60),
            'average_score' => $this->faker->numberBetween(30, 60),
            'assigned_at' => $this->faker->dateTimeBetween('-4 weeks', '-2 weeks'),
            'review_completed_at' => $this->faker->dateTimeBetween('-2 weeks', '-1 week'),
        ]);
    }

    /**
     * Indicate that the abstract has quality issues.
     */
    public function withQualityFlag(): static
    {
        return $this->state(fn (array $attributes) => [
            'quality_flag' => true,
            'quality_flag_reason' => $this->faker->sentence(),
            'quality_flag_priority' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'quality_flagged_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'quality_flagged_by' => User::factory()->state(['role' => 'admin']),
        ]);
    }
}
