<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationEvaluationAnswers;
use App\Models\RoundApplicationPromptResult;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $this->authorize('update', AccessControl::class);

        $cacheName = 'AnalyticsController::index';

        $roundsInThePastYear = cache()->remember('rounds_in_the_past_year', 86400, function () {
            return Round::where('created_at', '>=', now()->subYear())
                ->where('name', 'not like', '% test%')
                ->where('name', 'not like', '%test %')
                ->get(['id', 'uuid', 'name']);
        });


        $stats = cache()->remember($cacheName, 86400, function () use ($roundsInThePastYear) {
            $stats = [
                'rounds' => 0,
                'applications' => 0,
                'roundsEvaluatedByHumans' => 0,
                'roundApplicationsEvaluatedByHumans' => 0,
                'roundsEvaluatedByAI' => 0,
                'roundApplicationsEvaluatedByAI' => 0,
            ];

            foreach ($roundsInThePastYear as $round) {
                $applicationsCount = RoundApplication::where('round_id', $round->id)->count();
                $stats['rounds']++;
                $stats['applications'] += $applicationsCount;

                $roundApplicationEvaluationAnswers = RoundApplicationEvaluationAnswers::where('round_id', $round->id)
                    ->count();

                if ($roundApplicationEvaluationAnswers > 0) {
                    $stats['roundsEvaluatedByHumans']++;
                }

                $stats['roundApplicationsEvaluatedByHumans'] += $roundApplicationEvaluationAnswers;

                $roundApplicationPromptResults = RoundApplicationPromptResult::select('application_id')
                    ->where('round_id', $round->id)
                    ->groupBy('application_id')
                    ->havingRaw('COUNT(application_id) = 1')
                    ->get()
                    ->count();
                if ($roundApplicationPromptResults > 0) {
                    $stats['roundsEvaluatedByAI']++;
                }

                $stats['roundApplicationsEvaluatedByAI'] += $roundApplicationPromptResults;
            }

            return $stats;
        });

        // Get a list of rounds that have human evaluators, and pull out the round, together with the humans
        $roundsEvaluatedByHumans = Round::whereHas('evaluationAnswers', function ($query) {
            $query->with('user');
        })->with(['evaluationAnswers' => function ($query) {
            $query->with('user')->select('user_id', 'round_id')->groupBy('user_id', 'round_id');
        }])->get(['id', 'uuid', 'chain_id', 'name']);

        return Inertia::render('Analytics/Index', [
            'roundsInThePastYear' => $roundsInThePastYear,
            'stats' => $stats,
            'roundsEvaluatedByHumans' => $roundsEvaluatedByHumans,
        ]);
    }
}
