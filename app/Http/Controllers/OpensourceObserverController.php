<?php

namespace App\Http\Controllers;

use App\Services\GraphQLService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OpensourceObserverController extends Controller
{
    public $graphQLService;

    public function __construct()
    {
        $this->graphQLService = new GraphQLService(env('OPENSOURCE_OBSERVER_URL'), [], env('OPENSOURCE_OBSERVER_TOKEN'));
    }


    public function getProjectStatistics($slug)
    {
        if (!$slug || Str::length($slug) <= 0) {
            echo "OpensourceObserverController: Project slug not provided\n";
            return;
        }

        $projectData = $this->getProjectDetails($slug);

        $query = <<<'GRAPHQL'
        query MyQuery($projectId: String!) {
            github_metrics_by_project(where: {project_id: {_eq: $projectId}}) {
                avg_active_devs_6_months
                avg_fulltime_devs_6_months
                commits_6_months
                contributors
                contributors_6_months
                first_commit_date
                forks
                issues_closed_6_months
                issues_opened_6_months
                last_commit_date
                new_contributors_6_months
                project_id
                project_name
                pull_requests_merged_6_months
                pull_requests_opened_6_months
                repos
                stars
              }
        }
        GRAPHQL;

        if (!isset($projectData['project_id'])) {
            echo "OpensourceObserverController: Project not found for slug" . $slug . "\n";
            return;
        }

        $variables = [
            'projectId' => $projectData['project_id'],
        ];

        // Call the service with variables set to null if that's what the server expects
        $result = $this->graphQLService->query($query, $variables, "MyQuery");
    }


    public function getProjectDetails($slug)
    {
        $query = <<<'GRAPHQL'
        query GetProjectStatistics($projectSlug: String!) {
          projects(where: {project_slug: {_eq: $projectSlug}}) {
            project_id
            project_name
            project_slug
            user_namespace
          }
        }
        GRAPHQL;

        $variables = [
            'projectSlug' => $slug,
        ];

        // Call the service with variables set to null if that's what the server expects
        $result = $this->graphQLService->query($query, $variables, "GetProjectStatistics");
        $projectData = null;
        if (isset($result['data']['projects'][0])) {
            $projectData = $result['data']['projects'][0];
        }

        return $projectData;
    }
}
