<?php

namespace App\Http\Controllers;

use App\Models\RoundApplicationPromptResult;
use Illuminate\Http\Request;

class RoundApplicationPromptResultController extends Controller
{
    /**
     * Calculate the scores to make referencing easier
     */
    public static function calculateScore(RoundApplicationPromptResult $roundApplicationPromptResult)
    {

        if (is_array($roundApplicationPromptResult->results_data)) {
            $resultsData = $roundApplicationPromptResult->results_data;
        } else {
            $resultsData = json_decode($roundApplicationPromptResult->results_data, true);
        }

        $score = 0;
        $totalNrAnswers = 0;

        foreach ($resultsData as $result) {
            $totalNrAnswers += 1;
            if ($result['score'] == 'Yes') {
                $score += 1;
            }
        }

        $score = $score / $totalNrAnswers * 100;
        $roundApplicationPromptResult->score = $score;
        $roundApplicationPromptResult->save();

        return response()->json([
            'message' => 'Score calculated successfully',
            'data' => $roundApplicationPromptResult,
        ]);
    }
}
