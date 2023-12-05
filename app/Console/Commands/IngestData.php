<?php

namespace App\Console\Commands;

use App\Http\Controllers\RoundController;
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
use \PsychoB\Ethereum\AddressValidator;
use Illuminate\Support\Str;
use App\Services\AddressService;
use App\Services\DateService;
use App\Services\HashService;
use Web3\Web3;
use Web3\Contract;



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
        $this->indexerUrl = env('INDEXER_URL', 'https://indexer-production.fly.dev/data/');
        $this->blockTimeService = $blockTimeService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DirectoryParser $directoryParser)
    {
        // $rounds = Round::all();
        // foreach ($rounds as $round) {
        //     $this->updateMatchFunding($round);
        // }

        // die('done');

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
    }

    // Split the long running tasks into a separate function so we can run them in the background
    private function longRunningTasks()
    {
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



    // private function updateMatchFunding(Round $round)
    // {
    //     dd(AddressService::getContractMatchAmount('0x8de918F0163b2021839A8D84954dD7E8e151326D'));

    //     dd(AddressService::getContractABI($round->round_addr));
    // }

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
                            'amount_usd' => $donation['amountUSD'],
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
        $roundsData = Cache::remember($this->cacheName . "-rounds_data_2{$chain->chain_id}", now()->addMinutes(10), function () use ($chain) {
            $url = "{$this->indexerUrl}/{$chain->chain_id}/rounds.json";
            $response = Http::timeout($this->httpTimeout)->get($url);

            if ($response->status() === 404) {
                $this->error("404 Not Found for URL: $url");
                return;
            }

            return json_decode($response->body(), true);
        });

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
                        'amount_usd' => $roundData['amountUSD'],
                        'votes' => $roundData['votes'],
                        'token' => $roundData['token'],
                        'match_amount' => $roundData['matchAmount'],
                        'match_amount_usd' => $roundData['matchAmountUSD'],
                        'unique_contributors' => $roundData['uniqueContributors'],
                        'applications_start_time' => DateService::dateTimeConverter($roundData['applicationsStartTime']),
                        'applications_end_time' => DateService::dateTimeConverter($roundData['applicationsEndTime']),
                        'round_start_time' => DateService::dateTimeConverter($roundData['roundStartTime']),
                        'round_end_time' => DateService::dateTimeConverter($roundData['roundEndTime']),
                        'created_at_block' => $roundData['createdAtBlock'],
                        'updated_at_block' => $roundData['updatedAtBlock'],
                        'metadata' => $roundData['metadata'],
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

                if (!$round->evaluationQuestions && isset($round->metadata['eligibility']['requirements'])) {

                    $questionsMeta = $round->metadata['eligibility']['requirements'];
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



                if (isset($roundData['metadata']['name'])) {
                    $round->name = $roundData['metadata']['name'];
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

            Cache::put($cacheName, $hash, now()->addMonths(12));
        }
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
