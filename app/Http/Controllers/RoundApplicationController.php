<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationPromptResult;
use App\Models\RoundPrompt;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Inertia\Inertia;
use HaoZiTeam\ChatGPT\V2 as ChatGPTV2;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class RoundApplicationController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        if ($request->has('status')) {
            // Validate the request if necessary
            $status = $request->input('status', 'all');

            $userPreference = UserPreference::firstOrCreate([
                'user_id' => $request->user()->id,
                'key' => 'selectedApplicationStatus',
            ]);
            $userPreference->value = json_encode($status);
            $userPreference->save();
        } else {
            $userPreference = UserPreference::where('user_id', $request->user()->id)->where('key', 'selectedApplicationStatus')->first();
            if ($userPreference) {
                $status = json_decode($userPreference->value);
            } else {
                $status = 'pending';
            }
        }

        if ($request->has('selectedApplicationRoundType')) {
            $selectedApplicationRoundType = $request->input('selectedApplicationRoundType', 'all');
            $selectedApplicationRoundUuidList = '[]';

            $userPreference = UserPreference::firstOrCreate([
                'user_id' => $request->user()->id,
                'key' => 'selectedApplicationRoundType',
            ]);
            if (!$userPreference) {
                $userPreference->value = json_encode($selectedApplicationRoundType);
                $userPreference->save();
            }

            $userPreference = UserPreference::firstOrCreate([
                'user_id' => $request->user()->id,
                'key' => 'selectedApplicationRoundUuidList',
            ]);
            if (!$userPreference) {
                $userPreference->value = json_encode([]);
                $userPreference->save();
            }
        } else {
            $userPreference = UserPreference::where('user_id', $request->user()->id)->where('key', 'selectedApplicationRoundType')->first();
            if ($userPreference) {
                $selectedApplicationRoundType = json_decode($userPreference->value);
            } else {
                $selectedApplicationRoundType = 'all';
            }

            $userPreference = UserPreference::where('user_id', $request->user()->id)->where('key', 'selectedApplicationRoundUuidList')->first();
            if ($userPreference) {
                $selectedApplicationRoundUuidList = json_decode($userPreference->value, true);
            } else {
                $selectedApplicationRoundUuidList = '[]';
            }
        }

        $userPreference = UserPreference::firstOrCreate([
            'user_id' => $request->user()->id,
            'key' => 'selectedApplicationRemoveTests',
        ], [
            'value' => json_encode(0)
        ]);
        $selectedApplicationRemoveTests = json_decode($userPreference->value);
        if ($request->has('selectedApplicationRemoveTests')) {
            $userPreference->value = json_encode($request->input('selectedApplicationRemoveTests'));
            $userPreference->save();
            $selectedApplicationRemoveTests = json_decode($userPreference->value);
        }

        $listOfApplicationIdsToExclude = [];
        if ($selectedApplicationRemoveTests) {
            $listOfTestRounds = Round::where('name', 'like', '%test%')->pluck('id');
            $listOfApplicationIdsToExclude = RoundApplication::whereIn('round_id', $listOfTestRounds)->pluck('id');
        }

        $applications = RoundApplication::with([
            'round',
            'round.evaluationQuestions',
            'project',
            'project.applications',
            'project.applications.round',
            'evaluationAnswers' => function ($query) {
                $query->orderBy('id', 'desc');
            },
            'evaluationAnswers.user',
            'latestPrompt' => function ($query) {
                $query->orderBy('id', 'desc')->limit(1);
            },
            'results' => function ($query) {
                $query->orderBy('id', 'desc');
            }
        ])
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', strtolower($status));
            })
            ->when($selectedApplicationRoundType != 'all', function ($query) {
                $userPreference = UserPreference::where('user_id', auth()->user()->id)->where('key', 'selectedApplicationRoundUuidList')->first();
                if ($userPreference) {
                    $selectedApplicationRoundUuidList = json_decode($userPreference->value, true);
                    $listOfRoundIds = Round::whereIn('uuid', $selectedApplicationRoundUuidList)->pluck('id');
                    $query->whereIn('round_id', $listOfRoundIds);
                } else {
                    $query->whereIn('round_id', []);
                }

                // $query->whereIn('round_id', json_decode($selectedApplicationRoundTypeDetails));
            })
            ->whereNotIn('id', $listOfApplicationIdsToExclude)
            ->orderBy('id', 'desc')
            ->paginate();


        if ($request->wantsJson()) {
            return response()->json([
                'applications' => $applications,
                'selectedApplicationStatus' => $status,
                'selectedApplicationRoundType' => $selectedApplicationRoundType,
                'selectedApplicationRoundUuidList' => $selectedApplicationRoundUuidList,
                'selectedApplicationRemoveTests' => $selectedApplicationRemoveTests,
            ]);
        } else {
            return Inertia::render('Application/Index', [
                'applications' => $applications,
                'selectedApplicationStatus' => $status,
                'selectedApplicationRoundType' => $selectedApplicationRoundType,
                'selectedApplicationRoundUuidList' => $selectedApplicationRoundUuidList,
                'selectedApplicationRemoveTests' => $selectedApplicationRemoveTests,
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
        $result->project_id = $project->id;

        $result->prompt_id = $latestPrompt->id;
        $result->prompt_type = 'chatgpt';

        $promptData = RoundApplicationController::getPrompt($application);
        $result->prompt_data = $promptData['prompt'];
        $result->save();

        $chatGPT = new ChatGPTV2(env('OPENAI_API_KEY'), 'https://api.openai.com/');
        $chatGPT->addMessage($promptData['system_prompt'], 'system');
        $answers = $chatGPT->ask($result->prompt_data, 'user');

        $answer = null;
        foreach ($answers as $item) {
            $answer = $item['answer'];
        }

        $result->results_data = $answer;
        $result->save();

        return $result;;
    }

    public function checkAgainstChatGPT(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $result = $this->chatGPT($application);
        $result = $application->results()->orderBy('id', 'desc')->first();
        return redirect()->route('round.application.evaluate', ['application' => $application->id, 'result' => $result]);
    }


    public function checkAgainstChatGPTList(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $result = $this->chatGPT($application);
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



    // load the prompt for the application
    public static function getPrompt(RoundApplication $application)
    {

        $round = $application->round;
        $prompt = RoundPromptController::ensurePromptExists($round);

        $project = $application->project;

        $returnedFormat = 'Your response should only contain an array of comma separated objects for the evaluation criteria and returned in json format with each score being between 0 and 100:' . PHP_EOL . PHP_EOL . '[{
            "score": 15,
            "reason": "A specific reason for the score",
            "criteria": "A specific bit of evaluation criteria"
        }]';


        $search = [];
        $replace = [];

        $search[] = '{{ applicationAnswers }}';
        $replace[] = RoundApplicationController::getApplicationAnswers($application);

        $search[] = '{{ projectDetails }}';
        $replace[] = RoundApplicationController::getProjectDetails($application);

        $data = [
            'system_prompt' => $prompt->system_prompt . PHP_EOL . PHP_EOL . $returnedFormat,
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
        foreach ($metadata['application']['answers'] as $key => $answer) {
            if (!$answer['hidden']) {
                $answers .= $answer['question'] . ': ' . $answer['answer'] . PHP_EOL;
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

        return response()->json($historicApplicationsArray);
    }
}
