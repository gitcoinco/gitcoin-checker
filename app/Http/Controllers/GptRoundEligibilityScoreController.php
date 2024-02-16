<?php

namespace App\Http\Controllers;

use App\Models\GptRoundEligibilityScore;
use App\Models\Round;
use App\Services\HashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Orhanerday\OpenAi\OpenAi;

class GptRoundEligibilityScoreController extends Controller
{
    public function scoreRounds()
    {

        $open_ai = new OpenAi(env('OPENAI_API_KEY'));

        // Get all rounds
        $rounds = Round::with('applications')->whereDoesntHave('gptRoundEligibilityScores')->get();
        // Loop through each round
        foreach ($rounds as $round) {

            $eligibilityMetadata = json_decode($round->round_metadata, true);
            $applicationMetadata = json_decode($round->application_metadata, true);

            $eligibility = $eligibilityMetadata['eligibility'];
            $application = $applicationMetadata['applicationSchema']['questions'];


            $messages = [
                [
                    "role" => "system",
                    "content" => 'Act as a Gitcoin Round Consultant, whos job it is to evaluate whether a specific Round eligibility criteria matches up well with the questions asked in the round applications. The goal is to ensure that the eligibility criteria is well represented in the questions asked in the round applications. The consultant should provide a score and a reason for the score. The score should be a number between 1 and 100, where 1 is the worst possible score and 100 is the best possible score. The reason should be a short explanation of why the score was given. The consultant should also provide a response in a strict json format: { "score": 100, "reason": "The eligibility criteria is well represented in the questions asked in the round applications." }.',
                ],
                [
                    "role" => "user",
                    "content" => 'Eligibility requirement: ' . json_encode($eligibility) . PHP_EOL . PHP_EOL . 'Application questions: ' . json_encode($application)
                ],
            ];


            $roundId = $round->id;
            $cacheKey = "GptRoundEligibilityScoreController::scoreRounds_{$roundId}" . HashService::hashMultidimensionalArray($messages);

            if (Cache::has($cacheKey)) {
                $gptResponse = Cache::get($cacheKey);
            } else {
                $gptResponse = $open_ai->chat([
                    'model' => 'gpt-4-1106-preview',
                    'messages' => $messages,
                    'temperature' => 1.0,
                    'max_tokens' => 1000,
                    'frequency_penalty' => 0,
                    'presence_penalty' => 0,
                ]);
                Cache::put($cacheKey, $gptResponse, 60 * 24);
            }

            $gptResponse = json_decode($gptResponse, true);

            $content = str_replace(['```json', '```'], ['', ''], $gptResponse['choices'][0]['message']['content']);
            $content = json_decode($content, true);

            $gptRoundEligibilityScore = GptRoundEligibilityScore::firstOrCreate([
                'round_id' => $round->id,
            ], [
                'gpt_prompt' => json_encode($messages),
                'gpt_response' => json_encode($gptResponse),
                'score' => $content['score'],
                'reason' => $content['reason'],

            ]);

            if (!app()->environment('production')) {
                // don't run for all if it's not production
                break;
            }
        }
    }
}
