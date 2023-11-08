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
use kornrunner\Keccak;

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
    protected $description = 'Ingest data from the specified URL and populate the database';

    protected $cacheName = 'ingest-cache';

    protected $indexerUrl = '';

    protected $blockTimeService;

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

        $directories = Cache::remember($this->cacheName . '-directories', now()->addMinutes(10), function () use ($directoryParser) {
            $response = Http::timeout(120)->get($this->indexerUrl);
            $json = $directoryParser->parse($response->body());

            return json_decode($json, true);
        });

        $directories = $directories['directories'];

        if ($directories) {
            foreach ($directories as $directory) {
                $chainId = $directory['name'];  // Assuming 'name' contains the chain ID

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
        } else {
            $this->info("No directories available");
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
                $this->info("Processing donations data for chain ID: {$chain->chain_id}...");
                $this->updateDonations($round);
            }
        }
    }

    private function updateDonations(Round $round)
    {
        $donationsData = Cache::remember($this->cacheName . "-votes_data{$round->id}", now()->addMinutes(10), function () use ($round) {
            $url = "{$this->indexerUrl}/{$round->chain->chain_id}/rounds/{$round->round_addr}/votes.json";

            $response = Http::timeout(120)->get($url);
            return json_decode($response->body(), true);
        });

        if (count($donationsData) > 0) {
            foreach ($donationsData as $key => $donation) {
                $projectId = $this->getAddress($donation['projectId']);
                $project = Project::where('id_addr', $projectId)->first();

                if ($project) {
                    $project->donations()->updateOrCreate(
                        ['transaction_addr' => $donation['id']],
                        [
                            'application_id' => $donation['applicationId'],
                            'round_id' => $round->id,
                            'amount_usd' => $donation['amountUSD'],
                            'voter_addr' => $this->getAddress($donation['voter']),
                            'grant_addr' => $this->getAddress($donation['grantAddress']),
                            'block_number' => $donation['blockNumber'],
                        ]
                    );
                }
            }
        }
    }

    private function updateProjectOwnersForChain(Chain $chain)
    {

        $this->info("Processing project owners for chain ID: {$chain->chain_id}...");

        $indexerUrl = $this->indexerUrl;

        $projectData = Cache::remember($this->cacheName . "-project_owners_data{$chain->chain_id}", now()->addMinutes(10), function () use ($chain) {
            $response = Http::timeout(120)->get("{$this->indexerUrl}/{$chain->chain_id}/projects.json");
            return json_decode($response->body(), true);
        });

        if ($projectData && count($projectData) > 0) {
            foreach ($projectData as $key => $data) {
                $projectAddress = $this->getAddress($data['id']);
                $owners = $data['owners'];
                $project = Project::where('id_addr', $projectAddress)->first();

                if ($project && count($owners)) {
                    // Loop through and add all the owners
                    foreach ($owners as $ownerAddress) {
                        $ownerAddress = $this->getAddress($ownerAddress);
                        $project->owners()->updateOrCreate(
                            ['eth_addr' => $ownerAddress, 'project_id' => $project->id],
                        );
                    }
                }
            }
        }
    }

    // Some dates appear to be in seconds while others are in milliseconds.  Deal with it.
    private function dateTimeConverter($datetime)
    {
        try {
            if (strlen($datetime) == 10) {
                return date('Y-m-d H:i:s.v', $datetime);
            } else {
                // slice the last 3 zeros off
                $datetime = substr($datetime, 0, -3);
                return date('Y-m-d H:i:s.v', $datetime);
            }
        } catch (\Throwable $th) {
            return null;
        }
    }

    private function updateRounds($chain)
    {

        $indexerUrl = $this->indexerUrl;
        $roundsData = Cache::remember($this->cacheName . "-rounds_data_2{$chain->chain_id}", now()->addMinutes(10), function () use ($chain) {
            $response = Http::timeout(120)->get("{$this->indexerUrl}/{$chain->chain_id}/rounds.json");
            return json_decode($response->body(), true);
        });

        if (is_array($roundsData)) {
            foreach ($roundsData as $roundData) {

                $this->info("Processing round ID: {$roundData['id']}...");

                $this->info($roundData['applicationsStartTime']);

                $round = Round::updateOrCreate(
                    ['round_addr' => $this->getAddress($roundData['id']), 'chain_id' => $chain->id],
                    [
                        'amount_usd' => $roundData['amountUSD'],
                        'votes' => $roundData['votes'],
                        'token' => $roundData['token'],
                        'match_amount' => $roundData['matchAmount'],
                        'match_amount_usd' => $roundData['matchAmountUSD'],
                        'unique_contributors' => $roundData['uniqueContributors'],
                        'applications_start_time' => $this->dateTimeConverter($roundData['applicationsStartTime']),
                        'applications_end_time' => $this->dateTimeConverter($roundData['applicationsEndTime']),
                        'round_start_time' => $this->dateTimeConverter($roundData['roundStartTime']),
                        'round_end_time' => $this->dateTimeConverter($roundData['roundEndTime']),
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
        }
    }

    private function updateProjects($round)
    {
        $indexerUrl = $this->indexerUrl;

        $chain = $round->chain;

        $applicationData = Cache::remember($this->cacheName . "-project_data{$chain->id}-{$round->id}", now()->addMinutes(10), function () use ($chain, $round) {
            $url = "{$this->indexerUrl}/{$chain->chain_id}/rounds/{$round->round_addr}/applications.json";
            $response = Http::timeout(120)->get($url);
            return json_decode($response->body(), true);
        });

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
                        ['id_addr' => $this->getAddress($data['projectId'])],
                        [
                            'created_at' => $createdAt,
                            'title' => isset($projectData['title']) ? $projectData['title'] : null,
                            'description' => $description,
                            'website' => isset($projectData['website']) ? $projectData['website'] : null,
                            'userGithub' => isset($projectData['userGithub']) ? $projectData['userGithub'] : null,
                            'projectGithub' => isset($projectData['projectGithub']) ? $projectData['projectGithub'] : null,
                            'projectTwitter' => isset($projectData['projectTwitter']) ? $projectData['projectTwitter'] : null,
                            'metadata' => $projectData,
                        ]
                    );
                }
            }
        }
    }

    private function updateApplications($round)
    {
        $chain = $round->chain;

        $applicationData = Cache::remember($this->cacheName . "-rounds_application_data{$chain->chain_id}_{$round->id}", now()->addMinutes(10), function () use ($round, $chain) {
            $response = Http::timeout(120)->get("{$this->indexerUrl}/{$chain->chain_id}/rounds/{$round->round_addr}/applications.json");
            return json_decode($response->body(), true);
        });



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
                    ['round_id' => $round->id, 'project_addr' => $this->getAddress($data['projectId'])]
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
            }
        }
    }


    function getAddress($address)
    {
        if (Str::length($address) !== 42) {
            return $address;
        }

        // Remove any leading '0x'
        if (strpos($address, '0x') === 0) {
            $address = substr($address, 2);
        }

        // Ensure the address is 40 characters long (20 bytes)
        if (strlen($address) !== 40) {
            throw new Exception("Invalid address length");
        }

        // Convert the address to lowercase
        $address = strtolower($address);

        // Calculate the keccak256 hash of the address
        $hash = Keccak::hash($address, 256);

        // Initialize an empty checksum address
        $checksumAddress = '0x';

        // Iterate over each character in the original address
        for ($i = 0; $i < 40; $i++) {
            // If the ith bit of the hash is 1, uppercase the ith character, otherwise leave it as is
            $checksumAddress .= (hexdec($hash[$i]) >= 8)
                ? strtoupper($address[$i])
                : $address[$i];
        }

        return $checksumAddress;
    }
}
