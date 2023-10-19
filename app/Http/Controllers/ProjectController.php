<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
            'userScores',
            'project',
            'userScores.user',
            'latestPrompt' => function ($query) {
                $query->orderBy('id', 'desc')->limit(1);
            },
            'results' => function ($query) {
                $query->orderBy('id', 'desc');
            }
        ])->paginate();




        return Inertia::render('Project/Show', [
            'project' => $project,
            'applications' => $applications
        ]);
    }

    public function indexPublic($search = null)
    {
        $projects = Project::orderBy('id', 'desc')->paginate();

        return view('public.project.index', [
            'projects' => $projects,
            'canLogin' => true,
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

        $totalDonationsReceived = $project->donations()->sum('amount_usd');

        return view('public.project.show', [
            'project' => $project,
            'applications' => $applications,
            'descriptionHTML' => $descriptionHTML,
            'totalDonationsReceived' => $totalDonationsReceived,
        ]);
    }
}
