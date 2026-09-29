<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Speaker>
 */
class SpeakerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'bio' => $this->faker->paragraph(),
            'affiliation' => $this->faker->company(),
            'position' => $this->faker->jobTitle(),
            'type' => $this->faker->randomElement(['keynote', 'plenary', 'invited', 'panelist']),
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
