<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoundApplicationController;
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
use App\Services\NotificationService;
use BendeckDavid\GraphqlClient\Facades\GraphQL;
use Carbon\Carbon;

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

    protected $cacheName = 'ingest-cache4';

    protected $blockTimeService;
    protected $metabaseService;

    protected $httpTimeout = 120000;

    // Look at data between these dates to reduce processing
    protected $fromDate = null;
    protected $toDate = null;

    public $notificationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(BlockTimeService $blockTimeService, MetabaseService $metabaseService, NotificationService $notificationService)
    {
        parent::__construct();
        $this->blockTimeService = $blockTimeService;
        $this->metabaseService = $metabaseService;

        $this->fromDate = now()->subDays(30)->timestamp;
        $this->toDate = now()->addDays(60)->timestamp;

        $this->notificationService = $notificationService;
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

        // Chains are hardcoded for now but should be fetched from a dynamic source in the future
        $chainList = [1, 10, 137, 250, 42161, 424];

        $this->info('Chains...');
        sleep(2);
        foreach ($chainList as $key => $chainId) {
            $this->info("Processing data for chain ID: {$chainId}...");
            $chain = Chain::firstOrCreate(['chain_id' => $chainId]);
        }

        $this->info('Rounds...');
        sleep(2);
        foreach ($chainList as $key => $chainId) {
            $chain = Chain::where('chain_id', $chainId)->first();
            $this->info("Processing rounds data for chain ID: {$chainId}...");
            $this->updateRounds($chain, $this->fromDate, $this->toDate);
        }

        $this->info('Projects...');
        sleep(2);
        foreach ($chainList as $key => $chainId) {
            $chain = Chain::where('chain_id', $chainId)->first();
            $rounds = Round::where('chain_id', $chain->id)
                ->where('applications_start_time', '>=', Carbon::createFromTimestamp($this->fromDate))
                ->where('donations_end_time', '<=', Carbon::createFromTimestamp($this->toDate))
                ->get();

            foreach ($rounds as $round) {
                $this->info("Processing project data for chain: {$chainId}, round: {$round->round_addr}.");
                $this->updateProjects($round);
            }
        }


        $this->info('Applications...');
        sleep(2);
        foreach ($chainList as $key => $chainId) {
            $chain = Chain::where('chain_id', $chainId)->first();
            $rounds = Round::where('chain_id', $chain->id)
                ->where('applications_start_time', '>=', Carbon::createFromTimestamp($this->fromDate))
                ->where('donations_end_time', '<=', Carbon::createFromTimestamp($this->toDate))
                ->get();
            foreach ($rounds as $round) {
                $this->info("Processing applications data for chain: {$chainId}, round: {$round->round_addr}.");
                $this->updateApplications($round);
            }
        }

        $this->info('Funding...');
        sleep(2);
        foreach ($chainList as $key => $chainId) {
            $chain = Chain::where('chain_id', $chainId)->first();
            $rounds = Round::where('chain_id', $chain->id)
                ->where('applications_start_time', '>=', Carbon::createFromTimestamp($this->fromDate))
                ->where('donations_end_time', '<=', Carbon::createFromTimestamp($this->toDate))
                ->get();
            foreach ($rounds as $round) {
                $this->info("Processing application funding data for chain: {$chainId}, round: {$round->round_addr}.");
                $applications = RoundApplication::where('round_id', $round->id)->whereNotNull('approved_at')->whereNull('donor_amount_usd')->get();
                foreach ($applications as $application) {
                    $this->updateApplicationFunding($application);
                }
            }
        }
    }

    /**
     * Split the long running tasks into a separate function so we can run them in the background
     */
    private function longRunningTasks()
    {
        if (!app()->isLocal()) {
            $this->updateProjectSummaries();
        }

        // Loop through all the chains and update project owners
        $chains = Chain::all();
        foreach ($chains as $chain) {
            $this->updateProjectOwnersForChain($chain);

            $rounds = Round::where('chain_id', $chain->id)
                ->where('applications_start_time', '>=', Carbon::createFromTimestamp($this->fromDate))
                ->where('donations_end_time', '<=', Carbon::createFromTimestamp($this->toDate))
                ->get();
            foreach ($rounds as $round) {
                $this->info("Processing donations data for chain ID: {$chain->chain_id}, round ID: {$round->id}...");
                $this->updateDonations($round);
            }
        }
    }

    /**
     * Update the project summaries for projects that don't have them
     */
    private function updateProjectSummaries()
    {
        $projectController = new ProjectController();

        $projects = Project::whereNull('gpt_summary')->limit(100)->get();
        foreach ($projects as $project) {
            $this->info("Processing project: {$project->title}");
            $projectController->doGPTSummary($project);
        }
    }

    /**
     * Update the funding for a specific application
     */
    private function updateApplicationFunding(RoundApplication $application)
    {

        if ($application->match_amount_usd) {
            $this->info("Skipping application id: {$application->id} for project: {$application->project->title} because it already has a match amount");
            return;
        }

        if (!isset($application->project->title)) {
            $this->info("Skipping application id: {$application->id} because it has no project title");
            return;
        }

        $this->info("Processing application funding id: {$application->id} for project: {$application->project->title}");

        $round = $application->round;
        $chain = $round->chain;

        $metabase = $this->metabaseService->getMatchingDistribution($chain->chain_id, $application->project_addr, $application->application_id);

        $donorAmountUSD = $this->metabaseService->getDonorAmountUSD($round->round_addr, $application->application_id);
        $application->donor_amount_usd = $donorAmountUSD;
        $application->donor_contributions_count = $metabase['contributionsCount'];
        $application->match_amount_usd = $metabase['matchAmountUSD'];
        $application->save();

        $this->info("Successfully updated application id: {$application->id}");
    }

    /**
     * Update donations as they relate to a specific application
     */
    private function updateDonations(Round $round)
    {
        $cacheName = $this->cacheName . 'IngestData::updateDonations(' . $round->id . ')';

        $chain = $round->chain;

        $query = '
        donations(filter: {roundId: {equalTo: "' . $round->round_addr . '"}, chainId: {equalTo: ' . $chain->chain_id . '}}) {
            id
            projectId
            applicationId
            amountInUsd
            donorAddress
            recipientAddress
            blockNumber
          }
        ';

        $donationsData = $this->graphQLQuery($query);

        $hash = HashService::hashMultidimensionalArray($donationsData);

        if (Cache::get($cacheName) == $hash) {
            $this->info("Donations data for round {$round->id} has not changed. Skipping...");
            return;
        }


        if (isset($donationsData['donations']) && count($donationsData['donations']) > 0) {
            $this->info("Number of donations to process: " . count($donationsData['donations']));
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
                } else {
                    if (!$project) {
                        $this->info("Skipping donation {$donation['id']} because it has no project");
                    }
                    if (!$application) {
                        $this->info("Skipping donation {$donation['id']} because it has no application");
                    }
                    $this->info("project_addr: {$projectAddr}");
                    $this->info("application_id: {$donation['applicationId']}");
                    $this->info("round_id: {$round->id}");
                    sleep(10);
                }
            }
        }

        Cache::put($cacheName, $hash, now()->addMonths(12));
    }

    /**
     * Update the project owners for a specific project
     */
    private function updateProjectOwnersForChain(Chain $chain)
    {
        $cacheName = $this->cacheName . 'IngestData::updateProjectOwnersForChain(' . $chain->id . ')';

        $this->info("Processing project owners for chain ID: {$chain->chain_id}...");

        $query = '
        projects(filter: {chainId: {equalTo: ' . $chain->chain_id . '}}) {
            id
            roles {
                address
                role
            }
          }
        ';

        $projectData = $this->graphQLQuery($query);

        $hash = HashService::hashMultidimensionalArray($projectData);

        if (Cache::get($cacheName) == $hash) {
            $this->info("Project owners data for chain {$chain->id} has not changed. Skipping...");
            return;
        }

        if (isset($projectData['projects']) && count($projectData['projects']) > 0) {
            foreach ($projectData['projects'] as $key => $data) {
                $projectAddress = Str::lower($data['id']);
                $roles = $data['roles'];
                $project = Project::where('id_addr', $projectAddress)->first();

                if ($project && count($roles)) {
                    // Loop through and add all the owners
                    foreach ($roles as $role) {
                        if ($role['role'] == 'OWNER') {
                            $address = Str::lower($role['address']);
                            $project->owners()->updateOrCreate(
                                ['eth_addr' => $address, 'project_id' => $project->id],
                            );
                        }
                    }
                }
            }
        }

        Cache::put($cacheName, $hash, now()->addMonths(12));
    }


    /**
     * Update rounds for a specific chain between two dates
     */
    private function updateRounds($chain, $fromDate, $toDate)
    {

        $this->info("Processing rounds data for chain ID: {$chain->chain_id} between {$fromDate} and {$toDate}...");

        $query = '
rounds(filter: {
    chainId: {equalTo: ' . $chain->chain_id . '},
    applicationsStartTime: {greaterThan: "' . date('Y-m-d\TH:i:s', $fromDate) . '"},
    donationsEndTime: {lessThan: "' . date('Y-m-d\TH:i:s', $toDate) . '"}
}) {
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
    applicationMetadata
  }
';

        $roundsData = $this->graphQLQuery($query);

        $roundsData = $roundsData['rounds'];

        $hash = HashService::hashMultidimensionalArray($roundsData);
        $cacheName = $this->cacheName . "-updateRounds({$chain->id})-hash";

        if (Cache::get($cacheName) == $hash) {
            $this->info("Rounds data for chain {$chain->id} has not changed. Skipping...");
            return;
        }

        if (is_array($roundsData)) {
            $this->info("Number of rounds to process: " . count($roundsData));
            foreach ($roundsData as $roundData) {

                $round = Round::updateOrCreate(
                    ['round_addr' => Str::lower($roundData['id']), 'chain_id' => $chain->id],
                    [
                        'total_amount_donated_in_usd' => $roundData['totalAmountDonatedInUsd'],
                        'total_donations_count' => $roundData['totalDonationsCount'],
                        'match_token_address' => $roundData['matchTokenAddress'],
                        'match_amount' => $roundData['matchAmount'],
                        'match_amount_in_usd' => $roundData['matchAmountInUsd'],
                        'unique_donors_count' => $roundData['uniqueDonorsCount'],
                        'applications_start_time' => $roundData['applicationsStartTime'],
                        'applications_end_time' => $roundData['applicationsEndTime'],
                        'donations_start_time' => $roundData['donationsStartTime'],
                        'donations_end_time' => $roundData['donationsEndTime'],
                        'created_at_block' => $roundData['createdAtBlock'],
                        'updated_at_block' => $roundData['updatedAtBlock'],
                        'round_metadata' => json_encode($roundData['roundMetadata']),
                        'application_metadata' => json_encode($roundData['applicationMetadata']),
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

                $metadata = json_decode($round->round_metadata, true);

                if (!$round->evaluationQuestions && isset($metadata['eligibility']['requirements'])) {
                    $questionsMeta = $metadata['eligibility']['requirements'];
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



                if (isset($metadata['name'])) {
                    $round->name = $metadata['name'];
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
        $cacheName = $this->cacheName . 'IngestData::updateProjects(' . $round->id . ')';

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
        $applicationData = $this->graphQLQuery($query);

        $hash = HashService::hashMultidimensionalArray($applicationData);

        if (Cache::get($cacheName) == $hash) {
            $this->info("Projects data for round {$round->id} has not changed. Skipping...");
            return;
        }

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
                        'logoImg' => isset($metadata['logoImg']) ? $metadata['logoImg'] : null,
                        'bannerImg' => isset($metadata['bannerImg']) ? $metadata['bannerImg'] : null,
                    ]
                );

                $this->info("Successfully updated project: {$project->title}");
            }
        }

        Cache::put($cacheName, $hash, now()->addMonths(12));
    }

    private function graphQLQuery($query)
    {
        $cacheName = $this->cacheName . 'IngestData::graphQLQuery(' . $query . ')';

        $result = Cache::remember($cacheName, now()->addMinutes(10), function () use ($query) {
            $attempts = 0;
            while ($attempts < 3) {
                try {
                    $result = GraphQL::query($query)->get();
                    return $result;
                } catch (Exception $e) {
                    $this->info("GraphQL query failed. Trying again in 30 seconds...");
                    print_r($query);
                    sleep(30);
                    $attempts++;
                }
            }
            throw new Exception("GraphQL query failed after 3 attempts.");
        });
        return $result;
    }

    private function updateApplications($round)
    {
        $cacheName = $this->cacheName . 'IngestData::updateApplications(' . $round->id . ')';

        $chain = $round->chain;

        $query = '
        applications(filter: {roundId: {equalTo: "' . $round->round_addr . '"}}) {
            id
            statusSnapshots
            status
            createdAtBlock
            metadata
            projectId
            project {
                id
                createdAtBlock
                name
            }
          }
        ';

        $applicationData = $this->graphQLQuery($query);

        $hash = HashService::hashMultidimensionalArray($applicationData);

        if (Cache::get($cacheName) == $hash) {
            $this->info("Applications data for round {$round->id} has not changed. Skipping...");
            return;
        }

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

                if (!isset($data['projectId'])) {
                    $this->info("Skipping application {$data['id']} because it has no project ID");
                    continue;
                }

                $roundApplication = RoundApplication::updateOrCreate(
                    ['round_id' => $round->id, 'project_addr' => Str::lower($data['projectId']), 'application_id' => $data['id']]
                );


                $roundApplication->update([
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

                // Do a GPT evaluation if it hasn't been done yet
                $roundApplicationController = new RoundApplicationController($this->notificationService);
                $roundApplicationController->checkAgainstChatGPT($roundApplication);
            }
            Cache::put($cacheName, $hash, now()->addMonths(12));
        }
    }
}
