<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoundPromptController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

use App\Models\Chain;
use App\Models\Project;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Services\BlockTimeService;
use App\Services\DirectoryParser;
use Exception;
use Illuminate\Support\Str;
use App\Services\AddressService;
use App\Services\DateService;
use App\Services\HashService;

use BendeckDavid\GraphqlClient\Facades\GraphQL;

class IngestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingest:data {--longRunning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest data from the specified indexer URL and populate the database';

    protected $cacheName = 'ingest-cache';

    protected $indexerUrl = '';

    protected $blockTimeService;

    protected $httpTimeout = 120000;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(BlockTimeService $blockTimeService)
    {
        parent::__construct();
        $this->indexerUrl = env('INDEXER_URL', 'https://indexer-staging.fly.dev/graphiql');
        $this->blockTimeService = $blockTimeService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DirectoryParser $directoryParser)
    {
        $startTime = microtime(true);

        $longRunning = $this->option('longRunning') ?? false;

        if ($longRunning) {
            $this->info('Long running tasks');
            $this->longRunningTasks();
        } else {
            $this->info('Short running tasks');
            $this->shortRunningTasks($directoryParser);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime);
        $this->info("Execution time of script = " . $executionTime . " sec");

        $this->info('Data ingestion completed successfully.');
        return 0;
    }

    private function shortRunningTasks(DirectoryParser $directoryParser)
    {
        $this->info('Fetching directory list...');

        // Chains are hardcoded for now but should be fetched from a dynamic source
        $chainList = [1, 10, 137, 250, 42161, 424];

        foreach ($chainList as $key => $chainId) {
            $this->info("Processing data for chain ID: {$chainId}...");
            $chain = Chain::firstOrCreate(['chain_id' => $chainId]);

            $this->info("Processing rounds data for chain ID: {$chainId}...");
            $this->updateRounds($chain);

            die('rounds done');

            $rounds = Round::where('chain_id', $chain->id)->get();
            foreach ($rounds as $round) {
                $this->info("Processing project data for chain: {$chainId}, round: {$round->round_addr}.");
                $this->updateProjects($round);
            }
            foreach ($rounds as $round) {
                $this->info("Processing application data for chain: {$chainId}, round: {$round->round_addr}.");
                $this->updateApplications($round);
            }
        }

        $this->info('Fetching application funding data...');
        $applications = RoundApplication::whereNotNull('approved_at')->whereNull('donor_amount_usd')->get();
        foreach ($applications as $application) {
            $this->updateApplicationFunding($application);
        }
    }

    // Split the long running tasks into a separate function so we can run them in the background
    private function longRunningTasks()
    {
        $this->updateProjectSummaries();

        // Loop through all the chains and update project owners
        $chains = Chain::all();
        foreach ($chains as $chain) {
            $this->updateProjectOwnersForChain($chain);

            $rounds = Round::where('chain_id', $chain->id)->get();
            foreach ($rounds as $round) {
                $this->info("Processing donations data for chain ID: {$chain->chain_id}, round ID: {$round->id}...");
                $this->updateDonations($round);
            }
        }
    }

    private function updateProjectSummaries()
    {
        $projectController = new ProjectController();

        $projects = Project::whereNull('gpt_summary')->limit(100)->get();
        foreach ($projects as $project) {
            $this->info("Processing project: {$project->title}");
            $projectController->doGPTSummary($project);
        }
    }

    private function updateApplicationFunding(RoundApplication $application)
    {
        if ($application->donor_amount_usd && $application->match_amount_usd) {
            $this->info("Application id: {$application->id} already has donor_amount_usd set. Skipping...");
            return;
        }
        $this->info("Processing application id: {$application->id}");

        $round = $application->round;
        $chain = $round->chain;

        $application = RoundApplication::where('id', $application->id)->withSum('applicationDonations', 'total_amount_donated_in_usd')->first();


        $nodeAppUrl = env('NODE_APP_URL', 'http://localhost:3000');

        $url = $nodeAppUrl . "/get-match-pool-amount?chainId={$chain->chain_id}&roundId={$round->round_addr}&projectId={$application->project_addr}";


        $response = null;
        $attempts = 0;
        while ($attempts < 5) {
            try {
                $response = Http::get($url);
                if ($response->successful()) {
                    break;
                }
            } catch (\Exception $e) {
                $this->error("Attempt {$attempts} to get data from {$url} failed. Retrying...");
            }
            $attempts++;
        }

        if ($response && $response->successful()) {
            $data = $response->json();
            Cache::put($url, $data, now()->addMinutes(10));
        } else {
            $data = Cache::get($url);
            if (!$data) {
                $this->error("Failed to get data from {$url} after 5 attempts.");
                return;
            }
        }


        $match_usd = null;

        // We can get donor amount from the node app as well if needed, but let's use the contract if it's available
        $donor_usd = $application->application_donations_sum_amount_usd;
        $donor_contributions_count = 0;
        if (isset($data['donorAmountUSD'])) {
            $donor_usd = $data['donorAmountUSD'];
        }
        if (isset($data['matchAmountUSD'])) {
            $match_usd = $data['matchAmountUSD'];
        }
        if (isset($data['donorContributionsCount'])) {
            $donor_contributions_count = $data['donorContributionsCount'];
        }

        $application->donor_amount_usd = $donor_usd;
        $application->donor_contributions_count = $donor_contributions_count;
        $application->match_amount_usd = $match_usd;
        $application->save();

        $this->info("Successfully updated application id: {$application->id}");
    }

