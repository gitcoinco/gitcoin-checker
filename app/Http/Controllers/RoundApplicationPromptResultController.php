<?php

namespace App\Http\Controllers;

use App\Models\RoundApplicationPromptResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        if ($resultsData) {
            foreach ($resultsData as $result) {
                $totalNrAnswers += 1;
                if (isset($result['score']) && $result['score'] == 'Yes') {
                    $score += 1;
                }
            }
        }

        if ($totalNrAnswers == 0) {
            $score = 0;
        } else {
            $score = $score / $totalNrAnswers * 100;
        }
        $roundApplicationPromptResult->score = $score;
        $roundApplicationPromptResult->save();

        return response()->json([
            'message' => 'Score calculated successfully',
            'data' => $roundApplicationPromptResult,
        ]);
    }

    public static function averageGPTResponseTime()
    {
        $cacheName = 'RoundApplicationPromptResultController->averageGPTResponseTime()';

        $averageGPTEvaluationTime = Cache::remember($cacheName, 60, function () {
            $averageTime = intval(RoundApplicationPromptResult::where('prompt_type', 'chatgpt')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as average_time'))
                ->first()
                ->average_time);

            $averageTime = min($averageTime, 300);

            return $averageTime;
        });

        return $averageGPTEvaluationTime;
    }
}
