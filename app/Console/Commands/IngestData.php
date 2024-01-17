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

// Continue removing address service and going lowercase

use App\Services\DateService;
use App\Services\HashService;
use App\Services\MetabaseService;
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
    protected $description = 'Ingest data from the indexer and populate the database';

    protected $cacheName = 'ingest-cache';

    protected $blockTimeService;
    protected $metabaseService;

    protected $httpTimeout = 120000;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(BlockTimeService $blockTimeService, MetabaseService $metabaseService)
    {
        parent::__construct();
        $this->blockTimeService = $blockTimeService;
        $this->metabaseService = $metabaseService;
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

        // Chains are hardcoded for now but should be fetched from a dynamic source in the future
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

        $this->info('Fetching application funding data...');
        $applications = RoundApplication::whereNotNull('approved_at')->whereNull('donor_amount_usd')->get();
        foreach ($applications as $application) {
            $this->updateApplicationFunding($application);
        }
    }

    // Split the long running tasks into a separate function so we can run them in the background
    private function longRunningTasks()
    {
        //TODO::: Enable GPT summaries
        // $this->updateProjectSummaries();

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

        $metabase = $this->metabaseService->getMatchingDistribution($chain->chain_id, $application->project_addr, $application->application_id);

        $application->donor_amount_usd = $metabase['donorAmountUSD'];
        $application->donor_contributions_count = $metabase['contributionsCount'];
        $application->match_amount_usd = $metabase['matchAmountUSD'];
        $application->save();

        $this->info("Successfully updated application id: {$application->id}");
    }

    private function updateDonations(Round $round)
    {
        $query = '
        donations(filter: {roundId: {equalTo: "' . $round->round_addr . '"}}) {
            id
            projectId
            applicationId
            amountInUsd
            donorAddress
            recipientAddress
            blockNumber
          }
        ';
        $donationsData = GraphQL::query($query)->get();

        if (isset($donationsData['donations']) && count($donationsData['donations']) > 0) {
            foreach ($donationsData['donations'] as $key => $donation) {
                $projectAddr = Str::lower($donation['projectId']);
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
                            'amount_usd' => $donation['amountInUsd'],
                            'donor_address' => Str::lower($donation['donorAddress']),
                            'recipient_address' => Str::lower($donation['recipientAddress']),
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

        $query = '
        projects(filter: {chainId: {equalTo: ' . $chain->chain_id . '}}) {
            id
            ownerAddresses
          }
        ';
        $projectData = GraphQL::query($query)->get();

        if (isset($projectData['projects']) && count($projectData['projects']) > 0) {
            foreach ($projectData['projects'] as $key => $data) {
                $projectAddress = Str::lower($data['id']);
                $owners = $data['ownerAddresses'];
                $project = Project::where('id_addr', $projectAddress)->first();

                if ($project && count($owners)) {
                    // Loop through and add all the owners
                    foreach ($owners as $ownerAddress) {
                        $ownerAddress = Str::lower($ownerAddress);
                        $project->owners()->updateOrCreate(
                            ['eth_addr' => $ownerAddress, 'project_id' => $project->id],
                        );
                    }
                }
            }
        }
    }



    private function updateRounds($chain)
    {

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
                    ['round_addr' => Str::lower($roundData['id']), 'chain_id' => $chain->id],
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
                        'round_metadata' => json_encode($roundData['roundMetadata']),
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

    /**
     * Update project data by pulling projects for a specific round
     */
    private function updateProjects($round)
    {
        $query = '
        applications(filter: {roundId: {equalTo: "' . $round->round_addr . '"}}) {
            id
            project {
                id
                createdAtBlock
                name
                metadata
            }
          }
        ';
        $applicationData = GraphQL::query($query)->get();

        foreach ($applicationData['applications'] as $key => $data) {
            if (isset($data['project']) && count($data['project']) > 0) {

                $metadata = $data['project']['metadata'];

                $description = null;
                if (isset($metadata['description'])) {
                    $description = $metadata['description'];
                }

                $createdAt = now();
                if (isset($data['createdAtBlock'])) {
                    $createdAt = $this->blockTimeService->getBlockTime($round->chain, $data['createdAtBlock']);
                }


                $project = Project::updateOrCreate(
                    ['id_addr' => Str::lower($data['project']['id'])],
                    [
                        'created_at' => $createdAt,
                        'title' => isset($metadata['title']) ? $metadata['title'] : null,
                        'description' => $description,
                        'website' => isset($metadata['website']) ? $metadata['website'] : null,
                        'userGithub' => isset($metadata['userGithub']) ? $metadata['userGithub'] : null,
                        'projectGithub' => isset($metadata['projectGithub']) ? $metadata['projectGithub'] : null,
                        'projectTwitter' => isset($metadata['projectTwitter']) ? $metadata['projectTwitter'] : null,
                        'metadata' => json_encode($metadata),
                        'logoImg' => isset($metadata['logoImg']) ? base64_encode($metadata['logoImg']) : null,
                        'bannerImg' => isset($metadata['bannerImg']) ? $metadata['bannerImg'] : null,
                    ]
                );

                $this->info("Successfully updated project: {$project->title}");
            }
        }
    }

    private function updateApplications($round)
    {
        $chain = $round->chain;

        $query = '
        applications(filter: {roundId: {equalTo: "' . $round->round_addr . '"}}) {
            id
            statusSnapshots
            status
            createdAtBlock
            metadata
            project {
                id
                createdAtBlock
                name
            }
          }
        ';
        $applicationData = GraphQL::query($query)->get();


        if (isset($applicationData['applications']) && count($applicationData['applications']) > 0) {

            foreach ($applicationData['applications'] as $key => $data) {

                $this->info("Processing application: {$data['id']}");

                $createdAt = null;
                // When was this application created?
                if (isset($data['createdAtBlock'])) {
                    $createdAt = $this->blockTimeService->getBlockTime($chain, $data['createdAtBlock']);
                }

                $metadata = $data['metadata'];

                // When was this application approved / pending
                $rejectedAt = null;
                $approvedAt = null;
                if ($data['statusSnapshots']) {
                    foreach ($data['statusSnapshots'] as $key => $value) {
                        if (strtolower($value['status']) == 'rejected') {
                            $rejectedAt = $this->blockTimeService->getBlockTime($chain, $value['statusUpdatedAtBlock']['value']);
                        } else if (strtolower($value['status']) == 'approved') {
                            $approvedAt = $this->blockTimeService->getBlockTime($chain, $value['statusUpdatedAtBlock']['value']);
                        }
                    }
                }

                if (!$createdAt) {
                    throw new Exception("Unable to determine createdAt for application {$data['project']['id']}, chain {$chain->chain_id}, block {$data['createdAtBlock']}");
                }

                if (!isset($data['project']['id'])) {
                    $this->info("Skipping application {$data['id']} because it has no project ID");
                    continue;
                }

                $roundApplication = RoundApplication::updateOrCreate(
                    ['round_id' => $round->id, 'project_addr' => Str::lower($data['project']['id'])]
                );

                $roundApplication->update([
                    'application_id' => $data['id'],
                    'round_id' => $round->id,
                    'project_addr' => $data['project']['id'],
                    'status' => $data['status'],
                    'metadata' => json_encode($metadata),
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
        }
    }
}
