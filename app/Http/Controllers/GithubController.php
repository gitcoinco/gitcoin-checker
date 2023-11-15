<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            // Check activity for all repositories in a project (organization or user)
            $url = "$baseUrl/users/$identifier/repos";
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);

            if ($response) {
                $repos = json_decode($response, true);
                foreach ($repos as $repo) {
                    $repoName = $repo['name'];
                    curl_setopt($ch, CURLOPT_URL, "$baseUrl/repos/$identifier/$repoName/commits");
                    $commitsResponse = curl_exec($ch);

                    if ($commitsResponse) {
                        $commits = json_decode($commitsResponse, true);
                        foreach ($commits as $commit) {
                            if (isset($commit['commit']['committer']['date']) && strtotime($commit['commit']['committer']['date']) > $threeMonthsAgo) {
                                curl_close($ch);
                                $title = 'On Github.com, project ' . $identifier . ', repository ' . $repoName . ' has recent activity (within the last 3 months)';
                                if (!in_array($title, $return)) {
                                    $return[] = $title;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Check activity for a single user
            $url = "$baseUrl/users/$identifier/events/public";
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);

            if ($response) {
                $events = json_decode($response, true);
                foreach ($events as $event) {
                    if (isset($event['created_at']) && strtotime($event['created_at']) > $threeMonthsAgo) {
                        curl_close($ch);
                        $title = 'On Github.com, user ' . $identifier . ' has recent activity (within the last 3 months)';
                        if (!in_array($title, $return)) {
                            $return[] = $title;
                        }
                    }
                }
            }
        }

        curl_close($ch);
        if (count($return) > 0) {
            return $return;
        } else {
            if ($isProject) {
                $title = 'On Github.com, project ' . $identifier . ' has no recent activity (within the last 3 months)';
                $return[] = $title;
            } else {
                $title = 'On Github.com, user ' . $identifier . ' has no recent activity (within the last 3 months)';
                $return[] = $title;
            }
            return $return;
        }
    }
}
