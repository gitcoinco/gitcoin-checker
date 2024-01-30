<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GithubController extends Controller
{
    function checkGitHubActivity($identifier, $isProject = false)
    {
        $baseUrl = 'https://api.github.com';
        $threeMonthsAgo = strtotime('-3 months');
        $headers = [
            'User-Agent: PHP Script',
            'Authorization: token ' . env('GITHUB_TOKEN')
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $return = [];

        if ($isProject) {

            $specificRepo = null;
            if (stristr($identifier, '/')) {
                $identifierParts = explode('/', $identifier);
                $specificRepo = $identifierParts[1];
                $identifier = $identifierParts[0];
            }

            if ($specificRepo) {
                if (!array_key_exists($specificRepo, $return)) {
                    $return[$specificRepo] = [
                        'recent_activity' => [
                            'title' => 'Number of commits in the past 3 months',
                            'count' => 0,
                        ],
                        'pull_request_data' => $this->fetchPullRequestsData($baseUrl, $identifier, $specificRepo, $ch),
                        'issues_data' => $this->fetchIssuesData($baseUrl, $identifier, $specificRepo, $ch),
                    ];
                }

                curl_setopt($ch, CURLOPT_URL, "$baseUrl/repos/$identifier/$specificRepo/commits");
                $commitsResponse = curl_exec($ch);

                if ($commitsResponse) {
                    $commits = json_decode($commitsResponse, true);
                    foreach ($commits as $commit) {
                        if (isset($commit['commit']['committer']['date']) && strtotime($commit['commit']['committer']['date']) > $threeMonthsAgo) {
                            $title = 'On Github.com, project ' . $identifier . ', repository ' . $specificRepo . ' has recent activity (within the last 3 months)';
                            $return[$specificRepo]['recent_activity']['count']++;
                        }
                    }
                }
            } else {


                // Check activity for all repositories in a project (organization or user)
                $url = "$baseUrl/users/$identifier/repos";
                curl_setopt($ch, CURLOPT_URL, $url);
                $response = curl_exec($ch);

                if ($response) {
                    $repos = json_decode($response, true);

                    foreach ($repos as $repo) {
                        $repoName = $repo['name'];

                        if (!array_key_exists($repoName, $return)) {
                            $return[$repoName] = [
                                'recent_activity' => [
                                    'title' => 'Number of commits in the past 3 months',
                                    'count' => 0,
                                ],
                                'pull_request_data' => $this->fetchPullRequestsData($baseUrl, $identifier, $repoName, $ch),
                                'issues_data' => $this->fetchIssuesData($baseUrl, $identifier, $repoName, $ch),
                            ];
                        }


                        curl_setopt($ch, CURLOPT_URL, "$baseUrl/repos/$identifier/$repoName/commits");
                        $commitsResponse = curl_exec($ch);

                        if ($commitsResponse) {
                            $commits = json_decode($commitsResponse, true);
                            foreach ($commits as $commit) {
                                if (isset($commit['commit']['committer']['date']) && strtotime($commit['commit']['committer']['date']) > $threeMonthsAgo) {
                                    $title = 'On Github.com, project ' . $identifier . ', repository ' . $repoName . ' has recent activity (within the last 3 months)';
                                    $return[$repoName]['recent_activity']['count']++;
                                }
                            }
                        }
                    }
                }
            }
        } else {

            if (!array_key_exists($identifier, $return)) {
                $return[$identifier] = [
                    'recent_activity' => [
                        'title' => 'Number of commits in the past 3 months',
                        'count' => 0,
                    ],
                    'pull_request_data' => $this->fetchPullRequestsData($baseUrl, $identifier, $identifier, $ch),
                    'issues_data' => $this->fetchIssuesData($baseUrl, $identifier, $identifier, $ch),
                ];
            }



            // Check activity for a single user
            $url = "$baseUrl/users/$identifier/events/public";
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);

            if ($response) {
                $events = json_decode($response, true);
                foreach ($events as $event) {
                    if (isset($event['created_at']) && strtotime($event['created_at']) > $threeMonthsAgo) {
                        $title = 'On Github.com, user ' . $identifier . ' has recent activity (within the last 3 months)';
                        $return[$identifier]['recent_activity']['count']++;
                    }
                }
            }
        }

        curl_close($ch);

        return $return;
    }


    private function fetchPullRequestsData($baseUrl, $identifier, $repoName, $ch)
    {
        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
        $cacheKey = "pull_requests_data_{$identifier}_{$repoName}";
        $cachedData = Cache::get($cacheKey);

        $data = [];

        if ($cachedData) {
            $data = $cachedData;
        } else {
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/repos/$identifier/$repoName/pulls?state=all&since=$threeMonthsAgo");
            $response = curl_exec($ch);
            $data = json_decode($response, true);
            Cache::put($cacheKey, $data, 86400); // Cache for 1 day
        }

        $return = [];

        $return['title'] = 'Number of pull requests in the past 3 months';
        $return['open'] = 0;
        $return['closed'] = 0;
        $return['merged'] = 0;

        if (is_array($data)) {
            foreach ($data as $pullRequest) {
                if (!isset($pullRequest['state'])) {
                    continue;
                }
                if ($pullRequest['state'] == 'open') {
                    $return['open']++;
                } else if ($pullRequest['state'] == 'closed') {
                    $return['closed']++;
                } else if ($pullRequest['state'] == 'merged') {
                    $return['merged']++;
                }
            }
        }

        return $return;
    }

    private function fetchIssuesData($baseUrl, $identifier, $repoName, $ch)
    {
        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
        $cacheKey = "issues_data_{$identifier}_{$repoName}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            $data = $cachedData;
        } else {
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/repos/$identifier/$repoName/issues?state=all&since=$threeMonthsAgo");
            $response = curl_exec($ch);
            $data = json_decode($response, true);
            Cache::put($cacheKey, $data, 86400); // Cache for 1 day
        }

        $return = [];

        $return['title'] = 'Number of issues in the past 3 months';
        $return['open'] = 0;
        $return['closed'] = 0;

        if (is_array($data)) {
            foreach ($data as $issue) {
                if (!isset($issue['state'])) {
                    continue;
                }
                if ($issue['state'] == 'open') {
                    $return['open']++;
                } else if ($issue['state'] == 'closed') {
                    $return['closed']++;
                }
            }
        }

        return $return;
    }
}
