<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use App\Models\Project;
use App\Models\ProjectDonation;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationPromptResult;
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

        // has of request inputs
        $requestHash = md5(json_encode($request->all()));

        $cacheName = 'RoundController->show(' . $requestHash . ',' . $round->uuid . ')';

        $applications = Cache::remember($cacheName . '-applications', 60, function () use ($round) {
            return $round->applications()->with([
                'round' => function ($query) {
                    $query->select('id', 'uuid', 'name', 'applications_start_time', 'applications_end_time', 'round_addr', 'chain_id');
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
                ->select('id', 'uuid', 'application_id', 'project_addr', 'round_id', 'status', 'created_at', 'updated_at')
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

        if ($request->wantsJson()) {
            return response()->json([
                'round' => $round,
                'applications' => $applications,
                'averageGPTEvaluationTime' => $averageGPTEvaluationTime,
            ]);
        } else {
            return Inertia::render('Round/Show', [
                'round' => $round,
                'indexData' => env('GRAPHQL_ENDPOINT'),
                'applications' => $applications,
                'averageGPTEvaluationTime' => $averageGPTEvaluationTime,
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
            $rounds = Round::search($search)->paginate();
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

        $projectAddr = $round->applications()->where('status', 'APPROVED')->pluck('project_addr')->toArray();

        $totalRoundDonatators = ProjectDonation::where('round_id', $round->id)->count();
        $totalRoundDonors = ProjectDonation::where('round_id', $round->id)->distinct('donor_address')->count('donor_address');

        $matchingCap = 0;

        $metadata = json_decode($round->round_metadata, true);

        if (isset($metadata['quadraticFundingConfig']['matchingCapAmount'])) {
            $matchingCap = ($metadata['quadraticFundingConfig']['matchingCapAmount'] / 100) * $metadata['quadraticFundingConfig']['matchingFundsAvailable'];
        }

        $projects = RoundApplication::where('round_id', $round->id)
            ->join('projects', 'round_applications.project_addr', '=', 'projects.id_addr')
            ->selectRaw('projects.title, projects.slug, projects.logoImg, round_applications.project_addr, sum(round_applications.donor_amount_usd + round_applications.match_amount_usd) as total_amount')
            ->groupBy('projects.title', 'round_applications.project_addr')
            ->orderBy('total_amount', 'desc')
            ->paginate(100);

        $totalProjectsReachingMatchingCap = 0;
        if ($matchingCap > 0) {

            foreach ($projects as $key => $project) {
                if ($project->total_amount >= $matchingCap) {
                    $totalProjectsReachingMatchingCap++;
                }
            }
        }

        $roundToken = AddressService::getTokenFromAddress($round->match_token_address);

        return view('public.round.show', [
            'round' => $round,
            'projects' => $projects,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
            'totalRoundDonatators' => $totalRoundDonatators,
            'totalRoundDonors' => $totalRoundDonors,
            'totalProjectsReachingMatchingCap' => $totalProjectsReachingMatchingCap,
            'roundToken' => $roundToken,
            'matchingCap' => $matchingCap,
        ]);
    }
}
