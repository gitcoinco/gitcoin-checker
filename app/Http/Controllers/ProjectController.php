<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDonation;
use App\Models\RoundApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use League\CommonMark\CommonMarkConverter;
use Orhanerday\OpenAi\OpenAi;

class ProjectController extends Controller
{
    public function index($search = null)
    {
        $projects = Project::orderBy('id', 'desc')->paginate();

        return Inertia::render('Project/Index', [
            'projects' => $projects
        ]);
    }

    public function search($search = null)
    {
        $projects = Project::search($search)->paginate();
        return $projects;
    }

    public function show(Project $project)
    {
        $applications = $project->applications()->orderBy('id', 'desc')->with([
            'round',
            'project',
            'latestPrompt' => function ($query) {
                $query->orderBy('id', 'desc')->limit(1);
            },
            'results' => function ($query) {
                $query->orderBy('id', 'desc');
            },

        ])
            ->withSum('applicationDonations', 'amount_usd')
            ->paginate();

        $project->loadSum('projectDonations', 'amount_usd');
        return Inertia::render('Project/Show', [
            'project' => $project,
            'applications' => $applications
        ]);
    }

    public function listPublic(Request $request)
    {
        // Get a list of test projects in order to keep them out of the results
        $listOfTestProjectIds = Project::where(function ($query) {
            $query->where('title', 'like', ' test%')->orWhere('title', 'like', '% test %');
        })->pluck('id')->toArray();

        $search = $request->query('search');
        if ($search) {
            $projects = Project::search($search)->paginate();
        } else {
            $projects = Project::whereNotIn('id', $listOfTestProjectIds)->orderBy('id', 'desc')->paginate();
        }

        return view('public.project.list', [
            'projects' => $projects,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
            'search' => $search,
        ]);
    }

    public function randomProjectPublic()
    {
        $cacheTimeout = 2;
        $cacheName = 'ProjectController()->randomProjectPublic()';

        $listOfTestProjectIds = Project::where(function ($query) {
            $query->where('title', 'like', ' test%')->orWhere('title', 'like', '% test %');
        })->pluck('id')->toArray();


        $spotlightProject = Cache::remember($cacheName . '->spotlightProject1', $cacheTimeout, function () {
            $application = RoundApplication::where('donor_amount_usd', '>', 500)->where('match_amount_usd', '>', 500)->inRandomOrder()->first();

            if ($application == null) {
                return null;
            }

            return $application->project()->first();
        });


        $project = Project::whereNotIn('id', $listOfTestProjectIds)->whereNotNull('gpt_summary')->inRandomOrder()->first();
        return response()->json([
            'project' => $spotlightProject,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
        ]);
    }


    public function homePublic($search = null)
    {
        $cacheTimeout = 60 * 60 * 24 * 7;
        $cacheName = 'ProjectController()->homePublic()';

        $totalDonorAmountUSD = Cache::remember($cacheName . '->totalDonorAmountUSD', $cacheTimeout, function () {
            return RoundApplication::sum('donor_amount_usd');
        });
        $totalMatchAmountUSD = Cache::remember($cacheName . '->totalMatchAmountUSD', $cacheTimeout, function () {
            return RoundApplication::sum('match_amount_usd');
        });
        $totalUniqueDonors = Cache::remember($cacheName . '->totalUniqueDonors', $cacheTimeout, function () {
            return ProjectDonation::distinct('donor_address')->count('donor_address');
        });

        // Let's put one project in the spotlight.  Look for projects that have received over $500 of donor and match contributions
        $spotlightProject = Cache::remember($cacheName . '->spotlightProject', 60 * 60, function () {
            $application = RoundApplication::where('donor_amount_usd', '>', 500)->where('match_amount_usd', '>', 500)->inRandomOrder()->first();

            if ($application == null) {
                return null;
            }

            return $application->project()->first();
        });

        $spotlightProjectTotalDonorAmountUSD = 0;
        $spotlightProjectTotalMatchAmountUSD = 0;
        $spotlightProjectUniqueDonors = 0;

        if ($spotlightProject) {
            $spotlightProjectTotalDonorAmountUSD = Cache::remember($cacheName . '->spotlightProjectTotalDonorAmountUSD', $cacheTimeout, function () use ($spotlightProject) {
                return $spotlightProject->applications()->sum('donor_amount_usd');
            });

            $spotlightProjectTotalMatchAmountUSD = Cache::remember($cacheName . '->spotlightProjectTotalMatchAmountUSD', $cacheTimeout, function () use ($spotlightProject) {
                return $spotlightProject->applications()->sum('match_amount_usd');
            });

            $spotlightProjectUniqueDonors = Cache::remember($cacheName . '->spotlightProjectUniqueDonors1', $cacheTimeout, function () use ($spotlightProject) {
                return $spotlightProject->projectDonations()->distinct('donor_address')->count('donor_address');
            });
        }


        return view('public.project.home', [
            'canLogin' => true,
            'totalDonorAmountUSD' => $totalDonorAmountUSD,
            'totalMatchAmountUSD' => $totalMatchAmountUSD,
            'totalUniqueDonors' => $totalUniqueDonors,
            'spotlightProject' => $spotlightProject,
            'spotlightProjectTotalDonorAmountUSD' => $spotlightProjectTotalDonorAmountUSD,
            'spotlightProjectTotalMatchAmountUSD' => $spotlightProjectTotalMatchAmountUSD,
            'spotlightProjectUniqueDonors' => $spotlightProjectUniqueDonors,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
        ]);
    }

    public function sitemapPublic()
    {
        $projectsCount = Project::count();
        $sitemapCount = ceil($projectsCount / 1000);
        return view('public.project.sitemap', [
            'sitemapCount' => $sitemapCount,
        ]);
    }

