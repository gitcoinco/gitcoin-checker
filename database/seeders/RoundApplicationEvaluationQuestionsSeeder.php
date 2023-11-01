<?php

namespace Database\Seeders;

use App\Models\RoundApplicationEvaluationQuestions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoundApplicationEvaluationQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rounds = \App\Models\Round::all();
        foreach ($rounds as $key => $round) {
            RoundApplicationEvaluationQuestions::create([
                'round_id' => $round->id,
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
            ]);
        }
    }
}
