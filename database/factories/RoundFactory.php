<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Round>
 */
class RoundFactory extends Factory
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
            'chain_id' => $this->faker->randomNumber(),
            'round_addr' => '0x123',
            'amount_usd' => $this->faker->randomFloat(),
            'votes' => $this->faker->randomNumber(),
        ];
    }
}
