<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDonation;
use App\Models\RoundApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use League\CommonMark\CommonMarkConverter;

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
        $projects = Project::where('title', 'like', '%' . $search . '%')->orderBy('id', 'desc')->paginate();
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

    public function indexPublic($search = null)
    {
        $cacheName = 'ProjectController()->indexPublic()';
        //        $projects = Project::orderBy('id', 'desc')->paginate();

        $totalDonorAmountUSD = Cache::remember($cacheName . '->totalDonorAmountUSD', 60, function () {
            return RoundApplication::sum('donor_amount_usd');
        });
        $totalMatchAmountUSD = Cache::remember($cacheName . '->totalMatchAmountUSD', 60, function () {
            return RoundApplication::sum('match_amount_usd');
        });
        $totalUniqueDonors = Cache::remember($cacheName . '->totalUniqueDonors', 60, function () {
            return ProjectDonation::distinct('voter_addr')->count('voter_addr');
        });

        // Let's put one project in the spotlight.  Look for projects that have received over $500 of donor and match contributions
        $spotlightProject = Cache::remember($cacheName . '->spotlightProject', 5, function () {
            $application = RoundApplication::where('donor_amount_usd', '>', 500)->where('match_amount_usd', '>', 500)->inRandomOrder()->first();
            return $application->project()->first();
        });

        $spotlightProjectTotalDonorAmountUSD = Cache::remember($cacheName . '->spotlightProjectTotalDonorAmountUSD', 5, function () use ($spotlightProject) {
            return $spotlightProject->applications()->sum('donor_amount_usd');
        });

        $spotlightProjectTotalMatchAmountUSD = Cache::remember($cacheName . '->spotlightProjectTotalMatchAmountUSD', 5, function () use ($spotlightProject) {
            return $spotlightProject->applications()->sum('match_amount_usd');
        });

        $spotlightProjectUniqueDonors = Cache::remember($cacheName . '->spotlightProjectUniqueDonors1', 5, function () use ($spotlightProject) {
            return $spotlightProject->projectDonations()->distinct('voter_addr')->count('voter_addr');
        });


        return view('public.project.index', [
            //            'projects' => $projects,
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
        $projects = Cache::remember('ProjectController::sitemapPublic', 60, function () {
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

            $projects = Project::whereNotIn('id', $listOfProjectsIdsToExclude)->orderBy('id', 'desc')->count();

            return Project::whereNotIn('id', $listOfProjectsIdsToExclude)->orderBy('id', 'desc')->get();
        });


        return view('public.project.sitemap', [
            'projects' => $projects,
        ]);
    }

    public function showPublic(Project $project)
    {
        $applications = $project->applications()->orderBy('id', 'desc')->with('round')->paginate();

        $converter = new CommonMarkConverter();
        $descriptionHTML = null;

        if ($project->description) {
            $descriptionHTML = $converter->convertToHTML($project->description)->getContent();
        }

        $totalProjectDonorAmount = 0;
        $totalProjectDonorContributionsCount = 0;
        $totalProjectMatchAmount = 0;

        foreach ($applications as $application) {
            $totalProjectDonorAmount += $application->donor_amount_usd;
            $totalProjectDonorContributionsCount += $application->donor_contributions_count;
            $totalProjectMatchAmount += $application->match_amount_usd;
        }


        $totalDonationsReceived = $project->projectDonations()->sum('amount_usd');

        return view('public.project.show', [
            'project' => $project,
            'applications' => $applications,
            'descriptionHTML' => $descriptionHTML,
            'totalProjectDonorAmount' => $totalProjectDonorAmount,
            'totalProjectDonorContributionsCount' => $totalProjectDonorContributionsCount,
            'totalProjectMatchAmount' => $totalProjectMatchAmount,
        ]);
    }
}
