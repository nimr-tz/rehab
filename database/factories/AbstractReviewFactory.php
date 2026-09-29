<?php

namespace Database\Factories;

use App\Models\AbstractReview;
use App\Models\AbstractSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AbstractReview>
 */
class AbstractReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'abstract_submission_id' => AbstractSubmission::factory(),
            'reviewer_id' => User::factory()->state(['role' => 'reviewer']),
            'reviewer_number' => $this->faker->randomElement([1, 2]),
            'review_round' => 1,
            'technical_quality' => $this->faker->randomFloat(1, 1, 10),
            'novelty' => $this->faker->randomFloat(1, 1, 10),
            'relevance' => $this->faker->randomFloat(1, 1, 10),
            'clarity' => $this->faker->randomFloat(1, 1, 10),
            'score' => $this->faker->randomFloat(1, 0, 100),
            'comments' => $this->faker->paragraph(),
            'recommendation' => $this->faker->randomElement([
                'accept_oral',
                'accept_poster',
                'minor_revisions',
                'major_revisions',
                'reject'
            ]),
            'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the review is high quality.
     */
    public function highQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_quality' => $this->faker->randomFloat(1, 8, 10),
            'novelty' => $this->faker->randomFloat(1, 8, 10),
            'relevance' => $this->faker->randomFloat(1, 8, 10),
            'clarity' => $this->faker->randomFloat(1, 8, 10),
            'score' => $this->faker->randomFloat(1, 80, 100),
            'comments' => $this->faker->paragraphs(3, true),
            'recommendation' => $this->faker->randomElement(['accept_oral', 'accept_poster']),
        ]);
    }

    /**
     * Indicate that the review is low quality.
     */
    public function lowQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_quality' => $this->faker->randomFloat(1, 1, 5),
            'novelty' => $this->faker->randomFloat(1, 1, 5),
            'relevance' => $this->faker->randomFloat(1, 1, 5),
            'clarity' => $this->faker->randomFloat(1, 1, 5),
            'score' => $this->faker->randomFloat(1, 0, 50),
            'comments' => $this->faker->sentence(),
            'recommendation' => 'reject',
        ]);
    }

    /**
     * Indicate that the review has missing criteria.
     */
    public function missingCriteria(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_quality' => null,
            'novelty' => $this->faker->randomFloat(1, 1, 10),
            'relevance' => $this->faker->randomFloat(1, 1, 10),
            'clarity' => $this->faker->randomFloat(1, 1, 10),
            'score' => $this->faker->randomFloat(1, 0, 100),
            'comments' => $this->faker->paragraph(),
            'recommendation' => $this->faker->randomElement([
                'accept_oral',
                'accept_poster',
                'minor_revisions',
                'major_revisions',
                'reject'
            ]),
        ]);
    }

    /**
     * Indicate that the review has short comments.
     */
    public function shortComments(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_quality' => $this->faker->randomFloat(1, 1, 10),
            'novelty' => $this->faker->randomFloat(1, 1, 10),
            'relevance' => $this->faker->randomFloat(1, 1, 10),
            'clarity' => $this->faker->randomFloat(1, 1, 10),
            'score' => $this->faker->randomFloat(1, 0, 100),
            'comments' => $this->faker->sentence(),
            'recommendation' => $this->faker->randomElement([
                'accept_oral',
                'accept_poster',
                'minor_revisions',
                'major_revisions',
                'reject'
            ]),
        ]);
    }

    /**
     * Indicate that the review is for reviewer 1.
     */
    public function reviewer1(): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewer_number' => 1,
        ]);
    }

    /**
     * Indicate that the review is for reviewer 2.
     */
    public function reviewer2(): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewer_number' => 2,
        ]);
    }
}