    private function updateDonations(Round $round)
    {
        $donationsData = Cache::remember($this->cacheName . "-votes_data{$round->id}", now()->addMinutes(10), function () use ($round) {
            $url = "{$this->indexerUrl}/{$round->chain->chain_id}/rounds/{$round->round_addr}/votes.json";

            $response = Http::timeout($this->httpTimeout)->get($url);
            if ($response->status() === 404) {
                $this->error("404 Not Found for URL: $url");
                return;
            }

            return json_decode($response->body(), true);
        });

        $hash = HashService::hashMultidimensionalArray($donationsData);
        $cacheName = $this->cacheName . "-updateDonations({$round->id})-hash";

        if (Cache::get($cacheName) == $hash) {
            $this->info("Donations data for round {$round->id} has not changed. Skipping...");
            return;
        }

        if ($donationsData && count($donationsData) > 0) {
            foreach ($donationsData as $key => $donation) {

                $projectAddr = AddressService::getAddress($donation['projectId']);
                $project = Project::where('id_addr', $projectAddr)->first();

                // Find the application this belongs to
                $application = RoundApplication::where('round_id', $round->id)
                    ->where('project_addr', $projectAddr)
                    ->where('application_id', $donation['applicationId'])
                    ->first();

                if ($project && $application) {
                    $project->projectDonations()->updateOrCreate(
                        ['transaction_addr' => $donation['id']],
                        [
                            'application_id' => $donation['applicationId'],
                            'internal_application_id' => $application->id,
                            'round_id' => $round->id,
                            'total_amount_donated_in_usd' => $donation['totalAmountDonatedInUsd'],
                            'voter_addr' => AddressService::getAddress($donation['voter']),
                            'grant_addr' => AddressService::getAddress($donation['grantAddress']),
                            'block_number' => $donation['blockNumber'],
                        ]
                    );
                }
            }

            Cache::put($cacheName, $hash, now()->addMonths(12));
        }
    }

    private function updateProjectOwnersForChain(Chain $chain)
    {

        $this->info("Processing project owners for chain ID: {$chain->chain_id}...");

        $indexerUrl = $this->indexerUrl;

        $projectData = Cache::remember($this->cacheName . "-project_owners_data{$chain->chain_id}", now()->addMinutes(10), function () use ($chain) {
            $url = "{$this->indexerUrl}/{$chain->chain_id}/projects.json";
            $response = Http::timeout($this->httpTimeout)->get($url);

            if ($response->status() === 404) {
                $this->error("404 Not Found for URL: $url");
                return;
            }

            return json_decode($response->body(), true);
        });

        $hash = HashService::hashMultidimensionalArray($projectData);
        $cacheName = $this->cacheName . "-updateProjectOwnersForChain({$chain->id})-hash";

        if (Cache::get($cacheName) == $hash) {
            $this->info("Project owners data for chain {$chain->id} has not changed. Skipping...");
            return;
        }


        if ($projectData && count($projectData) > 0) {
            foreach ($projectData as $key => $data) {
                $projectAddress = AddressService::getAddress($data['id']);
                $owners = $data['owners'];
                $project = Project::where('id_addr', $projectAddress)->first();

                if ($project && count($owners)) {
                    // Loop through and add all the owners
                    foreach ($owners as $ownerAddress) {
                        $ownerAddress = AddressService::getAddress($ownerAddress);
                        $project->owners()->updateOrCreate(
                            ['eth_addr' => $ownerAddress, 'project_id' => $project->id],
                        );
                    }
                }
            }

            Cache::put($cacheName, $hash, now()->addMonths(12));
        }
    }



