<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

use App\Models\Chain;
use App\Models\Project;
use App\Models\Round;
use App\Models\RoundApplication;
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
    protected $signature = 'ingest:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest data from the specified URL and populate the database';

    protected $cacheName = 'ingest-cache';

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

        $directories = Cache::remember($this->cacheName . '-directories', now()->addDay(), function () use ($indexerUrl, $directoryParser) {
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

                $this->info("Processing rounds data for chain ID: {$chainId}...");
                $this->updateRounds($chain);


                $rounds = Round::where('chain_id', $chain->id)->get();
                foreach ($rounds as $round) {
                    $this->info("Processing application data for chain ID: {$chainId}...");
                    $this->updateApplications($round);

                    $this->info("Processing project data for chain ID: {$chainId}...");
                    $this->updateProjects($round);
                }
            }
        } else {
            $this->info("No directories available");
        }

        $this->info('Data ingestion completed successfully.');
        return 0;
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
        $indexerUrl = env('INDEXER_URL', 'https://indexer-production.fly.dev/data/');

        $roundsData = Cache::remember($this->cacheName . "-rounds_data_2{$chain->chain_id}", now()->addDay(), function () use ($indexerUrl, $chain) {
            $response = Http::get("{$indexerUrl}/{$chain->chain_id}/rounds.json");
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



                if (isset($roundData['metadata']['name'])) {
                    $round->name = $roundData['metadata']['name'];
                    $round->save();
                }
            }
        }
    }

    private function updateProjects($round)
    {
        $indexerUrl = env('INDEXER_URL', 'https://indexer-production.fly.dev/data/');

        $chain = $round->chain;

        $applicationData = Cache::remember($this->cacheName . "-project_data{$chain->id}-{$round->id}", now()->addDay(), function () use ($indexerUrl, $chain, $round) {
            $url = "{$indexerUrl}/{$chain->chain_id}/rounds/{$round->round_addr}/applications.json";
            $response = Http::get($url);
            return json_decode($response->body(), true);
        });

        if ($applicationData && count($applicationData) > 0) {

            foreach ($applicationData as $key => $data) {
                if (isset($data['metadata']['application']['project'])) {

                    $projectData = $data['metadata']['application']['project'];


                    // restrict the length of description to 1000 characters
                    $description = null;
                    if (isset($projectData['description']) && strlen($projectData['description']) > 30000) {
                        $projectData['description'] = substr($projectData['description'], 0, 30000);
                    }

                    $project = Project::updateOrCreate(
                        ['id_addr' => $this->getAddress($data['projectId'])],
                        [
                            'title' => isset($projectData['title']) ? $projectData['title'] : null,
                            'description' => $description,
                            'website' => isset($projectData['website']) ? $projectData['website'] : null,
                            'userGithub' => isset($projectData['userGithub']) ? $projectData['userGithub'] : null,
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
        $indexerUrl = env('INDEXER_URL', 'https://indexer-production.fly.dev/data/');

        $chain = $round->chain;

        $applicationData = Cache::remember($this->cacheName . "-rounds_application_data{$chain->chain_id}_{$round->id}", now()->addDay(), function () use ($indexerUrl, $round, $chain) {
            $response = Http::get("{$indexerUrl}/{$chain->chain_id}/rounds/{$round->round_addr}/applications.json");
            return json_decode($response->body(), true);
        });



        if ($applicationData && count($applicationData) > 0) {

            foreach ($applicationData as $key => $data) {

                $this->info("Processing application: {$data['projectId']}");

                // TODO:::Unsure if project.createdAt is the correct field to use here
                $createdAt = null;
                if (isset($data['metadata']['application']['project']['createdAt'])) {
                    $createdAt = date('Y-m-d H:i:s', $data['metadata']['application']['project']['createdAt'] / 1000);
                    // update round last_updated_at if it's newer than the current value
                    if ($createdAt > $round->last_application_at) {
                        $round->last_application_at = $createdAt;
                        $round->save();
                    }
                }


                RoundApplication::updateOrCreate(
                    ['round_id' => $round->id, 'project_addr' => $this->getAddress($data['projectId'])],
                    [
                        'round_id' => $round->id,
                        'project_addr' => $data['projectId'],
                        'status' => $data['status'],
                        'metadata' => json_encode($data['metadata']),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]
                );
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
