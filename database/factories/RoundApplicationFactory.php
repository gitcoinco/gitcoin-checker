<?php

namespace Database\Factories;

use App\Models\RoundApplication;
use App\Providers\EthereumAddressProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoundApplicationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RoundApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            // Assuming some potential fields for the RoundApplication model:
            'round_id' => $this->faker->randomNumber(),
            'status' => $this->faker->randomElement(['PENDING', 'APPROVED', 'REJECTED']),
            'project_addr' => '0x123',
        ];
    }
}
