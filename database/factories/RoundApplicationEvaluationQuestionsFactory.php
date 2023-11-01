<?php

namespace Database\Factories;

use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoundApplicationEvaluationQuestions>
 */
class RoundApplicationEvaluationQuestionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'round_id' => 1,
            'questions' => '[
                {
                    "text": "Question 1",
                    "type": "radio",
                    "options": [
                        "Yes",
                        "No",
                        "Uncertain"
                    ],
                    "weighting": 50
                },
                {
                    "text": "Question 2",
                    "type": "radio",
                    "options": [
                        "Yes",
                        "No",
                        "Uncertain"
                    ],
                    "weighting": 50
                }
            ]',
        ];
    }
}
