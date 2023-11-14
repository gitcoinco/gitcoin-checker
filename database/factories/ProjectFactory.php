<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'slug' => $this->faker->slug(),
            'id_addr' => '0x' . $this->faker->hexColor(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'website' => $this->faker->url(),
            'userGithub' => $this->faker->url(),
            'projectGithub' => $this->faker->url(),
            'projectTwitter' => $this->faker->url(),
            'metadata' => $this->faker->text(),
            'flagged_at' => $this->faker->dateTime(),
            'created_at' => $this->faker->dateTime(),
        ];
    }
}
