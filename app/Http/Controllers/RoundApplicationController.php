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
use Illuminate\Support\Str;

class RoundApplicationController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
        $application->load([
            'round',
            'round.evaluationQuestions',
            'project',
            'project.applications',
            'project.applications.round',
            'evaluationAnswers',
            'evaluationAnswers.user',
            'latestPrompt',
            'results'
        ]);

        return response()->json([
            'application' => $application,
        ]);
    }

    public function setFilters(Request $request)
    {
        $filterKeys = [
            'selectedSearchProjects' => '',
            'status' => 'all',
            'selectedApplicationRoundType' => 'all',
            'selectedApplicationRoundUuidList' => '[]',
            'selectedApplicationRemoveTests' => 0
        ];

        $filterData = [];

        foreach ($filterKeys as $key => $default) {
            if ($request->has($key)) {
                $value = $request->input($key, $default);

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

    public function getApplications(Request $request)
    {
        $filterData = $this->setFilters($request);

        $status = $filterData['status'];
        $selectedApplicationRoundType = $filterData['selectedApplicationRoundType'];

        $selectedApplicationRoundUuidList = $filterData['selectedApplicationRoundUuidList'];
        $selectedApplicationRemoveTests = $filterData['selectedApplicationRemoveTests'];
        $selectedSearchProjects = $filterData['selectedSearchProjects'];


        $listOfApplicationIdsToExclude = [];
        if ($selectedApplicationRemoveTests) {
            $listOfTestRounds = Round::where('name', 'like', '%test%')->pluck('id');
            $listOfApplicationIdsToExclude = RoundApplication::whereIn('round_id', $listOfTestRounds)->pluck('id');
        }

        $listOfApplicationIdsToInclude = [];
        if ($selectedSearchProjects && Str::length($selectedSearchProjects) > 0) {
            $listOfApplicationIdsToInclude = RoundApplication::whereHas('project', function ($query) use ($selectedSearchProjects) {
                $query->where('title', 'like', '%' . $selectedSearchProjects . '%');
            })->pluck('id');
        }


        $applications = RoundApplication::with([
            'round' => function ($query) {
                $query->select('id', 'uuid', 'name', 'round_start_time', 'round_end_time', 'round_addr', 'chain_id');
            },
            'round.chain' => function ($query) {
                $query->select('id', 'uuid', 'chain_id');
            },
            'round.evaluationQuestions' => function ($query) {
                $query->select('id', 'uuid', 'round_id', 'questions');
            },
            'project' => function ($query) use ($selectedSearchProjects) {
                if ($selectedSearchProjects && Str::length($selectedSearchProjects) > 0) {
                    $query->where('title', 'like', '%' . $selectedSearchProjects . '%');
                }
                $query->select('id', 'uuid', 'slug', 'id_addr', 'title', 'created_at', 'updated_at');
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
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', strtolower($status));
            })
            ->when(count($listOfApplicationIdsToInclude) > 0, function ($query) use ($listOfApplicationIdsToInclude) {
                $query->whereIn('id', $listOfApplicationIdsToInclude);
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
            })
            ->whereNotIn('id', $listOfApplicationIdsToExclude)
            ->orderBy('id', 'desc')
            ->select('id', 'uuid', 'application_id', 'project_addr', 'round_id', 'status', 'created_at', 'updated_at')
            ->paginate(10);

        $averageGPTEvaluationTime = intval(RoundApplicationPromptResult::where('prompt_type', 'chatgpt')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as average_time'))
            ->first()
            ->average_time);
        $averageGPTEvaluationTime = min($averageGPTEvaluationTime, 300);

        $data = [
            'applications' => $applications,
            'status' => $status,
            'selectedApplicationRoundType' => $selectedApplicationRoundType,
            'selectedApplicationRoundUuidList' => $selectedApplicationRoundUuidList,
            'selectedApplicationRemoveTests' => $selectedApplicationRemoveTests,
            'selectedSearchProjects' => $selectedSearchProjects,
            'averageGPTEvaluationTime' => $averageGPTEvaluationTime,
        ];
        return $data;
    }

    public function index(Request $request)
    {
        $data = $this->getApplications($request);

        if ($request->wantsJson()) {
            return response()->json([
                'indexData' => env('INDEXER_URL'),
                'applications' => $data['applications'],
                'selectedApplicationStatus' => $data['status'],
                'selectedApplicationRoundType' => $data['selectedApplicationRoundType'],
                'selectedApplicationRoundUuidList' => $data['selectedApplicationRoundUuidList'],
                'selectedApplicationRemoveTests' => $data['selectedApplicationRemoveTests'],
                'selectedSearchProjects' => $data['selectedSearchProjects'],

            ]);
        } else {
            return Inertia::render('Application/Index', [
                'indexData' => env('INDEXER_URL'),
                'applications' => $data['applications'],
                'selectedApplicationStatus' => $data['status'],
                'selectedApplicationRoundType' => $data['selectedApplicationRoundType'],
                'selectedApplicationRoundUuidList' => $data['selectedApplicationRoundUuidList'],
                'selectedApplicationRemoveTests' => $data['selectedApplicationRemoveTests'],
                'selectedSearchProjects' => $data['selectedSearchProjects'],
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

        $returnedFormat = 'Your response should only contain an array of comma separated objects for the evaluation criteria and returned in json format with each score being between 0 and 100:' . PHP_EOL . PHP_EOL . '[{
            "score": 15,
            "reason": "A specific reason for the score",
            "criteria": "A specific bit of evaluation criteria"
        }]';


        $search = [];
        $replace = [];

        $search[] = '{{ application.answers }}';
        $replace[] = RoundApplicationController::getApplicationAnswers($application);

        $search[] = '{{ project.details }}';
        $replace[] = RoundApplicationController::getProjectDetails($application);

        $search[] = '{{ project.historic_applications }}';
        $replace[] = RoundApplicationController::getProjectHistory($application);

        $search[] = '{{ round.eligibility.description }}';
        $replace[] = $round->metadata['eligibility']['description'];

        $search[] = '{{ round.name }}';
        $replace[] = $round->metadata['name'];

        $search[] = '{{ round.eligibility.requirements }}';
        $requirements = '';
        foreach ($round->metadata['eligibility']['requirements'] as $key => $requirement) {
            $requirements .= ($key + 1) . ' - ' . $requirement['requirement'] . PHP_EOL;
        }
        $replace[] = $requirements;

        $data = [
            'system_prompt' => str_replace($search, $replace, $prompt->system_prompt) . PHP_EOL . PHP_EOL . $returnedFormat,
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
                if (!$answer['hidden'] && isset($answer['answer'])) {
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

        return response()->json($historicApplicationsArray);
    }
}
