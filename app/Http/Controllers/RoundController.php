<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use App\Models\Project;
use App\Models\ProjectDonation;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationPromptResult;
use App\Models\RoundRole;
use App\Services\AddressService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoundController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function exportReviewCSV(Round $round)
    {
        $this->authorize('update', $round);

        // Find the headers
        $headers = [];
        $headers[] = 'Date';
        $headers[] = 'Score';
        $headers[] = 'Project';
        $headers[] = 'Reviewer';

        $evaluationQuestions = $round->evaluationQuestions;
        $questions = json_decode($evaluationQuestions->questions, true);

        foreach ($questions as $question) {
            $text = $question['text'];
            $words = explode(' ', $text);
            $words = array_slice($words, 0, 7); // Limit to first 10 words
            $text = implode(' ', $words);
            $headers[] = $text;
        }
        $headers[] = 'Notes';
        $headers[] = 'Project Address';

        $rows = [];

        $applications = $round->applications()->get();
        foreach ($applications as $application) {
            $evaluationAnswers = $application->evaluationAnswers()->get();

            foreach ($evaluationAnswers as $evaluationAnswer) {
                $row = [];

                $row[] = $evaluationAnswer->created_at->format('d M Y H:i');
                $row[] = $evaluationAnswer->score;
                $row[] = $application->project->title;
                $row[] = $evaluationAnswer->user->name;

                $answers = json_decode($evaluationAnswer->answers, true);
                foreach ($answers as $answer) {
                    $row[] = $answer;
                }
                $row[] = $evaluationAnswer->notes;
                $row[] = $application->project->id_addr;

                $rows[] = $row;
            }
        }


        $date = date('Y-m-d_H-i-s');
        $filename = 'round-' . $round->id . '-review_' . $date . '.csv';

        $handle = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        exit();
    }




    public function settingsUpdate(Round $round)
    {
        $this->authorize('update', $round);

        $round->load('chain');


        // validate application_result_availability_publicly
        $request = request();
        $request->validate([
            'application_result_availability_publicly' => 'required|in:public,private,processed'
        ]);

        $round->application_result_availability_publicly = $request->application_result_availability_publicly;
        $round->save();

        $this->notificationService->success('Settings updated');


        return Inertia::render('Round/Settings', [
            'round' => $round
        ]);
    }


    public function settings(Round $round)
    {
        $this->authorize('view', $round);
        $round->load('chain');
        return Inertia::render('Round/Settings', [
            'round' => $round
        ]);
    }

    public function getRoundData($showTestRounds, $roundIds = null)
    {
        $user = auth()->user();

        if ($user->is_admin) {
            $rounds = Round::orderBy('id', 'desc');
        } else if ($user->is_round_operator) {
            $roundsForThisOperator = $user->roundRoles()->pluck('round_id')->toArray();

            if ($roundIds) {
                $rounds = Round::whereIn('id', $roundIds)->orderByRaw("FIELD(id, " . implode(',', $roundIds) . ")");
            } else {
                $rounds = Round::whereIn('id', $roundsForThisOperator)->orderBy('created_at', 'desc');
            }
        }

        $rounds = $rounds->orderBy('last_application_at', 'desc')
            ->with(['chain', 'gptRoundEligibilityScores'])
            ->withCount('projects')
            ->withCount(['applications as pending_applications_count' => function ($query) {
                $query->where('status', 'PENDING');
            }])
            ->withAvg(['applications as applications_approved' => function ($query) {
                $query->where('status', 'APPROVED');
            }], 'score')
            ->withCount(['applications as approved_applications_count' => function ($query) {
                $query->where('status', 'APPROVED');
            }])
            ->withAvg(['applications as applications_rejected' => function ($query) {
                $query->where('status', 'REJECTED');
            }], 'score')
            ->withCount(['applications as rejected_applications_count' => function ($query) {
                $query->where('status', 'REJECTED');
            }])
            ->withAvg(['applications as applications_pending' => function ($query) {
                $query->where('status', 'PENDING');
            }], 'score')
            ->when(!$showTestRounds, function ($query) {
                $query->where('name', 'not like', '%test%');
            });


        return $rounds;
    }


    public function index($search = null)
    {
        $showTestRounds = filter_var(request('showTestRounds', false), FILTER_VALIDATE_BOOLEAN);

        $rounds = $this->getRoundData($showTestRounds)->paginate();

        return Inertia::render('Round/Index', [
            'rounds' => $rounds
        ]);
    }


    public function search($search = null)
    {
        $showTestRounds = filter_var(request('showTestRounds', false), FILTER_VALIDATE_BOOLEAN);

        $rounds = Round::where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('round_addr', 'like', '%' . $search . '%');
        })->orderBy('flagged_at', 'desc')->orderBy('donations_start_time', 'desc')->with(['chain', 'gptRoundEligibilityScores'])->withCount('projects')->withCount(['applications as pending_applications_count' => function ($query) {
            $query->where('status', 'PENDING');
        }])
            ->withAvg(['applications as applications_approved' => function ($query) {
                $query->where('status', 'APPROVED');
            }], 'score')
            ->withCount(['applications as approved_applications_count' => function ($query) {
                $query->where('status', 'APPROVED');
            }])
            ->withAvg(['applications as applications_rejected' => function ($query) {
                $query->where('status', 'REJECTED');
            }], 'score')
            ->withCount(['applications as rejected_applications_count' => function ($query) {
                $query->where('status', 'REJECTED');
            }])
            ->withAvg(['applications as applications_pending' => function ($query) {
                $query->where('status', 'PENDING');
            }], 'score')
            ->paginate();


        return $rounds;
    }

    public function show(Request $request, Round $round)
    {

        $this->authorize('view', $round);

        $user = auth()->user();

        // has of request inputs
        $requestHash = md5(json_encode($request->all()));

        $cacheName = 'RoundController->show1(' . $requestHash . ',' . $round->uuid . ')';

        $applications = Cache::remember($cacheName . '-applications', 60, function () use ($round) {
            return $round->applications()->with([
                'round' => function ($query) {
                    $query->select('id', 'uuid', 'name', 'applications_start_time', 'applications_end_time', 'round_addr', 'chain_id');
                },
                'round.evaluationQuestions' => function ($query) {
                    $query->select('id', 'uuid', 'round_id', 'questions');
                },
                'project' => function ($query) {
                    $query->select('id', 'uuid', 'slug', 'id_addr', 'title', 'description', 'website', 'logoImg', 'bannerImg', 'projectGithub', 'userGithub', 'projectTwitter', 'created_at', 'updated_at');
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
                ->select('id', 'uuid', 'application_id', 'project_addr', 'round_id', 'metadata', 'status', 'created_at', 'updated_at')
                ->whereHas('project')
                ->when(request('status', 'all') !== 'all', function ($query) {
                    $query->where('status', strtolower(request('status')));
                })
                ->orderBy('created_at', 'desc')
                ->paginate(100);
        });

        $averageGPTEvaluationTime = Cache::remember($cacheName . '-gpt', 60, function () {
            return intval(RoundApplicationPromptResult::where('prompt_type', 'chatgpt')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as average_time'))
                ->first()
                ->average_time);
        });
        $averageGPTEvaluationTime = min($averageGPTEvaluationTime, 300);

        $round = Cache::remember($cacheName . '-round', 60, function () use ($round) {
            return $round->load('chain');
        });

        $isRoundManager = $user->isAdmin || RoundRole::where('round_id', $round->id)->where('address', $user->eth_addr)->where('role', 'MANAGER')->exists();

        $stats = ['totalAverageScore' => 0, 'totalNrScores' => 0, 'totalHumanScores' => 0, 'totalAIScores' => 0, 'nrHumanScores' => 0, 'nrAIScores' => 0];

        foreach ($applications as $application) {

            $stats['nrAIScores'] += $application->results()->count();
            $result = $application->results()->first();
            if ($result) {
                $stats['totalAIScores'] += $result->score;
            }

            $stats['nrHumanScores'] += $application->evaluationAnswers()->count();
            $stats['totalHumanScores'] += $application->evaluationAnswers()->sum('score');
        }

        $stats['totalNrScores'] = $stats['nrHumanScores'] + $stats['nrAIScores'];
        if ($stats['totalHumanScores'] + $stats['totalAIScores'] > 0) {
            $stats['totalAverageScore'] = ($stats['totalHumanScores'] + $stats['totalAIScores']) / ($stats['nrHumanScores'] + $stats['nrAIScores']);
        }



        if ($request->wantsJson()) {
            return response()->json([
                'round' => $round,
                'applications' => $applications,
                'averageGPTEvaluationTime' => $averageGPTEvaluationTime,
                'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
                'isRoundManager' => $isRoundManager,
                'stats' => $stats
            ]);
        } else {
            return Inertia::render('Round/Show', [
                'round' => $round,
                'indexData' => env('GRAPHQL_ENDPOINT'),
                'applications' => $applications,
                'averageGPTEvaluationTime' => $averageGPTEvaluationTime,
                'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
                'isRoundManager' => $isRoundManager,
                'stats' => $stats
            ]);
        }
    }



    public function flag($id)
    {
        $this->authorize('update', AccessControl::class);

        $round = Round::findOrFail($id);
        if ($round->flagged_at) {
            $round->flagged_at = null;
        } else {
            $round->flagged_at = Carbon::now();
        }
        $round->save();

        return response()->json($round);
    }

    public function listPublic(Request $request)
    {
        $testIds = Round::where('name', 'like', '%test%')->pluck('id')->toArray();

        $search = $request->query('search');
        if ($search) {
            $rounds = Round::search($search)->paginate(50);
            $rounds->load(['chain']);
            $rounds->loadCount('applications');
        } else {
            $rounds = Round::orderBy('donations_start_time', 'desc')
                ->whereNotIn('id', $testIds)
                ->whereHas('applications')
                ->with(['chain'])
                ->withCount('applications')
                ->paginate();
        }

        $spotlightRound = Round::where('donations_start_time', '<', Carbon::now())->where('donations_end_time', '>', Carbon::now())->whereNotIn('id', $testIds)->inRandomOrder()->where('match_amount_in_usd', '>', 1000)->first();

        return view('public.round.list', [
            'rounds' => $rounds,
            'search' => $search,
            'spotlightRound' => $spotlightRound,
        ]);
    }

    public function showPublic(Round $round)
    {
        $round->load(['chain']);

        $totalRoundDonatators = ProjectDonation::where('round_id', $round->id)->count();
        $totalRoundDonors = ProjectDonation::where('round_id', $round->id)->distinct('donor_address')->count('donor_address');

        $matchingCap = 0;

        $metadata = json_decode($round->round_metadata, true);

        if (isset($metadata['quadraticFundingConfig']['matchingCapAmount'])) {
            $matchingCap = ($metadata['quadraticFundingConfig']['matchingCapAmount'] / 100) * $metadata['quadraticFundingConfig']['matchingFundsAvailable'];
        }

        $search = request('search', '');


        if ($search) {

            $projectIds = Project::search($search)->get()->map(function ($project) {
                return $project->id;
            });

            $projectIdsString = $projectIds->implode(',');

            $roundApplications = $round->applications()
                ->with(['project', 'evaluationAnswers', 'results'])
                ->whereIn('project_id', $projectIds)
                ->orderByRaw("FIELD(project_id, {$projectIdsString})")
                ->paginate();
        } else {


            $roundApplications = $round->applications()
                ->with(['project', 'evaluationAnswers', 'results'])
                ->orderByRaw('donor_amount_usd + match_amount_usd desc')
                ->paginate();
        }

        $totalProjectsReachingMatchingCap = 0;
        if ($matchingCap > 0) {

            foreach ($roundApplications as $key => $roundApplication) {
                if ($roundApplication->total_amount >= $matchingCap) {
                    $totalProjectsReachingMatchingCap++;
                }
            }
        }

        $roundToken = AddressService::getTokenFromAddress($round->match_token_address);

        return view('public.round.show', [
            'round' => $round,
            'roundApplications' => $roundApplications,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
            'totalRoundDonatators' => $totalRoundDonatators,
            'totalRoundDonors' => $totalRoundDonors,
            'totalProjectsReachingMatchingCap' => $totalProjectsReachingMatchingCap,
            'roundToken' => $roundToken,
            'matchingCap' => $matchingCap,
        ]);
    }
}
