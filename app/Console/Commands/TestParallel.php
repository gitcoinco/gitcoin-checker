<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Console\Command;

class TestParallel extends Command
{
    protected $signature = 'test:parallel';
    protected $description = 'Test parallel vs sequential processing';

    public function handle()
    {
        $urls = [];

        for ($i = 1; $i <= 100; $i++) {
            $urls[] = 'https://jsonplaceholder.typicode.com/posts/' . $i;
        }

        $this->info('Starting sequential processing...');
        $startTime = microtime(true);
        $this->sequentialProcessing($urls);
        $endTime = microtime(true);
        $this->info("Sequential processing time: " . ($endTime - $startTime) . " seconds");

        $this->info('Starting parallel processing...');
        $startTime = microtime(true);
        $this->parallelProcessing($urls);
        $endTime = microtime(true);
        $this->info("Parallel processing time: " . ($endTime - $startTime) . " seconds");
    }

    private function sequentialProcessing($urls)
    {
        $client = new Client();

        foreach ($urls as $url) {
            $response = $client->get($url);
            //            $this->info('Response: ' . $response->getBody());
        }
    }

    private function parallelProcessing($urls)
    {
        $client = new Client();
        $promises = [];

        foreach ($urls as $url) {
            $promises[] = $client->getAsync($url);
        }

        $results = Promise\Utils::unwrap($promises);

        foreach ($results as $response) {
            //            $this->info('Response: ' . $response->getBody());
        }
    }
}
