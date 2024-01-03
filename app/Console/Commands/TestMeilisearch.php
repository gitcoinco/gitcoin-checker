<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MeiliSearch\Client as MeilisearchClient; // Import Meilisearch PHP Client

class TestMeilisearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test whether we can connect to meilisearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Instantiate the Meilisearch client
        $client = new MeilisearchClient(config('scout.meilisearch.host'), config('scout.meilisearch.key'));

        try {
            // Use the correct method to get all indexes
            $indexes = $client->getIndexes();

            foreach ($indexes as $index) {
                $this->info($index['uid']); // Access the index UID
            }
        } catch (\Exception $e) {
            $this->error('Error connecting to Meilisearch: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
