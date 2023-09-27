<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

use App\Models\Chain;
use App\Models\Project;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationMetadata;
use App\Models\RoundMetadata;
use App\Services\DirectoryParser;
use Directory;

// ... other models ...

class IngestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingest:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest data from the specified URL and populate the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DirectoryParser $directoryParser)
    {
        $indexerUrl = env('INDEXER_URL', 'https://indexer-production.fly.dev/data/');

        $this->info('Fetching directory list...');

        $directories = Cache::remember('directories1', now()->addDay(), function () use ($indexerUrl, $directoryParser) {
            $response = Http::get($indexerUrl);
            $json = $directoryParser->parse($response->body());

            return json_decode($json, true);
        });

        $directories = $directories['directories'];

        if ($directories) {
            foreach ($directories as $directory) {
                $chainId = $directory['name'];  // Assuming 'name' contains the chain ID

                $this->info("Processing data for chain ID: {$chainId}...");
                $chain = Chain::firstOrCreate(['chain_id' => $chainId]);

                $this->info("Processing project data for chain ID: {$chainId}...");
                $this->updateProjects($chain);

                $this->info("Processing rounds data for chain ID: {$chainId}...");
                $this->updateRounds($chain);
            }
        } else {
            $this->info("No directories available");
        }

        $this->info('Data ingestion completed successfully.');
        return 0;
    }

    private function updateRounds($chain)
    {
        $indexerUrl = env('INDEXER_URL', 'https://indexer-production.fly.dev/data/');

        $roundsData = Cache::remember("rounds_data_2{$chain->chain_id}", now()->addDay(), function () use ($indexerUrl, $chain) {
            $response = Http::get("{$indexerUrl}/{$chain->chain_id}/rounds.json");
            return json_decode($response->body(), true);
        });

        if (is_array($roundsData)) {
            foreach ($roundsData as $roundData) {

                $this->info("Processing round ID: {$roundData['id']}...");
                $round = Round::updateOrCreate(
                    ['round_addr' => $roundData['id'], 'chain_id' => $chain->id],
                    [
                        'amountUSD' => $roundData['amountUSD'],
                        'votes' => $roundData['votes'],
                        'token' => $roundData['token'],
                        'matchAmount' => $roundData['matchAmount'],
                        'matchAmountUSD' => $roundData['matchAmountUSD'],
                        'uniqueContributors' => $roundData['uniqueContributors'],
                        'applicationsStartTime' => $roundData['applicationsStartTime'],
                        'applicationsEndTime' => $roundData['applicationsEndTime'],
                        'roundStartTime' => $roundData['roundStartTime'],
                        'roundEndTime' => $roundData['roundEndTime'],
                        'createdAtBlock' => $roundData['createdAtBlock'],
                        'updatedAtBlock' => $roundData['updatedAtBlock'],
                        'metadata' => json_encode($roundData['metadata']),
                    ]
                );

                if (isset($roundData['metadata']['name'])) {
                    $round->name = $roundData['metadata']['name'];
                    $round->save();
                }

                $this->updateApplications($round);
            }
        }
    }

    private function updateProjects($chain)
    {
        $indexerUrl = env('INDEXER_URL', 'https://indexer-production.fly.dev/data/');

        $projectData = Cache::remember("project_data{$chain->id}", now()->addDay(), function () use ($indexerUrl, $chain) {
            $response = Http::get("{$indexerUrl}/{$chain->chain_id}/projects.json");
            return json_decode($response->body(), true);
        });


        if ($projectData && count($projectData) > 0) {
            foreach ($projectData as $key => $data) {

                $project = Project::updateOrCreate(
                    ['id_addr' => $data['id']],
                    [
                        'project_number' => $data['projectNumber'],
                        'meta_ptr' => $data['metaPtr'],
                        'metadata' => json_encode($data['metadata']),
                        'owners' => json_encode($data['owners']),
                        'created_at_block' => $data['createdAtBlock'],
                    ]
                );
                if (isset($data['metadata']['title'])) {
                    $this->info("Processing project: {$data['metadata']['title']}");

                    $project->title = $data['metadata']['title'];
                    $project->save();
                }
            }
        }
    }

    private function updateApplications($round)
    {
        $indexerUrl = env('INDEXER_URL', 'https://indexer-production.fly.dev/data/');

        $applicationData = Cache::remember("rounds_application_data{$round->chain->chain_id}_{$round->id}", now()->addDay(), function () use ($indexerUrl, $round) {
            $response = Http::get("{$indexerUrl}/{$round->chain->chain_id}/rounds/{$round->round_addr}/applications.json");
            return json_decode($response->body(), true);
        });

        if ($applicationData && count($applicationData) > 0) {
            foreach ($applicationData as $key => $data) {
                RoundApplication::updateOrCreate(
                    ['round_id' => $round->id],
                    [
                        'project_addr' => $data['projectId'],
                        'status' => $data['status'],
                        'metadata' => json_encode($data['metadata']),
                    ]
                );
            }
        }
    }
}