    public function sitemapIndexPublic($index)
    {
        $projects = Cache::remember('ProjectController::sitemapPublic', 60, function () use ($index) {
            $projects =  Project::orderBy('id', 'desc')->get();

            $listOfProjectOwnersWithTestInTheTitle = [];
            foreach ($projects as $project) {
                if (str_contains(strtolower($project->title), 'test')) {

                    $owners = $project->owners()->get();
                    if (!$owners) {
                        continue;
                    }

                    foreach ($owners as $owner) {
                        if (!isset($listOfProjectOwnersWithTestInTheTitle[$owner->eth_addr])) {
                            $listOfProjectOwnersWithTestInTheTitle[$owner->eth_addr]['count'] = 0;
                            $listOfProjectOwnersWithTestInTheTitle[$owner->eth_addr]['project.id'] = $project->id;
                        }
                        $listOfProjectOwnersWithTestInTheTitle[$owner->eth_addr]['count'] += 1;
                    }
                }
            }

            // Remove any projects from listOfProjectOwnersWithTestInTheTitle that has < 2 projects
            foreach ($listOfProjectOwnersWithTestInTheTitle as $key => $value) {
                if ($value['count'] < 2) {
                    unset($listOfProjectOwnersWithTestInTheTitle[$key]);
                }
            }

            $listOfProjectsIdsToExclude = [];
            foreach ($listOfProjectOwnersWithTestInTheTitle as $key => $value) {
                $listOfProjectsIdsToExclude[] = $value['project.id'];
            }

            return Project::whereNotIn('id', $listOfProjectsIdsToExclude)
                ->orderBy('id', 'asc')
                ->skip($index * 1000)
                ->take(1000)
                ->get(['id', 'slug', 'updated_at']);
        });


        return view('public.project.sitemapIndex', [
            'projects' => $projects,
        ]);
    }

    public function showPublic(Project $project)
    {
        $cacheTimeout = 60 * 60;
        $cacheName = 'ProjectController::showPublic(' . $project->uuid . ')';
        $applications = $project->applications()->orderBy('id', 'desc')->with('round')->paginate();

        $converter = new CommonMarkConverter();
        $descriptionHTML = null;

        if ($project->description) {
            $descriptionHTML = $converter->convertToHTML($project->description)->getContent();
        }

        $totalProjectDonorAmount = Cache::remember($cacheName . '-totalProjectDonorAmount', $cacheTimeout, function () use ($applications) {
            $total = 0;
            foreach ($applications as $application) {
                $total += $application->donor_amount_usd;
            }
            return $total;
        });

        $totalProjectDonorContributionsCount = Cache::remember($cacheName . '-totalProjectDonorContributionsCount', $cacheTimeout, function () use ($applications) {
            $total = 0;
            foreach ($applications as $application) {
                $total += $application->donor_contributions_count;
            }
            return $total;
        });

        $totalProjectMatchAmount = Cache::remember($cacheName . '-totalProjectMatchAmount', $cacheTimeout, function () use ($applications) {
            $total = 0;
            foreach ($applications as $application) {
                $total += $application->match_amount_usd;
            }
            return $total;
        });


        $projectsInterestType = 'donated-to';
        $projectsInterest = Cache::remember($cacheName . '-projectsAlsoDonatedTo', $cacheTimeout, function () use ($project) {
            $donorsVoteAddr = ProjectDonation::where('project_id', $project->id)->distinct('donor_address')->pluck('donor_address')->toArray();

            $donations = ProjectDonation::whereIn('donor_address', $donorsVoteAddr)
                ->where('project_id', '!=', $project->id)
                ->select('project_id', 'amount_usd')
                ->distinct()
                ->orderBy('amount_usd', 'desc')
                ->limit(5)
                ->pluck('project_id')
                ->toArray();

            return Project::whereIn('id', $donations)->get();
        });

        if (count($projectsInterest) == 0) {
            $projectsInterestType = 'random';
            $projectsInterest = Project::whereNotNull('gpt_summary')->inRandomOrder()->limit(5)->get();
        }

        return view('public.project.show', [
            'project' => $project,
            'projectsInterest' => $projectsInterest,
            'projectsInterestType' => $projectsInterestType,
            'applications' => $applications,
            'descriptionHTML' => $descriptionHTML,
            'totalProjectDonorAmount' => $totalProjectDonorAmount,
            'totalProjectDonorContributionsCount' => $totalProjectDonorContributionsCount,
            'totalProjectMatchAmount' => $totalProjectMatchAmount,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
        ]);
    }

    // Update the gpt_summary using the project description if it's big enough
    public function doGPTSummary(Project $project)
    {
        if ($project->gpt_summary) {
            return $project;
        }

        // If the description is short, then just use it
        $wordCount = str_word_count($project->description);
        if ($wordCount <= 30) {
            $project->gpt_summary = $project->description;
            $project->save();
            return $project;
        }

        $open_ai = new OpenAi(env('OPENAI_API_KEY'));

        $messages = [
            [
                "role" => "system",
                "content" => 'Take a project description and shorten it to less than 30 words to create a summary of the project.  Do not include any links or images in the response, and where possible, do not include the project name.',
            ],
            [
                "role" => "user",
                "content" => $project->description
            ],
        ];

        $gptResponse = $open_ai->chat([
            'model' => 'gpt-4-1106-preview',
            'messages' => $messages,
            'temperature' => 1.0,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);

        $gptResponse = json_decode($gptResponse);


        // check for error
        if (isset($gptResponse->error)) {
            throw new \Exception($gptResponse->error->message);
        }

        if (isset($gptResponse->choices[0]->message->content)) {
            $project->gpt_summary = $gptResponse->choices[0]->message->content;
            $project->save();
            return $project;
        }
    }
}