    private function updateRounds($chain)
    {
        $indexerUrl = $this->indexerUrl;

        $roundsData = GraphQL::query('
        rounds(filter: {chainId: {equalTo: ' . $chain->chain_id . '}}) {
            id
            totalAmountDonatedInUsd
            matchAmount
            matchAmountInUsd
            applicationsStartTime
            applicationsEndTime
            donationsStartTime
            donationsEndTime
            createdAtBlock
            updatedAtBlock
            roundMetadata
            matchTokenAddress
            uniqueDonorsCount
            totalDonationsCount
          }
        ')->get();



        $roundsData = $roundsData['rounds'];

        $hash = HashService::hashMultidimensionalArray($roundsData);
        $cacheName = $this->cacheName . "-updateRounds({$chain->id})-hash";

        if (Cache::get($cacheName) == $hash) {
            $this->info("Rounds data for chain {$chain->id} has not changed. Skipping...");
            return;
        }

        if (is_array($roundsData)) {
            foreach ($roundsData as $roundData) {
                $this->info("Processing round ID: {$roundData['id']}...");

                $this->info($roundData['applicationsStartTime']);

                $round = Round::updateOrCreate(
                    ['round_addr' => AddressService::getAddress($roundData['id']), 'chain_id' => $chain->id],
                    [
                        'total_amount_donated_in_usd' => $roundData['totalAmountDonatedInUsd'],
                        'total_donations_count' => $roundData['totalDonationsCount'],
                        'match_token_address' => $roundData['matchTokenAddress'],
                        'match_amount' => $roundData['matchAmount'],
                        'match_amount_in_usd' => $roundData['matchAmountInUsd'],
                        'unique_donors_count' => $roundData['uniqueDonorsCount'],
                        'applications_start_time' => DateService::dateTimeConverter($roundData['applicationsStartTime']),
                        'applications_end_time' => DateService::dateTimeConverter($roundData['applicationsEndTime']),
                        'donations_start_time' => DateService::dateTimeConverter($roundData['donationsStartTime']),
                        'donations_end_time' => DateService::dateTimeConverter($roundData['donationsEndTime']),
                        'created_at_block' => $roundData['createdAtBlock'],
                        'updated_at_block' => $roundData['updatedAtBlock'],
                        'round_metadata' => $roundData['roundMetadata'],
                    ]
                );

                if (!$round->prompt) {
                    // set a default gpt prompt
                    $promptData = RoundPromptController::promptDefaults();
                    $round->prompt()->create([
                        'system_prompt' => $promptData['system_prompt'],
                        'prompt' => $promptData['prompt'],
                    ]);
                }

                if (!$round->evaluationQuestions && isset($round->round_metadata['eligibility']['requirements'])) {

                    $questionsMeta = $round->round_metadata['eligibility']['requirements'];
                    $questions = [];
                    foreach ($questionsMeta as $key => $q) {
                        if (Str::length($q['requirement']) > 0) {
                            $questions[] = [
                                'text' => $q['requirement'],
                                'type' => 'radio',
                                'options' => [
                                    'Yes',
                                    'No',
                                    'Uncertain',
                                ],
                                'weighting' => 0,
                            ];
                        }
                    }

                    if (count($questions) > 0) {
                        // Adjust the scoring
                        $totalQuestions = count($questions);
                        $baseScore = intdiv(100, $totalQuestions);
                        $remainder = 100 % $totalQuestions;

                        foreach ($questions as $key => $q) {
                            $questions[$key]['weighting'] = $baseScore;
                        }

                        // Distribute the remainder to the first few questions
                        for ($i = 0; $i < $remainder; $i++) {
                            $questions[$i]['weighting'] += 1;
                        }

                        // set default evaluation questions
                        $round->evaluationQuestions()->create([
                            'questions' => json_encode($questions),
                        ]);
                    }
                }



                if (isset($roundData['round_metadata']['name'])) {
                    $round->name = $roundData['round_metadata']['name'];
                    $round->save();
                }
            }

            Cache::put($cacheName, $hash, now()->addMonths(12));
        }
    }

    private function updateProjects($round)
    {
        $indexerUrl = $this->indexerUrl;

        $chain = $round->chain;

        $applicationData = Cache::remember($this->cacheName . "-project_data{$chain->id}-{$round->id}", now()->addMinutes(10), function () use ($chain, $round) {
            $url = "{$this->indexerUrl}/{$chain->chain_id}/rounds/{$round->round_addr}/applications.json";
            $response = Http::timeout($this->httpTimeout)->get($url);

            if ($response->status() === 404) {
                $this->error("404 Not Found for URL: $url");
                return;
            }

            return json_decode($response->body(), true);
        });

        $hash = HashService::hashMultidimensionalArray($applicationData);
        $cacheName = $this->cacheName . "-updateProjects({$round->id})-hash";

        if (Cache::get($cacheName) == $hash) {
            $this->info("Projects data for round {$round->id} has not changed. Skipping...");
            return;
        }


        if ($applicationData && count($applicationData) > 0) {

            foreach ($applicationData as $key => $data) {
                if (isset($data['metadata']['application']['project'])) {

                    $projectData = $data['metadata']['application']['project'];

                    // restrict the length of description to 1000 characters
                    $description = null;
                    if (isset($projectData['description'])) {
                        $description = $projectData['description'];
                    }

                    $createdAt = now();
                    if (isset($projectData['createdAt'])) {
                        $createdAt = date('Y-m-d H:i:s', intval($projectData['createdAt'] / 1000));
                    }

                    $project = Project::updateOrCreate(
                        ['id_addr' => AddressService::getAddress($data['projectId'])],
                        [
                            'created_at' => $createdAt,
                            'title' => isset($projectData['title']) ? $projectData['title'] : null,
                            'description' => $description,
                            'website' => isset($projectData['website']) ? $projectData['website'] : null,
                            'userGithub' => isset($projectData['userGithub']) ? $projectData['userGithub'] : null,
                            'projectGithub' => isset($projectData['projectGithub']) ? $projectData['projectGithub'] : null,
                            'projectTwitter' => isset($projectData['projectTwitter']) ? $projectData['projectTwitter'] : null,
                            'metadata' => $projectData,
                            'logoImg' => isset($projectData['logoImg']) ? $projectData['logoImg'] : null,
                            'bannerImg' => isset($projectData['bannerImg']) ? $projectData['bannerImg'] : null,
                        ]
                    );
                }
            }
        }
        Cache::put($cacheName, $hash, now()->addMonths(12));
    }

    private function updateApplications($round)
    {
        $chain = $round->chain;

        $applicationData = Cache::remember($this->cacheName . "-rounds_application_data{$chain->chain_id}_{$round->id}", now()->addMinutes(10), function () use ($round, $chain) {
            $url = "{$this->indexerUrl}/{$chain->chain_id}/rounds/{$round->round_addr}/applications.json";
            $response = Http::timeout($this->httpTimeout)->get($url);

            if ($response->status() === 404) {
                $this->error("404 Not Found for URL: $url");
                return;
            }

            return json_decode($response->body(), true);
        });

        $hash = HashService::hashMultidimensionalArray($applicationData);
        $cacheName = $this->cacheName . "-updateProjects({$round->id})-hash";

        if (Cache::get($cacheName) == $hash) {
            $this->info("Applications data for round {$round->id} has not changed. Skipping...");
            return;
        }

        if ($applicationData && count($applicationData) > 0) {

            foreach ($applicationData as $key => $data) {

                $this->info("Processing application: {$data['projectId']}");

                $createdAt = null;
                // When was this application created?
                if (isset($data['createdAtBlock'])) {
                    $createdAt = $this->blockTimeService->getBlockTime($chain, $data['createdAtBlock']);
                }

                // When was this application approved / pending
                $rejectedAt = null;
                $approvedAt = null;
                if ($data['statusSnapshots']) {
                    foreach ($data['statusSnapshots'] as $key => $value) {
                        if (strtolower($value['status']) == 'rejected') {
                            $rejectedAt = $this->blockTimeService->getBlockTime($chain, $value['statusUpdatedAtBlock']);
                        } else if (strtolower($value['status']) == 'approved') {
                            $approvedAt = $this->blockTimeService->getBlockTime($chain, $value['statusUpdatedAtBlock']);
                        }
                    }
                }

                if (!$createdAt) {
                    throw new Exception("Unable to determine createdAt for application {$data['projectId']}, chain {$chain->chain_id}, block {$data['createdAtBlock']}");
                }

                $roundApplication = RoundApplication::updateOrCreate(
                    ['round_id' => $round->id, 'project_addr' => AddressService::getAddress($data['projectId'])]
                );

                $roundApplication->update([
                    'application_id' => $data['id'],
                    'round_id' => $round->id,
                    'project_addr' => $data['projectId'],
                    'status' => $data['status'],
                    'metadata' => json_encode($data['metadata']),
                    'created_at' => $createdAt ? date('Y-m-d H:i:s', $createdAt) : null,
                    'rejected_at' => $rejectedAt ? date('Y-m-d H:i:s', $rejectedAt) : null,
                    'approved_at' => $approvedAt ? date('Y-m-d H:i:s', $approvedAt) : null,
                ]);

                // Potentially update round.last_application_at
                if ($round->last_application_at == null || $round->last_application_at < $createdAt) {
                    $round->last_application_at = $createdAt ? date('Y-m-d H:i:s', $createdAt) : $round->last_application_at;
                    $round->save();
                }
            }

            Cache::put($cacheName, $hash, now()->addMonths(12));
        }
    }
}
