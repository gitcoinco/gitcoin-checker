<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use App\Models\Project;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationPromptResult;
use App\Models\RoundPrompt;
use App\Models\RoundRole;
use App\Models\UserPreference;
use App\Policies\RoundApplicationPolicy;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Orhanerday\OpenAi\OpenAi;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Cache;
use RuntimeException;


class RoundApplicationController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function showPublic(RoundApplication $application)
    {

        return view('public.application.show', [
            'application' => $application,
            'project' => $application->project,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
            'promptResults' => $application->results()->orderBy('id', 'desc')->get(),

            'evaluationAnswers' => $application->evaluationAnswers()->with('user')->orderBy('id', 'desc')->get(),
        ]);
    }

    private function getAvgGPTScores()
    {
        $cacheName = 'RoundApplicationController->getAvgGPTScores()';
        return Cache::remember($cacheName, 60 * 24, function () {
            $statuses = ['APPROVED', 'REJECTED', 'PENDING'];
            $applicationStats = collect($statuses)->mapWithKeys(function ($status) {
                return [$status => ['count' => 0, 'avgGPTScore' => 0]];
            })->toArray();

            try {

                // Use chunking to process large datasets
                RoundApplication::whereHas('results')
                    ->with(['results' => function ($query) {
                        $query->selectRaw('application_id, AVG(score) as avgScore, COUNT(*) as count')
                            ->groupBy('application_id');
                    }])
                    ->chunk(100, function ($applications) use (&$applicationStats) {
                        foreach ($applications as $application) {
                            $status = strtoupper($application->status);
                            if (array_key_exists($status, $applicationStats) && !empty($application->results->first())) {
                                $applicationStats[$status]['count'] += $application->results->first()->count;
                                $applicationStats[$status]['avgGPTScore'] += $application->results->first()->avgScore * $application->results->first()->count;
                            }
                        }
                    });

                // Calculate average GPT scores
                foreach ($statuses as $status) {
                    if ($applicationStats[$status]['count'] > 0) {
                        $applicationStats[$status]['avgGPTScore'] /= $applicationStats[$status]['count'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error fetching applications: ' . $e->getMessage());
                Log::error($e->getTraceAsString());
                throw $e;  // Optionally, rethrow the exception if you want it to be handled by Laravel's global exception handler
            }

            return $applicationStats;
        });
    }

    public function deleteGPTResult(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $results = $application->results()->where('prompt_type', 'chatgpt')->get();
        foreach ($results as $result) {
            $result->delete();
        }

        return response()->json([
            'message' => 'GPT results deleted successfully',
            'application' => $application->id
        ]);
    }


    public function details(RoundApplication $application)
    {
        $application->load(['project']);
        return response()->json([
            'application' => $application,
        ]);
    }

    public function show(RoundApplication $application)
    {

        $this->authorize('view', $application);

        $application->load([
            'round.evaluationQuestions',
            'round.chain',
            'project',
            'project.applications',
            'project.applications.round',
            'evaluationAnswers',
            'evaluationAnswers.user',
            'latestPrompt',
            'results',

        ]);

        $user = auth()->user();

        $isRoundManager = $user->isAdmin || RoundRole::where('round_id', $application->round->id)->where('address', $user->eth_addr)->where('role', 'MANAGER')->exists();


        return Inertia::render('Application/Show', [
            'application' => $application,
            'round' => $application->round,
            'averageGPTEvaluationTime' => RoundApplicationPromptResultController::averageGPTResponseTime(),
            'isRoundManager' => $isRoundManager,
        ]);
    }

    public function apiShow(RoundApplication $application)
    {
        $application->load([
            'round',
            'round.chain',
            'round.evaluationQuestions',
            'project',
            'project.applications',
            'project.applications.round',
            'evaluationAnswers',
            'evaluationAnswers.user',
            'latestPrompt',
            'results',
        ]);

        return response()->json([
            'application' => $application,
        ]);
    }

    /**
     * Store the order in the user preferences
     */
    public function setOrder(Request $request)
    {
        $orderKeys = [
            'roundApplicationOrderBy' => 'created_at',
            'roundApplicationOrderByDirection' => 'desc',
        ];

        $orderData = [];

        foreach ($orderKeys as $key => $default) {
            if ($request->has($key)) {
                $value = $request->input($key, $default);
                if (!is_string($value)) {
                    $value = '';
                }

                $userPreference = UserPreference::updateOrCreate([
                    'user_id' => $request->user()->id,
                    'key' => $key,
                ], [
                    'value' => json_encode($value)
                ]);

                $orderData[$key] = is_string($value) && Str::length($value) > 0 ? $value : json_decode($userPreference->value);
            } else {
                $userPreference = UserPreference::where('user_id', $request->user()->id)->where('key', $key)->first();

                $orderData[$key] = $userPreference ? json_decode($userPreference->value) : $default;
            }
        }

        return $orderData;
    }

    /**
     * Store the filters in the user preferences
     */
    public function setFilters(Request $request)
    {
        $filterKeys = [
            'selectedSearchProjects' => '',
            'selectedApplicationStatus' => 'all',
            'selectedApplicationRoundType' => 'all',
            'selectedApplicationRoundUuidList' => '[]',
            'selectedApplicationRemoveTests' => 0
        ];

        $filterData = [];

        foreach ($filterKeys as $key => $default) {
            if ($request->has($key)) {
                $value = $request->input($key, $default);
                if (!is_string($value)) {
                    $value = '';
                }

                $userPreference = UserPreference::updateOrCreate([
                    'user_id' => $request->user()->id,
                    'key' => $key,
                ], [
                    'value' => json_encode($value)
                ]);

                $filterData[$key] = is_string($value) && Str::length($value) > 0 ? $value : json_decode($userPreference->value);
            } else {
                $userPreference = UserPreference::where('user_id', $request->user()->id)->where('key', $key)->first();

                $filterData[$key] = $userPreference ? json_decode($userPreference->value) : $default;
            }
        }

        return $filterData;
    }

    public function getApplications(Request $request, Round $round = null, $applyFilters = true, $paginate = 25)
    {


        $applications = RoundApplication::with([
            'round' => function ($query) {
                $query->select('id', 'uuid', 'name', 'applications_start_time', 'applications_end_time', 'round_addr', 'chain_id');
            },
            'round.chain' => function ($query) {
                $query->select('id', 'uuid', 'name', 'chain_id');
            },
            'round.evaluationQuestions' => function ($query) {
                $query->select('id', 'uuid', 'round_id', 'questions');
            },
            'project' => function ($query) {
                $query->select('id', 'uuid', 'slug', 'id_addr', 'title', 'website', 'logoImg', 'bannerImg', 'projectGithub', 'userGithub', 'projectTwitter', 'created_at', 'updated_at');
            },
            'project.applications' => function ($query) {
                $query->orderBy('created_at', 'desc');
                $query->select('id', 'uuid', 'application_id', 'round_id', 'project_addr', 'status', 'created_at');
            },
            'project.applications.round' => function ($query) {
                $query->select('id', 'uuid', 'name');
            },
            'evaluationAnswers' => function ($query) {
                $query->orderBy('id', 'desc');
            },
            'evaluationAnswers.user' => function ($query) {
                $query->select('id', 'uuid', 'name');
            },
            'latestPrompt' => function ($query) {
                $query->orderBy('id', 'desc')->limit(1);
                $query->select('id', 'uuid');
            },
            'results' => function ($query) {
                $query->select('id', 'uuid', 'application_id', 'round_id', 'project_id', 'prompt_id', 'results_data', 'created_at', 'updated_at');
            }
        ])
            ->orderBy('created_at', 'desc')
            ->select('id', 'uuid', 'application_id', 'project_addr', 'round_id', 'status', 'created_at', 'updated_at')
            ->whereHas('project')
            ->paginate($paginate);

        $averageGPTEvaluationTime = intval(RoundApplicationPromptResult::where('prompt_type', 'chatgpt')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as average_time'))
            ->first()
            ->average_time);
        $averageGPTEvaluationTime = min($averageGPTEvaluationTime, 300);

        $data = [
            'applications' => $applications,
            'averageGPTEvaluationTime' => $averageGPTEvaluationTime,
        ];

        return $data;
    }

    public function index(Request $request)
    {
        $data = $this->getApplications($request);

        if ($request->wantsJson()) {
            return response()->json([
                'indexData' => env('GRAPHQL_ENDPOINT'),
                'applications' => $data['applications'],

            ]);
        } else {
            return Inertia::render('Application/Index', [
                'indexData' => env('GRAPHQL_ENDPOINT'),
                'applications' => $data['applications'],
            ]);
        }
    }


    public function evaluateAllShow(Round $round)

    {
        $maxProjects = 50;
        $this->notificationService->info('While this app is in testing, we are only evaluating ' . $maxProjects . ' projects at a time.');

        $latestPrompt = $round->prompt()->orderBy('id', 'desc')->first();
        $projectsIdsWithEvaluatedPrompts = RoundApplicationPromptResult::where('prompt_id', $latestPrompt->id)
            ->distinct('project_id')
            ->pluck('project_id');

        $projects = $round->projects()->whereNotIn('projects.id', $projectsIdsWithEvaluatedPrompts)->with(['applications' => function ($query) use ($round) {
            $query->where('round_id', $round->id);
        }, 'applications.results' => function ($query) {
            $query->orderBy('id', 'desc');
        }])->paginate();



        return Inertia::render('Round/EvaluateAll', [
            'round' => $round,
            'projects' => $projects,
            'latestPrompt' => $latestPrompt,
        ]);
    }

    public function evaluate(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $round = $application->round;

        $application->load('project');

        $result = $application->results()->orderBy('id', 'desc')->first();

        return Inertia::render('Application/Evaluate', [
            'round' => $round,
            'application' => $application,
            'prompt' => RoundApplicationController::getPrompt($application),
            'result' => $result ? $result : '',
        ]);
    }

    private function chatGPT(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        // If the application has already been evaluated against the latest prompt, don't do it again
        $latestPrompt = $application->round->prompt()->orderBy('id', 'desc')->first();

        $latestResult = $application->results()->orderBy('id', 'desc')->first();
        if ($latestResult && $latestResult->prompt_id == $latestPrompt->id) {
            return $latestResult;
        }


        $result = new RoundApplicationPromptResult();
        $result->application_id = $application->id;
        $result->round_id = $application->round_id;

        $project = $application->project;
        if (!isset($project->id)) {
            // Send the error to Bugsnag but continue processing
            Bugsnag::notifyException(new RuntimeException('Project not found for application: ' . $application->id . ' - ' . $application->project_addr . ', round: ' . $application->round->id));
            return false;
        }

        $result->project_id = $project->id;

        $result->prompt_id = $latestPrompt->id;
        $result->prompt_type = 'chatgpt';

        $promptData = RoundApplicationController::getPrompt($application);
        $result->system_prompt = $promptData['system_prompt'];
        $result->prompt_data = $promptData['prompt'];
        $result->results_data = '[]';
        $result->save();

        $open_ai = new OpenAi(env('OPENAI_API_KEY'));

        $evaluationQuestions = $application->round->evaluationQuestions;

        try {
            $questions = json_decode($evaluationQuestions->questions, true);
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Failed to decode evaluation questions: ' . $e->getMessage());
            // Set questions to an empty array as a fallback
            $questions = [];
        }

        $messages = [
            [
                "role" => "system",
                "content" => $promptData['system_prompt'] . PHP_EOL . 'Respond to each question with criteria, score, and reason format.',
            ],
            [
                "role" => "user",
                "content" => $promptData['prompt']
            ],
        ];

        // Append each question as a new user message with specific format instructions
        foreach ($questions as $question) {
            $formattedQuestion = "Eligibility requirement: " . $question['text'] . PHP_EOL . "Provide a response in the format: " . PHP_EOL . "1) Criteria: [Full eligibility requirement]" . PHP_EOL . "2) Score: [Yes/No/Uncertain]" . PHP_EOL . "3) Reason: [Explanation].";
            $messages[] = [
                "role" => "user",
                "content" => $formattedQuestion
            ];
        }

        // dd($messages);

        $gptResponse = $open_ai->chat([
            'model' => 'gpt-4o-2024-05-13', // gpt-4-1106-preview
            'messages' => $messages,
            'temperature' => 1.0,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,

            // We should really get an array of objects back, however, this is not working yet.
            // 'functions' => [
            //     [
            //         'name'        => 'gpt_evaluation',
            //         'description' => 'The format in which answers should be returned.',
            //         'parameters'  => [
            //             'type'       => 'object',
            //             'properties' => [
            //                 'criteria' => [
            //                     'type'        => 'string',
            //                     'description' => 'A specific bit of evaluation criteria.',
            //                 ],
            //                 'score' => [
            //                     'type'        => 'string',
            //                     'description' => 'The score can be "Yes", "No", or "Uncertain".',
            //                 ],
            //                 'reason' => [
            //                     'type'        => 'string',
            //                     'description' => 'A specific reason for the score.',
            //                 ],
            //             ],
            //             'required'   => ['score', 'reason', 'criteria'],
            //         ],
            //     ],
            // ],
        ]);

        $gptResponse = json_decode($gptResponse);
        $search = ['```json', '```'];
        $replace = ['', ''];

        if (isset($gptResponse->choices[0]->message->content)) {
            $answer = ($gptResponse->choices[0]->message->content);
        } else {
            // If we don't get a response, add an empty array
            $answer = '[]';
        }

        $answer = str_replace($search, $replace, $answer);
        $answer = json_decode($answer, true);

        $result->results_data = $answer;
        $result->save();

        RoundApplicationPromptResultController::calculateScore($result);

        return $result;;
    }

    public function checkAgainstChatGPT(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $result = $this->chatGPT($application);
        if (!$result) {
            return response()->json(['error' => 'Failed to evaluate application.']);
        }

        $result = $application->results()->orderBy('id', 'desc')->first();
        return redirect()->route('round.application.evaluate', ['application' => $application->id, 'result' => $result]);
    }


    public function checkAgainstChatGPTList(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $result = $this->chatGPT($application);
        if (!$result) {
            return response()->json(['error' => 'Failed to evaluate application.']);
        }


        $result = $application->results()->orderBy('id', 'desc')->first();
        $round = $application->round;
        $project = $round->projects()->where('projects.id', $application->project->id)->with(['applications' => function ($query) use ($round, $application) {
            $query->where('round_id', $round->id);
            $query->where('id', $application->id);
        }, 'applications.results' => function ($query) {
            $query->orderBy('id', 'desc');
        }])->first();


        return response()->json(['project' => $project]);
    }

    public static function getProjectHistory(RoundApplication $application)
    {
        $project = $application->project;

        $history = $project->applications()->where('id', '!=', $application->id)->with(['round'])->orderBy('created_at', 'desc')->get();

        $return = '';
        foreach ($history as $item) {
            $return .= 'Round: ' . $item->round->name . PHP_EOL;
            $return .= 'Status: ' . $item->status . PHP_EOL;
            $return .= 'Date: ' . $item->created_at->diffForHumans() . PHP_EOL . PHP_EOL;
        }

        return $return;
    }



    // load the prompt for the application
    public static function getPrompt(RoundApplication $application)
    {

        $round = $application->round;
        $prompt = RoundPromptController::ensurePromptExists($round);

        $project = $application->project;

        $returnedFormat = 'Your response should only contain an array of comma separated objects for each and every bit of evaluation criteria and returned in json format:' . PHP_EOL . PHP_EOL . '[{
            "criteria": "The full specific eligibility requirement that this score relates to. It needs to be an exact match to the eligibility requirement as its used for string matching."
            "score": "Yes, No or Uncertain",
            "reason": "A specific reason for the score",
        }]';

        $search = [];
        $replace = [];

        $search[] = '{{ application.answers }}';
        $replace[] = RoundApplicationController::getApplicationAnswers($application);

        $search[] = '{{ project.name }}';
        $replace[] = $project->title;

        $search[] = '{{ project.details }}';
        $replace[] = RoundApplicationController::getProjectDetails($application);

        $search[] = '{{ project.historic_applications }}';
        $replace[] = RoundApplicationController::getProjectHistory($application);

        $metadata = json_decode($round->round_metadata, true);

        $search[] = '{{ round.eligibility.description }}';
        $replace[] = isset($metadata['eligibility']['description']) ? $metadata['eligibility']['description'] : '';

        $search[] = '{{ round.name }}';
        $replace[] = isset($metadata['name']) ? $metadata['name'] : '';

        $search[] = '{{ round.eligibility.requirements }}';
        $requirements = '';
        if (isset($metadata['eligibility']['requirements']) && is_array($metadata['eligibility']['requirements'])) {
            foreach ($metadata['eligibility']['requirements'] as $key => $requirement) {
                $requirements .= ($key + 1) . '. ' . $requirement['requirement'] . PHP_EOL;
            }
        }
        $replace[] = $requirements;

        $githubController = new GithubController();

        $githubResults = '';
        if ($project->userGithub || $project->projectGithub) {

            if ($project->userGithub) {
                $githubResults .= 'User Github: ' . json_encode($githubController->checkGitHubActivity($project->userGithub)) . PHP_EOL;
            }
            if ($project->projectGithub) {
                $githubResults .= 'Project Github: ' . json_encode($githubController->checkGitHubActivity($project->projectGithub, true)) . PHP_EOL;
            }
        }
        $search[] = '{{ github.recent_activity.summary }}';
        $replace[] = $githubResults;

        $ossController = new OpensourceObserverController();
        $ossResults = $ossController->getProjectStatistics($project->projectGithub);

        $search[] = '{{ github.oss.project.summary }}';
        $replace[] = $ossResults;

        $data = [
            'system_prompt' => str_replace($search, $replace, $prompt->system_prompt) . PHP_EOL . PHP_EOL . $returnedFormat . PHP_EOL,
            'prompt' => str_replace($search, $replace, $prompt->prompt),
        ];

        return $data;
    }

    public static function getProjectDetails(RoundApplication $application)
    {
        $project = $application->project;
        $details = '';

        $details .= 'Project name: ' . $project->title . PHP_EOL;
        $details .= 'Project website: ' . $project->website . PHP_EOL;
        $details .= 'Project description: ' . $project->description . PHP_EOL;

        $details .= 'Project Twitter: ' . ($project->projectTwitter ? 'https://twitter.com/' . $project->projectTwitter : 'N/A') . PHP_EOL;
        $details .= 'Project Github: ' . ($project->projectGithub ? 'https://github.com/' . $project->projectGithub : 'N/A') . PHP_EOL;
        $details .= 'User Github: ' . ($project->userGithub ? 'https://github.com/' . $project->userGithub : 'N/A') . PHP_EOL;

        return $details;
    }

    public static function getApplicationAnswers($application)
    {
        $metadata = json_decode($application->metadata, true);
        $answers = '';
        if (isset($metadata['application']['answers'])) {
            foreach ($metadata['application']['answers'] as $answer) {
                if (!isset($answer['encryptedAnswer']) && isset($answer['answer'])) {
                    $answers .= $answer['question'] . ': ';

                    if (is_array($answer['answer'])) {
                        $answers .= implode(', ', $answer['answer']);
                    } else {
                        $answers .= $answer['answer'];
                    }
                    $answers .= PHP_EOL;
                }
            }
        }

        return $answers;
    }

    public function statsHistory()
    {
        $user = auth()->user();

        $nrWeeks = 52;

        // pull out data for the past 52 weeks
        $historicApplicationsCreated = [];
        // Pad empty weeks with data
        $start = now()->subWeeks($nrWeeks);
        for ($i = 0; $i < $nrWeeks; $i++) {
            $date = $start->addWeek()->startOfWeek();
            $dateEndOfTheWeek = $date->copy()->endOfWeek();
            $week = $date->format('Y-m-d');
            if (!isset($historicApplicationsCreated[$week])) {
                $historicApplicationsCreated[$week] = ['date' => $week, 'created' => 0, 'approved' => 0, 'rejected' => 0, 'avgMinutesToApproval' => 0, 'avgMinutesToRejection' => 0];
            }


            $createdNr = RoundApplication::where('created_at', '>=', $date)
                ->where('created_at', '<=', $dateEndOfTheWeek)
                ->count();
            $historicApplicationsCreated[$week]['created'] = $createdNr;

            $approvedNr = RoundApplication::where('approved_at', '>=', $date)
                ->where('approved_at', '<=', $dateEndOfTheWeek)
                ->count();
            $historicApplicationsCreated[$week]['approved'] = $approvedNr;

            $rejectedNr = RoundApplication::where('rejected_at', '>=', $date)
                ->where('rejected_at', '<=', $dateEndOfTheWeek)
                ->count();
            $historicApplicationsCreated[$week]['rejected'] = $rejectedNr;

            $avgHoursToApproval = RoundApplication::where('approved_at', '>=', $date)
                ->where('approved_at', '<=', $dateEndOfTheWeek)
                ->avg(DB::raw('TIME_TO_SEC(TIMEDIFF(approved_at, created_at))')) / 60 / 60;
            $historicApplicationsCreated[$week]['avgHoursToApproval'] = intval($avgHoursToApproval);

            $avgHoursToRejection = RoundApplication::where('rejected_at', '>=', $date)
                ->where('rejected_at', '<=', $dateEndOfTheWeek)
                ->avg(DB::raw('TIME_TO_SEC(TIMEDIFF(approved_at, created_at))')) / 60 / 60;
            $historicApplicationsCreated[$week]['avgHoursToRejection'] = intval($avgHoursToRejection);
        }

        // Sort by keys to make sure data is in chronological order
        ksort($historicApplicationsCreated);

        // To ensure JSON array format instead of JSON object, use array_values to discard the original keys.
        $historicApplicationsArray = array_values($historicApplicationsCreated);

        $gptStats = $this->getAvgGPTScores();

        return response()->json(
            [
                'history' => $historicApplicationsArray,
                'gptStats' => $gptStats,
            ]
        );
    }

    /**
     * Update the score based on the GPT scores and the Humasn scores
     */
    public static function updateScore(RoundApplication $application)
    {
        $roundApplicationPromptResult = $application->results()->where('prompt_type', 'chatgpt')->orderBy('created_at', 'desc')->first();
        $roundApplicationevaluationAnswers = $application->evaluationAnswers()->orderBy('id', 'desc')->get();

        $totalScores = 0;
        $scores = 0;
        foreach ($roundApplicationevaluationAnswers as $roundApplicationevaluationAnswer) {
            $totalScores += 1;
            $scores += $roundApplicationevaluationAnswer->score;
        }
        if ($roundApplicationPromptResult) {
            $totalScores += 1;
            $scores += $roundApplicationPromptResult->score;
        }

        if ($totalScores > 0) {
            $application->score = $scores / $totalScores;
            $application->save();
        }
    }
}
